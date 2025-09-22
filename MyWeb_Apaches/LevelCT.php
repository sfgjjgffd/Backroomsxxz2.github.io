<?php
// 模拟数据
$categories = [
    [
        'main_title' => '普通层级',
        'sub_titles' => ['Level0', 'Level1','Level2'],
        'content' => [
            'Level0' => '11111',
            'Level1' => '2222222',
            'Level2' => '3333'
        ]
        ],
 [
        'main_title' => '子层级',
        'sub_titles' => ['Level0.1'],
        'content' => [
            'Level0.1' => '111'
        ]
        ],
 [
        'main_title' => '隐秘层级',
        'sub_titles' => ['NULL'],
        'content' => [
           'NULL' => 'Data IS Null!!!'
        ]
        ],
 [
        'main_title' => '设置选项',
        'sub_titles' => ['EXIT'],
        'content' => [
           'EXIT' => '<a href="./">--返回大厅链接--</a>'
        ]
        ]

];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类切换页面</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            color: #333;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
            z-index: 2;
        }
        
        .category-list {
            flex: 1;
            min-width: 300px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .content-area {
            flex: 2;
            min-width: 400px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .category-item {
            margin-bottom: 15px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .category-item:hover {
            transform: translateY(-5px);
        }
        
        .main-title {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sub-titles {
            background: #f8f9fa;
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .category-item.active .sub-titles {
            max-height: 200px;
        }
        
        .sub-title {
            padding: 12px 20px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .sub-title:last-child {
            border-bottom: none;
        }
        
        .sub-title:hover {
            background: #e9ecef;
        }
        
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        .content-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .content-text {
            font-size: 16px;
            line-height: 1.6;
            color: #34495e;
        }
        
        /* 星星动画 */
        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .star {
            position: absolute;
            background-color: white;
            border-radius: 50%;
            animation: fall linear infinite;
        }
        
        @keyframes fall {
            to {
                transform: translateY(100vh);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        h1 {
            text-align: center;
            color: white;
            margin: 20px 0 30px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            font-size: 2.5rem;
            position: relative;
            z-index: 2;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <h1>技术知识分类</h1>
    
    <div class="container">
        <div class="category-list">
            <?php foreach ($categories as $index => $category): ?>
            <div class="category-item" data-index="<?php echo $index; ?>">
                <div class="main-title">
                    <span><?php echo $category['main_title']; ?></span>
                    <span class="arrow">▼</span>
                </div>
                <div class="sub-titles">
                    <?php foreach ($category['sub_titles'] as $subIndex => $subTitle): ?>
                    <div class="sub-title" data-main-index="<?php echo $index; ?>" data-sub-index="<?php echo $subIndex; ?>">
                        <?php echo $subTitle; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="content-area">
            <?php foreach ($categories as $index => $category): ?>
                <?php foreach ($category['sub_titles'] as $subIndex => $subTitle): ?>
                <div class="content-section" id="content-<?php echo $index . '-' . $subIndex; ?>">
                    <h2 class="content-title"><?php echo $subTitle; ?></h2>
                    <p class="content-text"><?php echo $category['content'][$subTitle]; ?></p>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="stars"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化显示第一个分类的第一个子项
            const firstMainItem = document.querySelector('.category-item');
            const firstSubItem = document.querySelector('.sub-title');
            const firstContent = document.querySelector('.content-section');
            
            if (firstMainItem && firstSubItem && firstContent) {
                firstMainItem.classList.add('active');
                firstContent.classList.add('active');
            }
            
            // 分类标题点击事件
            document.querySelectorAll('.main-title').forEach(title => {
                title.addEventListener('click', function() {
                    const parent = this.parentElement;
                    const isActive = parent.classList.contains('active');
                    
                    // 关闭所有分类
                    document.querySelectorAll('.category-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    
                    // 如果点击的不是当前激活的，则打开它
                    if (!isActive) {
                        parent.classList.add('active');
                    }
                });
            });
            
            // 子标题点击事件
            document.querySelectorAll('.sub-title').forEach(subTitle => {
                subTitle.addEventListener('click', function() {
                    const mainIndex = this.getAttribute('data-main-index');
                    const subIndex = this.getAttribute('data-sub-index');
                    
                    // 隐藏所有内容
                    document.querySelectorAll('.content-section').forEach(section => {
                        section.classList.remove('active');
                    });
                    
                    // 显示选中内容
                    const contentToShow = document.getElementById(`content-${mainIndex}-${subIndex}`);
                    if (contentToShow) {
                        contentToShow.classList.add('active');
                    }
                });
            });
            
            // 创建星星动画
            createStars();
            
            function createStars() {
                const starsContainer = document.querySelector('.stars');
                const starCount = 100;
                
                for (let i = 0; i < starCount; i++) {
                    const star = document.createElement('div');
                    star.classList.add('star');
                    
                    // 随机大小
                    const size = Math.random() * 3 + 1;
                    star.style.width = `${size}px`;
                    star.style.height = `${size}px`;
                    
                    // 随机位置
                    star.style.left = `${Math.random() * 100}%`;
                    star.style.top = `${Math.random() * 100}%`;
                    
                    // 随机透明度
                    star.style.opacity = Math.random() * 0.7 + 0.3;
                    
                    // 随机动画持续时间
                    const duration = Math.random() * 5 + 5;
                    star.style.animationDuration = `${duration}s`;
                    
                    // 随机动画延迟
                    star.style.animationDelay = `${Math.random() * 5}s`;
                    
                    starsContainer.appendChild(star);
                }
            }
        });
    </script>
</body>
</html>