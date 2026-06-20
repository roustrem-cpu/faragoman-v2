<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\StoryService;
use App\Support\Rbac;

/**
 * Stories endpoints.
 *
 *  - index()   public JSON feed consumed by the front-end story viewer.
 *  - store()   guarded creation (requires the `stories.manage` permission).
 *  - destroy() guarded deletion (requires the `stories.manage` permission).
 *
 * The controller is intentionally view-less: the visible story ring bar is
 * rendered server-side inside the home view for zero-JS resilience, while the
 * immersive viewer hydrates from this JSON endpoint.
 */
final class StoryController
{
    private const PERMISSION = 'stories.manage';

    public function __construct(
        private StoryService $stories,
        private AuthService $auth,
        private Rbac $rbac,
    ) {
    }

    public function index(Request $request): Response
    {
        $stories = array_map(
            static fn ($story): array => $story->toArray(),
            $this->stories->active(),
        );

        return Response::json(['data' => $stories])
            ->withHeader('Cache-Control', 'public, max-age=120');
    }

    public function store(Request $request): Response
    {
        if (($denied = $this->guard()) !== null) {
            return $denied;
        }

        $title    = trim((string) $request->input('title', ''));
        $imageUrl = trim((string) $request->input('image_url', ''));
        $linkUrl  = trim((string) $request->input('link_url', ''));
        $order    = (int) $request->input('display_order', 0);

        if ($title === '' || $imageUrl === '') {
            return Response::json(['error' => 'title and image_url are required'], 422);
        }

        $id = $this->stories->create($title, $imageUrl, $linkUrl !== '' ? $linkUrl : null, $order);

        return Response::json(['id' => $id], 201);
    }

    public function destroy(Request $request, string $id): Response
    {
        if (($denied = $this->guard()) !== null) {
            return $denied;
        }

        $this->stories->delete((int) $id);

        return Response::json(['deleted' => true]);
    }

    /**
     * Returns a 401/403 response when the current user may not manage stories,
     * or null when the request is allowed to proceed.
     */
    private function guard(): ?Response
    {
        $user = $this->auth->user();

        if ($user === null) {
            return Response::json(['error' => 'authentication required'], 401);
        }

        if (!$this->rbac->can($user, self::PERMISSION)) {
            return Response::json(['error' => 'forbidden'], 403);
        }

        return null;
    }
}
