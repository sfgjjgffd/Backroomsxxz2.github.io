<?php
// 获取用户IP
$user_ip = $_SERVER['REMOTE_ADDR'];

// 扫描downfile目录
$downfile_dir = './downfile/';
$files = [];
if (is_dir($downfile_dir)) {
    $items = scandir($downfile_dir);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($downfile_dir . $item)) {
            $files[] = $item;
        }
    }
}

// 获取文件详细信息
$file_details = [];
foreach ($files as $file) {
    $file_path = $downfile_dir . $file . '/';
    
    // 读取标题
    $title = "未知标题";
    if (file_exists($file_path . 'title.txt')) {
        $title = file_get_contents($file_path . 'title.txt');
    }
    
    // 读取简介
    $info = "暂无简介";
    if (file_exists($file_path . 'info.txt')) {
        $info = file_get_contents($file_path . 'info.txt');
    }
    
    // 读取上传者IP和创建时间
    $uploader_ip = "未知";
    $create_time = "未知";
    $download_count = 0;
    if (file_exists($file_path . 'time.txt')) {
        $time_content = file_get_contents($file_path . 'time.txt');
        $time_data = explode('|', $time_content);
        if (count($time_data) >= 3) {
            $uploader_ip = $time_data[0];
            $create_time = $time_data[1];
            $download_count = intval($time_data[2]);
        }
    }
    
    // 获取文件列表
    $file_list = [];
    $dir_files = scandir($file_path);
    foreach ($dir_files as $dir_file) {
        if ($dir_file !== '.' && $dir_file !== '..' && 
            !in_array($dir_file, ['title.txt', 'info.txt', 'time.txt']) &&
            is_file($file_path . $dir_file)) {
            $file_list[] = $dir_file;
        }
    }
    
    $file_details[] = [
        'folder' => $file,
        'title' => $title,
        'info' => $info,
        'uploader_ip' => $uploader_ip,
        'create_time' => $create_time,
        'download_count' => $download_count,
        'files' => $file_list
    ];
}

// 处理文件上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload'])) {
    $title = $_POST['title'] ?? '未命名文件';
    $info = $_POST['info'] ?? '暂无简介';
    
    // 创建文件夹
    $folder_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $title);
    $folder_path = $downfile_dir . $folder_name;
    
    if (!is_dir($folder_path)) {
        mkdir($folder_path, 0755, true);
        
        // 创建title.txt
        file_put_contents($folder_path . '/title.txt', $title);
        
        // 创建info.txt
        file_put_contents($folder_path . '/info.txt', $info);
        
        // 创建time.txt
        $time_content = $user_ip . '|' . date('Y-m-d H:i:s') . '|0';
        file_put_contents($folder_path . '/time.txt', $time_content);
        
        // 移动上传的文件
        $uploaded_file = $_FILES['file_upload']['tmp_name'];
        $file_name = $_FILES['file_upload']['name'];
        move_uploaded_file($uploaded_file, $folder_path . '/' . $file_name);
        
        // 刷新页面
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// 处理文件下载
if (isset($_GET['download']) && isset($_GET['folder']) && isset($_GET['file'])) {
    $folder = $_GET['folder'];
    $file = $_GET['file'];
    $file_path = $downfile_dir . $folder . '/' . $file;
    
    if (file_exists($file_path)) {
        // 更新下载次数
        $time_file = $downfile_dir . $folder . '/time.txt';
        if (file_exists($time_file)) {
            $time_content = file_get_contents($time_file);
            $time_data = explode('|', $time_content);
            if (count($time_data) >= 3) {
                $time_data[2] = intval($time_data[2]) + 1;
                file_put_contents($time_file, implode('|', $time_data));
            }
        }
        
        // 发送文件
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件下载中心</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
            padding: 20px;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .file-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .file-header {
            background: #2575fc;
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .file-content {
            padding: 20px;
        }
        
        .file-info {
            margin-bottom: 15px;
        }
        
        .file-info p {
            margin: 8px 0;
            display: flex;
            align-items: center;
        }
        
        .file-info i {
            margin-right: 10px;
            color: #2575fc;
        }
        
        .btn {
            display: inline-block;
            background: #2575fc;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #1c64e0;
        }
        
        .btn-download {
            background: #4caf50;
        }
        
        .btn-download:hover {
            background: #3d8b40;
        }
        
        .btn-back {
            background: #f44336;
        }
        
        .btn-back:hover {
            background: #d32f2f;
        }
        
        .fixed-buttons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 1000;
        }
        
        .fixed-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #ff9800;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, background 0.3s ease;
            cursor: pointer;
        }
        
        .fixed-btn:hover {
            transform: scale(1.1);
            background: #f57c00;
        }
        
        .detail-view {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin: 0 auto;
            max-width: 800px;
        }
        
        .detail-header {
            text-align: center;
            margin-bottom: 30px;
            color: #2575fc;
        }
        
        .detail-content {
            margin-bottom: 30px;
        }
        
        .detail-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .my-file {
            border-left: 5px solid #ff9800;
        }
        
        .file-list {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        /* 上传模态框 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .submit-btn {
            background: #4caf50;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: #3d8b40;
        }
        
        @media (max-width: 768px) {
            .file-grid {
                grid-template-columns: 1fr;
            }
            
            .fixed-buttons {
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>文件下载中心</h1>
            <p class="subtitle">浏览、下载和管理您的文件资源 | 您的IP: <?php echo $user_ip; ?></p>
        </header>
        
        <div class="file-grid">
            <?php foreach ($file_details as $file): ?>
            <div class="file-card <?php echo $file['uploader_ip'] === $user_ip ? 'my-file' : ''; ?>">
                <div class="file-header">
                    <h3><?php echo htmlspecialchars($file['title']); ?></h3>
                </div>
                <div class="file-content">
                    <div class="file-info">
                        <p><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($file['info']); ?></p>
                        <p><i class="fas fa-user"></i> 上传者: <?php echo htmlspecialchars($file['uploader_ip']); ?></p>
                        <p><i class="fas fa-calendar"></i> 创建时间: <?php echo htmlspecialchars($file['create_time']); ?></p>
                        <p><i class="fas fa-download"></i> 下载次数: <?php echo $file['download_count']; ?></p>
                    </div>
                    <a href="?view=<?php echo urlencode($file['folder']); ?>" class="btn">查看详情</a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($file_details)): ?>
            <div class="detail-view">
                <div class="detail-header">
                    <h2>暂无文件</h2>
                </div>
                <div class="detail-content">
                    <p>downfile目录中没有找到任何文件，请上传文件。</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_GET['view'])): ?>
        <div class="detail-view">
            <?php
            $view_folder = $_GET['view'];
            $current_file = null;
            
            foreach ($file_details as $file) {
                if ($file['folder'] === $view_folder) {
                    $current_file = $file;
                    break;
                }
            }
            
            if ($current_file):
            ?>
            <div class="detail-header">
                <h2><?php echo htmlspecialchars($current_file['title']); ?></h2>
            </div>
            <div class="detail-content">
                <div class="file-info">
                    <p><i class="fas fa-file-alt"></i> 简介: <?php echo htmlspecialchars($current_file['info']); ?></p>
                    <p><i class="fas fa-user"></i> 上传者: <?php echo htmlspecialchars($current_file['uploader_ip']); ?></p>
                    <p><i class="fas fa-calendar"></i> 创建时间: <?php echo htmlspecialchars($current_file['create_time']); ?></p>
                    <p><i class="fas fa-download"></i> 下载次数: <?php echo $current_file['download_count']; ?></p>
                </div>
                
                <div class="file-list">
                    <h3><i class="fas fa-file"></i> 文件列表</h3>
                    <?php foreach ($current_file['files'] as $file_item): ?>
                    <div class="file-item">
                        <span><i class="fas fa-file"></i> <?php echo htmlspecialchars($file_item); ?></span>
                        <a href="?download=1&folder=<?php echo urlencode($current_file['folder']); ?>&file=<?php echo urlencode($file_item); ?>" class="btn btn-download">
                            <i class="fas fa-download"></i> 下载
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="detail-actions">
                <a href="./DownLoadFile.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> 返回列表</a>
            </div>
            <?php else: ?>
            <div class="detail-header">
                <h2>文件不存在</h2>
            </div>
            <div class="detail-content">
                <p>请求的文件不存在或已被删除。</p>
            </div>
            <div class="detail-actions">
                <a href="./" class="btn btn-back"><i class="fas fa-arrow-left"></i> 返回列表</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- 右下角固定按钮 -->
    <div class="fixed-buttons">
        <div class="fixed-btn" id="uploadBtn">
            <i class="fas fa-upload fa-lg"></i>
        </div>
        <a href="./" class="fixed-btn">
            <i class="fas fa-home fa-lg"></i>
        </a>
    </div>
    
    <!-- 上传模态框 -->
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>上传文件</h2>
                <span class="close-modal">&times;</span>
            </div>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">文件标题</label>
                    <input type="text" id="title" name="title" required placeholder="输入文件标题">
                </div>
                <div class="form-group">
                    <label for="info">文件简介</label>
                    <textarea id="info" name="info" required placeholder="输入文件简介"></textarea>
                </div>
                <div class="form-group">
                    <label for="file_upload">选择文件</label>
                    <input type="file" id="file_upload" name="file_upload" required>
                </div>
                <button type="submit" class="submit-btn">上传文件</button>
            </form>
        </div>
    </div>
    
    <script>
        // 上传模态框控制
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadModal = document.getElementById('uploadModal');
        const closeModal = document.querySelector('.close-modal');
        
        uploadBtn.addEventListener('click', () => {
            uploadModal.style.display = 'flex';
        });
        
        closeModal.addEventListener('click', () => {
            uploadModal.style.display = 'none';
        });
        
        // 点击模态框外部关闭
        window.addEventListener('click', (e) => {
            if (e.target === uploadModal) {
                uploadModal.style.display = 'none';
            }
        });
        
        // 只在详情页面显示详情视图
        <?php if (isset($_GET['view'])): ?>
        document.querySelector('.file-grid').style.display = 'none';
        <?php endif; ?>
    </script>
</body>
</html>