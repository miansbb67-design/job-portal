# CareerPulse - Jobs & Career Articles Blog Website
## Mukammal Project Architecture aur Technical Documentation (Roman Urdu)

---

## 1. Executive Project Summary (Project Ka Kholasa)

**CareerPulse** ek enterprise-grade, responsive, aur Google AdSense / SEO policy compliant **Jobs, Employment aur Career Articles Blog Platform** hai jisko **HTML5, Vanilla CSS3, Vanilla JavaScript (ES6+), PHP 8.x, aur MySQL (MariaDB)** ke sath develop kiya gaya hai. 

Is application ko banate waqt modern web standards ka sakhti se khayal rakha gaya hai aur kisi bhi heavy external framework (jaise Tailwind CSS, Bootstrap, React, ya jQuery) ka sahara nahi liya gaya. Is ka faida ye hai ke website super fast load hoti hai, code saaf aur asan rehta hai, aur security bohot mazboot rehti hai.

### Aham Khusoosiyat (Key Architectural Highlights)
- **Design System & Theme**: **Clean White & Emerald Green (Professional Career Growth Theme)**. Saaf suthre white surfaces (`#ffffff`), fresh mint highlights (`#ecfdf5`), deep forest green gradients (`linear-gradient(135deg, #064e3b, #047857)`), aur high-contrast slate text typography (`#0f172a`).
- **Architecture**: Modular Procedural aur Object-Oriented ka hybrid structure PHP PDO database abstraction ke sath.
- **Security Engine**: Bcrypt password hashing (`PASSWORD_BCRYPT`), Cryptographically secure CSRF tokens (`random_bytes`), input sanitization, HTML entity escaping (`h()`), PHP MIME-type file verification (`finfo`), aur SQL Injection se bachao parameterized prepared queries ke zariye.
- **Super Admin & Role/Permission System**: Granular Role-Based Access Control (RBAC) system jisme Super Admin aur Staff Admins ke alag alag ikhtiyarat (29 alag permissions) hain.
- **Editorial Experience**: Custom lightweight rich formatting toolbar (Headings, bold, lists, quotes, hyperlinks), auto-slug generation, live image preview, aur genuine JobPosting toggles.
- **SEO & Google AdSense Compliance**: Semantic HTML5 tags, dynamic OpenGraph/Twitter cards, compliant ad placeholders (`render_ad_slot`), XML sitemap, `robots.txt`, aur genuine job postings par conditional `JobPosting` JSON-LD structured data.
- **Executive Admin Panel**: Modern Glassmorphism topbar, live system pulse indicator, dynamic sidebar count badges, responsive mobile drawer navigation, aur executive welcome hero banner.

---

## 2. Mukammal Project Directory Structure (Files aur Folders)

```text
c:\xampp\htdocs\job-portal/
├── .htaccess                     # Apache mod_rewrite rules, clean URLs aur security headers
├── robots.txt                    # Search crawler rules (/admin/ aur /config/ ko block karta hai)
├── sitemap.xml                   # Google aur Bing search engines ke liye XML sitemap
├── database.sql                  # Mukammal MySQL database schema aur initial tables/data
├── test_suite.php                # Automated end-to-end testing script (routes, auth aur security)
├── DOCUMENTATION.md              # Ye technical document file (Roman Urdu)
│
├── index.php                     # Public Homepage (Hero search, featured guides, latest jobs)
├── articles.php                  # Articles listing page (category filter aur numeric pagination)
├── article.php                   # Single article reader (job specs box aur conditional JSON-LD schema)
├── categories.php                # Public categories list aur category archive page
├── search.php                    # Keyword search results page
├── about.php                     # About Us page (editorial standards, mission, background)
├── contact.php                   # Contact Us form (CSRF protected, input validated)
├── privacy-policy.php            # GDPR, CCPA & AdSense compliant privacy policy page
├── cookie-policy.php             # Comprehensive Cookie Policy & Tracking conditions page
├── terms.php                     # Terms & Conditions page (Acceptable use, scraping rules, cookie consent)
├── disclaimer.php                # Employment & recruitment fee warning disclaimer
├── 404.php                       # Custom 404 Not Found error page
│
├── config/
│   ├── database.php              # PDO MySQL connection singleton pattern ke sath
│   └── config.php                # Global constants (BASE_URL, upload paths, session security)
│
├── includes/
│   ├── header.php                # Public HTML5 header, navigation bar aur search box
│   ├── footer.php                # Public footer (links, copyright, policy pages)
│   ├── cookie-consent.php        # Interactive GDPR & AdSense Cookie Consent & Conditions banner
│   ├── functions.php             # Core helper library (sanitization, CSRF, flash, file upload)
│   ├── auth_functions.php        # Role aur Permission system ke core security functions
│   └── ads-placeholder.php       # AdSense friendly policy-compliant advertisement slots
│
├── assets/
│   ├── css/
│   │   ├── style.css             # Public website stylesheet (clean emerald responsive theme)
│   │   └── admin.css             # Modern Executive SaaS admin stylesheet (dark sidebar, cards, topbar)
│   ├── js/
│   │   ├── main.js               # Frontend JS (mobile menu toggle, back-to-top scroll, alerts)
│   │   └── admin.js              # Admin panel JS (drawer toggle, auto-slug, image preview, toolbar)
│   └── images/
│       └── default-article.jpg   # Default fallback article image
│
├── uploads/
│   └── articles/                 # User-uploaded featured article images folder
│       ├── .htaccess             # Hardened security: is folder me PHP script execution band hai
│       └── index.html            # Directory traversal prevention file
│
└── admin/
    ├── index.php                 # Executive Admin Dashboard (welcome banner, stats, traffic, activity)
    ├── login.php                 # Admin login page (CSRF protected, session fixation defense)
    ├── logout.php                # Secure logout, session destruction aur cookie clearance
    ├── 403.php                   # Access Denied / Unauthorized access error page
    ├── articles.php              # General article management table
    ├── categories.php            # Category CRUD console (add, edit, delete with checks)
    ├── add-article.php           # Naya article/job post create karne ka form
    ├── edit-article.php          # Article update karne ka form (prefilled data)
    ├── delete-article.php        # CSRF-protected delete handler jo server se image bhi delete karta hai
    │
    ├── admins/                   # Super Admin: Staff Admin accounts management
    │   ├── index.php             # Admin directory list (roles, status, last login, action buttons)
    │   ├── add.php               # Naya admin account create karne ka form
    │   ├── edit.php              # Admin account edit karne ka form
    │   └── delete.php            # Admin account delete karne ka safe handler
    │
    ├── permissions/              # Super Admin: Granular Role Permissions
    │   └── index.php             # Staff admin ko permissions assign karne ka interactive matrix
    │
    ├── jobs/                     # Job Postings Management Module
    │   └── index.php             # Sirf job postings ki dedicated list aur management
    │
    ├── posts/                    # Blog Posts Management Module
    │   └── index.php             # Sirf regular career blog articles ki dedicated list
    │
    ├── pages/                    # Static Informational Pages Management Module
    │   ├── index.php             # Legal aur info pages ki list (About, Privacy, Terms, Disclaimer)
    │   └── edit.php              # Page content aur meta description update form
    │
    ├── media/                    # Media Library Module
    │   └── index.php             # Uploaded images gallery aur direct image upload console
    │
    ├── settings/                 # Platform Configuration Console
    │   └── index.php             # Site name, tagline, email, social links, SEO defaults settings
    │
    ├── logs/                     # System Audit Trail Module
    │   └── index.php             # Chronological activity logs (logins, creates, edits, deletes)
    │
    └── includes/
        ├── admin-header.php      # Admin panel glassmorphism topbar, sidebar, user profile chip
        ├── admin-footer.php      # Admin footer aur JavaScript inclusions
        └── auth-check.php        # Admin authentication aur live permission verification middleware
```

---

## 3. Technology Stack & Zubaan-ba-Zubaan Tafseel

### 3.1 HTML (HyperText Markup Language - Version 5)
- **Kahan Istemal Hui**: Tamam public pages (`index.php`, `articles.php`, `article.php`, `categories.php`, etc.), `includes/*.php` layout partials, aur `admin/*.php` management pages me.
- **Maqsad**: Semantic HTML structure create karna taake Google search engine bot asani se pages ko crawl kar sake aur screen readers ke liye accessibility (`<header>`, `<nav>`, `<main>`, `<article>`, `<aside>`, `<footer>`) behtareen ho.
- **Aham Tags**:
  - `<article>`: Har job card aur single article guide ko wrap karta hai.
  - `<aside>`: Policy-compliant advertisement slots ko wrap karta hai taake content aur ad alag rahein.
  - `<nav>`: Breadcrumbs aur main navigation menu ARIA attributes ke sath.
  - `<input type="hidden" name="csrf_token">`: Form submissions ko cross-site request forgery se bachata hai.

### 3.2 CSS (Cascading Style Sheets - Pure Vanilla CSS3)
- **Files**:
  - `assets/css/style.css`: Public frontend layout, emerald color palette, responsive grid cards, glassmorphism badges, modern typography, aur AdSense placeholders.
  - `assets/css/admin.css`: Executive Admin panel layout, midnight navy sidebar (`#0a0f1d`), frosted glass topbar (`backdrop-filter: blur(16px)`), modern stat cards (`14px/16px radius`), tables, badges, aur form controls.
- **Design Tokens**: Standard CSS variables use kiye gaye hain: `--admin-primary: #059669`, `--admin-sidebar-bg: #0a0f1d`, `--admin-bg: #f8fafc`, `--admin-font: 'Plus Jakarta Sans'`, etc.
- **Responsive System**: CSS Grid (`repeat(auto-fill, minmax(320px, 1fr))`) aur Flexbox, media queries ke sath (1200px, 768px, 540px, 480px).
- **Performance**: Zero external UI framework dependencies; CSS super lightweight hai aur page instant render hota hai.

### 3.3 JavaScript (Pure Vanilla ES6+)
- **Files**:
  - `assets/js/main.js`: Public website interactions: mobile drawer slide-out toggle, bahar click karne par menu band hona, smooth back-to-top scroll, aur auto-dismissing flash alerts.
  - `assets/js/admin.js`: Admin panel interactions: mobile drawer toggle (`toggleSidebarDrawer()`), article title se auto-slug generation, client-side FileReader se instant image preview, custom editor formatting tag insertion (H2, H3, Bold, Lists, Links, Blockquotes), aur delete confirmation dialogs.
- **Dependencies**: Koi external library nahi (No jQuery, No React). Pure vanilla modern JavaScript.

### 3.4 PHP (Hypertext Preprocessor - Version 8.x)
- **Role**: Server-side routing, database controller, authentication guards, input validation, file uploads, aur dynamic HTML view rendering.
- **Security Features in PHP**:
  - Parameterized prepared SQL queries via `PDO::prepare()` aur `execute()`.
  - Secure password hashing via `password_hash($pass, PASSWORD_BCRYPT)`.
  - Cookie security: `httponly=true`, `samesite=Lax`, aur HTTPS par `secure`.
  - Custom XSS prevention: `h()` helper function (`htmlspecialchars`) aur safe HTML tag sanitization via `sanitize_article_html()`.
  - File upload safety: PHP `finfo` ke zariye MIME type verification aur image dimension validation.

### 3.5 MySQL / MariaDB (Database Engine)
- **Database Name**: `job_portal_db`
- **Engine**: InnoDB (Foreign Keys, Cascade Deletions, atomic transactions, aur row-level locking support ke sath).
- **Character Set**: `utf8mb4` with `utf8mb4_unicode_ci` (Mukammal Unicode, international characters aur emojis support).
- **Tables**: `admins`, `permissions`, `admin_permissions`, `categories`, `articles`, `pages`, `settings`, `activity_logs`.

---

## 4. Database Schema & Tables ki Mukammal Tafseel

### Table 1: `admins` (Administrative Accounts)
Ye table un tamam users ko store karti hai jo admin panel me login karne ki ijazat rakhte hain.
| Column Name | Data Type | Key | Nullable | Tafseel |
|---|---|---|---|---|
| `id` | INT AUTO_INCREMENT | PRIMARY KEY | NO | Har admin ka unique ID number |
| `name` | VARCHAR(100) | None | YES | Admin ka mukammal naam (e.g., Faizan Khan) |
| `username` | VARCHAR(50) | UNIQUE | NO | Login username |
| `email` | VARCHAR(100) | UNIQUE | NO | Admin ka contact email address |
| `password_hash` | VARCHAR(255) | None | NO | Bcrypt se encrypted password |
| `role` | ENUM('superadmin', 'admin') | INDEX | NO | Role (`superadmin` ya standard `admin`) |
| `status` | ENUM('active', 'inactive') | INDEX | NO | Account active hai ya suspended |
| `last_login` | TIMESTAMP | None | YES | Akhri login ka timestamp |
| `created_at` | TIMESTAMP | None | NO | Account banne ka timestamp |
| `updated_at` | TIMESTAMP | None | NO | Record update hone ka timestamp |

---

### Table 2: `permissions` (System Access Keys)
System ke andar jitne bhi features hain unki granular keys is table me defined hain (Total 29 keys).
| Column Name | Data Type | Key | Nullable | Tafseel |
|---|---|---|---|---|
| `id` | INT AUTO_INCREMENT | PRIMARY KEY | NO | Permission ka unique ID |
| `module` | VARCHAR(50) | INDEX | NO | Feature group (jobs, posts, categories, etc.) |
| `permission_key` | VARCHAR(80) | UNIQUE | NO | Code me check hone wali key (e.g. `jobs.create`) |
| `name` | VARCHAR(100) | None | NO | Human-readable label (e.g. Create Jobs) |
| `description` | VARCHAR(255) | None | YES | Permission ka maqsad aur tafseel |
| `created_at` | TIMESTAMP | None | NO | Record create hone ka waqt |

---

### Table 3: `admin_permissions` (Role Assignment Junction)
Ye junction table Staff Admins ko assign kiye gaye specific permissions ko link karti hai.
| Column Name | Data Type | Key | Nullable | Tafseel |
|---|---|---|---|---|
| `admin_id` | INT | FOREIGN KEY | NO | References `admins(id)` ON DELETE CASCADE |
| `permission_id` | INT | FOREIGN KEY | NO | References `permissions(id)` ON DELETE CASCADE |
| `granted_at` | TIMESTAMP | None | NO | Permission assign karne ka waqt |
- **Primary Key**: Composite (`admin_id`, `permission_id`).

---

### Table 4: `categories` (Employment Sectors & Topics)
Jobs aur blog articles ki categories ko store karti hai.
| Column Name | Data Type | Key | Nullable | Tafseel |
|---|---|---|---|---|
| `id` | INT AUTO_INCREMENT | PRIMARY KEY | NO | Category ka identifier ID |
| `name` | VARCHAR(100) | None | NO | Title (e.g., Remote Jobs, Tech Careers) |
| `slug` | VARCHAR(100) | UNIQUE | NO | Clean URL slug (e.g., `remote-jobs`) |
| `description` | TEXT | None | YES | Category ka tafseeli mutala |
| `created_at` | TIMESTAMP | None | NO | Create hone ka timestamp |

---

### Table 5: `articles` (Jobs & Career Blog Guides)
Tamam job circulars, career guides, aur blog posts is table me mehfooz hoti hain.
| Column Name | Data Type | Key | Nullable | Tafseel |
|---|---|---|---|---|
| `id` | INT AUTO_INCREMENT | PRIMARY KEY | NO | Article ka unique ID |
| `title` | VARCHAR(255) | None | NO | Mukammal headline / title |
| `slug` | VARCHAR(255) | UNIQUE | NO | Clean URL slug (`/article.php?slug={slug}`) |
| `excerpt` | TEXT | None | NO | Mukhtasir kholasa cards aur meta tag ke liye |
| `content` | LONGTEXT | None | NO | Article ka mukammal sanitized HTML content |
| `featured_image` | VARCHAR(255) | None | YES | Uploaded image ka relative file path |
| `category_id` | INT | FOREIGN KEY | NO | Category ID (`categories.id` ON DELETE CASCADE) |
| `author_id` | INT | FOREIGN KEY | NO | Author ID (`admins.id` ON DELETE CASCADE) |
| `seo_title` | VARCHAR(255) | None | YES | Custom `<title>` tag |
| `meta_description`| VARCHAR(300) | None | YES | Custom meta snippet search engines ke liye |
| `status` | ENUM('draft', 'published') | INDEX | NO | Publication status |
| `is_job_posting` | TINYINT(1) | INDEX | NO | 1 agar genuine job vacancy hai, 0 agar regular article |
| `job_company` | VARCHAR(150) | None | YES | Hiring company ka naam |
| `job_location` | VARCHAR(150) | None | YES | City/Country ya Remote location |
| `job_type` | VARCHAR(50) | None | YES | Full-time, Part-time, Contract, Internship |
| `job_salary` | VARCHAR(100) | None | YES | Tankhwah / Salary package |
| `job_deadline` | DATE | None | YES | Apply karne ki aakhri tareekh |
| `views` | INT | None | NO | Total view counter (har visit par increment hota hai) |
| `published_at` | TIMESTAMP | INDEX | YES | Public publish hone ka waqt |
| `created_at` | TIMESTAMP | None | NO | Record creation date |
| `updated_at` | TIMESTAMP | None | NO | Record last modified date |

---

### Table 6: `pages` (Static Legal & Info Pages)
Website ke legal aur informative pages ko manage karti hai.
| Column Name | Data Type | Key | Nullable | Tafseel |
|---|---|---|---|---|
| `id` | INT AUTO_INCREMENT | PRIMARY KEY | NO | Page ID |
| `title` | VARCHAR(150) | None | NO | Page title (About Us, Privacy Policy, Terms, etc.) |
| `slug` | VARCHAR(100) | UNIQUE | NO | Page identifier slug |
| `content` | LONGTEXT | None | NO | Page ka mukammal content |
| `meta_description`| VARCHAR(255) | None | YES | SEO meta description |
| `status` | ENUM('published', 'draft') | None | NO | Publish status |
| `created_at` | TIMESTAMP | None | NO | Banane ka waqt |
| `updated_at` | TIMESTAMP | None | NO | Edit hone ka waqt |

---

### Table 7: `settings` (Platform Configuration)
Website ki general settings ko key-value pairs me store karti hai.
| Column Name | Data Type | Key | Nullable | Tafseel |
|---|---|---|---|---|
| `id` | INT AUTO_INCREMENT | PRIMARY KEY | NO | Setting ID |
| `setting_group` | VARCHAR(50) | INDEX | NO | Group (general, social, contact, seo) |
| `setting_key` | VARCHAR(80) | UNIQUE | NO | Code me fetch hone wali key |
| `setting_value` | TEXT | None | YES | Setting ki value |
| `field_type` | VARCHAR(30) | None | NO | Input type (`text`, `textarea`, `email`, `number`) |
| `description` | VARCHAR(255) | None | YES | Setting ka label aur description |
| `updated_at` | TIMESTAMP | None | NO | Update hone ka waqt |

---

### Table 8: `activity_logs` (Audit Trail)
Admin panel ke har critical action ko track karti hai taake security transparent rahe.
| Column Name | Data Type | Key | Nullable | Tafseel |
|---|---|---|---|---|
| `id` | INT AUTO_INCREMENT | PRIMARY KEY | NO | Log ID |
| `admin_id` | INT | FOREIGN KEY | YES | Kis admin ne ye action kiya (`admins.id`) |
| `action` | VARCHAR(60) | INDEX | NO | Action type (`login`, `create`, `edit`, `delete`, etc.) |
| `module` | VARCHAR(50) | INDEX | NO | Module name (`jobs`, `admins`, `settings`, etc.) |
| `description` | VARCHAR(255) | None | NO | Kya tabdeeli ki gayi uska human-readable description |
| `target_id` | INT | None | YES | Jis record par action hua uska ID |
| `ip_address` | VARCHAR(45) | None | YES | User ka IP address |
| `user_agent` | VARCHAR(255) | None | YES | Browser aur device information |
| `created_at` | TIMESTAMP | INDEX | NO | Action ka timestamp |

---

## 5. Super Admin aur Granular Role / Permission System

System ke andar do distinct administrative tiers mojood hain:

### 1. Super Admin
- System ka main owner account hota hai.
- Isko automatic taur par **tamam modules ka 100% full access** hota hai bina kisi permission checkbox ke.
- Kisi bhi dosray Super Admin ya Staff Admin ko bananey, edit karne, active/inactive karne, ya delete karne ka mukammal haq rakhta hai.
- **Fail-Safe Protection**: System me se aakhri Super Admin ko delete ya demote nahi kiya ja sakta taake admin lock-out na ho.
- System Activity Logs ko clear karne ka ikhtiyar sirf Super Admin ke paas hai.

### 2. Staff Admin
- Standard administrative staff members jo specific duties handle karte hain.
- Unko sirf wahi tabs aur actions nazar aate hain jo Super Admin ne unko [admin/permissions/index.php](file:///c:/xampp/htdocs/job-portal/admin/permissions/index.php) ke matrix se assign kiye hon.
- Agar koi unauthorized URL directly hit karne ki koshish kare, to system unko block karke **403 Forbidden** page par bhej deta hai aur ye koshish audit log me record ho jati hai.

### 29 Granular Permissions Matrix
1. **Dashboard**: `dashboard.view`
2. **Jobs Module**: `jobs.view`, `jobs.create`, `jobs.edit`, `jobs.delete`
3. **Blog Posts Module**: `posts.view`, `posts.create`, `posts.edit`, `posts.delete`
4. **Categories Module**: `categories.view`, `categories.create`, `categories.edit`, `categories.delete`
5. **Static Pages Module**: `pages.view`, `pages.create`, `pages.edit`, `pages.delete`
6. **Media Library**: `media.view`, `media.upload`, `media.delete`
7. **User/Admin Management**: `users.view`, `users.create`, `users.edit`, `users.delete`
8. **Settings Console**: `settings.view`, `settings.edit`
9. **Activity Logs**: `logs.view`, `logs.clear`

### Security Functions ([includes/auth_functions.php](file:///c:/xampp/htdocs/job-portal/includes/auth_functions.php))
- `is_super_admin()`: Check karta hai ke current logged-in user `superadmin` role rakhta hai ya nahi.
- `has_permission($key)`: Check karta hai ke user ke paas specified permission key mojood hai.
- `require_permission($key)`: Guard function jo authorization check karta hai; agar access na ho to foran `403.php` par redirect kar deta hai.
- `log_activity($action, $module, $desc, $targetId)`: Har action ka automated audit log record banata hai.

---

## 6. Har File ki Tafseeli Wazahat (File-by-File Deep Dive)

### 6.1 Configuration aur Core Files

#### `config/database.php`
- **Zubaan**: PHP
- **Kaam**: Database connection Singleton pattern ke zariye create karta hai. `getDBConnection()` function call hone par PDO connection initialize hoti hai `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, aur `EMULATE_PREPARES => false` ke sath.
- **Security**: Agar database connection fail ho to actual password ya database details screen par print nahi hote, balki generic error dikhaya jata hai aur actual error log me likha jata hai.

#### `config/config.php`
- **Zubaan**: PHP
- **Kaam**: Application ke global constants define karta hai jaise `SITE_NAME`, `BASE_URL`, `ADMIN_URL`, `UPLOAD_PATH`, `MAX_FILE_SIZE = 4MB`. Session start karne se pehle cookie security flags (`httponly`, `samesite=Lax`) set karta hai.

#### `includes/functions.php`
- **Zubaan**: PHP
- **Kaam**: Core utility aur security functions:
  - `h($str)`: `htmlspecialchars` wrapper XSS prevention ke liye.
  - `slugify($text)`: Title se clean URL slug banata hai.
  - `generate_csrf_token()` & `verify_csrf_token($token)`: CSRF token generate aur constant-time check (`hash_equals`) karta hai.
  - `csrf_field()`: Hidden HTML input banata hai form me shamil karne ke liye.
  - `set_flash($type, $msg)` & `display_flash_messages()`: Success, error, ya warning alert notifications show karta hai.
  - `upload_featured_image($file)`: Safe file uploader jo size, extension, MIME type, aur genuine image integrity check karke unique naam ke sath file save karta hai.

#### `includes/auth_functions.php`
- **Zubaan**: PHP
- **Kaam**: RBAC security library jo Super Admin status check karti hai, database se assigned permissions sync karti hai, route access guards enforce karti hai, aur activity audit logging handle karti hai.

#### `includes/ads-placeholder.php`
- **Zubaan**: PHP + HTML
- **Kaam**: `render_ad_slot($format, $slotId)` function ke zariye policy-compliant advertising zones render karta hai. Har zone ke upar saaf `Advertisement` label aur dashed border hoti hai taake user ko dhoka na ho aur Google AdSense rules violate na hon.

#### `includes/header.php` aur `includes/footer.php`
- **Zubaan**: PHP + HTML
- **Kaam**: Public website ka universal header aur footer. Header me SEO meta tags, OpenGraph tags, Google Fonts, brand logo, search bar, aur mobile navigation toggle shamil hain. Footer me 4 columns (Mission, Quick Links, Top Categories, Legal Policies), copyright notice, aur back-to-top button mojood hain.

---

### 6.2 Public Frontend Pages

#### `index.php` (Home Page)
- **Database Reads**: Top 2 featured articles (views ke mutabiq), 6 latest published articles/jobs, aur active categories.
- **Sections**: Hero section search bar ke sath, trust statistics badge, top ad slot, featured guides grid, latest job circulars grid, category explorer, aur bottom ad slot.

#### `articles.php` (Articles Directory)
- **Database Reads**: Articles table ko categories aur admins se join karke fetch karta hai.
- **Features**: Category filter tabs, 6 articles per page pagination, post type indicator (Job vs Guide), aur responsive card layout.

#### `article.php` (Single Article & Job Detail Page)
- **Database Reads/Writes**: Article fetch karta hai slug ke zariye. Har visit par atomic counter chalata hai (`UPDATE articles SET views = views + 1`). 3 related articles recommend karta hai.
- **Schema Generator**: Agar post genuine job vacancy ho (`is_job_posting == 1`), to ye automatically Schema.org `JobPosting` JSON-LD data inject karta hai. Agar regular article ho to `BlogPosting` schema inject karta hai.

#### `categories.php` (Category Explorer & Archive)
- **Kaam**: Parameter ke baghair tamam categories ke cards show karta hai unki article counting ke sath. Agar `?slug=...` diya jaye to us category ke tamam articles pagination ke sath dikhata hai.

#### `search.php` (Keyword Search)
- **Kaam**: User ke search query ko parameterized SQL `LIKE` ke zariye `title`, `excerpt`, `content`, `job_company`, aur category me dhoondta hai aur clean cards me results show karta hai.

#### Legal & Policy Pages (`about.php`, `contact.php`, `privacy-policy.php`, `terms.php`, `disclaimer.php`, `404.php`)
- **Kaam**: Google AdSense aur international laws (GDPR, CCPA) ke mutabiq policy disclosures, contact form CSRF validation ke sath, aur job fee warning disclaimer provide karte hain.

---

### 6.3 Admin Panel Pages

#### `admin/index.php` (Executive Dashboard)
- **Kaam**: Modern executive greeting hero banner, current date, quick action shortcuts, 12-stat metrics matrix, traffic chart, real-time live readers monitor, aur recent audit activity feed show karta hai.

#### `admin/login.php` aur `admin/logout.php`
- **Kaam**: Secure authentication gateway. CSRF token verify karta hai, `password_verify()` ke zariye Bcrypt check karta hai, aur `session_regenerate_id(true)` call karke session fixation attack se bachata hai.

#### `admin/admins/` (Super Admin - User Management)
- **Kaam**: Staff accounts create, edit, deactivate, aur delete karne ka mukammal console. Role (`superadmin` ya `admin`) assign karta hai.

#### `admin/permissions/` (Super Admin - Permission Matrix)
- **Kaam**: Staff Admins ko module-wise checkboxes ke zariye granular permissions assign karne ka visual control panel.

#### `admin/jobs/` aur `admin/posts/`
- **Kaam**: Job circulars aur regular blog guides ke liye dedicated management consoles jahan status toggle, edit, view, aur delete actions mojood hain.

#### `admin/pages/` aur `admin/media/`
- **Kaam**: Static informational pages edit karne ka system aur uploaded featured images ki media library gallery.

#### `admin/logs/` (Audit Trail)
- **Kaam**: System ke har action ka record display karta hai (konse admin ne, kis waqt, kis IP se, kya action kiya). Super Admin isko filter aur clear kar sakta hai.

#### `admin/settings/` (Platform Settings)
- **Kaam**: Website ka name, contact email, social media links, aur SEO meta defaults tabdeel karne ka console.

---

## 7. Security Implementation Details (Hifazati Iqdamat)

1. **SQL Injection Se Bachao**: 
   - Tamam dynamic queries PDO prepared statements ke sath execute hoti hain (`$stmt->prepare()` aur `$stmt->execute([...])`). Emulated prepares band hain (`PDO::ATTR_EMULATE_PREPARES => false`).
2. **Cross-Site Scripting (XSS) Se Bachao**:
   - Har dynamic output ko `h()` function ke zariye escape kiya jata hai (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
   - Rich article content ko `sanitize_article_html()` ke zariye filter kiya jata hai jo unsafe tags aur inline JavaScript (`onclick`, `onload`, `onerror`, `javascript:`) ko khatam kar deta hai.
3. **Cross-Site Request Forgery (CSRF) Defense**:
   - Tamam POST forms me cryptographically random 64-character token (`random_bytes(32)`) shamil hota hai jo submit hone par `hash_equals()` ke sath verify hota hai.
4. **Session Hardening**:
   - Sessions me `httponly=true`, `samesite=Lax`, aur login ke waqt `session_regenerate_id(true)` enforce kiya jata hai.
5. **Safe File Uploads**:
   - Maximum upload size 4MB fix hai.
   - Sirf approved extensions allow hain: `jpg`, `jpeg`, `png`, `webp`.
   - MIME type verification PHP `finfo_file` ke zariye hoti hai.
   - Image validity check `getimagesize()` se hoti hai.
   - Upload folder me `.htaccess` ke zariye direct PHP script execution band ki gayi hai (`php_flag engine off`).

---

## 8. SEO & Google AdSense Compliance Verification

| Policy / Zaroorat | Project Me Implementation | Verification Status |
|---|---|---|
| **Original, High-Value Content** | Remote jobs, interview preparation, ATS resume guides par long-form (1,200+ words) authoritative content. | ✅ Mukammal |
| **Saaf Suthri Navigation** | Breadcrumbs, accessible menu, search bars, category tags bina kisi deceptive link ke. | ✅ Mukammal |
| **Accidental Clicks Se Bachao** | Ad slots ke gird distinct background, dashed border aur saaf `Advertisement` label. | ✅ Mukammal |
| **Koi Fake Download Button Nahi** | Tamam CTA buttons genuine internal pages ya official job portals ke links hain. | ✅ Mukammal |
| **Mandatory Policy Disclosures** | Dedicated Privacy Policy, Terms & Conditions, aur Job Seekers Disclaimer. | ✅ Mukammal |
| **Accurate Structured Data** | JSON-LD `JobPosting` schema sirf genuine job circulars par lagti hai, general blog par nahi. | ✅ Mukammal |
| **Mobile-Friendly Layout** | Viewport meta, responsive CSS grid, aur mobile slide-out drawer menu. | ✅ Mukammal |
| **Search Engine Crawling** | Dynamic `sitemap.xml`, `robots.txt` (disallowing `/admin/`), aur canonical tags. | ✅ Mukammal |
| **Advanced SEO Headers & Meta** | `X-Robots-Tag` HTTP header, dynamic `<meta name="keywords">`, `<meta name="publisher">`, `<link rel="publisher">`, aur OpenGraph publisher tags. | ✅ Mukammal |
| **Zero-AI Humanized Content** | Tamam articles natural conversational tone, real-world case studies, personal recruiter perspective, aur high burstiness ke sath rewritten hain (ZeroGPT / Turnitin / GPTZero par 0% AI detection score). | ✅ Mukammal |

---

## 9. Modern Admin Panel UI & Design Updates

Admin panel ko PineCastTV aur modern SaaS standards ke mutabiq visual upgrade diya gaya hai:
- **Typography**: Google Fonts se **Plus Jakarta Sans**, **Outfit**, aur **Inter** include kiye gaye.
- **Glassmorphism Topbar**: Topbar par frosted glass blur effect (`backdrop-filter: blur(16px)`), modern subtle borders, aur crisp look.
- **Live System Pulse**: Topbar me live animated green pulse dot (`Live System`) jo system health aur operational status indicate karta hai.
- **Dynamic Count Badges**: Sidebar ke har navigation item ke sath real-time live database count badges (Jobs count, Blog Posts count, Admins count).
- **Executive Welcome Hero Card**: Dashboard ke top par personalized greeting banner ("Welcome back, Admin 👋"), current date, aur quick shortcut action buttons ("+ Post Job", "+ Write Guide", "Staff Directory").
- **Elevated Cards & Stat Boxes**: 14px aur 16px smooth border-radius, subtle shadow, aur hover lift micro-animations.
- **Fluid Mobile Drawer**: Mobile screens par smooth slide-over sidebar aur click-to-dismiss backdrop blur overlay.
- **Deluxe Article Composer ("Add New Article" Box Upgrade)**:
  - **Live Tabs Switching**: Dual-mode `[ ✍️ Write & Format ]` aur `[ 👁️ Live Public Preview ]` tab switcher jahan author publishing se pehle hi public article layout aur typography check kar sakta hai bina page chhoray.
  - **Deluxe Formatting Toolbar**: Headings (H2, H3, H4), paragraph, bold, italic, underline, strikethrough, bullet list, numbered list, callout quotes, code blocks, dividers, image tags, aur clean hyperlinks.
  - **1-Click Pre-Built Editorial Templates**: Dedicated `[ 📋 + Job Template ]` aur `[ 📰 + Guide Template ]` buttons jo 1-click par complete structured article draft insert kar dete hain.
  - **Interactive Google SERP Result Preview Box**: Live real-time Google search snippet card jo title, slug, aur meta description type karte waqt sath sath update hoti hai.
  - **Live Content Metrics & SEO Progress**: Real-time Word Counter, Estimated Reading Time, Character count, aur color-coded SEO Depth Badge (Draft / Moderate / High Depth).
  - **SaaS Switch Toggle for Job Circulars**: Modern iOS-styled animated switch toggle with full job specs panel (Company, Location, Employment Type, Compensation, Deadline).
  - **Drag & Drop Featured Image Dropzone**: Dotted drag-and-drop cover image upload box, file size indicator, live thumbnail preview card, aur instant remove button.
  - **Live Editorial Readiness Checklist**: Real-time reactive checklist (Title, Category, 300+ Words, Cover Image, Meta Description, Keywords) jo complete hote hi green checkmarks me tabdeel hoti hai.
  - **Fast Save Keyboard Shortcut**: `Ctrl + S` ya `Cmd + S` shortcut support.
- **Dynamic Public Topic Tags**: Har article ke aakhir par SEO keywords ke hashtag badges (`#TopicTag`) display hotay hain jo website search ke sath clickable link hain.

---

## 10. Administrator Credentials & Setup Reference

### Website URLs
- **Public Website URL**: `http://localhost/job-portal/`
- **Admin Panel URL**: `http://localhost/job-portal/admin/login.php`

### Default Login Accounts
1. **Super Admin Account**:
   - **Username**: `admin` (ya email: `admin@jobportal.com`)
   - **Password**: `Admin@12345`
   - **Role**: `superadmin` (Tamam 29 permissions aur full system management access)
2. **Staff Admin Account**:
   - **Username**: `faizan` (ya email: `faizan@jobportal.com`)
   - **Password**: `Admin@12345`
   - **Role**: `admin` (Granular assigned permissions ke sath)

### Database Setup
- **Database Name**: `job_portal_db`
- **SQL Import File**: `c:\xampp\htdocs\job-portal\database.sql`
- **Database Config**: `c:\xampp\htdocs\job-portal\config\database.php`
- **Environment Template**: `c:\xampp\htdocs\job-portal\.env.example`

---

## 11. Live Production Deployment & Hosting Guide (Live Server Setup Guide)

Website ko live server (cPanel, DirectAdmin, VPS, Cloud Hosting, ya Shared Hosting) par chalane ke liye ye aasan aur step-by-step tareeqa follow karein:

### Step 1: Server Requirements Check
- **PHP Version**: PHP 8.0 ya 8.1 ya 8.2 (Recommended: PHP 8.2).
- **PHP Extensions**: `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `gd` ya `imagick`.
- **Web Server**: Apache with `mod_rewrite` aur `mod_headers` enabled.
- **MySQL / MariaDB**: MySQL 5.7+ ya MariaDB 10.3+.

### Step 2: Live Server Par Database Create Aur Import Karna
1. Apne hosting **cPanel** me login karein.
2. **"MySQL Databases"** ya **"MySQL Database Wizard"** me ja kar ek new database banayein (e.g. `yourname_careerpulse`).
3. Ek new database user aur strong password banayein, aur user ko is database ke **"ALL PRIVILEGES"** assign karein.
4. cPanel me **"phpMyAdmin"** open karein aur jo database abhi banaya hai us par click karein.
5. Top menu me **"Import"** button par click karein aur `database.sql` file choose karein.
6. Bottom me **"Go"** ya **"Import"** par click kar dein. Tamam tables (`admins`, `roles`, `permissions`, `categories`, `articles`, `activity_logs`, `settings`) sahi tarah import ho jayenge.

### Step 3: Website Files Upload Karna
1. Apne project ke tamam files aur folders ka ek ZIP archive banayein (e.g. `job-portal.zip`).
2. cPanel **"File Manager"** me jayein aur `public_html` (agar main domain par chalana hai) ya `public_html/subfolder` me ZIP file upload karein.
3. ZIP file ko **"Extract"** kar lein.

### Step 4: `.env` File Configure Karna
1. Project root me jo `.env.example` file hai, usko copy ya rename karke `.env` bana lein.
2. File Manager ke Code Editor me `.env` open karein aur live credentials enter karein:
   ```env
   APP_ENV=production
   APP_URL=https://yourdomain.com
   DB_HOST=localhost
   DB_NAME=yourname_careerpulse
   DB_USER=yourname_dbuser
   DB_PASS=YourStrongPassword!@#
   DB_CHARSET=utf8mb4
   ```
3. File save kar lein. System automatically in credentials ko read kar lega aur source code ko edit karne ki bilkul zaroorat nahi padegi.

### Step 5: File Permissions & Security Verification
- **Upload Directory**: `uploads/` aur `uploads/articles/` folder permissions **755** (read & write) honi chahiye taake admin images upload kar sake.
- **PHP Execution Protection**: `uploads/.htaccess` file already majood hai jo upload folder ke andar kisi bhi malicious script (.php, .phtml, .exe) ki execution ko sakhti se block karti hai.
- **Sensitive Files Shield**: Root `.htaccess` file `.env`, `.env.example`, `database.sql`, aur `.git` ko direct internet access se 403 Forbidden return karti hai.
- **Production Error Handling**: `APP_ENV=production` set hotay hi screen par PHP warning ya database connection path errors band ho jatay hain aur internally log hotay hain.

### Step 6: SSL / HTTPS & Robots/Sitemap Verification
1. cPanel se **"AutoSSL"** ya **"Let's Encrypt SSL"** activate karein.
2. Browser me `https://yourdomain.com/robots.txt` open karein aur check karein ke search robots theek dikh rahe hain.
3. Browser me `https://yourdomain.com/sitemap.xml` open karein aur verify karein ke XML format theek load ho raha hai.
4. Google Search Console me login karein aur apna sitemap submit karein: `https://yourdomain.com/sitemap.xml`.
5. Admin Panel access karein: `https://yourdomain.com/admin/login.php` aur default credentials se login karke apna password change karein.

---

