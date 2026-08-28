# Phân Tích Backend Moodle 5.0 — Phần 1: Kiến trúc & Cấu trúc

> **Phiên bản:** Moodle 5.0.6+ (Build: 20260306) | **Ngôn ngữ:** PHP ≥ 8.2 | **~29,000 files**

---

## 1. Xác Định Cấu Trúc Backend

### Backend nằm ở đâu?
Toàn bộ project **là backend** — Moodle là ứng dụng PHP server-rendered monolith. Không có tách riêng frontend/backend.

### Framework & Ngôn ngữ
- **PHP 8.2+** — custom framework tự xây dựng (không dùng Laravel/Symfony làm base)
- **JavaScript (AMD/YUI)** cho UI interaction
- **Mustache templates** cho rendering

### Entry Points

| Entry Point | Chức năng |
|---|---|
| `index.php` | Trang chủ (frontpage) |
| `login/index.php` | Đăng nhập |
| `config.php` → `lib/setup.php` | Bootstrap toàn hệ thống |
| `admin/index.php` | Admin panel & upgrade |
| `admin/cli/cron.php` | Cron scheduler |
| `webservice/rest/server.php` | REST API endpoint |
| `webservice/soap/server.php` | SOAP API endpoint |
| `pluginfile.php`, `file.php` | File serving |
| `login/token.php` | Token generation cho mobile/WS |

### Cây thư mục rút gọn

```
moodle/
├── admin/              # Admin UI, CLI tools, settings
│   ├── cli/            # 31 CLI scripts (cron, upgrade, backup...)
│   ├── settings/       # Admin settings pages
│   └── tool/           # Admin tools (plugins)
├── auth/               # Authentication plugins
│   ├── db/             # Database auth
│   ├── email/          # Email-based self-registration
│   ├── ldap/           # LDAP auth
│   ├── lti/            # LTI auth
│   ├── manual/         # Manual accounts
│   ├── oauth2/         # OAuth2 auth
│   └── shibboleth/     # Shibboleth SSO
├── lib/                # ★ CORE LIBRARY (~29K lines key files)
│   ├── setup.php       # Bootstrap (1214 lines)
│   ├── moodlelib.php   # Core functions (370K bytes!)
│   ├── accesslib.php   # Authorization (190K bytes)
│   ├── enrollib.php    # Enrolment (141K bytes)
│   ├── filelib.php     # File handling (211K bytes)
│   ├── datalib.php     # Data access helpers
│   ├── authlib.php     # Auth plugin base class
│   ├── classes/        # Core PHP classes (PSR-4)
│   │   ├── task/       # Scheduled & adhoc tasks
│   │   ├── event/      # Event system
│   │   ├── session/    # Session handlers
│   │   ├── output/     # Renderers
│   │   └── ...
│   ├── db/
│   │   ├── install.xml # ★ DB schema (257 core tables!)
│   │   ├── services.php# ★ Web service definitions (401 core APIs)
│   │   ├── tasks.php   # Scheduled task definitions
│   │   ├── upgrade.php # DB migrations
│   │   └── access.php  # Capability definitions
│   ├── dml/            # Database abstraction layer
│   ├── ddl/            # Database definition layer
│   └── form/           # Form library (Moodle Forms API)
├── mod/                # ★ Activity Modules (23 built-in)
│   ├── assign/         # Assignments
│   ├── quiz/           # Quizzes
│   ├── forum/          # Forums
│   ├── lesson/         # Lessons
│   └── ...
├── course/             # Course management
├── enrol/              # Enrolment plugins
├── blocks/             # Block plugins (sidebar widgets)
├── theme/              # Theme plugins
├── question/           # Question engine
├── grade/              # Gradebook
├── user/               # User profile management
├── message/            # Messaging system
├── calendar/           # Calendar system
├── badges/             # Badges/achievements
├── competency/         # Competency framework
├── analytics/          # Learning analytics
├── webservice/         # Web service framework
├── cache/              # Moodle Universal Cache (MUC)
├── backup/             # Backup/restore system
├── report/             # Report plugins
├── repository/         # File repository plugins
├── payment/            # Payment gateway
├── search/             # Global search
├── ai/                 # AI subsystem (Moodle 5.0+)
├── privacy/            # GDPR privacy API
├── login/              # Login/logout/signup pages
├── config-dist.php     # Config template (→ config.php)
└── version.php         # Version info
```

### Files cấu hình
- `config.php` (tạo từ `config-dist.php`) — DB, paths, secrets
- `lib/db/install.xml` — Database schema
- `lib/db/services.php` — Web service registry
- `lib/db/tasks.php` — Scheduled tasks
- `lib/db/access.php` — Capabilities/permissions
- `phpunit.xml.dist`, `behat.yml.dist` — Testing config
- `composer.json` — PHP dependencies

### Files core/business logic
- `lib/moodlelib.php` (370KB) — Core utility functions
- `lib/accesslib.php` (190KB) — RBAC authorization engine
- `lib/enrollib.php` (141KB) — Enrolment logic
- `lib/completionlib.php` (75KB) — Course completion
- `lib/gradelib.php` (66KB) — Gradebook logic
- `lib/filelib.php` (212KB) — File management

---

## 2. Phân Tích Kiến Trúc

### Kiến trúc: **Modular Monolith + Plugin Architecture**

Moodle sử dụng kiến trúc **monolith có hệ thống plugin mạnh mẽ**, KHÔNG phải MVC truyền thống.

```
┌─────────────────────────────────────────────────┐
│                  HTTP Request                    │
├─────────────────────────────────────────────────┤
│  config.php → lib/setup.php (Bootstrap)          │
│  ┌─────────────────────────────────────────┐     │
│  │  Global Objects: $CFG, $DB, $USER,      │     │
│  │  $PAGE, $OUTPUT, $SESSION, $COURSE      │     │
│  └─────────────────────────────────────────┘     │
├─────────────────────────────────────────────────┤
│  Page Script (e.g. course/view.php)              │
│    ├── require_login() / require_capability()    │
│    ├── Business Logic (lib functions + classes)   │
│    ├── $DB->get_records() (DML Layer)            │
│    └── $OUTPUT->header() + echo + footer()       │
├─────────────────────────────────────────────────┤
│  Database (PostgreSQL/MySQL/MariaDB/MSSQL)       │
└─────────────────────────────────────────────────┘
```

### Pattern thực tế (KHÔNG phải MVC chuẩn)

```
Request
  ↓
Page Script (PHP file = cả Controller + View logic)
  ↓
require_login() → Session check → Auth plugin chain
  ↓
require_capability() → accesslib RBAC check
  ↓
Library functions (xxxlib.php) = Service layer
  ↓
$DB->xxx() (DML) = Direct DB access (không có Repository pattern)
  ↓
Renderer ($OUTPUT) + Mustache templates = View
  ↓
HTML Response
```

> **Quan trọng:** Moodle KHÔNG tách Controller/Service/Repository rõ ràng. Mỗi PHP page script vừa là controller, vừa gọi trực tiếp `$DB`, vừa render output. Đây là thiết kế có chủ đích từ năm 1999, tối ưu cho khả năng mở rộng bằng plugin.

---

## 3. Phân Tích API (Web Services)

### Tổng quan
- **401 core web service functions** trong `lib/db/services.php`
- Protocol: **REST** (`webservice/rest/server.php`) và **SOAP** (`webservice/soap/server.php`)
- Authentication: **Token-based** (lấy từ `login/token.php`)

### Các API domain chính (trích)

| Domain | Số API | Ví dụ |
|---|---|---|
| `core_course` | ~30+ | `get_courses`, `create_courses`, `get_contents` |
| `core_user` | ~15+ | `get_users`, `create_users`, `update_users` |
| `core_enrol` | ~10+ | `get_enrolled_users`, `enrol_users` |
| `core_message` | ~20+ | `send_instant_messages`, `get_conversations` |
| `core_calendar` | ~15+ | `get_calendar_events`, `create_calendar_events` |
| `core_completion` | ~5+ | `get_activities_completion_status` |
| `core_grades` | ~5+ | `get_grades`, `update_grades` |
| `core_auth` | ~5 | `confirm_user`, `request_password_reset` |
| `core_blog` | ~6 | `get_entries`, `add_entry` |
| `core_badges` | ~5 | `get_user_badges`, `enable_badges` |
| `mod_assign` | ~10+ | `get_assignments`, `submit_for_grading` |
| `mod_forum` | ~10+ | `get_forums_by_courses`, `add_discussion` |
| `mod_quiz` | ~10+ | `get_quizzes_by_courses`, `get_attempt_data` |

### Cấu trúc một API definition

```php
// lib/db/services.php
'core_course_get_courses' => [
    'classname'    => 'core_course_external',      // Handler class
    'methodname'   => 'get_courses',               // Method
    'classpath'    => 'course/externallib.php',     // File path
    'description'  => 'Return course details',
    'type'         => 'read',                       // read|write
    'capabilities' => 'moodle/course:view',         // Required capability
    'ajax'         => true,                         // Callable via AJAX
    'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE], // Mobile app
];
```

---

## 4. Phân Tích Business Domains

### Các domain chính

#### 1. User Management
- **Entity:** `user` table (71 fields)
- **Lib:** `lib/classes/user.php` (71KB), `user/lib.php`
- **Auth:** `auth/` plugins (db, email, ldap, oauth2, shibboleth)
- **Rules:** Username uniqueness, password policy, email verification

#### 2. Course Management
- **Entity:** `course`, `course_categories`, `course_sections`, `course_modules`
- **Lib:** `course/lib.php`, `lib/modinfolib.php`
- **Rules:** Category hierarchy, course visibility, format plugins

#### 3. Enrolment
- **Entity:** `enrol`, `user_enrolments`
- **Lib:** `lib/enrollib.php` (141KB)
- **Plugins:** `enrol/manual`, `enrol/self`, `enrol/cohort`, `enrol/guest`
- **Rules:** Enrolment periods, capacity limits, role assignment on enrol

#### 4. Gradebook
- **Entity:** `grade_items`, `grade_grades`, `grade_categories`
- **Lib:** `lib/gradelib.php`, `grade/lib.php`
- **Rules:** Grade calculation, aggregation methods, grade letters

#### 5. Activity Modules (23 built-in)
- Assignment (`mod/assign`), Quiz (`mod/quiz`), Forum (`mod/forum`), etc.
- Each has: `lib.php`, `mod_form.php`, `view.php`, `db/install.xml`

#### 6. Completion Tracking
- **Entity:** `course_completions`, `course_modules_completion`
- **Lib:** `lib/completionlib.php`
- **Rules:** Manual/automatic tracking, grade-based, activity view

#### 7. Messaging & Notifications
- **Entity:** `messages`, `message_conversations`, `notifications`
- **Lib:** `lib/messagelib.php`, `message/lib.php`

#### 8. Calendar
- **Entity:** `event`
- **Lib:** `calendar/lib.php`

#### 9. Badges
- **Entity:** `badge`, `badge_issued`
- **Lib:** `lib/badgeslib.php`

#### 10. File Storage
- **Entity:** `files` table (content-addressable storage)
- **Lib:** `lib/filelib.php`, `lib/filestorage/`
- Files stored in `$CFG->dataroot/filedir/` by content hash

---

## 5. Phân Tích Database

### Database hỗ trợ
- PostgreSQL (recommended), MySQL/MariaDB, MS SQL Server, Aurora MySQL
- Config: `$CFG->dbtype` trong `config.php`

### ORM/Query Builder
- **Không có ORM truyền thống**
- Sử dụng **DML (Data Manipulation Language)** layer: `lib/dml/`
- API: `$DB->get_record()`, `$DB->insert_record()`, `$DB->execute()`, etc.
- **DDL layer** cho schema: `lib/ddl/`
- **XMLDB** cho schema definition: mỗi plugin có `db/install.xml`

### Số bảng: **257 bảng core** (trong `lib/db/install.xml`)
Mỗi plugin thêm bảng riêng → tổng có thể **400+ bảng**.

### Sơ đồ quan hệ chính

```
User
├── 1:N → user_enrolments (qua enrol)
├── 1:N → role_assignments
├── 1:N → messages
├── 1:N → notifications
├── 1:N → course_completions
├── 1:N → grade_grades
├── 1:N → badge_issued
└── 1:N → user_preferences

Course
├── N:1 → course_categories
├── 1:N → course_sections
│        └── 1:N → course_modules
├── 1:N → enrol
│        └── 1:N → user_enrolments
├── 1:N → context (CONTEXT_COURSE)
├── 1:N → grade_items
├── 1:N → course_completions
└── 1:N → event (calendar)

course_modules
├── N:1 → course
├── N:1 → modules (module type registry)
├── 1:1 → [mod_xxx table] (e.g. assign, quiz, forum)
├── 1:N → course_modules_completion
└── 1:1 → context (CONTEXT_MODULE)

Context (hierarchical)
├── SYSTEM (level 10) — root
├── USER (level 30)
├── COURSECAT (level 40)
├── COURSE (level 50)
├── MODULE (level 70)
└── BLOCK (level 80)
    └── 1:N → role_assignments

Role
├── 1:N → role_assignments
├── 1:N → role_capabilities
└── archetypes: manager, coursecreator, editingteacher,
                teacher, student, guest, user, frontpage
```

### Migration System
- `db/upgrade.php` trong mỗi plugin — imperative PHP migrations
- `db/install.xml` — declarative XMLDB schema
- Version tracking: `version.php` per plugin, `config_plugins` table

---

## 6. Authentication & Authorization

### Authentication (Pluggable)

**Cơ chế: Session-based + Plugin chain**

| Plugin | File | Cơ chế |
|---|---|---|
| `auth_manual` | `auth/manual/` | Admin tạo account |
| `auth_email` | `auth/email/` | Self-registration + email confirm |
| `auth_db` | `auth/db/` | External DB lookup |
| `auth_ldap` | `auth/ldap/` | LDAP/Active Directory |
| `auth_oauth2` | `auth/oauth2/` | OAuth2 (Google, Microsoft, etc.) |
| `auth_shibboleth` | `auth/shibboleth/` | Shibboleth SSO |
| `auth_lti` | `auth/lti/` | LTI launch auth |

### Login Flow

```
Client → GET /login/index.php
  ↓
Foreach enabled auth plugin:
  $authplugin->loginpage_hook()  (SSO redirect opportunity)
  ↓
POST username + password + logintoken
  ↓
authenticate_user_login() [lib/moodlelib.php]
  ├── Login token validation (CSRF)
  ├── reCAPTCHA check (optional)
  ├── Foreach auth plugin: $auth->user_login($username, $password)
  ├── Account lockout check
  ├── Password hash verify (bcrypt via password_verify())
  └── Return $user or error code
  ↓
complete_user_login($user) [lib/moodlelib.php]
  ├── Session regeneration
  ├── Load capabilities → $USER->access
  ├── Set MoodleSession cookie
  ├── Event: \core\event\user_loggedin
  └── Redirect to $SESSION->wantsurl
```

### Token cho Web Services
```
Client → POST /login/token.php
  { username, password, service }
  ↓
authenticate_user_login()
  ↓
external_generate_token()
  ↓
Return { token, privatetoken }
  ↓
Client → GET /webservice/rest/server.php
  ?wstoken=xxx&wsfunction=core_course_get_courses
```

### Authorization: **RBAC (Role-Based Access Control)**

**File chính:** `lib/accesslib.php` (190KB, 5194 lines)

```
has_capability('mod/forum:replypost', $context, $userid)
  ↓
Load user's role assignments for context path
  ↓
Walk context tree (Module → Course → Category → System)
  ↓
Aggregate: ALLOW wins unless PROHIBIT exists
  ↓
Return true/false
```

- **Context hierarchy:** System > Category > Course > Module > Block
- **Roles:** manager, coursecreator, editingteacher, teacher, student, guest
- **~700+ capabilities** defined across core + plugins
- `require_capability()` throws exception if denied
- `require_login()` — ensures user is authenticated

### Password Hashing
- `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
- Verified via `password_verify()`
- Managed in `lib/moodlelib.php` → `validate_internal_user_password()`

---

## 7. Middleware / Hooks

Moodle **không có middleware pipeline** như Express/Laravel. Thay vào đó:

| Mechanism | Khi nào | Chức năng |
|---|---|---|
| `require_login()` | Đầu mỗi page script | Auth check, session, enrolment verify |
| `require_capability()` | Trước business logic | RBAC permission check |
| `require_sesskey()` | Form submission | CSRF protection |
| Auth plugin hooks | `loginpage_hook()`, `pre_loginpage_hook()` | SSO intercept |
| Event system | `\core\event\*` | Post-action observers |
| Hook system (Moodle 4.3+) | `lib/db/hooks.php` | Callback-based extensibility |
| Output filters | `lib/filterlib.php` | Content transformation (MathJax, etc.) |

---

## 8. Validation & Error Handling

### Request Validation
- `required_param($name, $type)` / `optional_param($name, $default, $type)`
- PARAM types: `PARAM_INT`, `PARAM_TEXT`, `PARAM_RAW`, `PARAM_URL`, `PARAM_BOOL`, etc.
- Defined in `lib/classes/param.php` (43KB)

### Form Validation
- Moodle Forms API (`lib/formslib.php`, 155KB)
- Server-side `validation()` method per form class
- Client-side validation via JS

### Error Handling
- Global exception handler: `default_exception_handler()` in `lib/setuplib.php`
- `\moodle_exception` — base exception class
- Web services: `early_ws_exception_handler()` returns JSON errors
- HTTP status codes used properly in WS responses
- `debugging()` function for dev warnings

### ⚠️ Vấn đề
- Nhiều page scripts dùng `print_error()` (deprecated) thay vì exceptions
- Error format không 100% consistent giữa page scripts và web services

---

## 9. External Services

| Service | Vị trí | Chức năng |
|---|---|---|
| SMTP Email | `lib/phpmailer/` | Email notifications |
| LDAP | `auth/ldap/` | User directory |
| OAuth2 providers | `lib/classes/oauth2/` | Google, Microsoft SSO |
| LTI | `mod/lti/`, `auth/lti/` | External tool integration |
| BigBlueButton | `mod/bigbluebuttonbn/` | Video conferencing |
| AWS SDK | `lib/aws-sdk/` | S3, etc. |
| MaxMind GeoIP | `lib/maxmind/` | IP geolocation |
| reCAPTCHA | `lib/recaptchalib_v2.php` | Bot prevention |
| H5P hub | `lib/classes/task/h5p_get_content_types_task.php` | Interactive content |
| MoodleNet | `moodlenet/` | Content sharing network |
| Flickr | `lib/flickrlib.php` | Image repository |

---

## 10. Cache & Background Jobs

### Moodle Universal Cache (MUC)
- **Framework:** `cache/` directory
- **Stores:** File (default), Redis, Memcached, MongoDB, APCu
- Config: `lib/db/caches.php` per plugin
- **Dùng cho:** Role definitions, string translations, course modinfo, config values

### Session Storage
- Database (default), File, Redis, Memcached
- Config: `$CFG->session_handler_class` trong `config.php`

### Scheduled Tasks (Cron)
- **48+ core scheduled tasks** trong `lib/db/tasks.php`
- Entry point: `admin/cli/cron.php`
- Framework: `lib/classes/task/` (scheduled_task, adhoc_task)
- Key tasks: session cleanup, grade calculation, completion check, badge award, messaging, backup, search indexing

### Ad-hoc Tasks
- One-off async jobs queued via `\core\task\manager::queue_adhoc_task()`
- Used for: email sending, course deletion, enrolment sync

---

## 11. File & Storage

### Architecture: Content-Addressable Storage

```
Upload → file API (lib/filelib.php)
  ↓
SHA1 hash of content → stored in $CFG->dataroot/filedir/xx/yy/hash
  ↓
Metadata in `files` table (filename, component, filearea, contextid, hash)
  ↓
Serve via pluginfile.php (access-controlled) or file.php
```

- **Draft area:** Temporary uploads before save
- **Components:** Each plugin declares file areas
- Supported: images, documents, videos, SCORM packages, backups
- Virus scanning: `lib/antivirus/` plugin system
