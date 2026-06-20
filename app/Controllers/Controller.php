<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\View;

/**
 * Base controller — provides shared view rendering helpers so concrete
 * controllers stay thin and focused on orchestration only.
 */
abstract class Controller
{
    public function __construct(protected View $view)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html($this->view->render($template, $data), $status);
    }
}
