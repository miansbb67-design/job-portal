-- ============================================================================
-- Super Admin + Admin Management + Role & Permission System Migration
-- Database: job_portal_db
-- ============================================================================

-- 1. Enhance `admins` table with Name, Role, Status, Updated_At, Last_Login
ALTER TABLE `admins` 
    ADD COLUMN IF NOT EXISTS `name` VARCHAR(100) NOT NULL DEFAULT 'Administrator' AFTER `id`,
    ADD COLUMN IF NOT EXISTS `role` ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin' AFTER `password_hash`,
    ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER `role`,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
    ADD COLUMN IF NOT EXISTS `last_login` DATETIME NULL AFTER `updated_at`;

-- 2. Create `permissions` Table
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `permission_key` VARCHAR(60) NOT NULL UNIQUE,
    `permission_name` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create `admin_permissions` Pivot Table
CREATE TABLE IF NOT EXISTS `admin_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_admin_perm` (`admin_id`, `permission_id`),
    CONSTRAINT `fk_admin_perm_user` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_admin_perm_rule` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create `activity_logs` Audit Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NULL,
    `admin_name` VARCHAR(100) NULL,
    `action` VARCHAR(60) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_module` (`module`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create `pages` Table for Static Page Management
CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `content` LONGTEXT NOT NULL,
    `meta_description` VARCHAR(300) NULL,
    `status` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create `settings` Table for Website Settings
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(60) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Seed Granular System Permissions
-- ============================================================================
INSERT INTO `permissions` (`permission_key`, `permission_name`, `module`, `description`) VALUES
-- Dashboard
('dashboard.view', 'View Dashboard', 'Dashboard', 'Access and view admin analytics and metrics dashboard'),

-- Jobs Management
('jobs.view', 'View Jobs', 'Jobs', 'View verified job postings list and details'),
('jobs.create', 'Add Jobs', 'Jobs', 'Create and publish new job vacancies'),
('jobs.edit', 'Edit Jobs', 'Jobs', 'Modify existing job postings and compensation details'),
('jobs.delete', 'Delete Jobs', 'Jobs', 'Permanently remove job vacancies'),
('jobs.publish', 'Publish / Unpublish Jobs', 'Jobs', 'Toggle published or draft status for job vacancies'),

-- Blog Posts / Articles Management
('posts.view', 'View Posts', 'Blog Posts', 'View career articles and guide listings'),
('posts.create', 'Add Posts', 'Blog Posts', 'Write and publish new career guidance articles'),
('posts.edit', 'Edit Posts', 'Blog Posts', 'Edit existing articles, content, and metadata'),
('posts.delete', 'Delete Posts', 'Blog Posts', 'Permanently remove career articles'),
('posts.publish', 'Publish / Unpublish Posts', 'Blog Posts', 'Toggle published or draft status for career articles'),

-- Categories Management
('categories.view', 'View Categories', 'Categories', 'View list of career categories and tracks'),
('categories.create', 'Add Categories', 'Categories', 'Create new career categories and tracks'),
('categories.edit', 'Edit Categories', 'Categories', 'Modify category titles, slugs, and descriptions'),
('categories.delete', 'Delete Categories', 'Categories', 'Delete categories from the system'),

-- Pages Management
('pages.view', 'View Pages', 'Pages', 'View static site pages (About, Terms, Privacy, etc.)'),
('pages.create', 'Add Pages', 'Pages', 'Create new static website pages'),
('pages.edit', 'Edit Pages', 'Pages', 'Edit contents of static website pages'),
('pages.delete', 'Delete Pages', 'Pages', 'Delete custom static pages'),

-- Media Management
('media.view', 'View Media', 'Media', 'Browse media gallery and uploaded article images'),
('media.upload', 'Upload Media', 'Media', 'Upload new images and media assets'),
('media.delete', 'Delete Media', 'Media', 'Permanently delete uploaded media files'),

-- Users / Admins Management
('users.view', 'View Admins', 'Users / Admins', 'View administrator accounts and their roles'),
('users.create', 'Add Admins', 'Users / Admins', 'Create new administrator accounts'),
('users.edit', 'Edit Admins & Permissions', 'Users / Admins', 'Update administrator profiles, reset passwords, and assign permissions'),
('users.delete', 'Delete Admins', 'Users / Admins', 'Remove administrator accounts from system'),

-- Settings Management
('settings.view', 'View Settings', 'Settings', 'View site configuration and branding settings'),
('settings.edit', 'Edit Settings', 'Settings', 'Update website settings, email, and parameters'),

-- Activity & Audit Logs
('logs.view', 'View Activity Logs', 'Activity Logs', 'Inspect administrative audit trails and security activity logs')
ON DUPLICATE KEY UPDATE `permission_name` = VALUES(`permission_name`), `module` = VALUES(`module`), `description` = VALUES(`description`);

-- ============================================================================
-- Seed Default Pages
-- ============================================================================
INSERT INTO `pages` (`title`, `slug`, `content`, `meta_description`, `status`) VALUES
('About Us', 'about-us', '<h2>About CareerPulse</h2><p>CareerPulse is an independent, authoritative career guidance and employment resource platform dedicated to empowering job seekers with verified job alerts, interview frameworks, resume strategies, and career blueprints.</p><h3>Our Mission</h3><p>To bridge the gap between job seekers and authentic career opportunities through transparent, verified reporting and actionable editorial guides.</p>', 'Learn about CareerPulse mission, background, and verified editorial standards.', 'published'),
('Privacy Policy', 'privacy-policy', '<h2>Privacy Policy</h2><p>Your privacy is important to us. CareerPulse collects minimal log information necessary for security and user experience. We do not sell your personal data.</p>', 'Comprehensive privacy policy and user data protections at CareerPulse.', 'published'),
('Terms & Conditions', 'terms-and-conditions', '<h2>Terms & Conditions</h2><p>By accessing CareerPulse, you agree to comply with our acceptable use policies. All editorial content is protected by international copyright laws.</p>', 'Terms and conditions governing the use of CareerPulse website and services.', 'published'),
('Editorial Disclaimer', 'disclaimer', '<h2>Editorial Disclaimer</h2><p>CareerPulse publishes job alerts for informational purposes. Candidates should verify terms directly with prospective employers. We never charge candidates for job listings.</p>', 'Employment notice and editorial verification disclaimer.', 'published'),
('Contact Support', 'contact', '<h2>Contact Us</h2><p>Have an inquiry, editorial correction, or partnership proposal? Reach out to our team at contact@careerpulse.com.</p>', 'Get in touch with the CareerPulse editorial and administration team.', 'published')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- ============================================================================
-- Seed Default Settings
-- ============================================================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'CareerPulse', 'general'),
('site_tagline', 'Jobs & Career Guidance Hub', 'general'),
('contact_email', 'admin@careerpulse.com', 'general'),
('items_per_page', '6', 'content'),
('enable_ads', '0', 'monetization'),
('maintenance_mode', '0', 'system')
ON DUPLICATE KEY UPDATE `setting_group` = VALUES(`setting_group`);

-- ============================================================================
-- Ensure Super Admin Account
-- Set admin (id=1) to Super Admin role with secure password: Admin@12345
-- ============================================================================
UPDATE `admins` 
SET `name` = 'Super Administrator',
    `role` = 'superadmin',
    `status` = 'active',
    `password_hash` = '$2y$10$oFAqDMK7Z9RAHpI6Pdbs2OVe.oQbIBaqgi20QXA/8Lk5iz7C0/Buu'
WHERE `id` = 1;

-- If faizan (id=2) exists, update to proper name and admin role
UPDATE `admins`
SET `name` = 'Faizan Admin',
    `role` = 'admin',
    `status` = 'active',
    `password_hash` = '$2y$10$oFAqDMK7Z9RAHpI6Pdbs2OVe.oQbIBaqgi20QXA/8Lk5iz7C0/Buu'
WHERE `id` = 2;
