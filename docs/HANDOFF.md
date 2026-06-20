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


---

## Task D — Admin Panel: Article Management UI
_Date: 2026-06-20 • Phase 3 • Status: ✅ completed_

### Goal
Full CRUD + publish/unpublish for articles under `/admin/articles`, writing to
the existing production `articles` table with zero schema changes.

### Files Modified
- **NEW** `app/Controllers/AdminArticleController.php` — index / create / store /
  edit / update / destroy / publish / unpublish. Inline validation, flash
  messages via `?m=` redirect param, 404 for missing ids.
- **NEW** `resources/views/admin/articles/index.php` — management table (title,
  author, category, status tag, reads, row actions) + pagination.
- **NEW** `resources/views/admin/articles/form.php` — shared create/edit form
  (title, category, status, excerpt, content, image URL, tags) with error +
  old-input repopulation.
- **EDIT** `app/Models/Article.php` — added `imageUrl` + `postTag` (additive,
  read in `fromRow`) so the edit form can prefill them.
- **EDIT** `app/Repositories/ArticleRepository.php` — `adminList`/`adminCount`
  (all statuses), `allCategories`, and writes `create`/`update`/`delete`/
  `setStatus`.
- **EDIT** `app/Services/ArticleService.php` — admin list/pagination,
  `categories()`, and `createArticle`/`updateArticle`/`deleteArticle`/
  `setStatus` (each flushes the cache).
- **EDIT** `app/Support/Cache.php` — added `flush()` to clear all cache files
  after a mutation.
- **EDIT** `app/Core/Application.php` — `AdminArticleController` DI binding.
- **EDIT** `routes/web.php` — 8 routes under `/admin/articles`
  (reads: `[AuthMiddleware, gate.admin]`; writes also add `CsrfMiddleware`).
- **EDIT** `resources/css/app.css` + `public/assets/css/app.min.css` — admin
  toolbar, table, status tags, form styles, `.btn-sm`/`.btn-danger`/
  `.alert-success` (plain CSS, both files).

### Architectural Decisions
- **Legacy-faithful writes (CRITICAL).** The `articles` table is NOT defined in
  `database/schema.sql` (that file only adds new RBAC/stories tables). I
  recovered the real column set from the legacy `project` repo
  (`public_html/index.php`): INSERT uses
  `(user_id, author_name, category_id, title, post_tag, content, excerpt,
  image_url, status, key_point_1, key_point_2, key_point_3, is_scrolly,
  scrolly_data)`. Our INSERT mirrors it exactly (scrollytelling/key-point
  fields default to ''/0) so every NOT NULL column is satisfied. UPDATE only
  touches editable fields and deliberately leaves key_point_*/is_scrolly/
  scrolly_data/author_name intact → no data loss.
- **Status whitelist = `published|pending|approved|rejected`** — the exact
  values used by the legacy code (admin insert → `published`). `draft` is
  deliberately NOT used to avoid an out-of-range value on the production column.
  Unpublish maps to `pending`.
- **Access control** reuses the Task C `gate.admin` (admin.access) for the whole
  section; writes add CSRF. Finer content permissions can layer in later.
- **Cache invalidation:** every write calls `Cache::flush()` so public
  listings/counts refresh immediately (no daemon; shared-hosting friendly).
- **Image upload deferred to Task H** — the form currently takes an image URL/
  path (clearly labelled). DB untouched; LegacyBridge untouched; local
  compiled Tailwind only.

### Validation Performed
- `php -l` (PHP 8.4.21) passes on all 9 new/edited PHP files.
- Static SQL arity check: INSERT 14 placeholders ↔ 14 bind values in the exact
  legacy column order; UPDATE 7 placeholders.
- Render tests (real View + admin layout): article list (status tags, edit/
  publish/unpublish/delete forms with CSRF, pagination, success flash); create
  form; edit form (prefilled title/category/image/tags); validation-error form
  (messages + old input retained).
- Reflection coverage: every routed controller method exists; service +
  repository expose all new methods; `Cache::flush()` present.
- Router integration test: all 8 `/admin/articles` routes resolve to the right
  controller/method with correct GET/POST + id params; `/admin` and the
  `/{title}` catch-all still resolve correctly.
- Note: no MySQL in the sandbox, so write queries were validated structurally
  (arity + legacy-column parity) rather than executed.

### Next (Task E)
Dynamic RBAC Management UI (assign/revoke roles & permissions).

---

## Task E — Admin Panel: Dynamic RBAC Management UI
_Date: 2026-06-20 • Phase 3 • Status: ✅ completed_

### Goal
Give the Super Administrator a back-office UI to manage Role-Based Access
Control dynamically: create/edit/delete roles, grant permissions to roles,
assign roles to users, and apply per-user permission overrides — all driving
the additive RBAC tables and the existing `App\Support\Rbac` engine, with zero
schema changes to production.

### Files Modified
- **NEW** `app/Models/Role.php`, `app/Models/Permission.php` — read-only models
  over the additive `roles` / `permissions` tables.
- **NEW** `app/Repositories/RbacRepository.php` — all RBAC SQL: roles CRUD,
  `permissions`, `role_permissions` sync, `user_permissions` sync, user listing
  and `setUserRole()`. Includes `tablesReady()` (graceful degradation when
  schema.sql has not been imported).
- **NEW** `app/Services/RbacService.php` — orchestration: slug normalisation +
  validation, core-role protection, permission grouping, role assignment
  whitelist. No caching (see Decisions).
- **NEW** `app/Controllers/AdminRoleController.php` — index / role CRUD /
  permission editor / users list / role assignment / per-user overrides. Flash
  via `?m=` redirect param; 404 for missing ids; self-lockout guard.
- **NEW** views under `resources/views/admin/roles/`: `index.php` (roles table +
  stats + "schema not ready" notice), `form.php` (create/edit role),
  `permissions.php` (per-role permission matrix grouped by category),
  `users.php` (assign roles to users + pagination), `overrides.php` (per-user
  allow/deny/inherit segmented controls).
- **EDIT** `app/Core/Application.php` — DI bindings for `RbacRepository`,
  `RbacService`, `AdminRoleController`, and a new `gate.roles` middleware
  (`RoleMiddleware->require('roles.manage')`).
- **EDIT** `routes/web.php` — 12 routes under `/admin/roles*`
  (reads: `[AuthMiddleware, gate.roles]`; writes add `CsrfMiddleware`). Static
  segments registered before `{id}` patterns; whole block stays before the
  `/{title}` catch-all.
- **EDIT** `resources/css/app.css` + `public/assets/css/app.min.css` — appended
  the `.rbac-*`, `.perm-*`, `.override-*`, `.seg*`, `.role-rank` component
  styles (plain CSS, both source and compiled bundle).

### Architectural Decisions
- **Dedicated `gate.roles` (permission `roles.manage`), not `gate.admin`.**
  Role management is the most sensitive surface, so it requires `roles.manage`
  rather than the broad `admin.access`. By the seeded grants only the Super
  Admin (who bypasses all checks) holds it — section_admin is intentionally NOT
  granted `roles.manage`, matching the `Rbac` fallback matrix that denies
  `roles.*` to everyone below super_admin.
- **Role assignment writes the role *slug* to the existing `users.role`
  string column — no schema change.** Assignable values are whitelisted to
  slugs that exist in the `roles` table, so the production column can never
  receive an out-of-range value. Existing rows are never auto-rewritten; legacy
  `admin` keeps working via `Rbac::LEGACY_MAP` (→ super_admin) and the UI shows
  both the normalised role and the raw stored value for transparency.
- **Core roles are protected.** The five seeded slugs (super_admin,
  section_admin, editor, author, user) cannot be deleted and their slug is
  locked on edit (name/rank stay editable) because `Rbac` matches on them.
  Custom roles are fully editable/deletable.
- **Super Admin permission rows are never written** — it implicitly holds every
  permission, so its permission editor shows an informational note instead.
- **Self-lockout guard.** An admin cannot change their own account's role
  (prevents accidentally revoking their own access).
- **Graceful degradation.** `RbacRepository::tablesReady()` detects whether the
  additive tables were imported; if not, the index renders a guided
  "import database/schema.sql" notice and mutating routes redirect back — the
  app never fatals (mirrors the `Rbac` fallback philosophy).
- **No caching of RBAC data.** `Rbac::can()` reads the tables directly so
  changes take effect on the next request; we also avoid the
  `allowed_classes => false` model-corruption hazard flagged in Task B.
- **Additive DB only.** Writes touch only `roles`, `permissions`,
  `role_permissions`, `user_permissions` and the pre-existing `users.role`
  column. Store/Chat LegacyBridge untouched; local compiled Tailwind only, zero
  external requests; RTL/glass UI consistent with the Task C/D admin shell.

### Validation Performed
- `php -l` (PHP 8.4.21) passes on all new/edited PHP files (2 models,
  repository, service, controller, Application, routes, 5 views).
- Router integration test (stubbed container, pass-through middleware): all 12
  `/admin/roles*` routes resolve to the correct controller/method with correct
  GET/POST + id params; regression checks confirm `/admin`, `/admin/articles`,
  the `/{title}` catch-all and `/` still resolve correctly (static role
  segments correctly win over the `{id}` patterns).
- Render smoke tests through the real `View` + `layouts/admin`: roles index
  (populated + "schema not ready" notice), role form (core role with locked
  slug + new role with validation error/old input), permission matrix
  (editable + super-admin note), users list (self-row guard, raw-vs-normalised
  role, role select, pagination), per-user overrides (allow/deny/inherit
  segmented controls) — all render with no warnings/fatals.
- Reflection coverage: every routed controller method exists; `RbacService` and
  `RbacRepository` expose all methods used by the controller.
- Note: no MySQL in the sandbox, so write queries were validated structurally
  (placeholder/bind arity + additive-table parity) rather than executed; the
  `tablesReady()` fallback path was exercised via the render test.

### Next (Task F)
Admin Panel — User Management UI (list/search users, edit profile fields, ban/
unban) under `/admin/users`, reusing `gate.admin` and the `users.manage`
permission.

---

## Task F — Admin Panel: User Management UI
_Date: 2026-06-20 • Phase 3 • Status: ✅ completed_

### Goal
Build the back-office user administration screen under `/admin/users`: a
searchable, paginated user list, an edit-profile form, and ban/unban controls —
writing only to existing `users` columns (zero schema change).

### Files Modified
- **NEW** `app/Services/UserService.php` — orchestration: paginated `list()`/
  `total()`/`totalPages()` (20/page) with optional search, plus
  `updateProfile()` (validates email format + cross-user uniqueness) and
  `setBanned()`.
- **NEW** `app/Controllers/AdminUserController.php` — index (list + `?q=`
  search + `?page_num=`), edit, update, ban, unban. Flash via `?m=`; 404 for
  missing ids; self-ban guard.
- **NEW** `resources/views/admin/users/index.php` — management table (user,
  email, normalised role, active/banned tag, join date, actions) + search bar +
  pagination (search preserved across pages).
- **NEW** `resources/views/admin/users/form.php` — edit-profile form
  (display_name, email, user_title, avatar_url, user_bio) with an identity card
  showing username/role/status and a cross-link to the RBAC role-assignment
  screen; error + old-input repopulation.
- **EDIT** `app/Repositories/UserRepository.php` — `paginate`/`countAll` (LIKE
  search on username/email/display_name with paired `ESCAPE '!'`),
  `emailTakenByOther`, `updateProfile`, `setBanned` (writes confined to
  non-privileged columns).
- **EDIT** `app/Core/Application.php` — DI bindings for `UserService`,
  `AdminUserController`, and a new `gate.users` middleware
  (`RoleMiddleware->require('users.manage')`).
- **EDIT** `routes/web.php` — 5 routes under `/admin/users*` (reads:
  `[AuthMiddleware, gate.users]`; writes add `CsrfMiddleware`). Static segment
  before the `{id}` patterns; whole block before the `/{title}` catch-all.
- **EDIT** `resources/css/app.css` + `public/assets/css/app.min.css` — appended
  `.user-search`, `.tag--banned/.tag--active`, `.user-identity*` styles (plain
  CSS, both source and compiled bundle).

### Architectural Decisions
- **`gate.users` (permission `users.manage`).** Consistent with the Task E
  `gate.roles` pattern: the section requires `users.manage` rather than the
  broad `admin.access`. Per the seed, super_admin (bypass) and section_admin
  hold it — editors/authors do not — so user administration is correctly
  limited to admins.
- **Profile-only writes.** `updateProfile()` touches only display_name, email,
  user_title, user_bio and avatar_url. `username`, `password`, `role` and
  `author_rank` are deliberately never written here: roles live in the Task E
  RBAC UI (the form cross-links to it), passwords stay in AuthService. This
  keeps concerns separated and the privileged columns out of this surface.
- **Ban = the existing `is_banned` flag.** `AuthService::attempt()` already
  rejects banned users, so toggling `is_banned` immediately blocks/restores
  login with no other change. Self-ban is blocked (self-lockout guard); the
  list also hides the ban button on your own row.
- **Search is injection-safe.** User wildcards (`%`, `_`) are neutralised via a
  paired `ESCAPE '!'`; queries are fully parameterised (same convention as the
  Task B search). The search term is carried through pagination via the
  `baseUrl` query string.
- **DB untouched (additive-compatible).** Only pre-existing `users` columns are
  read/written — no migrations, no new tables. Store/Chat LegacyBridge
  untouched; local compiled Tailwind only, zero external requests; RTL/glass UI
  consistent with the Task C–E admin shell.

### Validation Performed
- `php -l` (PHP 8.4.21) passes on all new/edited PHP files (service,
  controller, repository, Application, routes, 2 views).
- Router integration test (stubbed container): all 5 `/admin/users*` routes
  resolve to the correct controller/method with correct GET/POST + id params;
  regression checks confirm `/admin/roles*`, `/admin/articles`, `/admin`, the
  `/{title}` catch-all and `/` still resolve correctly.
- Render smoke tests through the real `View` + `layouts/admin`: user list
  (active + banned tags, self-row guard hiding the ban button, search bar,
  pagination), empty search-result state, edit form (prefilled fields + RBAC
  cross-link), and validation-error form (email error + old input retained) —
  all render with no warnings/fatals.
- Reflection coverage: every routed controller method exists; `UserService` and
  the new `UserRepository` methods are all present.
- Note: no MySQL in the sandbox, so write/search queries were validated
  structurally (placeholder/bind arity + existing-column parity) rather than
  executed.

### Next (Task G)
Admin Panel — Comment Moderation UI (list/approve/reject/delete comments) under
`/admin/comments`, gated by the `comments.moderate` permission.


---

## Task G — Admin Panel: Comment Moderation UI
_Date: 2026-06-21 • Phase 3 • Status: ✅ completed_

### Goal
Give moderators a back-office screen under `/admin/comments` to review the
legacy `comments` table: list (filterable by status), approve, reject and
delete — writing only to existing columns with zero schema change.

### Files Modified
- **NEW** `app/Models/Comment.php` — read-only model over the legacy `comments`
  columns (id, user_id, guest_name, article_id, parent_id, comment, status,
  created_at) plus optional joined fields (`display_name`, `article_title`).
  Helpers: `authorName()` (display name → guest name → «ناشناس») and `isGuest()`.
- **NEW** `app/Repositories/CommentRepository.php` — all `comments` SQL:
  `paginate(status,limit,offset)` and `countAll(status)` (LEFT JOIN users for the
  author name, JOIN articles for the title — mirrors the legacy admin SELECT),
  `find(id)`, `pendingCount()`, `setStatus(id,status)`, `delete(id)`.
- **NEW** `app/Services/CommentService.php` — pagination (20/page), status
  filter whitelist (`pending|approved|all`), and `approve`/`reject`/`delete`.
- **NEW** `app/Controllers/AdminCommentController.php` — index (`?status=` filter
  + `?page_num=`), approve, reject, destroy. Flash via `?m=` redirect param;
  404 for missing ids.
- **NEW** `resources/views/admin/comments/index.php` — moderation table (comment
  excerpt, author + guest badge, article link, status tag, date, row actions)
  with status-filter tabs (pending badge count) + pagination.
- **EDIT** `app/Core/Application.php` — DI bindings for `CommentRepository`,
  `CommentService`, `AdminCommentController`, and a new `gate.comments`
  middleware (`RoleMiddleware->require('comments.moderate')`).
- **EDIT** `routes/web.php` — 4 routes under `/admin/comments` (read:
  `[AuthMiddleware, gate.comments]`; writes add `CsrfMiddleware`). Registered in
  the admin block, before the `/{title}` catch-all.
- **EDIT** `resources/css/app.css` + `public/assets/css/app.min.css` — appended
  `.comment-filters*` and `.comment-cell` styles (plain CSS, both source and
  compiled bundle, same convention as Tasks A–F).

### Architectural Decisions
- **Dedicated `gate.comments` (permission `comments.moderate`), not
  `gate.admin`.** Mirrors the Task E/F pattern of section-scoped gates. Per the
  seeded grants this is held by super_admin (bypass), section_admin AND editor —
  so editors can moderate discussion without the broader admin permissions. The
  `comments.moderate` permission already existed in `database/schema.sql`
  (category `comments`); no schema/seed change was needed.
- **Backward-compatible status whitelist = `{pending, approved}` (CRITICAL).**
  The legacy `comments` table is NOT defined in `database/schema.sql`. Its column
  set and the only status values it uses were recovered from the legacy
  `project` repo: the INSERT in `public_html/index.php`
  (`user_id, guest_name, guest_email, article_id, parent_id, comment, status`),
  the public read in `includes/chat_functions.php`, and the moderation list in
  `pages/admin/comments.php`. Legacy only ever writes `'approved'` or
  `'pending'`. To guarantee no out-of-range value reaches the production column
  (same precedent as the Task D article-status decision), moderation maps:
  approve → `'approved'`, reject → `'pending'` (un-approve / hide; reversible),
  delete → row removed. The public article query shows `'approved'` to everyone
  (and a user's own `'pending'`), so reject correctly removes a comment from the
  public view.
- **Repository owns all SQL; service orchestrates; controller stays thin** —
  consistent with the existing Repository/Service/Controller layering. Reads use
  the LEFT JOIN users / JOIN articles shape lifted from the legacy admin query
  so guest comments (NULL `user_id`) and the article title both resolve.
- **Article links use the title-based public route** (`/{rawurlencode(title)}`,
  Task A) rather than the legacy `index.php?page=article&id=` URL.
- **DB additive-compatible.** Only the pre-existing `comments` columns are
  read/written — no migrations, no new tables, no dropped columns. Store/Chat
  LegacyBridge untouched; local compiled Tailwind only, zero external requests;
  RTL/glass UI consistent with the Task C–F admin shell.

### Validation Performed
- `php -l` (PHP 8.4.21) passes on all new/edited PHP files (model, repository,
  service, controller, Application, routes) and the new view.
- Render smoke tests through the real view template (helpers + `mbstring`):
  pending list (filter tab active + pending badge, status tag, approve+delete
  forms with CSRF, truncated RTL comment text, title-based article link, no
  reject button on a pending row), approved list (success flash, reject+delete
  forms, guest badge, no approve button on an approved row), and the empty-state
  + 2-page pagination — all render with no warnings/fatals.
- Reflection/coverage check: every routed controller method exists
  (index/approve/reject/destroy); `CommentService` exposes every method the
  controller calls; `CommentRepository` exposes every method the service calls.
- Static SQL arity: paginate (2 base placeholders + 1 when a status filter is
  applied ↔ limit/offset[/status] binds), countAll (0/1 ↔ status), find (1 ↔ id),
  setStatus (2 ↔ status,id), delete (1 ↔ id) — all balanced; the status column
  is only ever bound `'approved'`/`'pending'`.
- Note: no MySQL in the sandbox, so write/list queries were validated
  structurally (placeholder/bind arity + legacy-column parity) rather than
  executed — consistent with prior tasks.

### Next (Task H)
Admin Panel — Stories Management UI (manage the additive `stories` table:
list/create/reorder/activate/delete) under `/admin/stories`, gated by the
`stories.manage` permission.
