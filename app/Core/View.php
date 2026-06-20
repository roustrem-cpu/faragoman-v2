<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Tiny native-PHP template engine. Views are plain .php files under
 * resources/views and receive escaped data through a `$e()` helper.
 * No external templating dependency = nothing extra to install on the host.
 */
final class View
{
    public function __construct(private string $viewPath)
    {
    }

    /**
     * Render a view (optionally wrapped in a layout) to a string.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = $this->renderRaw($template, $data);

        if ($layout !== null) {
            $content = $this->renderRaw($layout, array_merge($data, ['content' => $content]));
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderRaw(string $template, array $data): string
    {
        $file = $this->viewPath . '/' . str_replace('.', '/', $template) . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("View [{$template}] not found at {$file}.");
        }

        // Escape helper exposed to every template.
        $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}
