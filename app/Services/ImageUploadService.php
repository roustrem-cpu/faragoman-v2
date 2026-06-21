<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * ImageUploadService (Task J) — secure, dependency-free image uploads.
 *
 * Handles the admin image fields for Articles and Stories. It needs no GD,
 * Imagick or any third-party library, so it runs verbatim on the low-resource
 * shared-hosting target (Apache/Nginx + PHP-FPM, no Docker/queues).
 *
 * Pipeline:
 *  1. Inspect the PHP upload error code (a friendly message per failure mode).
 *  2. Enforce a maximum byte size.
 *  3. Detect the *real* MIME type from the file bytes (finfo, with a
 *     getimagesize fallback) — never trusts the client Content-Type — and map
 *     it to an allowed canonical extension (JPEG / PNG / WEBP).
 *  4. Generate a safe, collision-free filename (32 random hex chars + epoch).
 *  5. Create the destination directory safely (recursive, 0755).
 *  6. move_uploaded_file() into public/uploads/{subdir}/ and return the
 *     web-relative path (e.g. "uploads/articles/ab12….jpg") to be stored in the
 *     existing `image_url` column — 100% backward compatible (that column
 *     already holds URLs/paths; nothing is dropped or renamed).
 */
final class ImageUploadService
{
    /** Allowed real MIME type => canonical filename extension. */
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /** Default 5 MiB cap. */
    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @param string $uploadRoot   absolute path to public/uploads
     * @param string $publicPrefix web path prefix persisted in the DB
     * @param int    $maxBytes     maximum accepted upload size in bytes
     */
    public function __construct(
        private string $uploadRoot,
        private string $publicPrefix = 'uploads',
        private int $maxBytes = self::MAX_BYTES,
    ) {
    }

    /**
     * True when the request actually carried a file in this field. An empty
     * field (UPLOAD_ERR_NO_FILE) is NOT an upload — callers treat that as
     * "keep the existing value".
     *
     * @param array<string, mixed>|null $file a single $_FILES entry
     */
    public function hasUpload(?array $file): bool
    {
        return is_array($file)
            && isset($file['error'])
            && (int) $file['error'] !== UPLOAD_ERR_NO_FILE
            && (string) ($file['tmp_name'] ?? '') !== '';
    }

    /**
     * Validate and store an uploaded image under public/uploads/{$subDir}.
     *
     * @param array<string, mixed> $file   a single $_FILES entry
     * @param string               $subDir destination bucket, e.g. 'articles'
     *
     * @return string web-relative path to persist (e.g. uploads/articles/xxx.jpg)
     *
     * @throws RuntimeException on any validation/move failure
     */
    public function store(array $file, string $subDir): string
    {
        $this->assertUploadOk($file);

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $this->maxBytes) {
            throw new RuntimeException('حجم تصویر باید بیشتر از صفر و حداکثر ' . $this->maxMegabytes() . ' مگابایت باشد.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('فایل بارگذاری‌شده معتبر نیست.');
        }

        $mime = $this->detectMime($tmp);
        if (!isset(self::ALLOWED[$mime])) {
            throw new RuntimeException('فقط تصاویر با قالب JPEG، PNG یا WEBP مجاز هستند.');
        }
        $ext = self::ALLOWED[$mime];

        $bucket    = $this->sanitizeSegment($subDir);
        $targetDir = $this->ensureDir($bucket);
        $name      = $this->uniqueName($ext);
        $dest      = $targetDir . '/' . $name;

        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('ذخیره‌سازی تصویر روی سرور ناموفق بود.');
        }

        @chmod($dest, 0644);

        return $this->publicPrefix . '/' . $bucket . '/' . $name;
    }

    // ------------------------------------------------------------------ //

    /** @param array<string, mixed> $file */
    private function assertUploadOk(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        match ($error) {
            UPLOAD_ERR_OK         => null,
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE  => throw new RuntimeException('حجم فایل از حد مجاز سرور بیشتر است.'),
            UPLOAD_ERR_PARTIAL    => throw new RuntimeException('بارگذاری فایل به‌صورت ناقص انجام شد؛ دوباره تلاش کنید.'),
            UPLOAD_ERR_NO_FILE    => throw new RuntimeException('هیچ فایلی انتخاب نشده است.'),
            UPLOAD_ERR_NO_TMP_DIR => throw new RuntimeException('پوشهٔ موقت سرور در دسترس نیست.'),
            UPLOAD_ERR_CANT_WRITE => throw new RuntimeException('نوشتن فایل روی دیسک ناموفق بود.'),
            UPLOAD_ERR_EXTENSION  => throw new RuntimeException('بارگذاری توسط یکی از افزونه‌های سرور متوقف شد.'),
            default               => throw new RuntimeException('خطای ناشناخته در بارگذاری فایل رخ داد.'),
        };
    }

    private function detectMime(string $tmp): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return strtolower($mime);
                }
            }
        }

        // Fallback (also rejects anything that is not a real raster image).
        $info = @getimagesize($tmp);
        if (is_array($info) && isset($info['mime'])) {
            return strtolower((string) $info['mime']);
        }

        return 'application/octet-stream';
    }

    private function ensureDir(string $bucket): string
    {
        $dir = rtrim($this->uploadRoot, '/\\') . '/' . $bucket;

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('ایجاد پوشهٔ بارگذاری ناموفق بود.');
            }
        }

        if (!is_writable($dir)) {
            throw new RuntimeException('پوشهٔ بارگذاری قابل نوشتن نیست.');
        }

        return $dir;
    }

    private function uniqueName(string $ext): string
    {
        return bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
    }

    private function sanitizeSegment(string $segment): string
    {
        $segment = strtolower($segment);
        $segment = preg_replace('/[^a-z0-9_-]/', '', $segment) ?? '';

        return $segment !== '' ? $segment : 'misc';
    }

    private function maxMegabytes(): string
    {
        return (string) (int) round($this->maxBytes / (1024 * 1024));
    }
}
