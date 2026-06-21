document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card'); // این ممکن است نیازی نباشد
    const scrollContainer = document.getElementById('scrollContainer');
    const scrollLeftBtn = document.getElementById('scrollLeft');
    const scrollRightBtn = document.getElementById('scrollRight');
    
    // اسکرول افقی با دکمه‌ها
    scrollLeftBtn.addEventListener('click', function() {
        scrollContainer.scrollBy({
            left: -170, // مقدار اسکرول بر اساس عرض کارت‌ها
            behavior: 'smooth'
        });
    });
    
    scrollRightBtn.addEventListener('click', function() {
        scrollContainer.scrollBy({
            left: 170, // مقدار اسکرول بر اساس عرض کارت‌ها
            behavior: 'smooth'
        });
    });
    
    // فعال کردن اسکرول با کشیدن موس (درگ)
    let isDown = false;
    let startX;
    let scrollLeft;
    
    scrollContainer.addEventListener('mousedown', (e) => {
        isDown = true;
        scrollContainer.classList.add('active-dragging'); // یک کلاس برای نشان دادن حالت درگ (اختیاری)
        startX = e.pageX - scrollContainer.offsetLeft;
        scrollLeft = scrollContainer.scrollLeft;
    });
    
    scrollContainer.addEventListener('mouseleave', () => {
        isDown = false;
        scrollContainer.classList.remove('active-dragging');
    });
    
    scrollContainer.addEventListener('mouseup', () => {
        isDown = false;
        scrollContainer.classList.remove('active-dragging');
    });
    
    scrollContainer.addEventListener('mousemove', (e) => {
        if(!isDown) return;
        e.preventDefault();
        const x = e.pageX - scrollContainer.offsetLeft;
        const walk = (x - startX) * 2; // حساسیت درگ
        scrollContainer.scrollLeft = scrollLeft - walk;
    });
    
    // افزودن عملکرد به دکمه "جزئیات"
    const buttons = document.querySelectorAll('.action-btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // جلوگیری از انتشار رویداد به عناصر والد
            const card = this.closest('.card');
            const bookTitle = card.querySelector('.book-title').textContent;
            const authorName = card.querySelector('.author-name').textContent;
            alert(`جزئیات بیشتری درباره کتاب "${bookTitle}" اثر ${authorName} نمایش داده خواهد شد.`);
            // در آینده می‌توانید به صفحه جزئیات کتاب هدایت کنید:
            // window.location.href = `index.php?page=book_details&id=${card.dataset.bookId}`;
        });
    });
});