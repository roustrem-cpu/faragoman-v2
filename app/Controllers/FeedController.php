<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ArticleService;

/**
 * Syndication endpoints. Exposes the latest published content to:
 *
 *  - RSS readers          -> GET /feed         (application/rss+xml)
 *  - JSON Feed clients     -> GET /feed.json    (application/feed+json)
 *  - CLI / terminal tools  -> GET /feed.txt     (text/plain; great for curl/wget)
 *
 * Content negotiation: a request to /feed from a terminal client (curl, wget,
 * httpie, lynx, w3m) automatically receives the lightweight plain-text feed,
 * while browsers and feed readers receive RSS. This keeps a single canonical
 * URL friendly to both humans-in-a-shell and machines.
 */
final class FeedController
{
    private const MAX_ITEMS = 30;

    public function __construct(private ArticleService $articles)
    {
    }

    /**
     * Canonical /feed endpoint with User-Agent based negotiation.
     */
    public function index(Request $request): Response
    {
        $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $isTerminal = (bool) preg_match('/\b(curl|wget|httpie|lynx|w3m|powershell|python-requests)\b/', $ua);

        return $isTerminal ? $this->text($request) : $this->rss($request);
    }

    public function rss(Request $request): Response
    {
        $items = $this->articles->homeFeed(1, self::MAX_ITEMS);
        $site  = $this->baseUrl();
        $now   = date(DATE_RSS);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= "  <channel>\n";
        $xml .= '    <title>' . $this->cdata('فراگمان') . "</title>\n";
        $xml .= '    <link>' . $this->esc($site) . "</link>\n";
        $xml .= '    <description>' . $this->cdata('مجلهٔ محتوای فارسی فراگمان') . "</description>\n";
        $xml .= "    <language>fa-IR</language>\n";
        $xml .= '    <lastBuildDate>' . $this->esc($now) . "</lastBuildDate>\n";
        $xml .= '    <atom:link href="' . $this->esc($site . '/feed') . '" rel="self" type="application/rss+xml" />' . "\n";

        foreach ($items as $article) {
            $url = $site . '/' . rawurlencode($article->title);
            $xml .= "    <item>\n";
            $xml .= '      <title>' . $this->cdata($article->title) . "</title>\n";
            $xml .= '      <link>' . $this->esc($url) . "</link>\n";
            $xml .= '      <guid isPermaLink="true">' . $this->esc($url) . "</guid>\n";
            if ($article->categoryName) {
                $xml .= '      <category>' . $this->cdata($article->categoryName) . "</category>\n";
            }
            if ($article->authorName) {
                $xml .= '      <dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">' . $this->cdata($article->authorName) . "</dc:creator>\n";
            }
            if ($article->createdAt) {
                $xml .= '      <pubDate>' . $this->esc(date(DATE_RSS, strtotime((string) $article->createdAt))) . "</pubDate>\n";
            }
            if ($article->excerpt) {
                $xml .= '      <description>' . $this->cdata((string) $article->excerpt) . "</description>\n";
            }
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n</rss>\n";

        return (new Response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=utf-8']))
            ->withHeader('Cache-Control', 'public, max-age=300');
    }

    public function json(Request $request): Response
    {
        $site  = $this->baseUrl();
        $items = [];

        foreach ($this->articles->homeFeed(1, self::MAX_ITEMS) as $article) {
            $items[] = array_filter([
                'id'             => (string) $article->id,
                'url'            => $site . '/' . rawurlencode($article->title),
                'title'          => $article->title,
                'summary'        => $article->excerpt,
                'author'         => $article->authorName ? ['name' => $article->authorName] : null,
                'date_published' => $article->createdAt ? date(DATE_ATOM, strtotime((string) $article->createdAt)) : null,
                'tags'           => $article->categoryName ? [$article->categoryName] : null,
            ], static fn ($v): bool => $v !== null);
        }

        return (new Response(
            (string) json_encode([
                'version'       => 'https://jsonfeed.org/version/1.1',
                'title'         => 'فراگمان',
                'home_page_url' => $site,
                'feed_url'      => $site . '/feed.json',
                'items'         => $items,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            200,
            ['Content-Type' => 'application/feed+json; charset=utf-8'],
        ))->withHeader('Cache-Control', 'public, max-age=300');
    }

    /**
     * Lightweight, machine- and terminal-friendly plain-text feed.
     */
    public function text(Request $request): Response
    {
        $site  = $this->baseUrl();
        $lines = [];
        $lines[] = 'فراگمان — آخرین مطالب';
        $lines[] = str_repeat('=', 40);
        $lines[] = '';

        foreach ($this->articles->homeFeed(1, self::MAX_ITEMS) as $i => $article) {
            $n = $i + 1;
            $lines[] = sprintf('%2d. %s', $n, $article->title);
            if ($article->authorName || $article->categoryName) {
                $lines[] = '    ' . trim(($article->authorName ?? '') . ($article->categoryName ? ' · ' . $article->categoryName : ''));
            }
            $lines[] = '    ' . $site . '/' . rawurlencode($article->title);
            $lines[] = '';
        }

        $lines[] = str_repeat('-', 40);
        $lines[] = 'RSS:  ' . $site . '/feed';
        $lines[] = 'JSON: ' . $site . '/feed.json';

        return (new Response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']))
            ->withHeader('Cache-Control', 'public, max-age=300');
    }

    private function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function cdata(string $value): string
    {
        return '<![CDATA[' . str_replace(']]>', ']]&gt;', $value) . ']]>';
    }
}
