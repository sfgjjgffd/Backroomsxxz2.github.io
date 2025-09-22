<?php
// 配置
$videoBaseDir = "MoviesFile/";
$allowedVideoTypes = ['mp4', 'webm', 'avi', 'mov', 'wmv', 'mkv'];

// 工具函数
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function getClientComputer() {
    return gethostbyaddr(getClientIP());
}

function sanitizeFileName($name) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // 上传视频
        if ($_POST['action'] === 'upload' && isset($_FILES['video_file'])) {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            
            if (!empty($title) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                // 创建文件夹
                $folderName = time() . '_' . sanitizeFileName($title);
                $folderPath = $videoBaseDir . $folderName;
                
                if (!file_exists($folderPath)) {
                    mkdir($folderPath, 0777, true);
                    
                    // 保存标题和描述
                    file_put_contents($folderPath . '/title.txt', $title);
                    file_put_contents($folderPath . '/info.txt', $description);
                    file_put_contents($folderPath . '/uploader.txt', getClientIP());
                    
                    // 移动视频文件
                    $fileExtension = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
                    if (in_array($fileExtension, $allowedVideoTypes)) {
                        $videoPath = $folderPath . '/video.' . $fileExtension;
                        move_uploaded_file($_FILES['video_file']['tmp_name'], $videoPath);
                        
                        // 创建空评论文件
                        file_put_contents($folderPath . '/comments.txt', '');
                        
                        $uploadSuccess = "视频上传成功!";
                        header("Location: ?view=my");
                        exit();
                    } else {
                        rmdir($folderPath);
                        $uploadError = "不支持的视频格式。支持的格式: " . implode(', ', $allowedVideoTypes);
                    }
                }
            } else {
                $uploadError = "请填写标题并选择视频文件";
            }
        }
        // 添加评论
        elseif ($_POST['action'] === 'add_comment' && isset($_POST['comment']) && isset($_POST['video_folder'])) {
            $comment = trim($_POST['comment']);
            $videoFolder = $_POST['video_folder'];
            
            if (!empty($comment) && file_exists($videoBaseDir . $videoFolder)) {
                $commentData = array(
                    'text' => $comment,
                    'ip' => getClientIP(),
                    'computer' => getClientComputer(),
                    'time' => date('Y-m-d H:i:s')
                );
                
                $commentsFile = $videoBaseDir . $videoFolder . '/comments.txt';
                $comments = array();
                
                if (file_exists($commentsFile)) {
                    $comments = json_decode(file_get_contents($commentsFile), true) ?: array();
                }
                
                $comments[] = $commentData;
                file_put_contents($commentsFile, json_encode($comments));
                
                header("Location: " . $_SERVER['PHP_SELF'] . "?video=" . urlencode($videoFolder) . "&view=" . urlencode($_GET['view'] ?? 'all'));
                exit();
            }
        }
        // 删除视频
        elseif ($_POST['action'] === 'delete_video' && isset($_POST['video_folder'])) {
            $videoFolder = $_POST['video_folder'];
            $folderPath = $videoBaseDir . $videoFolder;
            
            if (file_exists($folderPath . '/uploader.txt')) {
                $uploaderIP = trim(file_get_contents($folderPath . '/uploader.txt'));
                
                if ($uploaderIP === getClientIP()) {
                    // 删除整个文件夹
                    array_map('unlink', glob("$folderPath/*.*"));
                    rmdir($folderPath);
                    
                    header("Location: " . $_SERVER['PHP_SELF'] . "?view=my");
                    exit();
                }
            }
        }
    }
}

// 获取所有视频
$videos = array();
if (file_exists($videoBaseDir)) {
    $folders = array_diff(scandir($videoBaseDir), array('..', '.'));
    
    foreach ($folders as $folder) {
        $folderPath = $videoBaseDir . $folder;
        
        if (is_dir($folderPath)) {
            $videoInfo = array('folder' => $folder);
            
            // 获取标题
            if (file_exists($folderPath . '/title.txt')) {
                $videoInfo['title'] = file_get_contents($folderPath . '/title.txt');
            } else {
                $videoInfo['title'] = '未知标题';
            }
            
            // 获取描述
            if (file_exists($folderPath . '/info.txt')) {
                $videoInfo['description'] = file_get_contents($folderPath . '/info.txt');
            } else {
                $videoInfo['description'] = '暂无描述';
            }
            
            // 获取上传者
            if (file_exists($folderPath . '/uploader.txt')) {
                $videoInfo['uploader'] = file_get_contents($folderPath . '/uploader.txt');
            } else {
                $videoInfo['uploader'] = '未知上传者';
            }
            
            // 获取上传时间
            $timestamp = explode('_', $folder)[0];
            $videoInfo['upload_time'] = date('Y-m-d H:i:s', $timestamp);
            
            // 获取视频文件
            $videoFile = null;
            foreach ($allowedVideoTypes as $ext) {
                if (file_exists($folderPath . '/video.' . $ext)) {
                    $videoFile = 'video.' . $ext;
                    break;
                }
            }
            
            if ($videoFile) {
                $videoInfo['video_file'] = $videoFile;
                $videos[$folder] = $videoInfo;
            }
        }
    }
}

// 确定当前视图
$view = isset($_GET['view']) ? $_GET['view'] : 'all';
if (!in_array($view, ['all', 'my', 'upload'])) {
    $view = 'all';
}

// 确定当前视频
$currentVideo = null;
if (!empty($_GET['video']) && isset($videos[$_GET['video']])) {
    $currentVideo = $videos[$_GET['video']];
    $currentVideo['folder'] = $_GET['video'];
    
    // 获取评论
    $commentsFile = $videoBaseDir . $_GET['video'] . '/comments.txt';
    if (file_exists($commentsFile)) {
        $currentVideo['comments'] = json_decode(file_get_contents($commentsFile), true) ?: array();
    } else {
        $currentVideo['comments'] = array();
    }
}

// 处理搜索和筛选
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$currentIP = getClientIP();

if ($searchQuery) {
    $filteredVideos = array();
    foreach ($videos as $folder => $video) {
        if (stripos($video['title'], $searchQuery) !== false || 
            stripos($video['description'], $searchQuery) !== false) {
            $filteredVideos[$folder] = $video;
        }
    }
    $videos = $filteredVideos;
}

if ($view === 'my') {
    $filteredVideos = array();
    foreach ($videos as $folder => $video) {
        if ($video['uploader'] === $currentIP) {
            $filteredVideos[$folder] = $video;
        }
    }
    $videos = $filteredVideos;
}

// 排序视频：最新的在前面
uasort($videos, function($a, $b) {
    return strcmp($b['folder'], $a['folder']);
});
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>视频分享平台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #00a1d6;
            --secondary-color: #f25d8e;
            --dark-bg: #f4f4f4;
            --light-bg: #ffffff;
            --text-dark: #333333;
            --text-light: #666666;
            --border-color: #e5e9ef;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* 头部导航 */
        .header {
            background: var(--primary-color);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 1.8em;
            font-weight: bold;
        }
        
        .menu {
            display: flex;
            gap: 20px;
        }
        
        .menu a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .menu a:hover, .menu a.active {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .search-box {
            display: flex;
            width: 40%;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 4px 0 0 4px;
            font-size: 1em;
        }
        
        .search-box button {
            padding: 10px 15px;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        /* 内容区域 */
        .content {
            display: flex;
            gap: 20px;
        }
        
        .main-content {
            flex: 7;
        }
        
        .sidebar {
            flex: 3;
        }
        
        /* 视频播放器 */
        .video-player {
            background: var(--light-bg);
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        video {
            width: 100%;
            background: #000;
        }
        
        .video-info {
            padding: 20px;
        }
        
        .video-info h2 {
            font-size: 1.5em;
            margin-bottom: 10px;
            color: var(--text-dark);
        }
        
        .video-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: var(--text-light);
            font-size: 0.9em;
        }
        
        .video-actions {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .video-actions button {
            padding: 8px 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .video-actions button:hover {
            background: #0084ad;
        }
        
        .btn-danger {
            background: var(--secondary-color) !important;
        }
        
        .btn-danger:hover {
            background: #e64c7e !important;
        }
        
        /* 评论区 */
        .comments-section {
            background: var(--light-bg);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
        }
        
        .comments-section h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .comment-form {
            margin-bottom: 20px;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            resize: vertical;
            min-height: 80px;
            margin-bottom: 10px;
        }
        
        .comment-form button {
            padding: 8px 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .comment {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .comment-content {
            flex: 1;
        }
        
        .comment-author {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .comment-meta {
            font-size: 0.8em;
            color: var(--text-light);
            margin-bottom: 8px;
        }
        
        .comment-text {
            margin-bottom: 8px;
        }
        
        .comment-actions {
            display: flex;
            gap: 15px;
        }
        
        .comment-actions button {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        
        /* 视频列表 */
        .video-list {
            background: var(--light-bg);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 15px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .video-list h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .video-item {
            display: flex;
            margin-bottom: 15px;
            cursor: pointer;
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .video-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .video-thumb {
            width: 40%;
            position: relative;
        }
        
        .video-thumb video {
            width: 100%;
            height: 80px;
            object-fit: cover;
        }
        
        .video-thumb::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 30px;
            background: rgba(0,0,0,0.7);
            border-radius: 50%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 60%;
        }
        
        .video-item-info {
            width: 60%;
            padding: 10px;
        }
        
        .video-item-info h4 {
            font-size: 0.9em;
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .video-item-info p {
            font-size: 0.8em;
            color: var(--text-light);
        }
        
        /* 上传界面 */
        .upload-section {
            background: var(--light-bg);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .upload-section h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--secondary-color);
            color: white;
        }
        
        /* 消息提示 */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* 响应式设计 */
        @media (max-width: 992px) {
            .content {
                flex-direction: column;
            }
            
            .search-box {
                width: 60%;
            }
        }
        
        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                gap: 15px;
            }
            
            .menu {
                width: 100%;
                justify-content: center;
            }
            
            .search-box {
                width: 100%;
            }
        }
        
        /* 标签样式 */
        .tags {
            display: flex;
            gap: 8px;
            margin: 10px 0;
        }
        
        .tag {
            padding: 4px 8px;
            background: #eaf5ff;
            color: var(--primary-color);
            border-radius: 4px;
            font-size: 0.8em;
        }
        
        /* 空状态 */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }
        
        .empty-state i {
            font-size: 3em;
            margin-bottom: 15px;
            color: var(--border-color);
        }
        
        /* 视图容器 */
        .view-container {
            display: none;
        }
        
        .view-container.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- 头部导航 -->
    <div class="header">
        <div class="nav">
            <div class="logo">视频分享平台</div>
            <div class="menu">
                <a href="?view=all" class="<?php echo $view === 'all' ? 'active' : ''; ?>"><i class="fas fa-globe"></i> 所有视频</a>
                <a href="?view=my" class="<?php echo $view === 'my' ? 'active' : ''; ?>"><i class="fas fa-user"></i> 我的视频</a>
                <a href="?view=upload" class="<?php echo $view === 'upload' ? 'active' : ''; ?>"><i class="fas fa-upload"></i> 发布视频</a>
	       <a href="./">返回大厅</a>
            </div>
            <form class="search-box" method="GET" action="">
                <input type="hidden" name="view" value="<?php echo $view; ?>">
                <input type="text" name="search" placeholder="搜索视频..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>

    <div class="container">
        <?php if (isset($uploadSuccess)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $uploadSuccess; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($uploadError)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $uploadError; ?>
            </div>
        <?php endif; ?>

        <!-- 所有视频视图 -->
        <div class="view-container <?php echo $view === 'all' ? 'active' : ''; ?>">
            <div class="content">
                <div class="main-content">
                    <?php if ($currentVideo): ?>
                    <!-- 视频播放器 -->
                    <div class="video-player">
                        <video controls>
                            <source src="<?php echo htmlspecialchars($videoBaseDir . $currentVideo['folder'] . '/' . $currentVideo['video_file']); ?>" type="video/mp4">
                            您的浏览器不支持HTML5视频播放。
                        </video>
                        <div class="video-info">
                            <h2><?php echo htmlspecialchars($currentVideo['title']); ?></h2>
                            <div class="video-meta">
                                <span>上传者: <?php echo htmlspecialchars($currentVideo['uploader']); ?></span>
                                <span>上传时间: <?php echo htmlspecialchars($currentVideo['upload_time']); ?></span>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($currentVideo['description'])); ?></p>
                        </div>
                    </div>

                    <!-- 评论区 -->
                    <div class="comments-section">
                        <h3><i class="fas fa-comments"></i> 评论区 (<?php echo count($currentVideo['comments']); ?>条评论)</h3>
                        <div class="comment-form">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="add_comment">
                                <input type="hidden" name="video_folder" value="<?php echo htmlspecialchars($currentVideo['folder']); ?>">
                                <textarea name="comment" placeholder="留下你的评论..." required></textarea>
                                <button type="submit">发表评论</button>
                            </form>
                        </div>

                        <?php if (!empty($currentVideo['comments'])): ?>
                            <?php foreach ($currentVideo['comments'] as $comment): ?>
                            <div class="comment">
                                <div class="comment-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="comment-content">
                                    <div class="comment-author">用户 (IP: <?php echo htmlspecialchars($comment['ip']); ?>)</div>
                                    <div class="comment-meta">计算机: <?php echo htmlspecialchars($comment['computer']); ?> • <?php echo htmlspecialchars($comment['time']); ?></div>
                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['text'])); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-comment-slash"></i>
                                <p>暂无评论，成为第一个评论者吧！</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php elseif (!empty($videos)): ?>
                    <div class="empty-state">
                        <i class="fas fa-play-circle"></i>
                        <p>请从右侧选择一个视频开始观看</p>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-video-slash"></i>
                        <p>暂无视频</p>
                        <?php if ($searchQuery): ?>
                            <p>没有找到匹配"<?php echo htmlspecialchars($searchQuery); ?>"的视频</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="sidebar">
                    <!-- 视频列表 -->
                    <div class="video-list">
                        <h3><i class="fas fa-list"></i> 所有视频</h3>
                        
                        <?php if (!empty($videos)): ?>
                            <?php foreach ($videos as $folder => $video): ?>
                            <div class="video-item" onclick="window.location='?view=all&video=<?php echo urlencode($folder); ?>'">
                                <div class="video-thumb">
                                    <video preload="metadata">
                                        <source src="<?php echo htmlspecialchars($videoBaseDir . $folder . '/' . $video['video_file']); ?>#t=0.5" type="video/mp4">
                                    </video>
                                </div>
                                <div class="video-item-info">
                                    <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                    <p>IP: <?php echo htmlspecialchars($video['uploader']); ?></p>
                                    <p>上传时间: <?php echo htmlspecialchars($video['upload_time']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-video-slash"></i>
                                <p>暂无视频</p>
                                <?php if ($searchQuery): ?>
                                    <p>没有找到匹配"<?php echo htmlspecialchars($searchQuery); ?>"的视频</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 我的视频视图 -->
        <div class="view-container <?php echo $view === 'my' ? 'active' : ''; ?>">
            <div class="content">
                <div class="main-content">
                    <?php if ($currentVideo): ?>
                    <!-- 视频播放器 -->
                    <div class="video-player">
                        <video controls>
                            <source src="<?php echo htmlspecialchars($videoBaseDir . $currentVideo['folder'] . '/' . $currentVideo['video_file']); ?>" type="video/mp4">
                            您的浏览器不支持HTML5视频播放。
                        </video>
                        <div class="video-info">
                            <h2><?php echo htmlspecialchars($currentVideo['title']); ?></h2>
                            <div class="video-meta">
                                <span>上传者: <?php echo htmlspecialchars($currentVideo['uploader']); ?></span>
                                <span>上传时间: <?php echo htmlspecialchars($currentVideo['upload_time']); ?></span>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($currentVideo['description'])); ?></p>
                            <div class="video-actions">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_video">
                                    <input type="hidden" name="video_folder" value="<?php echo htmlspecialchars($currentVideo['folder']); ?>">
                                    <button type="submit" class="btn-danger" onclick="return confirm('确定要删除这个视频吗？此操作不可恢复。')">
                                        <i class="fas fa-trash"></i> 删除视频
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- 评论区 -->
                    <div class="comments-section">
                        <h3><i class="fas fa-comments"></i> 评论区 (<?php echo count($currentVideo['comments']); ?>条评论)</h3>
                        <div class="comment-form">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="add_comment">
                                <input type="hidden" name="video_folder" value="<?php echo htmlspecialchars($currentVideo['folder']); ?>">
                                <textarea name="comment" placeholder="留下你的评论..." required></textarea>
                                <button type="submit">发表评论</button>
                            </form>
                        </div>

                        <?php if (!empty($currentVideo['comments'])): ?>
                            <?php foreach ($currentVideo['comments'] as $comment): ?>
                            <div class="comment">
                                <div class="comment-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="comment-content">
                                    <div class="comment-author">用户 (IP: <?php echo htmlspecialchars($comment['ip']); ?>)</div>
                                    <div class="comment-meta">计算机: <?php echo htmlspecialchars($comment['computer']); ?> • <?php echo htmlspecialchars($comment['time']); ?></div>
                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['text'])); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-comment-slash"></i>
                                <p>暂无评论，成为第一个评论者吧！</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php elseif (!empty($videos)): ?>
                    <div class="empty-state">
                        <i class="fas fa-play-circle"></i>
                        <p>请从右侧选择一个视频开始观看</p>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-video-slash"></i>
                        <p>您还没有上传任何视频</p>
                        <a href="?view=upload" class="btn btn-primary">去发布视频</a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="sidebar">
                    <!-- 我的视频列表 -->
                    <div class="video-list">
                        <h3><i class="fas fa-list"></i> 我的视频</h3>
                        
                        <?php if (!empty($videos)): ?>
                            <?php foreach ($videos as $folder => $video): ?>
                            <div class="video-item" onclick="window.location='?view=my&video=<?php echo urlencode($folder); ?>'">
                                <div class="video-thumb">
                                    <video preload="metadata">
                                        <source src="<?php echo htmlspecialchars($videoBaseDir . $folder . '/' . $video['video_file']); ?>#t=0.5" type="video/mp4">
                                    </video>
                                </div>
                                <div class="video-item-info">
                                    <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                    <p>上传时间: <?php echo htmlspecialchars($video['upload_time']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-video-slash"></i>
                                <p>您还没有上传任何视频</p>
                                <a href="?view=upload" class="btn btn-primary">去发布视频</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 发布视频视图 -->
        <div class="view-container <?php echo $view === 'upload' ? 'active' : ''; ?>">
            <div class="content">
                <div class="main-content">
                    <!-- 上传界面 -->
                    <div class="upload-section">
                        <h3><i class="fas fa-upload"></i> 发布新视频</h3>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload">
                            <div class="form-group">
                                <label for="video-title">视频标题</label>
                                <input type="text" id="video-title" name="title" placeholder="输入视频标题" required>
                            </div>
                            <div class="form-group">
                                <label for="video-desc">视频简介</label>
                                <textarea id="video-desc" name="description" placeholder="输入视频简介"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="video-file">选择视频文件 (支持: <?php echo implode(', ', $allowedVideoTypes); ?>)</label>
                                <input type="file" id="video-file" name="video_file" accept="video/*" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">上传视频</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="sidebar">
                    <!-- 我的视频列表 -->
                    <div class="video-list">
                        <h3><i class="fas fa-list"></i> 我的视频</h3>
                        
                        <?php 
                        // 获取当前用户的所有视频
                        $myVideos = array();
                        foreach ($videos as $folder => $video) {
                            if ($video['uploader'] === $currentIP) {
                                $myVideos[$folder] = $video;
                            }
                        }
                        ?>
                        
                        <?php if (!empty($myVideos)): ?>
                            <?php foreach ($myVideos as $folder => $video): ?>
                            <div class="video-item" onclick="window.location='?view=my&video=<?php echo urlencode($folder); ?>'">
                                <div class="video-thumb">
                                    <video preload="metadata">
                                        <source src="<?php echo htmlspecialchars($videoBaseDir . $folder . '/' . $video['video_file']); ?>#t=0.5" type="video/mp4">
                                    </video>
                                </div>
                                <div class="video-item-info">
                                    <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                    <p>上传时间: <?php echo htmlspecialchars($video['upload_time']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-video-slash"></i>
                                <p>您还没有上传任何视频</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 简单的交互功能
        document.addEventListener('DOMContentLoaded', function() {
            // 暂停所有缩略图视频
            const thumbVideos = document.querySelectorAll('.video-thumb video');
            thumbVideos.forEach(video => {
                video.addEventListener('mouseover', function() {
                    this.play().catch(e => {});
                });
                video.addEventListener('mouseout', function() {
                    this.pause();
                    this.currentTime = 0.5;
                });
            });
            
            // 表单提交确认
            const deleteForms = document.querySelectorAll('form[action="delete_video"]');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('确定要删除这个视频吗？此操作不可恢复。')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
