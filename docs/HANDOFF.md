# Faragoman v2 — HANDOFF (AI-to-AI log)

This is the authoritative communication channel between continuation agents.
Each entry is appended after a completed backlog task. Newest entries go at the
bottom. Read `docs/PROGRESS.md` for the high-level phase checklist.

---

## Task A — Article Detail Page
_Date: 2026-06-20 • Phase 3 • Status: ✅ completed_

### Goal
Close the 404 raised by the home feed. Every home card links to
`/{rawurlencode(title)}`, but no route handled that path, so clicking any
article 404'd. Task A adds the missing route → controller → service → view.

### Files Modified
- **NEW** `app/Controllers/ArticleController.php` — `show(Request, string $title)`;
  resolves a published article by title via `ArticleService::findByTitle()`,
  renders `article`, falls back to `errors.404` (HTTP 404) otherwise.
- **EDIT** `app/Core/Application.php` — registered the `ArticleController` binding
  in `controllerBindings()` (the container is explicit, not autowiring).
- **EDIT** `routes/web.php` — imported `ArticleController` and registered
  `GET /{title}` as the **last** route (single-segment catch-all; all specific
  routes above it still win because the Router returns the first match).
- **NEW** `resources/views/article.php` — premium glass reading surface
  (category badge, title, author/views/date meta, excerpt, rich body).
- **EDIT** `resources/css/app.css` — appended `.article*` component styles as
  plain CSS (same convention used for Stories so it survives `npm run build`).
- **EDIT** `public/assets/css/app.min.css` — appended the minified `.article*`
  rules so the *served* (compiled) bundle is in sync without a rebuild step.

### Architectural Decisions
- **Title-based routing (not id/slug).** The home view already emits
  `/{rawurlencode(title)}`. To *fix the existing 404 without touching home.php*,
  the route resolves by title. `ArticleService::findByTitle()` already existed,
  so no service/repository changes were needed (Repository Pattern preserved).
- **Catch-all registered last.** `/{title}` matches `[^/]+`; placing it after
  every specific route keeps `/login`, `/stories`, `/feed*`, etc. intact. The
  Router dispatches on first match in registration order.
- **Published-only visibility + graceful 404.** Non-existent or non-published
  titles render the shared `errors.404` view (wrapped in the layout) with a 404
  status — no fatal errors, consistent UX.
- **Body rendered as raw HTML.** Article bodies are author-managed rich HTML
  (legacy behaviour); echoed as-is. All view-level dynamic strings (title,
  author, category, excerpt) are escaped via `e()`.
- **DB untouched.** Read-only feature. No schema changes, no migrations, no
  dropped columns — 100% backward compatible with the production database.
- **Frontend rules honoured.** Local compiled Tailwind only, zero external
  requests, vanilla CSS additions, `prefers-reduced-motion` respected.

### Validation Performed
- `php -l` (PHP 8.4.21) passes on: `ArticleController.php`, `Application.php`,
  `routes/web.php`, `resources/views/article.php` (+ touched layout/404 views).
- End-to-end render smoke test through the real `View` engine wrapped in
  `layouts/app`: a fake published `Article` renders the full page (title, badge,
  meta, view count `12,345`, rich body, header/footer) — no warnings/fatals.
- 404 fallback path renders the layout-wrapped `errors.404` view successfully.

### Next (Task B)
Category, Author, and Search pages on the new router (reuse `ArticleRepository`;
add finder methods there rather than in controllers).
