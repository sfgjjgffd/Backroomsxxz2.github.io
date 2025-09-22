<?php
// 首先处理session和连接计数
session_start();

// 设置session超时时间（5分钟）
$timeout = 300;

// 检查是否已有session和最后活动时间
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    session_start();
}

// 更新最后活动时间
$_SESSION['LAST_ACTIVITY'] = time();

// 获取用户IP
$user_ip = $_SERVER['REMOTE_ADDR'];

// 读取或初始化连接计数
$connects_file = './posts/connects.txt';

// 读取现有连接数据
$connections = [];
if (file_exists($connects_file)) {
    $data = file_get_contents($connects_file);
    $connections = json_decode($data, true) ?: [];
}

// 移除超时的连接（5分钟无活动）
$current_time = time();
foreach ($connections as $ip => $timestamp) {
    if ($current_time - $timestamp > $timeout) {
        unset($connections[$ip]);
    }
}

// 添加或更新当前连接
$connections[$user_ip] = $current_time;

// 保存更新后的连接数据
file_put_contents($connects_file, json_encode($connections));

// 获取活跃连接数
$active_connections = count($connections);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后端控制台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --sidebar-width: 280px;
            --header-height: 80px;
            --footer-height: 60px;
            --transition-speed: 0.3s;
            --main-bg-color: #f5f7fa;
            --card-bg-color: rgba(255, 255, 255, 0.85);
            --text-color: #333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: var(--main-bg-color);
            min-height: 100vh;
            padding: 0;
            margin: 0;
            color: var(--text-color);
            transition: all var(--transition-speed);
        }

        /* 主题颜色 */
        body.theme-dark {
            --main-bg-color: #2c3e50;
            --card-bg-color: rgba(44, 62, 80, 0.9);
            --text-color: #ecf0f1;
        }

        body.theme-blue {
            --main-bg-color: #e3f2fd;
            --card-bg-color: rgba(227, 242, 253, 0.9);
            --text-color: #0d47a1;
        }

        body.theme-green {
            --main-bg-color: #e8f5e9;
            --card-bg-color: rgba(232, 245, 233, 0.9);
            --text-color: #1b5e20;
        }

        body.theme-orange {
            --main-bg-color: #fff3e0;
            --card-bg-color: rgba(255, 243, 224, 0.9);
            --text-color: #e65100;
        }

        body.theme-purple {
            --main-bg-color: #f3e5f5;
            --card-bg-color: rgba(243, 229, 245, 0.9);
            --text-color: #4a148c;
        }

        /* 布局样式 */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* 侧边栏样式 */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--card-bg-color);
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: all var(--transition-speed);
        }

        .logo {
            padding: 20px 25px;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
            font-size: 28px;
        }

        .nav-menu {
            flex: 1;
        }

        .nav-item {
            padding: 15px 25px;
            margin: 8px 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            color: #555;
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .nav-item.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .nav-item i {
            margin-right: 12px;
            font-size: 20px;
            width: 24px;
            text-align: center;
        }

        /* 主内容区域样式 */
        .main-content {
            flex: 1;
            flex-direction: column;
        }

        /* 顶部标题栏 */
        .top-header {
            height: var(--header-height);
            background: var(--card-bg-color);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 99;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
        }

        .theme-selector {
            display: flex;
            align-items: center;
        }

        .theme-label {
            margin-right: 15px;
            font-weight: 500;
            color: #666;
        }

        .theme-colors {
            display: flex;
            gap: 10px;
        }

        .theme-color {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .theme-color:hover {
            transform: scale(1.15);
        }

        /* 内容区域样式 */
        .content-area {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .content-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .content-section.active {
            display: block;
        }

        .card {
            background: var(--card-bg-color);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 20px 25px;
            font-weight: 600;
            font-size: 18px;
            color: var(--primary-color);
        }

        .card-body {
            padding: 25px;
        }

        /* 概览部分样式 */
        .editable-text {
            padding: 15px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.6);
            border: 1px dashed #ccc;
            min-height: 150px;
            transition: all 0.3s ease;
        }

        .editable-text:focus {
            outline: none;
            border: 1px solid var(--primary-color);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--card-bg-color) 0%, rgba(249, 250, 252, 0.9) 100%);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        /* 链接部分样式 */
        .link-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.6);
            margin-bottom: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #444;
        }

        .link-item:hover {
            background: rgba(67, 97, 238, 0.1);
            transform: translateX(5px);
            text-decoration: none;
            color: var(--primary-color);
        }

        .link-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 18px;
        }

        .link-text {
            flex: 1;
        }

        .link-title {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .link-desc {
            font-size: 13px;
            color: #777;
        }

        /* 用户连接部分样式 */
        .connection-stats {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            border-radius: 16px;
            color: white;
        }

        .connection-text h3 {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .connection-count {
            font-size: 42px;
            font-weight: 700;
            color: white;
        }

        .connection-chart {
            height: 200px;
            margin-top: 20px;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-table th {
            background: rgba(67, 97, 238, 0.1);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--primary-color);
        }

        .user-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .user-table tr:hover {
            background: rgba(67, 97, 238, 0.05);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: rgba(76, 201, 240, 0.2);
            color: #4cc9f0;
        }

        .status-inactive {
            background: rgba(108, 117, 125, 0.2);
            color: #6c757d;
        }

        /* 底部信息栏样式 */
        .footer {
            height: var(--footer-height);
            background: var(--card-bg-color);
            backdrop-filter: blur(10px);
            box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.08);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #666;
            font-size: 14px;
        }

        .security-status {
            display: flex;
            align-items: center;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #4cc9f0;
            margin-right: 10px;
        }

        .footer-links a {
            color: #666;
            text-decoration: none;
            margin-left: 20px;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        /* 动画效果 */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 响应式设计 */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                height: 100vh;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- 侧边栏 -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-cube"></i>
                <span>管理系统</span>
            </div>
            
            <div class="nav-menu">
                <div class="nav-item active" data-target="overview">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>概览</span>
                </div>
                <div class="nav-item" data-target="links">
                    <i class="fas fa-link"></i>
                    <span>链接</span>
                </div>
                <div class="nav-item" data-target="connections">
                    <i class="fas fa-users"></i>
                    <span>用户连接</span>
                </div>
            </div>
        </div>

        <!-- 主内容区域 -->
        <div class="main-content">
			<br><br>
                        <div class="card-header">
                            <i class="fas fa-file-alt me-2"></i>网页介绍
                        </div>
                        <div class="card-body">
                            <div>
                                <h5>本网站的所有均为Ai提供!如有需要源码自行下载或利用工具等下载..<br>网站没有登录注册类的需要认证的东西,不往大发展,中转站的.因为到这里的都只能是我认识的<br>比如说现在连接的(RadminLAN)那么你就有IP等出了问题或者入侵了,构成犯罪,那么这个软件会涉及,到时候查你</h5>
                            </div>
                        </div>
            <!-- 顶部标题栏 -->
            <div class="top-header">
                <h1 class="page-title">控制面板</h1>
                <div class="theme-selector">
                    <span class="theme-label">主题颜色:</span>
                    <div class="theme-colors">
                        <div class="theme-color" style="background: #f5f7fa;" data-theme="default"></div>
                        <div class="theme-color" style="background: #2c3e50;" data-theme="dark"></div>
                        <div class="theme-color" style="background: #e3f2fd;" data-theme="blue"></div>
                        <div class="theme-color" style="background: #fff3e0;" data-theme="orange"></div>
                        <div class="theme-color" style="background: #f3e5f5;" data-theme="purple"></div>
                    </div>
                </div>
            </div>

            <!-- 内容区域 -->
            <div class="content-area">
                <!-- 概览部分 -->
                <div id="overview" class="content-section active">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-file-alt me-2"></i>概览信息
                        </div>
                        <div class="card-body">
                            <div class="editable-text" contenteditable="true">
                                <p>登录成功!!</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-microchip"></i>
                            </div>
                            <div class="stat-value">24%</div>
                            <div class="stat-label">网站维护度</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-memory"></i>
                            </div>
                            <div class="stat-value">62%</div>
                            <div class="stat-label">网站持久度</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-hdd"></i>
                            </div>
                            <div class="stat-value">45%</div>
                            <div class="stat-label">网站管理度</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-network-wired"></i>
                            </div>
                            <div class="stat-value">-1.2 Gbps</div>
                            <div class="stat-label">网络 防御</div>
                        </div>

			<div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-network-wired"></i>
                            </div>
                            <div class="stat-value">11 G</div>
                            <div class="stat-label">网站 成分</div>
                        </div>
                    </div>
                </div>
	
                <!-- 链接部分 -->
                <div id="links" class="content-section">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-external-link-alt me-2"></i>常用链接
                        </div>
                        <div class="card-body">
                            <a href="./Friends.php" class="link-item">
                                <div class="link-icon">
                                    <i class="fab fa-github"></i>
                                </div>
                                <div class="link-text">
                                    <div class="link-title">帖子 网页</div>
                                    <div class="link-desc">可以发表言论,这是一个帖子记录所有人发布的信息,一起分享事物.</div>
                                </div>
                            </a>
                            
                            <a href="./MoviesUI.php" class="link-item">
                                <div class="link-icon">
                                    <i class=""></i>
                                </div>
                                <div class="link-text">
                                    <div class="link-title">网站所有视频</div>
                                    <div class="link-desc">这里有你想看的嘛,跟bilibili一样的功能,有用的都有!</div>
                                </div>
                            </a>
                            
                            <a href="./DownLoadFile.php" class="link-item">
                                <div class="link-icon">
                                    <i class=""></i>
                                </div>
                                <div class="link-text">
                                    <div class="link-title">下载 地址</div>
                                    <div class="link-desc">所有文件 下载的网址!</div>
                                </div>
                            </a>
                            
                            <a href="../OtherFile/RL_GFF.psx" download="Radmin_LAN.exe" class="link-item">
                                <div class="link-icon">
                                    <i class=""></i>
                                </div>
                                <div class="link-text">
                                    <div class="link-title">内网穿透 软件</div>
                                    <div class="link-desc">联机需要这个软件!详细教程请看视频了解.</div>
                                </div>
			<a href="../SQLCon.php" class="link-item">
                                <div class="link-icon">
                                    <i class=""></i>
                                </div>
                                <div class="link-text">
                                    <div class="link-title">前往 数据库</div>
                                    <div class="link-desc">查看数据库的信息,或者是当一个数据库中的小成员?</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 用户连接部分 -->
                <div id="connections" class="content-section">
                    <div class="connection-stats">
                        <div class="connection-text">
                            <h3>当前活跃用户连接数</h3>
                            <p>基于PHP Session的实时统计</p>
                        </div>
                        <div class="connection-count">
                            <?php echo $active_connections; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-globe me-2"></i>用户分布
                        </div>
                        <div class="card-body">
                            <div class="connection-chart">
                                <canvas id="connectionsChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-list me-2"></i>活跃会话列表
                        </div>
                        <div class="card-body">
                            <table class="user-table">
                                <thead>
                                    <tr>
                                        <th>IP地址</th>
                                        <th>最后活动</th>
                                        <th>状态</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // 显示活跃会话
                                    foreach ($connections as $ip => $timestamp) {
                                        $last_active = date('Y-m-d H:i:s', $timestamp);
                                        $time_diff = $current_time - $timestamp;
                                        $status = $time_diff < 60 ? '活跃' : '空闲';
                                        
                                        echo "<tr>
                                                <td>$ip</td>
                                                <td>$last_active</td>
                                                <td><span class='status-badge " . ($status == '活跃' ? 'status-active' : 'status-inactive') . "'>$status</span></td>
                                              </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 底部信息栏 -->
            <div class="footer">
                <div class="security-status">
                    <div class="status-indicator"></div>
                    <span>系统安全状态: 良好 | 最后安全检查: 2025-9-21 9:30:45</span>
                </div>
                <div class="footer-links">
                    <a href="../OtherFile/xdzs.psx" download="zcshib.txt"><i class="fas fa-shield-alt me-1"></i>安全策略</a>
                    <a href="../OtherFile/xhsiz.pss" download="yinsi.txt"><i class="fas fa-lock me-1"></i>隐私政策</a>
                    <a href="../OtherFile/zdasf.psxa" download="xitxx.txt"><i class="fas fa-info-circle me-1"></i>关于系统</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // 菜单切换功能
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                // 更新活动菜单项
                document.querySelectorAll('.nav-item').forEach(nav => {
                    nav.classList.remove('active');
                });
                this.classList.add('active');
                
                // 显示对应内容区域
                const targetId = this.getAttribute('data-target');
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(targetId).classList.add('active');
                
                // 更新页面标题
                const pageTitle = document.querySelector('.page-title');
                if (targetId === 'overview') pageTitle.textContent = '控制面板';
                else if (targetId === 'links') pageTitle.textContent = '资源链接';
                else if (targetId === 'connections') pageTitle.textContent = '用户连接';
            });
        });
        
        // 主题切换功能
        document.querySelectorAll('.theme-color').forEach(color => {
            color.addEventListener('click', function() {
                const theme = this.getAttribute('data-theme');
                document.body.className = '';
                if (theme !== 'default') {
                    document.body.classList.add('theme-' + theme);
                }
                
                // 更新活动指示器
                document.querySelectorAll('.theme-color').forEach(c => {
                    c.style.border = 'none';
                });
                this.style.border = '2px solid #fff';
                this.style.boxShadow = '0 0 0 2px #4361ee';
            });
        });
        
        // 初始化连接趋势图表
        const ctx = document.getElementById('connectionsChart').getContext('2d');
        const connectionsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['9:00', '9:30', '10:00', '10:30', '11:00', '11:30'],
                datasets: [{
                    label: '用户连接数',
                    data: [135, 142, 158, 168, 192, <?php echo $active_connections; ?>],
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 100,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
