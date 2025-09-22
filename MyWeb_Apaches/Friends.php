<?php
session_start();

// 配置信息
define('POST_DIR', 'posts/');
define('DRAFT_DIR', 'drafts/');
define('UPLOAD_DIR', 'uploads/');

// 创建必要的目录
if (!file_exists(POST_DIR)) mkdir(POST_DIR, 0777, true);
if (!file_exists(DRAFT_DIR)) mkdir(DRAFT_DIR, 0777, true);
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);

// 获取用户IP
$user_ip = $_SERVER['REMOTE_ADDR'];

// 生成机器名
$machine_name = "Machine_" . substr(md5($user_ip), 0, 8);

// 设置登录时间（如果未设置）
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_draft'])) {
        saveDraft();
    } elseif (isset($_POST['publish'])) {
        publishPost();
    } elseif (isset($_POST['update_post'])) {
        updatePost();
    }
}

// 处理删除操作
if (isset($_GET['delete_draft'])) {
    deleteDraft($_GET['delete_draft']);
} elseif (isset($_GET['delete_post'])) {
    deletePost($_GET['delete_post']);
}

// 获取当前模式（所有帖子/我的帖子/草稿）
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'all';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// 获取要编辑的草稿ID
$edit_draft_id = isset($_GET['edit_draft']) ? $_GET['edit_draft'] : '';

// 获取要编辑的帖子ID
$edit_post_id = isset($_GET['edit_post']) ? $_GET['edit_post'] : '';

// 获取草稿内容（如果正在编辑）
$draft_content = [];
if ($edit_draft_id && file_exists(DRAFT_DIR . $edit_draft_id . '.json')) {
    $draft_content = json_decode(file_get_contents(DRAFT_DIR . $edit_draft_id . '.json'), true);
}

// 获取帖子内容（如果正在编辑）
$post_content = [];
if ($edit_post_id) {
    $post_files = glob(POST_DIR . '*/' . $edit_post_id . '.json');
    if (!empty($post_files)) {
        $post_content = json_decode(file_get_contents($post_files[0]), true);
    }
}

// 获取帖子列表
$posts = getPosts($mode, $search_query);

// 获取草稿列表
$drafts = getDrafts();

// 保存草稿函数
function saveDraft() {
    global $user_ip;
    
    $draft_name = $_POST['draft_name'] ?: '未命名草稿';
    $post_title = $_POST['post_title'];
    $post_content = $_POST['post_content'];
    
    // 处理图片上传
    $image_path = handleImageUpload();
    
    // 如果是编辑现有草稿
    if (!empty($_POST['draft_id'])) {
        $draft_id = $_POST['draft_id'];
        // 删除旧的图片（如果有新图片上传）
        if ($image_path && !empty($_POST['old_image']) && file_exists($_POST['old_image'])) {
            unlink($_POST['old_image']);
        }
    } else {
        $draft_id = uniqid();
    }
    
    // 如果没有新图片上传，保留旧图片
    if (!$image_path && isset($_POST['old_image'])) {
        $image_path = $_POST['old_image'];
    }
    
    // 保存草稿数据
    $draft_data = [
        'id' => $draft_id,
        'name' => $draft_name,
        'title' => $post_title,
        'content' => $post_content,
        'image' => $image_path,
        'author_ip' => $user_ip,
        'created' => time(),
        'updated' => time()
    ];
    
    file_put_contents(DRAFT_DIR . $draft_id . '.json', json_encode($draft_data));
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?mode=drafts&saved=1');
    exit;
}

// 发布帖子函数
function publishPost() {
    global $user_ip;
    $post_title = $_POST['post_title'];
    $post_content = $_POST['post_content'];
    
    // 处理图片上传
    $image_path = handleImageUpload();
    
    // 创建帖子目录
    $date_folder = date('Y-m-d');
    $post_folder = POST_DIR . $date_folder . '_' . $user_ip . '/';
    if (!file_exists($post_folder)) mkdir($post_folder, 0777, true);
    
    // 生成帖子ID
    $post_id = uniqid();
    
    // 保存帖子数据
    $post_data = [
        'id' => $post_id,
        'title' => $post_title,
        'content' => $post_content,
        'image' => $image_path,
        'author_ip' => $user_ip,
        'created' => time(),
        'updated' => time()
    ];
    
    file_put_contents($post_folder . $post_id . '.json', json_encode($post_data));
    
    // 如果是从草稿发布的，删除草稿
    if (!empty($_POST['draft_id'])) {
        $draft_file = DRAFT_DIR . $_POST['draft_id'] . '.json';
        if (file_exists($draft_file)) {
            unlink($draft_file);
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?published=1');
    exit;
}

// 更新帖子函数
function updatePost() {
    $post_id = $_POST['post_id'];
    $post_title = $_POST['post_title'];
    $post_content = $_POST['post_content'];
    
    // 查找帖子文件
    $post_files = glob(POST_DIR . '*/' . $post_id . '.json');
    if (empty($post_files)) {
        return;
    }
    
    $post_file = $post_files[0];
    $post_data = json_decode(file_get_contents($post_file), true);
    
    // 处理图片上传
    $image_path = handleImageUpload();
    
    // 如果没有新图片上传，保留旧图片
    if (!$image_path) {
        $image_path = $post_data['image'];
    } else {
        // 删除旧的图片
        if (!empty($post_data['image']) && file_exists($post_data['image'])) {
            unlink($post_data['image']);
        }
    }
    
    // 更新帖子数据
    $post_data['title'] = $post_title;
    $post_data['content'] = $post_content;
    $post_data['image'] = $image_path;
    $post_data['updated'] = time();
    
    file_put_contents($post_file, json_encode($post_data));
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?mode=my&updated=1');
    exit;
}

// 删除草稿函数
function deleteDraft($draft_id) {
    $draft_file = DRAFT_DIR . $draft_id . '.json';
    if (file_exists($draft_file)) {
        // 删除关联的图片
        $draft_data = json_decode(file_get_contents($draft_file), true);
        if (!empty($draft_data['image']) && file_exists($draft_data['image'])) {
            unlink($draft_data['image']);
        }
        unlink($draft_file);
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?mode=drafts');
    exit;
}

// 删除帖子函数
function deletePost($post_id) {
    $post_files = glob(POST_DIR . '*/' . $post_id . '.json');
    if (!empty($post_files)) {
        // 删除关联的图片
        $post_data = json_decode(file_get_contents($post_files[0]), true);
        if (!empty($post_data['image']) && file_exists($post_data['image'])) {
            unlink($post_data['image']);
        }
        unlink($post_files[0]);
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?mode=my');
    exit;
}

// 处理图片上传
function handleImageUpload() {
    if (!empty($_FILES['post_image']['name'])) {
        $image_ext = pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid() . '.' . $image_ext;
        move_uploaded_file($_FILES['post_image']['tmp_name'], UPLOAD_DIR . $image_name);
        return UPLOAD_DIR . $image_name;
    }
    return '';
}

// 获取帖子列表函数
function getPosts($mode, $search_query = '') {
    global $user_ip;
    
    $posts = [];
    $folders = glob(POST_DIR . '*', GLOB_ONLYDIR);
    
    foreach ($folders as $folder) {
        $files = glob($folder . '/*.json');
        foreach ($files as $file) {
            $post_data = json_decode(file_get_contents($file), true);
            
            // 过滤模式
            if ($mode === 'my' && $post_data['author_ip'] !== $user_ip) {
                continue;
            }
            
            // 搜索过滤
            if ($search_query && 
                stripos($post_data['title'], $search_query) === false && 
                stripos($post_data['content'], $search_query) === false) {
                continue;
            }
   
            $posts[] = $post_data;
        }
    }
    
    // 按时间倒序排列
    usort($posts, function($a, $b) {
        return $b['created'] - $a['created'];
    });
    
    return $posts;
}

// 获取草稿列表函数
function getDrafts() {
    global $user_ip;
    
    $drafts = [];
    $files = glob(DRAFT_DIR . '*.json');
    
    foreach ($files as $file) {
        $draft_data = json_decode(file_get_contents($file), true);
        // 只显示当前用户的草稿
        if ($draft_data['author_ip'] === $user_ip) {
            $drafts[] = $draft_data;
        }
    }
    
    // 按时间倒序排列
    usort($drafts, function($a, $b) {
        return $b['created'] - $a['created'];
    });
    
    return $drafts;
}

// 计算目录大小
function dirSize($directory) {
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
        $size += $file->getSize();
    }
    return $size;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>帖子管理系统</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Microsoft YaHei', sans-serif;
        }
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
        }
        .user-info div {
            flex: 1;
        }
        .main-layout {
            display: flex;
            gap: 20px;
        }
        .sidebar {
            flex: 0 0 250px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .content {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .menu {
            list-style: none;
        }
        .menu li {
            margin-bottom: 10px;
        }
        .menu a {
            display: block;
            padding: 10px 15px;
            background: #f0f0f0;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        .menu a:hover, .menu a.active {
            background: #6a11cb;
            color: white;
        }
        .post-form {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            margin-right: 10px;
        }
        .btn-primary {
            background: #6a11cb;
            color: white;
        }
        .btn-primary:hover {
            background: #2575fc;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .post-list {
            margin-top: 20px;
        }
        .post-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .post-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #6a11cb;
        }
        .post-meta {
            color: #777;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .post-content {
            margin-bottom: 10px;
            line-height: 1.8;
        }
        .post-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 4px;
            margin-top: 10px;
            border: 1px solid #ddd;
        }
        .search-box {
            margin-bottom: 20px;
            display: flex;
        }
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        .search-box button {
            padding: 10px 15px;
            background: #6a11cb;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-size: 16px;
        }
        .footer {
            margin-top: 40px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .footer-links {
            margin-bottom: 10px;
        }
        .footer-links a {
            margin: 0 10px;
            color: #6a11cb;
            text-decoration: none;
            font-weight: bold;
        }
        .disclaimer {
            color: #777;
            font-size: 14px;
            line-height: 1.6;
        }
        .draft-list, .post-list {
            margin-top: 20px;
        }
        .draft-item, .post-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
        }
        .draft-item:hover, .post-item:hover {
            background: #f9f9f9;
        }
        .draft-actions, .post-actions {
            display: flex;
            gap: 10px;
        }
        .draft-actions a, .post-actions a {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-size: 14px;
        }
        .btn-edit {
            background: #ffc107;
        }
        .btn-edit:hover {
            background: #e0a800;
        }
        .btn-delete {
            background: #dc3545;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .btn-publish {
            background: #28a745;
        }
        .btn-publish:hover {
            background: #218838;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #777;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            color: #ddd;
        }
        .mode-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #6a11cb;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>帖子管理系统</h1>
            <div class="user-info">
                <div>
                    <p><strong>IP地址:</strong> <?php echo $user_ip; ?></p>
                    <p><strong>机器名:</strong> <?php echo $machine_name; ?></p>
                </div>
                <div>
                    <p><strong>权限:</strong> 普通用户</p>
                    <p><strong>登录时间:</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></p>
                </div>
            </div>
        </header>

        <div class="main-layout">
            <div class="sidebar">
                <ul class="menu">
                    <li><a href="?mode=all" class="<?php echo $mode === 'all' ? 'active' : ''; ?>">所有帖子</a></li>
                    <li><a href="?mode=my" class="<?php echo $mode === 'my' ? 'active' : ''; ?>">我的帖子</a></li>
                    <li><a href="?mode=drafts" class="<?php echo $mode === 'drafts' ? 'active' : ''; ?>">草稿箱 (<?php echo count($drafts); ?>)</a></li>
                </ul>
                
                <div style="margin-top: 30px;">
                    <h3>系统信息</h3>
                    <p>总帖子数: <?php echo count(getPosts('all', '')); ?></p>
                    <p>我的帖子: <?php echo count(getPosts('my', '')); ?></p>
                    <p>存储使用: <?php echo round((dirSize(POST_DIR) + dirSize(DRAFT_DIR) + dirSize(UPLOAD_DIR)) / 1024, 2); ?> KB</p>
                </div>
            </div>

            <div class="content">
                <?php if (isset($_GET['saved']) && $_GET['saved'] == '1'): ?>
                    <div class="alert alert-success">
                        草稿已成功保存！
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['published']) && $_GET['published'] == '1'): ?>
                    <div class="alert alert-success">
                        帖子已成功发布！
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
                    <div class="alert alert-success">
                        帖子已成功更新！
                    </div>
                <?php endif; ?>

                <?php if ($mode !== 'drafts'): ?>
                <div class="search-box">
                    <input type="text" id="search-input" placeholder="搜索帖子标题或内容..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button onclick="performSearch()">搜索</button>
                </div>
                <?php endif; ?>

                <?php if ($mode === 'drafts'): ?>
                    <div class="mode-title">
                        <h2>草稿箱</h2>
                        <span><?php echo count($drafts); ?> 个草稿</span>
                    </div>
                    
                    <div class="draft-list">
                        <?php if (empty($drafts)): ?>
                            <div class="empty-state">
                                <i>📝</i>
                                <h3>暂无草稿</h3>
                                <p>您还没有保存任何草稿</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($drafts as $draft): ?>
                                <div class="draft-item">
                                    <div style="flex: 1;">
                                        <div style="font-weight: bold; margin-bottom: 5px;">
                                            <?php echo htmlspecialchars($draft['name']); ?>
                                        </div>
                                        <div style="margin-bottom: 5px;">
                                            <?php echo htmlspecialchars($draft['title']); ?>
                                        </div>
                                        <div style="font-size: 12px; color: #777;">
                                            创建: <?php echo date('Y-m-d H:i', $draft['created']); ?>
                                            | 更新: <?php echo date('Y-m-d H:i', $draft['updated']); ?>
                                        </div>
                                    </div>
                                    <div class="draft-actions">
                                        <a href="?mode=drafts&edit_draft=<?php echo $draft['id']; ?>" class="btn-edit">编辑</a>
                                        <a href="?mode=drafts&delete_draft=<?php echo $draft['id']; ?>" class="btn-delete" onclick="return confirm('确定要删除这个草稿吗？')">删除</a>
                                        <a href="?mode=drafts&edit_draft=<?php echo $draft['id']; ?>&publish=1" class="btn-publish">发布</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="mode-title">
                    <h2><?php 
                        if ($mode === 'drafts' && $edit_draft_id) {
                            echo '编辑草稿';
                        } elseif ($edit_post_id) {
                            echo '编辑帖子';
                        } else {
                            echo '发布新帖子';
                        }
                    ?></h2>
                </div>
                
                <form class="post-form" method="POST" enctype="multipart/form-data">
                    <?php if ($edit_draft_id): ?>
                        <input type="hidden" name="draft_id" value="<?php echo $edit_draft_id; ?>">
                    <?php endif; ?>
                    
                    <?php if ($edit_post_id): ?>
                        <input type="hidden" name="post_id" value="<?php echo $edit_post_id; ?>">
                        <input type="hidden" name="update_post" value="1">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="draft_name">草稿名称（可选）</label>
                        <input type="text" class="form-control" id="draft_name" name="draft_name" 
                               value="<?php 
                               if (isset($draft_content['name'])) echo htmlspecialchars($draft_content['name']);
                               elseif (isset($post_content['title'])) echo htmlspecialchars($post_content['title'] . ' - 编辑副本');
                               ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="post_title">帖子标题 *</label>
                        <input type="text" class="form-control" id="post_title" name="post_title" required
                               value="<?php 
                               if (isset($draft_content['title'])) echo htmlspecialchars($draft_content['title']);
                               elseif (isset($post_content['title'])) echo htmlspecialchars($post_content['title']);
                               ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="post_content">帖子内容 *</label>
                        <textarea class="form-control" id="post_content" name="post_content" required><?php 
                        if (isset($draft_content['content'])) echo htmlspecialchars($draft_content['content']);
                        elseif (isset($post_content['content'])) echo htmlspecialchars($post_content['content']);
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="post_image">上传图片（可选）</label>
                        <input type="file" class="form-control" id="post_image" name="post_image" accept="image/*">
                        
                        <?php if (isset($draft_content['image']) && $draft_content['image'] && file_exists($draft_content['image'])): ?>
                            <div style="margin-top: 10px;">
                                <p>当前图片:</p>
                                <img src="<?php echo $draft_content['image']; ?>" alt="草稿图片" style="max-width: 200px; border: 1px solid #ddd;">
                                <input type="hidden" name="old_image" value="<?php echo $draft_content['image']; ?>">
                            </div>
                        <?php elseif (isset($post_content['image']) && $post_content['image'] && file_exists($post_content['image'])): ?>
                            <div style="margin-top: 10px;">
                                <p>当前图片:</p>
                                <img src="<?php echo $post_content['image']; ?>" alt="帖子图片" style="max-width: 200px; border: 1px solid #ddd;">
                                <input type="hidden" name="old_image" value="<?php echo $post_content['image']; ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <?php if ($edit_post_id): ?>
                            <button type="submit" class="btn btn-success">更新帖子</button>
                        <?php else: ?>
                            <button type="submit" name="save_draft" class="btn btn-secondary">保存草稿</button>
                            <button type="submit" name="publish" class="btn btn-primary">发布帖子</button>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($mode !== 'drafts'): ?>
                <div class="mode-title">
                    <h2><?php echo $mode === 'my' ? '我的帖子' : '所有帖子'; ?></h2>
                    <span><?php echo count($posts); ?> 篇帖子</span>
                </div>
                
                <div class="post-list">
                    <?php if (empty($posts)): ?>
                        <div class="empty-state">
                            <i>📭</i>
                            <h3>暂无帖子</h3>
                            <p><?php echo $mode === 'my' ? '您还没有发布任何帖子' : '系统目前还没有帖子'; ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="post-item">
                                <div style="flex: 1;">
                                    <div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div>
                                    <div class="post-meta">
                                        机器: <?php echo "Machine_" . substr(md5($post['author_ip']), 0, 8); ?> | 
                                        IP: <?php echo htmlspecialchars($post['author_ip']); ?> | 
                                        创建: <?php echo date('Y-m-d H:i', $post['created']); ?> | 
                                        更新: <?php echo date('Y-m-d H:i', $post['updated']); ?>
                                    </div>
                                    <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                                    <?php if ($post['image'] && file_exists($post['image'])): ?>
                                        <img src="<?php echo $post['image']; ?>" alt="帖子图片" class="post-image">
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($post['author_ip'] === $user_ip): ?>
                                    <div class="post-actions">
                                        <a href="?mode=my&edit_post=<?php echo $post['id']; ?>" class="btn-edit">编辑</a>
                                        <a href="?mode=my&delete_post=<?php echo $post['id']; ?>" class="btn-delete" onclick="return confirm('确定要删除这个帖子吗？')">删除</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            <div class="footer-links">
                <a href="./">首页</a>
                <a href="../OtherWeb/Sions.php">关于我们</a>
                <a href="../OtherFile/slxj.psxx" download="tiaokuan.txt">使用条款</a>
                <a href="../OtherFile/xhsiz.pss" download="yinsi.txt">隐私政策</a>
                <a href="javascript:alert('QQ3580539018, 有问题加管理员.')">联系我们</a>
            </div>
            <div class="disclaimer">
                <p><strong>网络安全声明：</strong>禁止利用本系统进行任何非法入侵、传播恶意软件、侵犯他人隐私或其他违法活动。</p>
                <p>用户应遵守当地法律法规，尊重他人隐私和知识产权，所有操作都将被记录用于安全审计。</p>
            </div>
        </div>
    </div>

    <script>
        function performSearch() {
            const searchQuery = document.getElementById('search-input').value;
            const currentMode = '<?php echo $mode; ?>';
            window.location.href = `?mode=${currentMode}&search=${encodeURIComponent(searchQuery)}`;
        }
        
        // 自动发布草稿
        <?php if (isset($_GET['publish']) && isset($_GET['edit_draft'])): ?>
            document.getElementById('draft_name').value = "<?php echo addslashes($draft_content['name']); ?>";
            document.getElementById('post_title').value = "<?php echo addslashes($draft_content['title']); ?>";
            document.getElementById('post_content').value = "<?php echo addslashes($draft_content['content']); ?>";
            document.getElementById('post_image').required = false;
            setTimeout(function() {
                document.querySelector('button[name="publish"]').click();
            }, 500);
        <?php endif; ?>
        
        // 回车键搜索
        document.getElementById('search-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    </script>
</body>
</html>
