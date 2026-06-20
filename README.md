# فراگمان نسخه ۲ (Faragoman v2)

بازنویسی کامل و مدرنِ پروژه‌ی فراگمان با معماری الهام‌گرفته از Laravel، رابط کاربری پرمیوم با عمق شبه‌سه‌بعدی، و **سازگاری کامل با دیتابیس و هاست اشتراکی فعلی**.

> این نسخه از صفر بازنویسی شده است؛ اما با همان دیتابیس، همان کاربران و همان رمزهای عبور قبلی کار می‌کند. **هیچ تغییری در دیتابیس لازم نیست.**

---

## ✨ ویژگی‌ها

- **معماری تمیز و لایه‌ای:** Front Controller → Router → Middleware → Controller → Service → Repository → Database.
- **تزریق وابستگی (DI Container)** سبک و سریع، مناسب هاست اشتراکی.
- **سیستم نقش و دسترسی سلسله‌مراتبی (RBAC)** کاملاً دینامیک و قابل‌گسترش بدون تغییر کد.
- **رابط کاربری پرمیوم:** گلسمورفیسم، سایه‌های نرم و واقعی، گرادیان‌های ظریف، میکرو-اینتراکشن، و طراحی واکنش‌گرا (Mobile-first).
- **Tailwind کامپایل‌شده و محلی:** بدون CDN، بدون وابستگی runtime، بدون درخواست شبکه‌ی خارجی.
- **کش فایل‌محور:** بدون نیاز به Redis یا هیچ سرویس جانبی.
- **بدون نیاز به Composer روی هاست:** Autoloader داخلی PSR-4.
- **سازگاری کامل به عقب:** هش رمز عبور `bcrypt`، کلید سشن `user_id`، و تمام جداول فعلی دست‌نخورده.

---

## 🚀 نصب — فقط یک قدم

> **تنها کاری که باید انجام دهید: ویرایش اطلاعات دیتابیس در `config/database.php`.**

### روش پیشنهادی (document root روی `public`)

1. کل پروژه را در هاست آپلود کنید.
2. document root دامنه را روی پوشه‌ی `public/` تنظیم کنید.
3. فایل `config/database.php` را باز کرده و اطلاعات دیتابیس فعلی خود را وارد کنید:

   ```php
   return [
       'host' => 'localhost',
       'name' => 'نام_دیتابیس_شما',
       'user' => 'یوزر_دیتابیس_شما',
       'pass' => 'رمز_دیتابیس_شما',
       'port' => 3306,
       'charset' => 'utf8mb4',
   ];
   ```

4. تمام! سایت بلافاصله بالا می‌آید. ✅

### روش جایگزین (نمی‌توانید document root را تغییر دهید)

اگر هاست شما اجازه‌ی تغییر document root را نمی‌دهد، کل پروژه را داخل `public_html` آپلود کنید. فایل `.htaccess` موجود در ریشه، به‌صورت خودکار همه‌ی درخواست‌ها را به `public/` هدایت می‌کند و دسترسی مستقیم به کد اپلیکیشن را مسدود می‌کند.

---

## 🗄️ دیتابیس و سازگاری به عقب

- اپلیکیشن به **همان دیتابیس فعلی** متصل می‌شود. هیچ مهاجرت مخربی انجام نمی‌شود.
- ورود کاربران فعلی بدون تغییر کار می‌کند (تأیید با `password_verify` روی هش‌های `bcrypt` موجود).
- سشن‌های فعال با کلید `user_id` همچنان معتبر می‌مانند.

### فعال‌سازی RBAC پیشرفته (اختیاری)

سیستم دسترسی بدون این مرحله هم با یک ماتریس پیش‌فرض کار می‌کند. برای فعال‌کردن مدیریت کامل و دینامیک نقش‌ها:

1. وارد phpMyAdmin شوید و دیتابیس فعلی را انتخاب کنید.
2. فایل `database/schema.sql` را از تب **Import** اجرا کنید.

این اسکریپت **فقط جداول جدید می‌سازد** (`roles`, `permissions`, `role_permissions`, `user_permissions`) و هیچ جدول موجودی را تغییر نمی‌دهد.

| نقش | شرح |
|---|---|
| `super_admin` | دسترسی نامحدود؛ مدیریت همه‌ی نقش‌ها و دسترسی‌ها (نگاشت از `admin` قدیمی). |
| `section_admin` | دسترسی به یک یا چند بخش مشخص از پنل، با مجوزهای دلخواهِ تعیین‌شده توسط مدیر کل. |
| `editor` | مدیریت و ویرایش محتوا طبق دسترسی‌ها. |
| `author` | ایجاد و مدیریت محتوای خودش. |
| `user` | کاربر عادی بدون دسترسی مدیریتی. |

---

## 🎨 ساخت فرانت‌اند (فقط هنگام توسعه)

CSS کامپایل‌شده از پیش در `public/assets/css/app.min.css` موجود است؛ **برای اجرا روی هاست نیازی به build نیست.** فقط هنگام تغییر استایل‌ها:

```bash
npm install        # یک‌بار
npm run build      # تولید CSS مینیفای‌شده‌ی production
npm run dev        # حالت watch هنگام توسعه
```

---

## 🧱 ساختار پروژه

```
faragoman-v2/
├── public/              # تنها پوشه‌ی قابل‌دسترس از وب (entrypoint: index.php)
│   ├── index.php        # Front Controller
│   ├── .htaccess        # rewrite + هدرهای امنیتی + کش
│   └── assets/          # CSS/JS/فونت/تصاویرِ کامپایل‌شده و محلی
├── app/
│   ├── Core/            # Application, Container, Router, Request, Response, View, Database
│   ├── Controllers/     # کنترلرهای لایه‌ی HTTP
│   ├── Services/        # منطق کسب‌وکار
│   ├── Repositories/    # دسترسی به دیتابیس (الگوی Repository)
│   ├── Models/          # مدل‌های داده
│   ├── Middleware/      # Auth, RBAC, CSRF
│   └── Support/         # Autoloader, Validator, Cache, Rbac, helpers
├── config/
│   ├── database.php     # ⭐ تنها فایلی که باید ویرایش شود
│   └── app.php
├── routes/web.php       # تعریف مسیرها (جدا از کنترلرها)
├── resources/
│   ├── css/app.css      # سورس Tailwind
│   └── views/           # قالب‌ها (PHP خام، بدون وابستگی)
├── storage/cache/       # کش فایل‌محور
├── database/schema.sql  # جداول RBAC (افزایشی و غیرمخرب)
└── README.md
```

---

## 🔌 ماژول‌های موجود

- **فروشگاه (Store)** و **گفتگو (Chat):** این دو ماژول طبق درخواست **دست‌نخورده** منتقل می‌شوند و رفتارشان تغییر نمی‌کند.
- **استوری (Stories):** در فازهای بعدی به‌صورت کامل پیاده‌سازی و در معماری جدید یکپارچه می‌شود.

---

## 🛡️ امنیت

- محافظت CSRF روی تمام درخواست‌های تغییر-حالت.
- Prepared statements در تمام کوئری‌ها (جلوگیری از SQL Injection).
- خروجی escape‌شده در قالب‌ها (جلوگیری از XSS).
- هدرهای امنیتی و مسدودسازی دسترسی مستقیم به کد اپلیکیشن.

> ⚠️ **هشدار مهم:** در ریپوی قبلی فایل `chat-server/serviceAccountKey.json` (کلید سرویس‌اکانت فایربیس) کامیت شده بود. حتماً آن کلید را در Firebase Console **باطل و بازصادر** کنید. در نسخه‌ی جدید هیچ کلید حساسی در ریپو قرار نمی‌گیرد.

---

## 🧪 کیفیت کد

- اصول **SOLID، DRY، KISS** و **Separation of Concerns**.
- تایپ‌های strict (`declare(strict_types=1)`) در سراسر کد.
- تحلیل ایستا با **PHPStan** (`composer analyse`).

---

## 📌 وضعیت (نقشه‌ی راه)

این انتشار، **فاز ۱ (پایه‌ی معماری)** است: هسته‌ی فریم‌ورک، احراز هویت، RBAC، فید خانه، و خط تولید فرانت‌اند. ماژول‌های بعدی (مقاله، پروفایل، ویکی، پنل ادمین کامل، و پیاده‌سازی استوری) به‌صورت تدریجی اضافه می‌شوند.

---

© فراگمان — تمامی حقوق محفوظ است.


---

## Stories

The historically-disabled **Stories** feature is re-enabled on the new architecture.

- **Database**: `database/schema.sql` creates a `stories` table with `IF NOT EXISTS`
  (columns `id, title, image_url, link_url, display_order, is_active, created_at`).
  Existing databases that already have a `stories` table are **left untouched**.
- **Front-end**: a premium pseudo-3D ring bar renders on the home page (server-side,
  works without JS) and an immersive full-screen viewer (vanilla JS in
  `public/assets/js/stories.js`, zero external dependencies) plays through them with
  progress bars, keyboard and tap navigation, and respects `prefers-reduced-motion`.
- **API**:
  - `GET  /stories`             — public JSON list consumed by the viewer.
  - `POST /stories`             — create (requires `stories.manage`, CSRF + auth).
  - `POST /stories/{id}/delete` — delete (requires `stories.manage`, CSRF + auth).
- **Resilience**: if the `stories` table is missing, the home page still renders —
  the service returns an empty list instead of throwing.

## Syndication (RSS & CLI feeds)

Latest published content is exposed to readers and machines with **no external services**:

| URL          | Format                         | Audience                         |
|--------------|--------------------------------|----------------------------------|
| `/feed`      | RSS 2.0 (UA-negotiated)        | browsers & feed readers          |
| `/feed/rss`  | RSS 2.0 (forced)               | RSS clients                      |
| `/feed.json` | JSON Feed 1.1                  | modern feed clients              |
| `/feed.txt`  | plain text                     | CLI / terminal (curl, wget, ...) |

`/feed` performs **content negotiation**: a request from a terminal client
(`curl`, `wget`, `httpie`, `lynx`, `w3m`, ...) automatically receives the lightweight
plain-text feed, while browsers and feed readers receive RSS - one canonical URL for
both humans-in-a-shell and machines.

```bash
curl -s https://your-domain.tld/feed        # plain text (auto-negotiated)
curl -s https://your-domain.tld/feed.json   # JSON Feed
```

## Backward compatibility: Store & Chat

The **Store** and **Chat** modules are operational and must remain untouched. They
expect a global mysqli handle named `$conn` (from the legacy `db_config.php`).
`App\Support\LegacyBridge` exposes the **same** connection managed by the new
`Database` layer as the global `$conn`, so the original module code runs verbatim with
a single source of credentials (`config/database.php`).

To serve them, drop the untouched modules into:

```
<project root>/legacy/store/index.php
<project root>/legacy/chat/index.php
```

Requests to `/store` and `/chat` are then served by the original code before the new
router runs. Until those files are present, the mount point is a safe no-op.
