<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>悬浮互动界面</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
            padding: 20px;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            height: 80vh;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 20px;
            overflow: hidden;
        }
        
        /* 中央头像 */
        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(45deg, #ff8a00, #e52e71);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border: 4px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            transition: transform 0.5s, box-shadow 0.5s;
            cursor: pointer;
        }
        
        .avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }
        
        .avatar img {
            width: 90%;
            height: 90%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* 悬浮容器 */
        .floating-box {
            position: absolute;
            width: 220px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            opacity: 0.9;
            z-index: 5;
            overflow: hidden;
            cursor: pointer;
        }
        
        .floating-box:hover {
            transform: scale(1.05);
            opacity: 1;
            background: rgba(255, 255, 255, 0.25);
            z-index: 11;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
        }
        
        .floating-box h3 {
            color: white;
            margin-bottom: 12px;
            font-weight: 600;
            text-align: center;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            font-size: 16px;
        }
        
        .floating-box p {
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 12px;
        }
        
        .floating-box img {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* 返回按钮 */
        .back-btn {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 30px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-50%) translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }
        
        /* 互动小人 */
        .character {
            position: fixed;
            width: 70px;
            height: 70px;
            background: #ff6b6b;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 100;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s, background 0.3s;
            top: 30px;
            right: 30px;
        }
        
        .character:hover {
            transform: scale(1.1);
        }
        
        .character i {
            font-size: 30px;
            color: white;
        }
        
        /* 粒子背景 */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            pointer-events: none;
        }
        
        /* 响应式设计 */
        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                height: auto;
                padding: 100px 0;
            }
            
            .floating-box {
                position: relative;
                margin: 15px 0;
                width: 80%;
                max-width: 250px;
            }
            
            .avatar {
                margin-bottom: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="avatar">
            <img src="../pictures/stzhongypn.ico" alt="致命错误!!">
        </div>
        
        <!-- 四周悬浮容器 -->
        <div class="floating-box" id="box1" data-href="">
            <h3>这个位置还没人</h3>
            <p>没有合适人选...</p>
            <img src="../pictures/stpn1.ico" alt="致命错误!!">
        </div>
        
        <div class="floating-box" id="box2" data-href="">
            <h3>这个位置还没人</h3>
            <p>没有合适人选...</p>
            <img src="../pictures/stpn2.ico" alt="致命错误!!">
        </div>
        
        <div class="floating-box" id="box3" data-href="">
            <h3>这个位置还没人</h3>
            <p>没有合适人选...</p>
            <img src="../pictures/stpn3.ico" alt="致命错误!!">
        </div>
        
        <div class="floating-box" id="box4" data-href="">
            <h3>这个位置还没人</h3>
            <p>没有合适人选...</p>
            <img src="../pictures/stpn4.ico" alt="致命错误!!">
        </div>
        
        <!-- 互动小人 -->
        <div class="character" id="character">
            <i class="fas fa-user"></i>
        </div>
        
        <!-- 返回按钮 -->
        <button class="back-btn">
            <i class="fas fa-arrow-left"></i> 返回首页
        </button>
    </div>

    <div class="particles" id="particles"></div>

    <script>
        // 创建随机粒子背景
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                const size = Math.random() * 10 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                particlesContainer.appendChild(particle);
                
                // 为每个粒子设置动画
                animateParticle(particle);
            }
        }
        
        function animateParticle(particle) {
            const duration = Math.random() * 10 + 10;
            
            particle.animate([
                { transform: 'translateY(0px)', opacity: 0.5 },
                { transform: `translateY(${Math.random() * 50 - 25}px) translateX(${Math.random() * 40 - 20}px)`, opacity: 0.8 }
            ], {
                duration: duration * 1000,
                iterations: Infinity,
                direction: 'alternate',
                easing: 'ease-in-out'
            });
        }
        
        // 优化漂浮效果 - 随机移动且不超出容器
        function optimizeFloating() {
            const boxes = document.querySelectorAll('.floating-box');
            const container = document.querySelector('.container');
            const containerRect = container.getBoundingClientRect();
            
            // 为每个盒子设置随机初始位置
            boxes.forEach(box => {
                const boxWidth = box.offsetWidth;
                const boxHeight = box.offsetHeight;
                
                // 初始随机位置（确保在容器内）
                const maxX = containerRect.width - boxWidth;
                const maxY = containerRect.height - boxHeight;
                
                let randomX = Math.random() * maxX;
                let randomY = Math.random() * maxY;
                
                // 确保不覆盖中央头像
                const avatar = document.querySelector('.avatar');
                const avatarRect = avatar.getBoundingClientRect();
                const avatarCenterX = avatarRect.left + avatarRect.width/2 - containerRect.left;
                const avatarCenterY = avatarRect.top + avatarRect.height/2 - containerRect.top;
                
                const minDistance = 150; // 最小距离
                
                // 检查是否太接近中心
                let distance = Math.sqrt(
                    Math.pow(randomX + boxWidth/2 - avatarCenterX, 2) + 
                    Math.pow(randomY + boxHeight/2 - avatarCenterY, 2)
                );
                
                if (distance < minDistance) {
                    // 如果太接近，调整位置
                    const angle = Math.atan2(
                        randomY + boxHeight/2 - avatarCenterY,
                        randomX + boxWidth/2 - avatarCenterX
                    );
                    
                    randomX = avatarCenterX + Math.cos(angle) * minDistance - boxWidth/2;
                    randomY = avatarCenterY + Math.sin(angle) * minDistance - boxHeight/2;
                    
                    // 确保调整后的位置仍在容器内
                    randomX = Math.max(0, Math.min(maxX, randomX));
                    randomY = Math.max(0, Math.min(maxY, randomY));
                }
                
                box.style.left = `${randomX}px`;
                box.style.top = `${randomY}px`;
                
                // 设置随机移动
                setRandomMovement(box, container);
            });
        }
        
        // 设置元素的随机移动
        function setRandomMovement(element, container) {
            const containerRect = container.getBoundingClientRect();
            let x = parseFloat(element.style.left) || 0;
            let y = parseFloat(element.style.top) || 0;
            
            let xSpeed = (Math.random() - 0.5) * 0.8;
            let ySpeed = (Math.random() - 0.5) * 0.8;
            
            function move() {
                const elementWidth = element.offsetWidth;
                const elementHeight = element.offsetHeight;
                
                x += xSpeed;
                y += ySpeed;
                
                // 边界检测和反弹
                if (x <= 0) {
                    x = 0;
                    xSpeed = Math.abs(xSpeed) * 0.95;
                } else if (x + elementWidth >= containerRect.width) {
                    x = containerRect.width - elementWidth;
                    xSpeed = -Math.abs(xSpeed) * 0.95;
                }
                
                if (y <= 0) {
                    y = 0;
                    ySpeed = Math.abs(ySpeed) * 0.95;
                } else if (y + elementHeight >= containerRect.height) {
                    y = containerRect.height - elementHeight;
                    ySpeed = -Math.abs(ySpeed) * 0.95;
                }
                
                // 偶尔随机改变方向
                if (Math.random() < 0.01) {
                    xSpeed = (Math.random() - 0.5) * 0.8;
                    ySpeed = (Math.random() - 0.5) * 0.8;
                }
                
                element.style.left = `${x}px`;
                element.style.top = `${y}px`;
                
                requestAnimationFrame(move);
            }
            
            move();
        }
        
        // 互动小人功能 - 鼠标移过时逃跑
        const character = document.getElementById('character');
        let characterX = window.innerWidth - 100;
        let characterY = 30;
        let isRunning = false;
        
        // 初始化小人位置
        character.style.left = `${characterX}px`;
        character.style.top = `${characterY}px`;
        
        // 鼠标移动事件监听
        document.addEventListener('mousemove', function(e) {
            const mouseX = e.clientX;
            const mouseY = e.clientY;
            
            // 计算鼠标和小人之间的距离
            const distance = Math.sqrt(
                Math.pow(mouseX - (characterX + 35), 2) + 
                Math.pow(mouseY - (characterY + 35), 2)
            );
            
            // 如果鼠标靠近小人且小人没有在逃跑
            if (distance < 150 && !isRunning) {
                isRunning = true;
                
                // 计算逃跑方向（远离鼠标）
                const angle = Math.atan2(
                    characterY + 35 - mouseY,
                    characterX + 35 - mouseX
                );
                
                // 逃跑速度
                const runSpeed = 8;
                
                // 计算新位置
                let newX = characterX + Math.cos(angle) * runSpeed * 10;
                let newY = characterY + Math.sin(angle) * runSpeed * 10;
                
                // 确保不超出窗口边界
                newX = Math.max(10, Math.min(window.innerWidth - 80, newX));
                newY = Math.max(10, Math.min(window.innerHeight - 80, newY));
                
                // 应用新位置
                character.style.transition = 'left 0.5s, top 0.5s';
                character.style.left = `${newX}px`;
                character.style.top = `${newY}px`;
                
                // 更新位置变量
                characterX = newX;
                characterY = newY;
                
                // 一段时间后停止逃跑
                setTimeout(() => {
                    isRunning = false;
                }, 1000);
            }
        });
        
        // 点击小人改变颜色
        character.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // 随机改变小人颜色
            const colors = ['#ff6b6b', '#48dbfb', '#1dd1a1', '#feca57', '#ff9ff3', '#54a0ff'];
            const randomColor = colors[Math.floor(Math.random() * colors.length)];
            character.style.backgroundColor = randomColor;
            
            // 添加动画效果
            character.style.transform = 'scale(1.2)';
            setTimeout(() => {
                character.style.transform = 'scale(1)';
            }, 300);
        });
        
        // 悬浮容器点击跳转
        document.querySelectorAll('.floating-box').forEach(box => {
            box.addEventListener('click', function() {
                const url = this.getAttribute('data-href');
                window.open(url, '_blank');
            });
        });
        
        // 返回按钮功能
        const backBtn = document.querySelector('.back-btn');
        backBtn.addEventListener('click', function() {
            // 添加点击动画
            backBtn.style.transform = 'translateX(-50%) scale(0.95)';
            backBtn.style.opacity = '0.8';
            
            setTimeout(() => {
                backBtn.style.transform = 'translateX(-50%) scale(1)';
                backBtn.style.opacity = '1';
	      setTimeout(() => location.href='https://mywebsfirst.com/Main.php',50);
            }, 200);
        });
        
        // 中央头像点击效果
        const avatar = document.querySelector('.avatar');
        avatar.addEventListener('click', function() {
            this.style.transform = 'scale(1.1) rotate(5deg)';
            setTimeout(() => {
                this.style.transform = 'scale(1) rotate(0deg)';
            }, 500);
        });
        
        // 初始化
        window.addEventListener('load', function() {
            createParticles();
            optimizeFloating();
        });
        
        // 窗口大小改变时重新计算
        window.addEventListener('resize', optimizeFloating);
    </script>
</body>
</html>