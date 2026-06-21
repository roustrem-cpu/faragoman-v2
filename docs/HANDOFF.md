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


---

## Task H — Admin Panel: Stories Management UI
_Date: 2026-06-21 • Phase 3 • Status: ✅ completed_

### Goal
Give administrators a back-office screen under `/admin/stories` to manage the
additive `stories` table: list, create, edit, reorder, activate/deactivate and
delete — driving the same data the public home story-ring reads, with zero
schema change.

### Files Modified
- **NEW** `app/Controllers/AdminStoryController.php` — index / create / store /
  edit / update / activate / deactivate / moveUp / moveDown / destroy. Inline
  validation (title + image_url required, length caps), flash via `?m=` redirect
  param, 404 for missing ids.
- **NEW** `resources/views/admin/stories/index.php` — management table (image
  thumb, title, link, order, active/inactive tag, per-row actions: ▲/▼ reorder
  with end-disabled buttons, edit, activate/deactivate, delete) + create button;
  shows a guided notice when the optional `is_active` column is absent.
- **NEW** `resources/views/admin/stories/form.php` — shared create/edit form
  (title, image_url, link_url, display_order) with error + old-input repopulation.
- **EDIT** `app/Repositories/StoryRepository.php` — added writes `update()`
  (legacy-guaranteed columns only) and `setActive()` (guarded), plus
  `supportsActiveFlag()` column-probe. Existing `all`/`find`/`create`/`delete`/
  `reorder` preserved verbatim.
- **EDIT** `app/Services/StoryService.php` — added admin `all()` (uncached,
  defensive), `find()`, `update()`, `setActive()`, `supportsActiveFlag()` and
  `move()` (sequential re-index + neighbour swap); every mutation flushes the
  `stories:active` cache. Public `active()`/`create()`/`delete()` unchanged.
- **EDIT** `app/Core/Application.php` — DI binding for `AdminStoryController` and
  a new `gate.stories` middleware (`RoleMiddleware->require('stories.manage')`).
- **EDIT** `routes/web.php` — 10 routes under `/admin/stories` (reads:
  `[AuthMiddleware, gate.stories]`; writes add `CsrfMiddleware`). Static segments
  before the `{id}` patterns; whole block before the `/{title}` catch-all.
- **EDIT** `resources/css/app.css` + `public/assets/css/app.min.css` — appended
  `.story-thumb` / `.story-link` styles (plain CSS, both source and compiled
  bundle, same convention as Tasks A–G).

### Architectural Decisions
- **Dedicated `gate.stories` (permission `stories.manage`).** Consistent with the
  Task E/F/G section-gate pattern. Per the seed it is held by super_admin
  (bypass) and section_admin — editors/authors do not get it, so story curation
  is limited to admins. The `stories.manage` permission already existed in
  `database/schema.sql`; no schema/seed change was needed.
- **Optional `is_active` handled defensively (CRITICAL).** The additive `stories`
  schema adds `is_active`, but a pre-existing legacy `stories` table created
  before this column will not have it (CREATE TABLE IF NOT EXISTS never alters an
  existing table). `StoryRepository::supportsActiveFlag()` probes for the column
  with a guarded SELECT (mysqli runs in STRICT/exception mode, so a missing
  column throws and is caught → false). `setActive()` is a safe no-op when the
  column is absent, and the admin UI hides the activate/deactivate buttons and
  shows a guided notice. The model already treats a missing column as "active",
  so visibility is unchanged on legacy databases. create/edit writes touch only
  the legacy-guaranteed columns (title, image_url, link_url, display_order).
- **Deterministic reorder.** `move()` re-indexes the whole list to a clean
  sequential `display_order` (0..n) and swaps the target with its neighbour, so
  ordering is reliable even when legacy rows had duplicate/gappy order values —
  and it only ever writes the always-present `display_order` column.
- **Cache coherence.** Every admin mutation flushes the `stories:active` cache so
  the public home ring reflects changes on the next request (no daemon required;
  shared-hosting friendly). The admin list reads uncached for a live view.
- **Frozen modules untouched.** Stories is the historically-disabled feature
  re-enabled in the rewrite — NOT the frozen Store/Chat modules — so it is fully
  editable. Store/Chat LegacyBridge untouched; local compiled Tailwind only,
  zero external requests; RTL/glass UI consistent with the Task C–G admin shell.

### Validation Performed
- `php -l` (PHP 8.4.21) passes on all new/edited PHP files (controller,
  repository, service, Application, routes) and both views.
- Render smoke tests through the real view templates (helpers + `mbstring`):
  index with `is_active` supported (success flash, image thumbs, active+inactive
  tags, ▲/▼ reorder with first/last buttons disabled, activate/deactivate +
  delete CSRF forms, create button), index with the column absent (guided notice
  + activate/deactivate hidden), empty-state, create form, and edit form
  (prefilled values + validation error) — all render with no warnings/fatals.
- Reflection/coverage: every routed controller method exists
  (index/create/store/edit/update/activate/deactivate/moveUp/moveDown/destroy);
  `StoryService` exposes every method the controller calls; `StoryRepository`
  exposes every method the service calls; the public `StoryController` contract
  (`active`/`create`/`delete`) is preserved.
- Static SQL arity: find (1↔id), create (4↔title,image,link,order), update
  (5↔…,id), delete (1↔id), reorder (2↔order,id), setActive (2↔active,id),
  all/supportsActiveFlag (0) — all balanced; writes touch only existing columns
  and `is_active` is written only after the column-presence probe passes.
- Note: no MySQL in the sandbox, so write/reorder queries were validated
  structurally (placeholder/bind arity + legacy-column parity + the guarded
  `is_active` path) rather than executed — consistent with prior tasks.

### Next (Task I)
Profile and Wiki pages (public `/profile` / author profile + the wiki/knowledge
pages), reusing the existing `wiki_functions` data shape from the legacy repo
where applicable.


---

## Task I — Profile & Wiki pages
_Date: 2026-06-21 • Phase 3 • Status: ✅ completed_

### Goal
Add the two remaining public surfaces the header already linked to but had no
routes for: a user **profile** page (`/profile` for the signed-in user,
`/profile/{id}` for anyone) and the **knowledge-base / glossary** (`/wiki` index
+ `/wiki/{slug}` single term), both reading existing data with zero schema change.

### Files Modified
- **NEW** `app/Models/Wiki.php` — read-only model over the legacy `wiki_terms`
  table (id, term, slug, brief_desc, full_content, display_type, updated_at) with
  an `isFullPage()` helper.
- **NEW** `app/Repositories/WikiRepository.php` — `publishedList()` (alphabetical
  glossary, published only) and `findBySlug()` (single published term).
- **NEW** `app/Services/WikiService.php` — cached `list()` + `find()`; both
  defensive (return []/null if the `wiki_terms` table is absent).
- **NEW** `app/Controllers/WikiController.php` — index + show($slug) with a
  graceful 404.
- **NEW** `app/Controllers/ProfileController.php` — `me()` (own profile; guests
  redirected to /login) and `show($id)` (public profile, 404 if missing).
  Resolves profile fields via UserService and published articles via the
  existing ArticleService author feed. The private email column is never shown.
- **NEW** `resources/views/profile.php` — profile header (avatar/initial, name,
  @username, title, join date, archive link) + optional bio + the shared
  `feed-grid`/`pagination` partials for the author's articles.
- **NEW** `resources/views/wiki/index.php` — glossary grid (full-page terms link
  to their page; tooltip-only terms render inline).
- **NEW** `resources/views/wiki/show.php` — single term page (term, brief,
  author-managed full HTML body, updated date) with a back link.
- **EDIT** `resources/views/author.php` — added a "مشاهده پروفایل کامل" link to
  `/profile/{id}` so public profiles are reachable from the author archive.
- **EDIT** `app/Core/Application.php` — DI bindings for `WikiRepository`,
  `WikiService`, `ProfileController` and `WikiController`.
- **EDIT** `routes/web.php` — `GET /profile` (AuthMiddleware), `GET /profile/{id}`,
  `GET /wiki`, `GET /wiki/{slug}` — all registered before the `/{title}` catch-all.
- **EDIT** `resources/css/app.css` + `public/assets/css/app.min.css` — appended
  `.profile*` / `.wiki*` / `.listing__profile-link` styles (plain CSS, both
  source and compiled bundle, same convention as Tasks A–H).

### Architectural Decisions
- **Header links were already present, routes were not.** `partials/header.php`
  already pointed the nav at `/wiki` and the signed-in chip at `/profile`; Task I
  simply supplies the missing routes/controllers/views, so no header change was
  needed and nothing is orphaned. The author archive now cross-links to the full
  profile, and the profile links back to the article archive.
- **`/profile` vs `/profile/{id}`.** The bare `/profile` route is guarded by
  `AuthMiddleware` and shows the current user (matching the header chip);
  `/profile/{id}` is the public view of any user. Both render the same `profile`
  view via a shared private helper. Registered before the single-segment
  `/{title}` catch-all so the static `/profile` and `/wiki` segments win.
- **Read-only, reuse over duplication.** Profile articles reuse the existing
  `ArticleService::authorFeed/authorName/authorTotalPages` and the shared
  `feed-grid`/`pagination` partials rather than new queries. Wiki reads only the
  legacy `wiki_terms` columns; status is filtered to `'published'` exactly as the
  legacy code did.
- **Graceful degradation.** `WikiService` wraps every repository call in
  try/catch so a missing `wiki_terms` table yields an empty glossary / 404
  instead of a fatal — mirroring the StoryService resilience pattern. The
  glossary list is filesystem-cached (300s); single-term reads are uncached.
- **Content rendering.** A term's `full_content` is author-managed rich HTML and
  is echoed raw (same convention as article bodies); the brief description and
  all profile fields are escaped via `e()`. The legacy in-body term auto-linking
  (DOM rewriting in `wiki_functions.php`) was intentionally NOT ported — it is a
  presentation nicety, not a compatibility requirement; discoverability is
  provided by the glossary index instead. (Candidate refinement for later.)
- **DB untouched.** Read-only feature; no schema/data changes; Store/Chat
  LegacyBridge untouched; local compiled Tailwind only, zero external requests;
  RTL/glass UI consistent with the rest of the rewrite.

### Validation Performed
- `php -l` (PHP 8.4.21) passes on all new/edited PHP files (2 controllers, wiki
  model/repository/service, Application, routes) and all views.
- Render smoke tests through the real view templates (helpers + `mbstring`):
  profile with articles (header, initial-avatar fallback, @username, title, bio,
  archive link, article grid, 2-page pager), profile with image avatar + no
  articles/bio (empty-state, bio block omitted), wiki index (full-page term
  linked, tooltip term not linked, "more" link), empty wiki index, wiki show with
  full HTML body + updated date, wiki show brief-only fallback, and the edited
  author page (profile cross-link) — all render with no warnings/fatals.
- Reflection/coverage: routed methods exist (ProfileController me/show,
  WikiController index/show); WikiController→WikiService (list/find) and
  WikiService→WikiRepository (publishedList/findBySlug) resolve; ProfileController
  uses ArticleService::authorFeed/authorTotalPages and UserService::find — all
  present.
- Route precedence: `/profile`, `/profile/{id}`, `/wiki`, `/wiki/{slug}` all
  registered before the `/{title}` catch-all (verified by registration order).
- Static SQL arity: publishedList (0), findBySlug (1 ↔ slug) — balanced; reads
  filter `status = 'published'` and touch only existing columns.
- Note: no MySQL in the sandbox, so the wiki queries were validated structurally
  (arity + legacy-column parity + the defensive missing-table path) rather than
  executed — consistent with prior tasks.

### Next (Task J)
Image upload pipeline (storage & optimization) — wire real image uploads for the
article and story image fields (currently URL/path inputs), saved under
`public/uploads/…` with size/format handling, on shared hosting (no queues).

---

## Task J — Image Upload Pipeline
_Date: 2026-06-21 • Phase 3 • Status: ✅ completed_

### Goal
Replace the URL/path text inputs on the admin Article and Story forms with real
image uploads. Files are validated and saved under `public/uploads/…`, and the
resulting web-relative path is written into the EXISTING `image_url` column —
zero schema change, fully backward compatible. Must run on low-resource shared
hosting (Apache/Nginx + PHP-FPM): no GD/Imagick, no external libraries, no
queues.

### Files Modified
- **NEW** `app/Services/ImageUploadService.php` — secure, dependency-free upload
  service. Pipeline: (1) inspect the PHP upload error code (friendly message per
  mode); (2) enforce a max byte size (5 MiB default, injectable); (3) detect the
  REAL MIME type from the file bytes via `finfo` (with a `getimagesize`
  fallback) and map it to an allowed canonical extension — JPEG / PNG / WEBP —
  never trusting the client Content-Type; (4) generate a collision-free filename
  (`bin2hex(random_bytes(16))` + epoch + ext); (5) create the destination dir
  safely (`mkdir(0755, recursive)` + writability check); (6) `move_uploaded_file`
  into `public/uploads/{bucket}/` and return e.g. `uploads/articles/ab12….jpg`.
  Bucket names are sanitised (`[^a-z0-9_-]` stripped) so a caller can never path-
  traverse out of `public/uploads`.
- **EDIT** `app/Controllers/AdminArticleController.php` — constructor now also
  receives `ImageUploadService`. New `applyUpload()` helper: a freshly uploaded
  `image_file` wins; otherwise the fallback (existing `image_url` on edit, empty
  on create) is preserved, so editing without choosing a new file keeps the
  current image. Upload failures surface as an `image_file` field error and
  re-render the form (422). Article images remain OPTIONAL.
- **EDIT** `app/Controllers/AdminStoryController.php` — same `ImageUploadService`
  injection + `applyUpload()`. Story images are REQUIRED on create (the file
  input is `required`; an empty `image_url` fails validation under the
  `image_file` key) and preserved on edit unless replaced.
- **EDIT** `resources/views/admin/articles/form.php` — `enctype="multipart/form-data"`;
  the image field is now `<input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">`
  with a current-image preview on edit, a format/size hint, and an `image_file`
  error slot. A hidden `image_url` carries the current value for transparency
  (the controller is authoritative).
- **EDIT** `resources/views/admin/stories/form.php` — same multipart conversion;
  the file input is `required` on create (omitted on edit so the existing image
  can stand), with preview + error slot.
- **EDIT** `app/Core/Application.php` — registered the `ImageUploadService`
  singleton (`new ImageUploadService($basePath . '/public/uploads', 'uploads')`)
  and threaded it into the `AdminArticleController` and `AdminStoryController`
  factory closures (the container is explicit, not autowiring).

### Architectural Decisions
- **Real-byte MIME validation, not the client header.** The allow-list keys on
  the `finfo` MIME of the actual bytes; a `.png` containing PHP is detected as
  `text/x-php` and rejected. `getimagesize` is the fallback if `finfo` is
  unavailable. Only JPEG/PNG/WEBP are accepted.
- **Dependency-free + shared-hosting-safe.** No GD/Imagick/Composer packages; the
  service uses only core PHP (`finfo`, `move_uploaded_file`, `random_bytes`,
  `mkdir`). On-the-fly resizing/optimisation was intentionally NOT added — it
  would require GD/Imagick which the constraints forbid; the size cap is the
  resource guard instead. (Candidate refinement if an image extension is later
  guaranteed on the host.)
- **Collision-free, non-guessable names.** 16 random bytes + epoch keep names
  unique and unpredictable; the original client filename is discarded entirely
  (no user-controlled string ever touches the filesystem path).
- **Path-traversal safe.** The upload bucket segment is sanitised to
  `[a-z0-9_-]`; `'../etc'` → `etc`, `'a/b'` → `ab`, empty → `misc`, so writes
  are always confined to `public/uploads/<bucket>/`.
- **Backward-compatible storage.** The validated web path is stored in the
  pre-existing `image_url` column exactly as the old URL/path strings were —
  no migration, no dropped column. Old rows that already hold a full URL keep
  working; the form preview renders both `/uploads/...` paths and absolute URLs.
- **Optional vs required mirrors the data model.** Articles may have no image
  (optional); stories are a visual ring and require one (required on create,
  preserved on edit).

### Validation Performed
- `php -l` (PHP 8.4.21) passes on all touched files: `ImageUploadService.php`,
  both admin controllers, `Application.php`, and both `form.php` views.
- Unit smoke test (reflection) of `ImageUploadService`: `detectMime` correctly
  classifies real JPEG/PNG/WEBP and rejects a PHP-payload `.png` (→ `text/x-php`,
  not allowed); `sanitizeSegment` neutralises traversal (`../etc`→`etc`,
  `a/b`→`ab`, ``→`misc`); `uniqueName` yields distinct names with the right
  extension; `ensureDir` creates a writable directory; `hasUpload` returns false
  for `null`/`UPLOAD_ERR_NO_FILE` and true for a real upload; the upload-error
  matrix maps every `UPLOAD_ERR_*` code to a Persian message.
- Render smoke tests through the real view templates (helpers stubbed, `E_ALL`
  display_errors on): article create, article edit (current-image preview +
  `image_file` error), story create (file input `required`), story edit
  (preview + error) — all render with no warnings/notices; every form carries
  `enctype="multipart/form-data"` and a `type="file"` input.
- Note: `move_uploaded_file`/`is_uploaded_file` only succeed for genuine HTTP
  uploads, so the final move step was validated structurally (guards + the
  surrounding pipeline) rather than executed — consistent with prior tasks and
  the no-runtime-server sandbox.

### Next (Task K)
Drop-in copy of the untouched legacy Store & Chat modules under `/legacy` so the
already-wired `LegacyBridge` in `public/index.php` can serve them.


---

## Task K — Legacy Integration & Finalization
_Date: 2026-06-21 • Phase 3 • Status: ✅ completed_

### Goal
Vendor the strictly-frozen legacy **Store** and **Chat** modules into
faragoman-v2 under `legacy/store/` and `legacy/chat/` so the existing
`LegacyBridge` (already wired in `public/index.php`) can serve them verbatim.
`public/index.php` maps `/store*` → `legacy/store/index.php` and `/chat*` →
`legacy/chat/index.php`, booting the shared mysqli `$conn` first — so this task
is purely a file-placement step (no code change to the bridge).

### Files Added (copied verbatim from the legacy `project` repo)
- `legacy/store/index.php`            ← `public_html/store/index.php` (9070 B)
- `legacy/store/css/home_cards.css`   ← `public_html/store/css/home_cards.css` (5417 B)
- `legacy/store/css/store.css`        ← `public_html/store/css/store.css` (13048 B)
- `legacy/store/js/home_cards.js`     ← `public_html/store/js/home_cards.js` (2865 B)
- `legacy/store/js/store.js`          ← `public_html/store/js/store.js` (2881 B)
- `legacy/chat/index.php`             ← `public_html/chat.php` (3020 B)

### Architectural Decisions
- **Store = the whole `store/` directory, copied byte-for-byte.** It is a self-
  contained module (`index.php` + `css/` + `store/js/`) that computes its own
  base path from `$_SERVER['PHP_SELF']` and loads its assets relatively, so it
  works unmodified at `legacy/store/`. Byte sizes match the source exactly.
- **Chat: there is NO `chat/` directory in the legacy repo.** A full recursive
  walk of `public_html/` confirmed the only chat artefacts are: `chat.php` (the
  web entry — a self-contained "chat temporarily disabled" notice page),
  `chat-server/` (a Node.js realtime backend: `server.js` + a Firebase
  `serviceAccountKey.json`), and `chat_uploads/` (runtime user-generated upload
  storage). The `LegacyBridge` contract requires `legacy/chat/index.php`, so the
  frozen, PHP-servable chat surface — `chat.php` — was copied verbatim to
  `legacy/chat/index.php`. The module is intentionally minimal because the chat
  is currently disabled in production (the page itself says so).
- **Deliberately EXCLUDED from `legacy/chat/`:**
  - `chat-server/serviceAccountKey.json` — a live Firebase **service-account
    credential**; committing it would be a secret leak. Never vendored.
  - `chat-server/server.js` — a Node realtime server that cannot run on the
    shared-hosting PHP-FPM target (the engineering constraints forbid Docker /
    queue workers / long-running Node processes), so it is dead weight in a PHP
    deploy and was left out.
  - `chat_uploads/` — runtime user content, not source code; it does not belong
    in the repository and is recreated at runtime.
  These exclusions keep the drop-in faithful to what is actually servable on the
  target host while avoiding a credential leak.
- **Modules are frozen.** Not a single byte of the copied Store/Chat files was
  edited — they run against the new stack solely through `LegacyBridge`, which
  exposes the same mysqli `$conn` the new `Database` layer manages. DB untouched;
  100% backward compatible.

### Validation Performed
- Byte-for-byte fidelity: each copied file's byte length equals the size GitHub
  reports for its legacy source (9070 / 5417 / 13048 / 2865 / 2881 / 3020).
- `php -l` (PHP 8.4.21): `legacy/store/index.php` and `legacy/chat/index.php`
  both report no syntax errors.
- Bridge contract: the resulting tree provides exactly `legacy/store/index.php`
  and `legacy/chat/index.php`, the two entry points `public/index.php`'s
  `LegacyBridge` block requires (`/store*` and `/chat*` now resolve to real
  files instead of the prior safe no-op).

### Project Status — COMPLETE
Phase 3 (Tasks A–K) is finished. The faragoman-v2 migration is feature-complete
on the new Laravel-inspired architecture (DI container, router + middleware,
service/repository layers, view engine), with the legacy Store & Chat modules
mounted under `/legacy`. The single remaining backlog item is a full end-to-end
run against a copy of the production database, which needs a staging environment
(the sandbox has no MySQL; everything here was validated structurally and via
render/unit smoke tests).
