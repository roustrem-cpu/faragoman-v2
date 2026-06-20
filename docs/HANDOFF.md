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


---

## Task B — Category, Author & Search pages
_Date: 2026-06-20 • Phase 3 • Status: ✅ completed_

### Goal
Add the three content-discovery listings on the new router and make them
reachable from the existing UI.

### Files Modified
- **EDIT** `app/Repositories/ArticleRepository.php` — added read-only finders:
  `byCategory`/`countByCategory`, `byAuthor`/`countByAuthor`,
  `search`/`countSearch` (LIKE with explicit `ESCAPE '!'`), plus
  `findCategoryName` / `findAuthorName`.
- **EDIT** `app/Services/ArticleService.php` — `categoryFeed`/`categoryTotalPages`/
  `categoryName`, `authorFeed`/`authorTotalPages`/`authorName`,
  `searchResults`/`searchTotalPages` (+ `offset()`/`pageCount()` helpers).
- **NEW** `app/Controllers/CategoryController.php`, `AuthorController.php`,
  `SearchController.php` — thin controllers; graceful 404 for unknown
  category/author; empty-query search renders a prompt state.
- **NEW** `resources/views/category.php`, `author.php`, `search.php`.
- **NEW** `resources/views/partials/feed-grid.php` (shared article grid) and
  `partials/pagination.php` (shared pager).
- **EDIT** `routes/web.php` — `/search`, `/category/{id}`, `/author/{id}`
  registered BEFORE the `/{title}` catch-all.
- **EDIT** `app/Core/Application.php` — DI bindings for the 3 controllers.
- **EDIT** `resources/views/home.php` — category badge now links to
  `/category/{id}` and author name to `/author/{id}`.
- **EDIT** `resources/views/partials/header.php` — added a «جستجو» nav link.
- **EDIT** `resources/css/app.css` + `public/assets/css/app.min.css` — appended
  `.listing*` and `.search-form*` styles (plain CSS, both source and bundle).

### Architectural Decisions
- **Finders live in the Repository, services orchestrate** (Repository Pattern
  preserved); controllers stay thin.
- **Scalar-only caching.** Only integer COUNTs are cached. Article object lists
  are fetched fresh because the shared `Cache` unserializes with
  `allowed_classes => false`, which would corrupt cached model instances.
  Mirrors the existing `totalPages()`. ⚠️ NOTE for future agents: the existing
  `homeFeed()` *does* cache Article objects and is therefore subject to that
  same corruption on a cache hit — left untouched (out of scope), flagged here.
- **Search safety.** User wildcards (`%`, `_`) are neutralised via paired
  `ESCAPE '!'`; queries are fully parameterised. Searches title + excerpt + body
  of published articles only.
- **Reachability.** Home cards + header now link into the new pages so nothing
  is orphaned. The catch-all `/{title}` still resolves single-segment article
  URLs because the new routes are registered before it.
- **DB untouched.** Read-only feature; no schema/data changes; Store/Chat
  LegacyBridge untouched. Local compiled Tailwind only; zero external requests.

### Validation Performed
- `php -l` (PHP 8.4.21) passes on all new/edited PHP files (repository, service,
  3 controllers, Application, routes, 5 views/partials, home, header).
- Render smoke tests through the real `View` + layout: category (list +
  pagination + category/author links), category empty state, author (active
  page), search (encoded `q` + `&amp;` pager separator, results summary),
  empty-query search prompt, and the edited home view — all render with no
  warnings/fatals.

### Next (Task C)
Admin Panel — Foundation, layout, and routing.
