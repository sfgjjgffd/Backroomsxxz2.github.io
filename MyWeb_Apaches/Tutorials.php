<?php
// 获取用户信息
$ip = $_SERVER['REMOTE_ADDR'];
$browser = $_SERVER['HTTP_USER_AGENT'];
$os = get_os();
$time = date('Y-m-d H:i:s');
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '直接访问';

// 函数：获取操作系统信息
function get_os() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os_platform = "未知操作系统";
    
    $os_array = array(
        '/windows nt 10/i'      => 'Windows 10',
        '/windows nt 6.3/i'     => 'Windows 8.1',
        '/windows nt 6.2/i'     => 'Windows 8',
        '/windows nt 6.1/i'     => 'Windows 7',
        '/windows nt 6.0/i'     => 'Windows Vista',
        '/windows nt 5.2/i'     => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     => 'Windows XP',
        '/windows xp/i'         => 'Windows XP',
        '/windows nt 5.0/i'     => 'Windows 2000',
        '/windows me/i'         => 'Windows ME',
        '/win98/i'              => 'Windows 98',
        '/win95/i'              => 'Windows 95',
        '/win16/i'              => 'Windows 3.11',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i'        => 'Mac OS 9',
        '/linux/i'              => 'Linux',
        '/ubuntu/i'             => 'Ubuntu',
        '/iphone/i'             => 'iPhone',
        '/ipod/i'               => 'iPod',
        '/ipad/i'               => 'iPad',
        '/android/i'            => 'Android',
        '/blackberry/i'         => 'BlackBerry',
        '/webos/i'              => 'Mobile'
    );
    
    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
            break;
        }
    }
    return $os_platform;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户信息与公告面板</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            width: 48%;
            animation: slideInLeft 0.8s ease;
        }
        
        .nav-box {
            background: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            width: 48%;
            animation: slideInRight 0.8s ease;
        }
        
        .user-info h2, .nav-box h2 {
            color: #6a11cb;
            margin-bottom: 15px;
            border-bottom: 2px solid #a777e3;
            padding-bottom: 8px;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            width: 120px;
            color: #6a11cb;
        }
        
        .nav-links {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .nav-links a {
            display: block;
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 17, 203, 0.3);
        }
        
        .nav-links a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(106, 17, 203, 0.5);
        }
        
        .announcement-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(31, 38, 135, 0.2);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            animation: fadeIn 1.2s ease;
        }
        
        .announcement-section h2 {
            text-align: center;
            color: #6a11cb;
            margin-bottom: 25px;
            font-size: 28px;
        }
        
        .announcement-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .announcement-card {
            background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .announcement-card h3 {
            color: #6a11cb;
            margin-bottom: 15px;
        }
        
        .announcement-card p {
            color: #555;
            line-height: 1.6;
        }
        
        .announcement-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #6a11cb, #2575fc);
        }
        
        .pulse {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4cd964;
            margin-right: 8px;
            box-shadow: 0 0 0 rgba(76, 217, 100, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(76, 217, 100, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(76, 217, 100, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(76, 217, 100, 0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
            }
            
            .user-info, .nav-box {
                width: 100%;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                <h2>用户信息</h2>
                <div class="info-item">
                    <span class="info-label">IP 地址:</span>
                    <span><?php echo $ip; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">浏览器:</span>
                    <span><?php echo $browser; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">操作系统:</span>
                    <span><?php echo $os; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">访问时间:</span>
                    <span><?php echo $time; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">来源页面:</span>
                    <span><?php echo $referer; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">状态:</span>
                    <span><span class="pulse"></span>在线</span>
                </div>
            </div>
            
            <div class="nav-box">
                <h2>快速导航</h2>
                <div class="nav-links">
                    <a href="./">首页</a>
                    <a href="./Main.php">控制台</a>
                    <a href="./Friends.php">帖子</a>
                    <a href="./MainFirst.php">主界面</a>
                    <a href="MoviesUI.php">视频</a>
                    <a href="./OtherWeb/Sions.php">作者详情</a>
		 <a href="./DownloadFile.php">下载文件</a>
                </div>
            </div>
        </div>
        
        <div class="announcement-section">
            <h2>系统公告</h2>
            <div class="announcement-cards">
                <div class="announcement-card">
                    <h3>通知</h3>
                    <p>没有什么信息</p>
                </div>
		
            </div>
        </div>
    </div>

    <script>
        // 添加卡片悬停动画效果
        document.querySelectorAll('.announcement-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.background = 'linear-gradient(135deg, #e6deff, #d4e4ff)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.background = 'linear-gradient(135deg, #f5f7fa, #e4e8f0)';
            });
        });
        
        // 添加导航链接点击效果
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', (e) => {
                // 添加点击动画效果
                link.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    link.style.transform = '';
                }, 200);
               
            });
        });
    </script>
</body>
</html>