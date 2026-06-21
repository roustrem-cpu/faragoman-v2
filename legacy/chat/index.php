<div class="chat-disabled-container" style="min-height: 70vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="message-card" style="max-width: 600px; width: 100%; background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; padding: 50px 30px; text-align: center; position: relative; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        
        <div style="position: absolute; top: -50px; left: 50%; transform: translateX(-50%); width: 200px; height: 200px; background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0) 70%); z-index: 0;"></div>

        <div class="icon-wrapper" style="position: relative; z-index: 1; margin-bottom: 30px;">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5));">
                <path d="M17 12C17 14.7614 14.7614 17 12 17C9.23858 17 7 14.7614 7 12C7 9.23858 9.23858 7 12 7C14.7614 7 17 9.23858 17 12Z" stroke="#3b82f6" stroke-width="1.5"/>
                <path d="M12 2V4M12 20V22M4 12H2M22 12H20M19.0711 19.0711L17.6569 17.6569M6.34315 6.34315L4.92893 4.92893M19.0711 4.92893L17.6569 6.34315M6.34315 19.0711L4.92893 17.6569" stroke="#3b82f6" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>

        <h2 style="color: #fff; font-size: 1.8rem; margin-bottom: 20px; font-weight: 800; letter-spacing: -0.5px; position: relative; z-index: 1;">سکوت در تالار گفتگو</h2>
        
        <p style="color: #a1a1aa; font-size: 1.1rem; line-height: 1.8; margin-bottom: 35px; position: relative; z-index: 1;">
            دوستان عزیز، فعلاً تالار گفتگو در این قسمت خاموش شده است. ما در حال بازسازی این بخش  هستیم
        </p>

        <div style="display: flex; flex-direction: column; gap: 15px; position: relative; z-index: 1;">
            <a href="index.php" style="display: block; padding: 14px 30px; background: #3b82f6; color: #fff; text-decoration: none; border-radius: 12px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);">
                بازگشت به صفحه اصلی مقالات
            </a>
            
            <p style="color: #52525b; font-size: 0.9rem;">
                می‌توانید نظرات خود را در ذیل مقاله‌ها با ما در میان بگذارید.
            </p>
        </div>
    </div>
</div>

<style>
    /* اگر قالب سایتت تیره نیست، این استایل‌ها به تیرگی صفحه کمک میکنه */
    body {
        background-color: #09090b !important;
    }
    .message-card:hover {
        transform: translateY(-5px);
        transition: transform 0.3s ease;
        border-color: rgba(59, 130, 246, 0.3) !important;
    }
</style>
