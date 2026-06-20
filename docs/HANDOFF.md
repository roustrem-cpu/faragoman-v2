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


---

## Task C — Admin Panel: Foundation, layout & routing
_Date: 2026-06-20 • Phase 3 • Status: ✅ completed_

### Goal
Stand up the back-office shell: a gated `/admin` entry point, a reusable admin
layout (sidebar + topbar), a dashboard landing page, and the routing/DI
plumbing that Tasks D–F will build their management UIs on top of.

### Files Modified
- **NEW** `app/Controllers/AdminController.php` — `dashboard()` renders the
  admin shell; reads only (`ArticleService::publishedCount()`).
- **NEW** `resources/views/layouts/admin.php` — admin shell layout (separate
  from the public `layouts/app`); `noindex` meta; loads the same compiled CSS.
- **NEW** `resources/views/admin/dashboard.php` — stat card + section hub grid.
- **NEW** `resources/views/partials/admin-sidebar.php` — nav (dashboard,
  articles, users, comments, stories, roles) with active-state highlighting.
- **NEW** `resources/views/partials/admin-topbar.php` — heading, view-site
  link, user chip + CSRF-protected logout.
- **EDIT** `app/Controllers/Controller.php` — added `renderWith($layout, ...)`
  so controllers can choose a non-default layout (the base `render()` always
  used `layouts/app`).
- **EDIT** `app/Services/ArticleService.php` — added `publishedCount()` (cached
  scalar; shares the home-count cache key).
- **EDIT** `app/Core/Application.php` — registered the `AdminController` binding
  and a `gate.admin` middleware binding
  (`RoleMiddleware->require('admin.access')`).
- **EDIT** `routes/web.php` — `GET /admin` with `[AuthMiddleware, gate.admin]`,
  registered before the `/{title}` catch-all.
- **EDIT** `resources/css/app.css` + `public/assets/css/app.min.css` — appended
  the `.admin-*`, `.stat-card`, `.admin-cards` shell styles (plain CSS, both
  source and compiled bundle).

### Architectural Decisions
- **Access control via existing primitives.** `RoleMiddleware` is configured
  through its `require($permission)` clone; since the Router resolves route
  middleware by *container id*, I exposed a pre-configured instance under the
  `gate.admin` id rather than changing the Router. Pipeline:
  `AuthMiddleware` (guests → /login) → `gate.admin` (RBAC `admin.access`).
- **Permission slug `admin.access`.** Super Admins bypass all checks (always
  in); other roles get in once granted via the RBAC tables / fallback matrix.
  Task E will provide the UI to assign it.
- **Dedicated admin layout** kept fully separate from the public layout (own
  sidebar/topbar chrome, `noindex`), reusing the same compiled CSS bundle.
- **CSS-only responsiveness** (no JS): sidebar is a horizontal scroll bar on
  narrow screens and a sticky vertical rail at ≥860px (RTL-aware via grid).
- **Foundation only.** Sidebar/dashboard link to `/admin/articles`,
  `/admin/users`, etc. — those routes are intentionally not built yet (Tasks D
  & F) and 404 gracefully until then. DB untouched (read-only); LegacyBridge
  untouched; local compiled Tailwind only, zero external requests.

### Validation Performed
- `php -l` (PHP 8.4.21) passes on all 9 new/edited PHP files.
- Admin dashboard renders through `layouts/admin` (sidebar active state, stat
  value `1,234`, user chip, CSRF logout, section cards) — no warnings/fatals.
- Router integration test with a stubbed container: `/admin` →
  `AdminController::dashboard` through the `[AuthMiddleware, gate.admin]`
  pipeline; `/{title}` still resolves to `ArticleController::show`;
  `/admin/articles` (not yet built) 404s; `/search`, `/category/{id}`, `/`
  all resolve correctly — confirming route precedence and middleware-id
  resolution.

### Next (Task D)
Admin Panel — Article Management UI (list/create/edit/publish under
`/admin/articles`, using a write-capable repository path).
