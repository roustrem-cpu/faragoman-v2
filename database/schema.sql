-- ============================================================================
--  Faragoman v2 — RBAC schema (ADDITIVE / NON-DESTRUCTIVE)
-- ============================================================================
--
--  Running this script is OPTIONAL and 100% safe:
--    * It only CREATEs new tables with `IF NOT EXISTS`.
--    * It does NOT touch, alter or drop any existing table (users, articles, …).
--    * Existing accounts, passwords and sessions keep working unchanged.
--    * If you skip it, the app falls back to a built-in permission matrix.
--
--  Import once via phpMyAdmin (Import tab) on your existing database.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Roles --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`       VARCHAR(50)  NOT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `rank`       SMALLINT     NOT NULL DEFAULT 10,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`     VARCHAR(100) NOT NULL,
  `name`     VARCHAR(150) NOT NULL,
  `category` VARCHAR(60)  NOT NULL DEFAULT 'general',
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role ⇄ Permission --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id`       INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `rp_permission_idx` (`permission_id`),
  CONSTRAINT `rp_role_fk`       FOREIGN KEY (`role_id`)       REFERENCES `roles` (`id`)       ON DELETE CASCADE,
  CONSTRAINT `rp_permission_fk` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-user override (grant or deny a single permission) --------------------
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `user_id`       INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  `effect`        ENUM('allow','deny') NOT NULL DEFAULT 'allow',
  PRIMARY KEY (`user_id`, `permission_id`),
  KEY `up_permission_idx` (`permission_id`),
  CONSTRAINT `up_permission_fk` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: role hierarchy -----------------------------------------------------
INSERT IGNORE INTO `roles` (`slug`, `name`, `rank`) VALUES
  ('super_admin',   'مدیر کل',        100),
  ('section_admin', 'مدیر بخش',        80),
  ('editor',        'ویراستار',        60),
  ('author',        'نویسنده',         40),
  ('user',          'کاربر عادی',      10);

-- Seed: baseline permissions ----------------------------------------------
INSERT IGNORE INTO `permissions` (`slug`, `name`, `category`) VALUES
  ('content.view',        'مشاهده محتوا',            'content'),
  ('content.create',      'ایجاد محتوا',             'content'),
  ('content.update_own',  'ویرایش محتوای خود',       'content'),
  ('content.update_any',  'ویرایش هر محتوا',         'content'),
  ('content.publish',     'انتشار محتوا',            'content'),
  ('content.delete',      'حذف محتوا',               'content'),
  ('comments.moderate',   'مدیریت دیدگاه‌ها',         'comments'),
  ('stories.manage',      'مدیریت استوری‌ها',         'stories'),
  ('users.manage',        'مدیریت کاربران',          'users'),
  ('roles.manage',        'مدیریت نقش‌ها و دسترسی‌ها', 'roles'),
  ('settings.manage',     'مدیریت تنظیمات سایت',      'settings');

-- Seed: default role → permission grants -----------------------------------
-- super_admin needs no rows (bypasses all checks in code).
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
WHERE r.slug = 'section_admin' AND p.slug IN
  ('content.view','content.create','content.update_any','content.publish','content.delete',
   'comments.moderate','stories.manage','users.manage','settings.manage');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
WHERE r.slug = 'editor' AND p.slug IN
  ('content.view','content.create','content.update_any','content.publish','comments.moderate');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
WHERE r.slug = 'author' AND p.slug IN
  ('content.view','content.create','content.update_own');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
WHERE r.slug = 'user' AND p.slug IN ('content.view');

SET FOREIGN_KEY_CHECKS = 1;
