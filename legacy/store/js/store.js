document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('carousel');
    const items = document.querySelectorAll('.carousel-item');
    const prevBtn = document.getElementById('prev');
    const nextBtn = document.getElementById('next');
    const indicators = document.querySelectorAll('.indicator');

    let currentIndex = 0;
    const totalItems = items.length;
    let isAnimating = false;

    // === تابع اصلی چرخش کاروسل ===
    function rotateCarousel() {
        if (isAnimating) return;
        isAnimating = true;

        items.forEach((item, index) => {
            const angle = (index - currentIndex) * (360 / totalItems);
            const zValue = window.innerWidth < 768 ? 250 : 400;

            item.style.transform = `rotateY(${angle}deg) translateZ(${zValue}px)`;
            item.style.transition = 'transform 0.8s ease';
        });

        // بروزرسانی ایندیکیتورها
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === currentIndex);
        });

        setTimeout(() => { isAnimating = false; }, 800);
    }

    // === کنترل دکمه‌های جهت ===
    nextBtn.addEventListener('click', () => {
        if (isAnimating) return;
        currentIndex = (currentIndex + 1) % totalItems;
        rotateCarousel();
    });

    prevBtn.addEventListener('click', () => {
        if (isAnimating) return;
        currentIndex = (currentIndex - 1 + totalItems) % totalItems;
        rotateCarousel();
    });

    // === کنترل با کلیک روی ایندیکیتور ===
    indicators.forEach(indicator => {
        indicator.addEventListener('click', () => {
            const index = parseInt(indicator.getAttribute('data-index'));
            if (index !== currentIndex) {
                currentIndex = index;
                rotateCarousel();
            }
        });
    });

    // === چرخش خودکار ===
    setInterval(() => {
        currentIndex = (currentIndex + 1) % totalItems;
        rotateCarousel();
    }, 7000);

    // === اجرای اولیه ===
    rotateCarousel();

    // ================================
    // 🔹 کنترل اسکرول افقی کارت‌های کتاب‌ها
    // ================================
    const bookProfiles = document.getElementById('bookProfiles');
    const scrollLeftBtn = document.getElementById('scrollLeft');
    const scrollRightBtn = document.getElementById('scrollRight');

    if (bookProfiles && scrollLeftBtn && scrollRightBtn) {
        scrollLeftBtn.addEventListener('click', () => {
            bookProfiles.scrollBy({ left: -300, behavior: 'smooth' });
        });
        scrollRightBtn.addEventListener('click', () => {
            bookProfiles.scrollBy({ left: 300, behavior: 'smooth' });
        });
    }
});