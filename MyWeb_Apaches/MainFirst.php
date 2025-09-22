<?php
// 获取客户端IP地址
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

$current_date = date('Y-m-d H:i:s');
$client_ip = getClientIP();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>主要的大厅</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        
        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
        }
        
        .container {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .login-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .content-wrapper {
            display: flex;
            gap: 20px;
            margin-bottom: 100px;
        }
        
        .left-panel, .right-panel {
            flex: 1;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
            max-height: 60vh;
        }
        
        .center-panel {
            flex: 2;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
            overflow-y: auto;
            max-height: 60vh;
        }
        
        .panel-title {
            font-size: 1.2em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: #4ecca3;
        }
        
        .panel-content {
            line-height: 1.6;
        }
        
        .bottom-menu {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(26, 26, 46, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: center;
            padding: 15px 0;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.4);
            z-index: 100;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .menu-item {
            margin: 0 25px;
            position: relative;
        }
        
        .menu-item a {
            color: #fff;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 50px;
            background: linear-gradient(45deg, #4ecca3, #2c9680);
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            font-weight: bold;
        }
        
        .menu-item a:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
            background: linear-gradient(45deg, #2c9680, #4ecca3);
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .content-wrapper {
                flex-direction: column;
            }
            
            .bottom-menu {
                flex-wrap: wrap;
            }
            
            .menu-item {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    
    <div class="container">
        <div class="login-info">
            登录时间: <?php echo $current_date; ?> | IP地址: <?php echo $client_ip; ?>
        </div>
        
        <div class="content-wrapper">
            <div class="left-panel">
                <h3 class="panel-title">左侧文档</h3>
                <div class="panel-content">
                    <p>这里是左侧文档区域，可以放置一些辅助信息或导航链接。</p>
                    <p>系统状态：正常运行</p>
                    <p>用户权限：普通用户</p>
                    <p>消息通知：无未读消息</p>
                    <p>快捷操作：编辑个人资料</p>
                </div>
            </div>
            
            <div class="center-panel">
                <h3 class="panel-title">中心文档</h3>
                <div class="panel-content">
                    <p>这里是中心文档区域，显示主要内容和信息。</p>
                    <p>欢迎使用我们的系统！这里提供了丰富的功能和工具，帮助您更高效地完成工作。</p>
                    <p>今日提醒：您有3个待处理的任务，请及时查看。</p>
                    <p>系统公告：我们将于下周进行系统维护，请提前保存您的工作。</p>
                    <p>功能更新：新增了数据分析模块，欢迎体验并提供反馈。</p>
                    <p>使用技巧：您可以使用快捷键Ctrl+K快速打开搜索功能。</p>
                </div>
            </div>
            
            <div class="right-panel">
                <h3 class="panel-title">右侧文档</h3>
                <div class="panel-content">
                    <p>这里是右侧文档区域，可以放置一些附加信息或工具。</p>
                    <p>最近访问：控制台</p>
                    <p>收藏夹：数据分析</p>
                    <p>推荐内容：用户指南</p>
                    <p>系统工具：设置</p>
                </div>
            </div>
        </div>
        <div class="content-wrapper">
            <div class="left-panel">
                <h3 class="panel-title">左侧文档</h3>
                <div class="panel-content">
                    <p>这里是左侧文档区域，可以放置一些辅助信息或导航链接。</p>
                    <p>系统状态：正常运行</p>
                    <p>用户权限：普通用户</p>
                    <p>消息通知：无未读消息</p>
                    <p>快捷操作：编辑个人资料</p>
                </div>
            </div>
            
            <div class="center-panel">
                <h3 class="panel-title">中心文档</h3>
                <div class="panel-content">
                    <p>这里是中心文档区域，显示主要内容和信息。</p>
                    <p>欢迎使用我们的系统！这里提供了丰富的功能和工具，帮助您更高效地完成工作。</p>
                    <p>今日提醒：您有3个待处理的任务，请及时查看。</p>
                    <p>系统公告：我们将于下周进行系统维护，请提前保存您的工作。</p>
                    <p>功能更新：新增了数据分析模块，欢迎体验并提供反馈。</p>
                    <p>使用技巧：您可以使用快捷键Ctrl+K快速打开搜索功能。</p>
                </div>
            </div>
            
            <div class="right-panel">
                <h3 class="panel-title">右侧文档</h3>
                <div class="panel-content">
                    <p>这里是右侧文档区域，可以放置一些附加信息或工具。</p>
                    <p>最近访问：控制台</p>
                    <p>收藏夹：数据分析</p>
                    <p>推荐内容：用户指南</p>
                    <p>系统工具：设置</p>
                </div>
            </div>
        </div>
    </div>
    
    <nav class="bottom-menu">
        <div class="menu-item">
            <a href="../Main.php" target="_blank">前往控制台</a>
        </div>
        <div class="menu-item">
            <a href="../LevelCT.php" target="_blank">前往教程网页</a>
        </div>
        <div class="menu-item">
            <a href="../Tutorials.php" target="_blank">了解更多</a>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // 初始化粒子背景
        document.addEventListener('DOMContentLoaded', function() {
            particlesJS('particles-js', {
                particles: {
                    number: { value: 80, density: { enable: true, value_area: 800 } },
                    color: { value: "#ffffff" },
                    shape: { type: "circle" },
                    opacity: { value: 0.5, random: true },
                    size: { value: 3, random: true },
                    line_linked: {
                        enable: true,
                        distance: 150,
                        color: "#4ecca3",
                        opacity: 0.4,
                        width: 1
                    },
                    move: {
                        enable: true,
                        speed: 2,
                        direction: "none",
                        random: true,
                        straight: false,
                        out_mode: "out",
                        bounce: false
                    }
                },
                interactivity: {
                    detect_on: "canvas",
                    events: {
                        onhover: { enable: true, mode: "grab" },
                        onclick: { enable: true, mode: "push" },
                        resize: true
                    },
                    modes: {
                        grab: { distance: 140, line_linked: { opacity: 1 } },
                        push: { particles_nb: 4 }
                    }
                },
                retina_detect: true
            });
        });
    </script>
</body>
</html>