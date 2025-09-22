<?php
// 开启会话保存连接状态
session_start();

// 数据库连接变量
$host = '';
$username = '';
$password = '';
$database = '';
$connection = null;
$success_msg = '';
$error_msg = '';

// 处理数据库连接
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connect_db'])) {
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // 尝试连接数据库
    $connection = new mysqli($host, $username, $password);
    
    if ($connection->connect_error) {
        $error_msg = "连接失败: " . $connection->connect_error;
    } else {
        $success_msg = "成功连接到数据库服务器!";
        $_SESSION['db_host'] = $host;
        $_SESSION['db_username'] = $username;
        $_SESSION['db_password'] = $password;
        $_SESSION['db_connection'] = true;
    }
}

// 如果已有连接信息，尝试重新连接
if (!isset($connection) && isset($_SESSION['db_connection']) && $_SESSION['db_connection']) {
    $host = $_SESSION['db_host'];
    $username = $_SESSION['db_username'];
    $password = $_SESSION['db_password'];
    
    $connection = new mysqli($host, $username, $password);
    
    if ($connection->connect_error) {
        $error_msg = "自动连接失败: " . $connection->connect_error;
        unset($_SESSION['db_connection']);
    }
}

// 处理数据库选择
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_db']) && isset($connection)) {
    $database = $_POST['database'];
    
    if ($connection->select_db($database)) {
        $success_msg = "已选择数据库: $database";
        $_SESSION['selected_db'] = $database;
    } else {
        $error_msg = "选择数据库失败: " . $connection->error;
    }
}

// 处理SQL查询
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_query']) && isset($connection) && isset($_SESSION['selected_db'])) {
    $query = $_POST['query'];
    
    $result = $connection->query($query);
    
    if ($result) {
        if ($result === TRUE) {
            $success_msg = "查询执行成功";
            $rowCount = $connection->affected_rows;
        } else {
            $results = [];
            $rowCount = $result->num_rows;
            
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
            
            $result->free();
        }
    } else {
        $error_msg = "查询错误: " . $connection->error;
    }
}

// 处理申请表提交到服务器
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    $content = $_POST['application_content'];
    
    if (!empty($content)) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $time = date('Ymd_His');
        $random = rand(1000, 9999);
        $filename = "{$ip}_{$time}_{$random}.txt";
        $folder = "applications";
        
        // 确保目录存在
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        
        // 写入文件
        if (file_put_contents("$folder/$filename", $content)) {
            $success_msg = "申请已提交到服务器，文件已保存: $filename";
        } else {
            $error_msg = "保存申请到服务器时出错";
        }
    } else {
        $error_msg = "申请内容不能为空";
    }
}

// 获取所有数据库
if (isset($connection)) {
    $result = $connection->query("SHOW DATABASES");
    $databases = [];
    
    while ($row = $result->fetch_array()) {
        $databases[] = $row[0];
    }
    
    $result->free();
}

// 获取当前数据库的所有表
if (isset($connection) && isset($_SESSION['selected_db'])) {
    $result = $connection->query("SHOW TABLES");
    $tables = [];
    
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    $result->free();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库查询管理系统</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --menu-width: 260px;
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            padding: 0;
            margin: 0;
        }

        /* 侧边栏样式 */
        .sidebar {
            width: var(--menu-width);
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: var(--transition);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            margin: 10px 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .sidebar-menu {
            padding: 15px 0;
        }

        .menu-item {
            padding: 14px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .menu-item.active {
            background: rgba(255, 255, 255, 0.15);
            border-left: 4px solid var(--success);
        }

        .menu-item i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .menu-item span {
            flex: 1;
        }

        /* 主内容区域 */
        .main-content {
            flex: 1;
            margin-left: var(--menu-width);
            padding: 30px;
            transition: var(--transition);
        }

        .section {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 卡片样式 */
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .card-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }

        .card-header i {
            margin-right: 10px;
            color: var(--primary);
            font-size: 1.4rem;
        }

        .card-header h3 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--dark);
        }

        /* 表单样式 */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray);
        }

        .form-control {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            padding: 14px 28px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-block {
            width: 100%;
        }

        /* 表格样式 */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: var(--primary);
            color: white;
            font-weight: 500;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tr:hover {
            background-color: #f1f5f9;
        }

        /* 状态指示器 */
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .connected {
            background: #4ade80;
        }

        .disconnected {
            background: #f87171;
        }

        /* 消息样式 */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        /* 统计卡片 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            text-align: center;
        }

        .stat-card h4 {
            margin-bottom: 10px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card h4 i {
            margin-right: 8px;
        }

        .stat-card h2 {
            margin: 10px 0;
            font-size: 2.2rem;
        }

        /* 响应式设计 */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1100;
                background: var(--primary);
                color: white;
                border: none;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
                cursor: pointer;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>
<body>
    <!-- 移动端菜单切换按钮 -->
    <button class="menu-toggle" id="menuToggle" style="display: none;">
        <i class="fas fa-bars"></i>
    </button>

    <!-- 侧边栏 -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-database"></i> 数据管理系统</h2>
            <p>高效数据查询与处理</p>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-item active" data-target="dashboard">
                <i class="fas fa-home"></i>
                <span>网页详情</span>
            </div>
            <div class="menu-item" data-target="system-overview">
                <i class="fas fa-chart-pie"></i>
                <span>系统概览</span>
            </div>
            <div class="menu-item" data-target="db-connect">
                <i class="fas fa-plug"></i>
                <span>连接数据库</span>
            </div>
            <div class="menu-item" data-target="db-query">
                <i class="fas fa-search"></i>
                <span>数据库查询</span>
            </div>
            <div class="menu-item" data-target="application">
                <i class="fas fa-file-alt"></i>
                <span>申请表</span>
            </div>
            <div class="menu-item" data-target="local-applications">
                <i class="fas fa-desktop"></i>
                <span>本地申请记录</span>
            </div>
            <div class="menu-item" data-target="db-status">
                <i class="fas fa-server"></i>
                <span>数据库状态</span>
                <span class="status-indicator <?php echo isset($connection) ? 'connected' : 'disconnected'; ?>"></span>
            </div>
	<a href="./" style="text-decoration: none;Color:red"><div style="padding: 5px; margin-top: 30px;">
            <div class="menu-item" style="border: 1px solid yellow;border-radius:15px; ">
                <i class=""></i>
                <span>返回大厅</span>
            </div></div></a>
        </div>
        
        <div style="padding: 20px; margin-top: 30px;">
            <div class="card" style="background: rgba(255, 255, 255, 0.1); border: none; color: white;">
                <h4><i class="fas fa-info-circle"></i> 系统信息</h4>
                <p>当前版本: v2.3.1</p>
                <p>最后更新: <?php echo date('Y-m-d'); ?></p>
                <?php if (isset($connection)): ?>
                <p>已连接到: <?php echo $host; ?></p>
                <?php if (isset($_SESSION['selected_db'])): ?>
                <p>当前数据库: <?php echo $_SESSION['selected_db']; ?></p>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 主内容区域 -->
    <div class="main-content">
        <!-- 消息提示 -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>
        
        <!-- 大厅面板 -->
        <div class="section active" id="dashboard">
            <h1 style="margin-bottom: 25px;">数据库管理系统控制台</h1>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tachometer-alt"></i>
                    <h3>欢迎使用</h3>
                </div>
                <p>欢迎使用数据库管理系统。您可以通过左侧菜单进行数据库连接、查询和申请操作。</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h4><i class="fas fa-database"></i> 数据库</h4>
                        <h2><?php echo isset($databases) ? count($databases) : '0'; ?></h2>
                        <p>已连接: <?php echo isset($connection) ? '是' : '否'; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h4><i class="fas fa-table"></i> 数据表</h4>
                        <h2><?php echo isset($tables) ? count($tables) : '0'; ?></h2>
                        <p>当前数据库: <?php echo isset($_SESSION['selected_db']) ? $_SESSION['selected_db'] : '无'; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h4><i class="fas fa-file"></i> 申请文件</h4>
                        <h2>
                            <?php 
                            $folder = "applications";
                            if (file_exists($folder)) {
                                $files = scandir($folder);
                                echo count($files) - 2; // 减去 . 和 ..
                            } else {
                                echo '0';
                            }
                            ?>
                        </h2>
                        <p>服务器端存储</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i>
                    <h3>最近活动</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>时间</th>
                                <th>操作</th>
                                <th>状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                <td>页面加载</td>
                                <td><span class="status-indicator connected"></span> 成功</td>
                            </tr>
                            <?php if (isset($connection)): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                <td>数据库连接</td>
                                <td><span class="status-indicator connected"></span> 成功</td>
                            </tr>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['selected_db'])): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                <td>选择数据库: <?php echo $_SESSION['selected_db']; ?></td>
                                <td><span class="status-indicator connected"></span> 成功</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 系统概览 -->
        <div class="section" id="system-overview">
            <h1 style="margin-bottom: 25px;">系统概览</h1>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i>
                    <h3>系统统计</h3>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);">
                        <h4><i class="fas fa-database"></i> 数据库数量</h4>
                        <h2><?php echo isset($databases) ? count($databases) : '0'; ?></h2>
                        <p>总共可用</p>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #4cc9f0 0%, #3a0ca3 100%);">
                        <h4><i class="fas fa-table"></i> 数据表数量</h4>
                        <h2><?php echo isset($tables) ? count($tables) : '0'; ?></h2>
                        <p>当前数据库</p>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #7209b7 0%, #3a0ca3 100%);">
                        <h4><i class="fas fa-hdd"></i> 服务器存储</h4>
                        <h2>
                            <?php 
                            $folder = "applications";
                            if (file_exists($folder)) {
                                $size = 0;
                                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder)) as $file) {
                                    $size += $file->getSize();
                                }
                                echo round($size / 1024, 2) . ' KB';
                            } else {
                                echo '0 KB';
                            }
                            ?>
                        </h2>
                        <p>申请文件占用</p>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #f72585 0%, #3a0ca3 100%);">
                        <h4><i class="fas fa-users"></i> 用户访问</h4>
                        <h2><?php echo isset($_SESSION['db_connection']) ? '1' : '0'; ?></h2>
                        <p>当前活跃</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i>
                    <h3>系统信息</h3>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>项目</th>
                                <th>值</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>PHP版本</td>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <td>服务器软件</td>
                                <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                            </tr>
                            <tr>
                                <td>数据库连接</td>
                                <td><?php echo isset($connection) ? '已建立' : '未连接'; ?></td>
                            </tr>
                            <tr>
                                <td>当前数据库</td>
                                <td><?php echo isset($_SESSION['selected_db']) ? $_SESSION['selected_db'] : '未选择'; ?></td>
                            </tr>
                            <tr>
                                <td>申请文件数量</td>
                                <td>
                                    <?php 
                                    $folder = "applications";
                                    if (file_exists($folder)) {
                                        $files = scandir($folder);
                                        echo count($files) - 2;
                                    } else {
                                        echo '0';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 数据库连接 -->
        <div class="section" id="db-connect">
            <h1 style="margin-bottom: 25px;">数据库连接</h1>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-plug"></i>
                    <h3>连接设置</h3>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label for="host">主机地址</label>
                        <input type="text" id="host" name="host" class="form-control" value="localhost" placeholder="例如: localhost 或 192.168.1.100" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">用户名</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="输入数据库用户名" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">密码</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="输入数据库密码">
                    </div>
                    
                    <button type="submit" name="connect_db" class="btn btn-block">
                        <i class="fas fa-link"></i> 连接数据库
                    </button>
                </form>
            </div>
            
            <?php if (isset($databases) && !empty($databases)): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-database"></i>
                    <h3>数据库列表</h3>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label for="database">选择数据库</label>
                        <select id="database" name="database" class="form-control" required>
                            <?php foreach ($databases as $db): ?>
                                <option value="<?php echo $db; ?>" <?php echo (isset($_SESSION['selected_db']) && $_SESSION['selected_db'] == $db) ? 'selected' : ''; ?>>
                                    <?php echo $db; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="select_db" class="btn btn-block">
                        <i class="fas fa-check"></i> 选择数据库
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 数据库查询 -->
        <div class="section" id="db-query">
            <h1 style="margin-bottom: 25px;">数据库查询</h1>
            
            <?php if (isset($tables) && !empty($tables)): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i>
                    <h3>数据表列表</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>表名</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $table): ?>
                            <tr>
                                <td><?php echo $table; ?></td>
                                <td>
                                    <button class="btn use-table-btn" style="padding: 8px 16px;" data-table="<?php echo $table; ?>">
                                        使用
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-search"></i>
                    <h3>SQL查询</h3>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label for="query">输入SQL查询语句</label>
                        <textarea id="query" name="query" class="form-control" rows="4" placeholder="例如: SELECT * FROM users WHERE active = 1;" required></textarea>
                    </div>
                    
                    <button type="submit" name="run_query" class="btn">
                        <i class="fas fa-play"></i> 执行查询
                    </button>
                </form>
                
                <?php if (isset($results) && is_array($results)): ?>
                <div style="margin-top: 30px;">
                    <h4>查询结果 (<?php echo $rowCount; ?> 行)</h4>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($results[0]) as $column): ?>
                                        <th><?php echo $column; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 申请表 -->
        <div class="section" id="application">
            <h1 style="margin-bottom: 25px;">数据申请提交</h1>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-file-alt"></i>
                    <h3>申请表单</h3>
                </div>
                <form method="post" id="applicationForm">
                    <div class="form-group">
                        <label for="application_content">申请内容</label>
                        <textarea id="application_content" name="application_content" class="form-control" placeholder="详细描述您的数据申请需求..." required></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <button type="submit" name="submit_application" class="btn" style="flex: 1;">
                            <i class="fas fa-cloud-upload-alt"></i> 提交到服务器
                        </button>
                        <button type="button" id="saveLocal" class="btn" style="flex: 1; background: #7209b7;">
                            <i class="fas fa-desktop"></i> 保存到本地
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i>
                    <h3>服务器申请记录</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>文件名</th>
                                <th>大小</th>
                                <th>修改时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $folder = "applications";
                            if (file_exists($folder)) {
                                $files = array_diff(scandir($folder), array('.', '..'));
                                
                                foreach ($files as $file) {
                                    $filepath = $folder . '/' . $file;
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($file) . "</td>";
                                    echo "<td>" . round(filesize($filepath) / 1024, 2) . " KB</td>";
                                    echo "<td>" . date("Y-m-d H:i:s", filemtime($filepath)) . "</td>";
                                    echo "<td><a href='$filepath' download class='btn' style='padding: 8px 16px;'><i class='fas fa-download'></i> 下载</a></td>";
                                    echo "</tr>";
                                }
                                
                                if (count($files) === 0) {
                                    echo "<tr><td colspan='4'>暂无申请文件</td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>暂无申请文件</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 本地申请记录 -->
        <div class="section" id="local-applications">
            <h1 style="margin-bottom: 25px;">本地申请记录</h1>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-desktop"></i>
                    <h3>本地存储的申请</h3>
                </div>
                <p>这里显示的是保存在您本地浏览器中的申请记录。这些记录仅存储在您的设备上，不会发送到服务器。</p>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>日期</th>
                                <th>内容预览</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="localApplicationsList">
                            <tr>
                                <td colspan="3">暂无本地申请记录</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <button id="clearLocalApplications" class="btn" style="background: #f72585;">
                        <i class="fas fa-trash"></i> 清空本地记录
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 菜单切换功能
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function() {
                // 移除所有活动状态
                document.querySelectorAll('.menu-item').forEach(i => {
                    i.classList.remove('active');
                });
                
                // 添加当前活动状态
                this.classList.add('active');
                
                // 隐藏所有内容区域
                document.querySelectorAll('.section').forEach(section => {
                    section.classList.remove('active');
                });
                
                // 显示选中内容区域
                const targetId = this.getAttribute('data-target');
                document.getElementById(targetId).classList.add('active');
            });
        });

        // 使用表按钮功能
        document.querySelectorAll('.use-table-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tableName = this.getAttribute('data-table');
                document.getElementById('query').value = `SELECT * FROM ${tableName} LIMIT 10`;
                
                // 切换到查询标签
                document.querySelectorAll('.menu-item').forEach(i => {
                    i.classList.remove('active');
                });
                document.querySelector('[data-target="db-query"]').classList.add('active');
                
                document.querySelectorAll('.section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById('db-query').classList.add('active');
            });
        });

        // 响应式菜单切换
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        // 检查窗口大小
        function checkWindowSize() {
            if (window.innerWidth <= 992) {
                menuToggle.style.display = 'block';
                sidebar.classList.remove('active');
            } else {
                menuToggle.style.display = 'none';
                sidebar.classList.add('active');
            }
        }
        
        // 初始检查
        checkWindowSize();
        
        // 窗口大小变化时检查
        window.addEventListener('resize', checkWindowSize);
        
        // 菜单切换按钮点击事件
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });

        // 保存申请到本地
        document.getElementById('saveLocal').addEventListener('click', function() {
            const content = document.getElementById('application_content').value;
            
            if (!content) {
                alert('请先填写申请内容');
                return;
            }
            
            // 创建时间戳
            const now = new Date();
            const timestamp = now.toISOString().replace(/[:.]/g, '-');
            
            // 创建申请对象
            const application = {
                id: 'app-' + timestamp,
                content: content,
                date: now.toLocaleString()
            };
            
            // 获取现有申请或初始化空数组
            let applications = JSON.parse(localStorage.getItem('applications') || '[]');
            
            // 添加新申请
            applications.push(application);
            
            // 保存回localStorage
            localStorage.setItem('applications', JSON.stringify(applications));
            
            // 更新显示
            updateLocalApplicationsList();
            
            alert('申请已保存到本地浏览器存储中！');
        });

        // 更新本地申请列表
        function updateLocalApplicationsList() {
            const applications = JSON.parse(localStorage.getItem('applications') || '[]');
            const listElement = document.getElementById('localApplicationsList');
            
            if (applications.length === 0) {
                listElement.innerHTML = '<tr><td colspan="3">暂无本地申请记录</td></tr>';
                return;
            }
            
            // 清空列表
            listElement.innerHTML = '';
            
            // 按日期倒序排列
            applications.sort((a, b) => new Date(b.date) - new Date(a.date));
            
            // 添加申请到列表
            applications.forEach(app => {
                const row = document.createElement('tr');
                
                // 日期单元格
                const dateCell = document.createElement('td');
                dateCell.textContent = app.date;
                row.appendChild(dateCell);
                
                // 内容预览单元格
                const contentCell = document.createElement('td');
                contentCell.textContent = app.content.length > 50 ? app.content.substring(0, 50) + '...' : app.content;
                row.appendChild(contentCell);
                
                // 操作单元格
                const actionCell = document.createElement('td');
                
                // 查看按钮
                const viewBtn = document.createElement('button');
                viewBtn.className = 'btn';
                viewBtn.style.marginRight = '8px';
                viewBtn.style.padding = '8px 16px';
                viewBtn.innerHTML = '<i class="fas fa-eye"></i> 查看';
                viewBtn.onclick = () => {
                    alert(`申请内容:\n\n${app.content}\n\n提交时间: ${app.date}`);
                };
                actionCell.appendChild(viewBtn);
                
                // 删除按钮
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn';
                deleteBtn.style.background = '#f72585';
                deleteBtn.style.padding = '8px 16px';
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i> 删除';
                deleteBtn.onclick = () => {
                    if (confirm('确定要删除这条申请记录吗？')) {
                        const updatedApplications = applications.filter(a => a.id !== app.id);
                        localStorage.setItem('applications', JSON.stringify(updatedApplications));
                        updateLocalApplicationsList();
                    }
                };
                actionCell.appendChild(deleteBtn);
                
                row.appendChild(actionCell);
                listElement.appendChild(row);
            });
        }

        // 清空本地申请记录
        document.getElementById('clearLocalApplications').addEventListener('click', function() {
            if (confirm('确定要清空所有本地申请记录吗？此操作不可恢复。')) {
                localStorage.removeItem('applications');
                updateLocalApplicationsList();
            }
        });

        // 页面加载时更新本地申请列表
        document.addEventListener('DOMContentLoaded', function() {
            updateLocalApplicationsList();
        });
    </script>
</body>
</html>