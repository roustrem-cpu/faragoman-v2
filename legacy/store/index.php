<?php
// این فایل store/index.php است.
// این یک فایل PHP است، اگرچه فعلاً محتوای داینامیک زیادی ندارد.
// این به ما کمک می‌کند تا بعداً داده‌ها را از دیتابیس واکشی کنیم.

// مسیر پایه برای فروشگاه
$base_store_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
if ($base_store_path == '' || $base_store_path == '.') { $base_store_path = ''; }

// داده‌های نمونه برای کتاب‌ها (فعلاً از URLهای ثابت استفاده می‌کنیم)
// بعداً این بخش را به دیتابیس متصل می‌کنیم.
$books = [
    [
        'id' => 1,
        'title' => 'هنر ظریف رهایی',
        'author' => 'مارک منسون',
        'description' => 'کتابی درباره چگونگی رهایی از دغدغه‌های روزمره و تمرکز بر آنچه واقعاً مهم است.',
        'pages' => 256,
        'rating' => 4.8,
        'image_url' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1074&q=80'
    ],
    [
        'id' => 2,
        'title' => 'قدرت عادت',
        'author' => 'چارلز داهیگ',
        'description' => 'بررسی علمی عادت‌ها و نحوه شکل‌گیری و تغییر آنها در زندگی روزمره.',
        'pages' => 371,
        'rating' => 4.7,
        'image_url' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=898&q=80'
    ],
    [
        'id' => 3,
        'title' => 'شاهنامه',
        'author' => 'فردوسی',
        'description' => 'یکی از بزرگترین حماسه‌های جهان و مهمترین اثر ادبی زبان فارسی.',
        'pages' => 1200,
        'rating' => 5.0,
        'image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1074&q=80'
    ],
    [
        'id' => 4,
        'title' => 'کیمیاگر',
        'author' => 'پائولو کوئلیو',
        'description' => 'داستان چوپان جوانی که برای یافتن گنجی افسانه‌ای به آفریقا سفر می‌کند.',
        'pages' => 208,
        'rating' => 4.6,
        'image_url' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=928&q=80'
    ],
    [
        'id' => 5,
        'title' => 'انسان خردمند',
        'author' => 'یووال نوح هاراری',
        'description' => 'تاریخچه مختصر بشر از عصر حجر تا عصر فناوری و آینده پیش رو.',
        'pages' => 498,
        'rating' => 4.5,
        'image_url' => 'https://images.unsplash.com/photo-1589998059171-988d887df646?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=876&q=80'
    ],
    [
        'id' => 6,
        'title' => 'صد سال تنهایی',
        'author' => 'گابریل گارسیا مارکز',
        'description' => 'رمانی حماسی که داستان هفت نسل از خانواده بوئندیا را روایت می‌کند.',
        'pages' => 417,
        'rating' => 4.9,
        'image_url' => 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=774&q=80'
    ]
];

$totalItems = count($books);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فراگمان - فروشگاه</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- لینک به فایل CSS اختصاصی فروشگاه -->
    <link rel="stylesheet" href="<?php echo $base_store_path; ?>/css/store.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>فروشگاه فراگمان</h1>
            <p>مکانی برای کشف جهان‌های جدید ادبیات گمانه‌زن، فانتزی و علمی‌تخیلی</p>
            <a href="<?php echo $base_store_path; ?>/admin/" class="admin-panel-link">پنل مدیریت فروشگاه</a>
        </div>
        
        <div class="carousel-3d" id="carousel">
            <?php foreach ($books as $index => $book): ?>
            <div class="carousel-item" data-index="<?php echo $index; ?>">
                <div class="book-cover">
                    <img src="<?php echo htmlspecialchars($book['image_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                </div>
                <div class="book-details">
                    <h2 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h2>
                    <div class="book-author">
                        <i class="fas fa-user-pen"></i>
                        <span><?php echo htmlspecialchars($book['author']); ?></span>
                    </div>
                    <p class="book-description"><?php echo htmlspecialchars($book['description']); ?></p>
                    <div class="book-meta">
                        <div class="meta-item">
                            <span class="meta-value"><?php echo htmlspecialchars($book['pages']); ?></span>
                            <span class="meta-label">صفحه</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-value"><?php echo htmlspecialchars($book['rating']); ?></span>
                            <span class="meta-label">امتیاز</span>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-secondary">
                            <i class="far fa-bookmark"></i>
                            ذخیره
                        </button>
                        <button class="btn btn-primary">
                            جزئیات
                            <i class="fas fa-arrow-left"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="navigation">
            <button class="nav-btn" id="prev">
                <i class="fas fa-arrow-right"></i>
            </button>
            <button class="nav-btn" id="next">
                <i class="fas fa-arrow-left"></i>
            </button>
        </div>
        
        <div class="indicators">
            <?php for ($i = 0; $i < $totalItems; $i++): ?>
            <div class="indicator <?php if($i === 0) echo 'active'; ?>" data-index="<?php echo $i; ?>"></div>
            <?php endfor; ?>
        </div>
        
        <div class="footer">
            <p>© 2023 فروشگاه فراگمان - تمامی حقوق محفوظ است</p>
        </div>
    </div>
<section class="book-profiles">
    <h2 class="section-title">کتاب‌های منتخب</h2>
    <div class="book-profiles-wrapper">
        <?php foreach ($books as $book): ?>
        <div class="book-card">
            <img src="<?php echo htmlspecialchars($book['image_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
            <div class="book-info">
                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                <p><?php echo htmlspecialchars($book['author']); ?></p>
                <div class="card-meta">
                    <span><?php echo htmlspecialchars($book['pages']); ?> صفحه</span>
                    <span>★ <?php echo htmlspecialchars($book['rating']); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="scroll-buttons">
        <button id="scrollLeft"><i class="fas fa-chevron-right"></i></button>
        <button id="scrollRight"><i class="fas fa-chevron-left"></i></button>
    </div>
</section>
    <!-- لینک به فایل JavaScript اختصاصی فروشگاه -->
    <script src="<?php echo $base_store_path; ?>/js/store.js?v=<?php echo time(); ?>"></script>
</body>
</html>