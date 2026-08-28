# Phân Tích Backend Moodle 5.0 — Phần 2: Vận Hành, Bảo Mật & Đánh Giá

---

## 12. Configuration & Environment

### Environment Variables & Config
- **Tất cả config trong `config.php`** (tạo từ `config-dist.php`, 1342 lines)
- Không dùng `.env` files — config là PHP thuần

| Config | Ý nghĩa |
|---|---|
| `$CFG->dbtype` | DB driver: pgsql, mysqli, mariadb, sqlsrv |
| `$CFG->dbhost/dbname/dbuser/dbpass` | DB credentials |
| `$CFG->wwwroot` | Site URL |
| `$CFG->dataroot` | File storage path (outside web root) |
| `$CFG->dirroot` | Auto-set, Moodle installation path |
| `$CFG->admin` | Admin directory name |
| `$CFG->debug` | Debug level |
| `$CFG->session_handler_class` | Session store (DB/File/Redis/Memcached) |

### Environments
- **Dev:** `$CFG->debug = E_ALL; $CFG->debugdisplay = 1;`
- **Prod:** `$CFG->debug = 0; $CFG->debugdisplay = 0;`
- **PHPUnit:** `config.php` + `phpunit.xml.dist`
- **Behat:** `$CFG->behat_wwwroot`, `$CFG->behat_dataroot`

### ⚠️ Security Note
- `config-dist.php` chứa **placeholder credentials** (`'username'`, `'password'`) — đây là template, KHÔNG phải hard-coded secrets
- Actual `config.php` nằm trong `.gitignore`
- Encryption keys: `$CFG->secretdataroot` hoặc `$CFG->dataroot/secret/`

---

## 13. Logging & Monitoring

### Logging
- **Event system:** `lib/classes/event/` — structured event logging
- Standard logstore: `admin/tool/log/` → DB table `logstore_standard_log`
- Legacy: `log` table (deprecated nhưng vẫn tồn tại)
- DB query logging: `$CFG->dboptions['logall']`, `$CFG->dboptions['logslow']`
- PHP error log: standard `error_log()` + `ini_set('log_errors', '1')`

### Performance Monitoring
- `MDL_PERF` constants cho performance profiling
- XHProf integration: `lib/xhprof/`
- Page footer performance info (dev mode)
- `init_performance_info()` in `lib/setup.php`

### Health Check
- `admin/index.php` — upgrade/status checks
- `admin/cli/checks.php` — CLI health checks
- Environment checks: `admin/environment.php`
- Security overview: `report/security/`

### Đánh giá: **5/10 cho production debugging**
- Event system tốt cho audit trail
- Thiếu structured logging (JSON format)
- Không có APM integration built-in
- Không có distributed tracing

---

## 14. Phân Tích Testing

### Test Framework

| Type | Framework | Location |
|---|---|---|
| Unit tests | PHPUnit 11 | `*/tests/*_test.php` |
| Integration tests | PHPUnit + DB | `*/tests/*_test.php` |
| Behat (BDD/E2E) | Behat 3 + Mink | `*/tests/behat/*.feature` |

### Config
- `phpunit.xml.dist` (16KB) — comprehensive test suite config
- `behat.yml.dist` — Behat configuration
- `composer.json` dev dependencies: phpunit, behat, mink, vfsstream

### Coverage
- Core `lib/tests/` — extensive tests
- Each plugin has `tests/` directory
- **Most modules have tests** — Moodle mandates tests for core contributions
- Data generators: `lib/testing/generator/`

---

## 15. Phân Tích Security

### Implemented Protections

| Threat | Protection | File |
|---|---|---|
| **SQL Injection** | Parameterized queries via DML (`$DB->get_records()`) | `lib/dml/` |
| **XSS** | `s()`, `format_string()`, `format_text()`, `clean_param()` | `lib/weblib.php` |
| **CSRF** | `sesskey()` token required on all forms | `lib/moodlelib.php` |
| **Auth bypass** | `require_login()` on every page | `lib/moodlelib.php` |
| **Authz bypass** | `require_capability()` per action | `lib/accesslib.php` |
| **Password** | `password_hash(PASSWORD_DEFAULT)` = bcrypt | `lib/moodlelib.php` |
| **Login brute force** | Account lockout after N failures | `lib/moodlelib.php` |
| **File upload** | Antivirus scanning, file type restrictions | `lib/antivirus/` |
| **reCAPTCHA** | Login/signup captcha | `lib/recaptchalib_v2.php` |
| **Login tokens** | Per-session login form CSRF tokens | `lib/classes/session/manager.php` |
| **Content trust** | `RISK_XSS` capability flag system | `lib/accesslib.php` |

### ⚠️ Potential Issues

| Issue | Severity | Details |
|---|---|---|
| **Some raw SQL** | 🟡 Medium | Some plugins use `$DB->execute()` with concatenated SQL |
| **CORS** | 🟢 Low | Not configured by default (relies on same-origin) |
| **Rate limiting** | 🟡 Medium | Account lockout exists, but no API rate limiting |
| **Session fixation** | 🟢 Low | Session regenerated on login (`complete_user_login`) |
| **SSRF** | 🟡 Medium | `lib/filelib.php` curl calls — `$CFG->curlsecurityblockedhosts` available |
| **Sensitive data in logs** | 🟢 Low | Password not logged, but user data could appear in debug |

---

## 16. Phân Tích Dependencies

### Core Dependencies (từ `composer.json`)

| Category | Dependency | Role |
|---|---|---|
| **Runtime** | PHP ≥ 8.2 | Core language |
| **Extensions** | curl, mbstring, gd, intl, sodium, zip, openssl | Required PHP extensions |
| **DB** | pgsql/mysqli/sqlsrv ext | Database driver |
| **Testing** | phpunit/phpunit ^11 | Unit/integration tests |
| **Testing** | behat/behat ^3 | BDD acceptance tests |
| **Testing** | behat/mink + mink-browserkit-driver | Browser simulation |
| **Testing** | mikey179/vfsstream | Virtual filesystem |
| **Testing** | oleg-andreyev/mink-phpwebdriver | Selenium WebDriver |
| **Debug** | filp/whoops ^2.15 | Pretty error pages |

### Bundled Libraries (trong `lib/`)

| Library | Directory | Purpose |
|---|---|---|
| AWS SDK | `lib/aws-sdk/` | Cloud services |
| PHPMailer | `lib/phpmailer/` | Email sending |
| TCPDF | `lib/tcpdf/` | PDF generation |
| HTMLPurifier | `lib/htmlpurifier/` | XSS sanitization |
| PHP-JWT | `lib/php-jwt/` | JWT token handling |
| Guzzle HTTP | `lib/guzzlehttp/` | HTTP client |
| Mustache | `lib/mustache/` | Template engine |
| SimplePie | `lib/simplepie/` | RSS/Atom parsing |
| Slim | `lib/slim/` | Router framework |
| ScssPhp | `lib/scssphp/` | SCSS compilation |
| WebAuthn | `lib/webauthn/` | Passwordless auth |
| Markdown | `lib/markdown/` | Markdown parsing |

> **Quan trọng nhất:** DML layer (`lib/dml/`) — toàn bộ data access đều đi qua đây.

---

## 17. Deployment & Infrastructure

### Deployment
- **Không có Dockerfile** trong source
- **Không có docker-compose** trong source
- **GitHub Actions:** 5 workflows trong `.github/workflows/`
  - `push.yml` — CI on push
  - `onebyone.yml` — Sequential testing
  - `windows.yml` — Windows CI
  - `web-installer-test.yml` — Installation test
  - `close-pull-requests.yml` — PR management

### Traditional Deployment
```
1. Clone/download source → web server document root
2. Create config.php from config-dist.php
3. Create dataroot directory (outside web root!)
4. Run admin/cli/install.php (or web installer)
5. Setup cron: * * * * * php admin/cli/cron.php
6. Configure web server (Apache/Nginx)
```

### CLI Tools (`admin/cli/`)
- `install.php` — CLI installer (31KB!)
- `upgrade.php` — Database upgrade
- `cron.php` — Scheduled task runner
- `maintenance.php` — Toggle maintenance mode
- `purge_caches.php` — Cache purge
- `backup.php` / `restore_backup.php`
- `reset_password.php`
- `kill_all_sessions.php`

---

## 18. Backend Flow Diagrams

### Request Flow
```
Client (Browser/Mobile App)
    ↓
Web Server (Apache/Nginx)
    ↓
PHP Script (e.g. course/view.php)
    ↓
require('config.php')
    ↓
lib/setup.php [Bootstrap]
  ├── Register autoloader
  ├── Connect to DB ($DB)
  ├── Initialize $CFG from config table
  ├── Start session ($SESSION, $USER)
  ├── Set exception handler
  └── Load core libraries
    ↓
require_login($course) [auth check]
  ├── Session valid? → continue
  ├── Token valid? (WS) → continue
  └── Not authenticated → redirect /login/
    ↓
require_capability('moodle/course:view', $context)
  ├── Load user role assignments
  ├── Walk context tree
  └── Check capability → 403 if denied
    ↓
Business Logic (lib functions / $DB queries)
    ↓
$OUTPUT->header() + content + $OUTPUT->footer()
    ↓
HTML Response → Client
```

### Authentication Flow
```
Client
  ↓ POST /login/index.php
  ↓ {username, password, logintoken}
  ↓
Validate logintoken (CSRF)
  ↓
Foreach auth plugin in $authsequence:
  $authplugin->loginpage_hook()
  ↓ (may redirect for SSO)
  ↓
authenticate_user_login($username, $password)
  ├── Check account exists
  ├── Check not suspended/deleted
  ├── Check lockout counter
  ├── Foreach auth plugin:
  │     $auth->user_login($username, $password)
  │     → password_verify() for internal
  ├── Check confirmed
  └── Return $user or AUTH_LOGIN_FAILED
  ↓
complete_user_login($user)
  ├── \core\session\manager::login_user($user)
  ├── Session ID regeneration
  ├── Load $USER->access (capabilities)
  ├── Fire \core\event\user_loggedin
  └── Set username cookie
  ↓
Redirect → $SESSION->wantsurl or Dashboard
```

### Business Flow: Student Submits Assignment
```
Student → mod/assign/view.php?id=123&action=submit
  ↓
require_login($course, true, $cm)
  ↓
require_capability('mod/assign:submit', $context)
  ↓
$assign = new assign($context, $cm, $course)
  ↓
$assign->process_submit_for_grading()
  ├── Check submission exists
  ├── Check deadline not passed
  ├── Check max attempts not exceeded
  ├── $DB->update_record('assign_submission', ...)
  ├── Fire \mod_assign\event\submission_status_updated
  ├── Queue notification adhoc task
  └── Redirect with success message
  ↓
Cron → Send notification email to teacher
```

### Business Flow: Teacher Grades Student
```
Teacher → mod/assign/view.php?id=123&action=grade
  ↓
require_capability('mod/assign:grade', $context)
  ↓
$assign->process_save_grade()
  ├── Validate grade value
  ├── $DB->insert/update 'assign_grades'
  ├── grade_update() → update gradebook
  ├── Fire \mod_assign\event\submission_graded
  ├── Check completion criteria → update if needed
  └── Queue feedback notification
```

### Business Flow: Admin Creates Course
```
Admin → course/edit.php (POST)
  ↓
require_capability('moodle/course:create', $categorycontext)
  ↓
create_course($data)
  ├── $DB->insert_record('course', ...)
  ├── Create course context
  ├── Create default sections
  ├── Setup default enrolment methods
  ├── Setup default blocks
  ├── Fire \core\event\course_created
  └── Return $course
```

---

## 19. Tổng Hợp Backend

| # | Câu hỏi | Trả lời |
|---|---|---|
| 1 | **Dùng để làm gì?** | Learning Management System — quản lý khóa học, sinh viên, bài tập, điểm, giao tiếp |
| 2 | **Module chính?** | Course, User, Enrolment, Gradebook, 23 Activity Modules, Auth, Messaging, Calendar, Badges, Completion, Analytics, AI |
| 3 | **Business domains?** | Course Management, User/Auth, Enrolment, Assessment/Grading, Completion Tracking, Messaging, Calendar, Badges, Competency, Content/Files |
| 4 | **DB chứa gì?** | 257+ tables: users, courses, grades, submissions, messages, logs, sessions, capabilities, config |
| 5 | **API quan trọng?** | 401 web service functions: course CRUD, user management, grade operations, enrolment, messaging, calendar |
| 6 | **Auth hoạt động?** | Session-based + plugin chain (manual/email/LDAP/OAuth2/Shibboleth). RBAC with 700+ capabilities |
| 7 | **External systems?** | SMTP, LDAP, OAuth2, LTI, BigBlueButton, reCAPTCHA, H5P, MoodleNet, AWS |
| 8 | **Synchronous?** | Page rendering, grade calculation, auth, most web service calls |
| 9 | **Asynchronous?** | Email notifications, backup, course deletion, search indexing, enrolment sync (via adhoc/scheduled tasks + cron) |
| 10 | **Deploy?** | Traditional LAMP/LEMP stack, CLI installer, cron job, no containerization in source |

---

## 20. Đánh Giá Chất Lượng Kiến Trúc

| Tiêu chí | Điểm | Lý do |
|---|---|---|
| **Architecture** | 7/10 | Plugin system cực kỳ mạnh, context hierarchy thông minh. Nhưng thiếu clean separation of concerns |
| **Code Organization** | 7/10 | Cấu trúc plugin nhất quán (`db/`, `classes/`, `lib.php`). Nhưng `lib/moodlelib.php` 370KB = god file |
| **Maintainability** | 6/10 | Plugin isolation tốt, nhưng core coupling cao. Global state ($CFG, $DB, $USER) everywhere |
| **Scalability** | 7/10 | Read replicas, MUC cache, Redis sessions, cron workers. Horizontal scaling possible |
| **Security** | 8/10 | Mature security model: RBAC, parameterized queries, XSS filtering, CSRF tokens, capability system |
| **Performance** | 6/10 | MUC caching helps, but heavy DB queries per page. No query optimization layer |
| **Testability** | 7/10 | PHPUnit + Behat comprehensive. Data generators. But global state makes unit testing harder |
| **Observability** | 5/10 | Event system good for audit. But no structured logging, no APM, no metrics export |
| **Error Handling** | 6/10 | Global exception handler. But inconsistent between page scripts and WS. Some legacy print_error() |
| **Documentation** | 8/10 | Excellent inline PHPDoc. `upgrade.txt` per component. `UPGRADING.md`. Official docs site |

---

## 21. Vấn Đề & Technical Debt

### 🔴 Critical
| Issue | Location | Detail |
|---|---|---|
| God files | `lib/moodlelib.php` (370KB), `lib/accesslib.php` (190KB) | Quá lớn, khó maintain |
| Global mutable state | `$CFG`, `$DB`, `$USER`, `$PAGE`, `$OUTPUT` | Khó test, race conditions |

### 🟠 High
| Issue | Location | Detail |
|---|---|---|
| No Repository pattern | Toàn bộ code | `$DB->xxx()` gọi trực tiếp khắp nơi, impossible to mock cleanly |
| No Service layer | Page scripts | Business logic trộn lẫn với presentation |
| Legacy API | `lib/deprecatedlib.php` (30KB) | Nhiều deprecated functions vẫn tồn tại |
| Bundled third-party libs | `lib/guzzlehttp/`, `lib/phpmailer/`, etc. | Nên dùng Composer thay vì vendor trong source |

### 🟡 Medium
| Issue | Location | Detail |
|---|---|---|
| Missing rate limiting | Web service endpoints | Chỉ có login lockout, không có API rate limit |
| Inconsistent error format | Page scripts vs WS | WS trả JSON error, pages trả HTML error |
| No structured logging | Toàn bộ | Thiếu JSON structured logs cho ELK/Datadog |
| Large install.xml | `lib/db/install.xml` (395KB) | 257 tables in one file |
| Unix timestamps | Mọi nơi | `timemodified`, `timecreated` là INT, không phải DATETIME |

### 🟢 Low
| Issue | Location | Detail |
|---|---|---|
| jQuery + YUI legacy | `lib/jquery/`, `lib/yui/` | Đang migrate sang AMD, nhưng legacy vẫn còn |
| PHP style inconsistency | Legacy code | Hỗn hợp `var $x` vs `public $x`, function vs class method |
| No API versioning | Web services | Tất cả API cùng version, no v1/v2 |

---

## 22. Backend Learning Map

### Thứ tự đọc code cho developer mới

#### Phase 1: Hiểu cơ bản (Ngày 1-2)
1. **`version.php`** — Phiên bản, branch
2. **`config-dist.php`** (lines 1-200) — Hiểu config structure
3. **`index.php`** (149 lines) — Entry point, request flow cơ bản
4. **`lib/setup.php`** (lines 1-100) — Bootstrap process

#### Phase 2: Hiểu core (Ngày 3-5)
5. **`lib/moodlelib.php`** — Skim functions: `require_login()`, `authenticate_user_login()`, `complete_user_login()`
6. **`lib/accesslib.php`** — Focus: `has_capability()`, context system, role definitions
7. **`lib/datalib.php`** — DML helper functions
8. **`lib/dml/moodle_database.php`** — `$DB` API: `get_record()`, `insert_record()`, `execute()`

#### Phase 3: Hiểu database (Ngày 5-7)
9. **`lib/db/install.xml`** — Skim tables: `user`, `course`, `enrol`, `user_enrolments`, `role_assignments`, `context`
10. **`lib/db/services.php`** — Web service API registry
11. **`lib/db/access.php`** — Capability definitions

#### Phase 4: Hiểu một module (Ngày 7-10)
12. **`mod/forum/`** — Tốt nhất để hiểu plugin structure:
    - `mod/forum/lib.php` — Plugin API functions
    - `mod/forum/view.php` — Page script pattern
    - `mod/forum/db/install.xml` — Plugin schema
    - `mod/forum/db/access.php` — Plugin capabilities

#### Phase 5: Hiểu auth & user (Ngày 10-12)
13. **`login/index.php`** — Login flow đầy đủ
14. **`lib/authlib.php`** — Auth plugin base class
15. **`auth/manual/auth.php`** — Simplest auth plugin

#### Phase 6: Business flows (Ngày 12-15)
16. **`course/view.php`** — How courses are displayed
17. **`mod/assign/locallib.php`** — Assignment submission/grading flow
18. **`lib/enrollib.php`** — Enrolment logic
19. **`lib/completionlib.php`** — Completion tracking

### Có thể bỏ qua lúc đầu
- `lib/yui/`, `lib/jquery/` — Legacy JS
- `lib/tcpdf/`, `lib/phpspreadsheet/` — Export libraries
- `lib/bennu/`, `lib/evalmath/` — Niche utilities
- `admin/tool/` — Admin tools (trừ khi làm admin features)
- `analytics/`, `competency/` — Advanced features
- `mnet/` — Deprecated networking

### Files quan trọng nhất (Top 10)
1. `lib/setup.php` — Bootstrap
2. `lib/moodlelib.php` — Core functions
3. `lib/accesslib.php` — Authorization
4. `lib/enrollib.php` — Enrolment
5. `lib/db/install.xml` — Database schema
6. `lib/db/services.php` — API registry
7. `login/index.php` — Auth flow
8. `config-dist.php` — Configuration
9. `lib/filelib.php` — File handling
10. `lib/classes/task/manager.php` — Task scheduling

---

## Tóm Tắt Kiến Trúc Backend (Onboarding Document)

> **Moodle 5.0** là một **Learning Management System** viết bằng **PHP 8.2+**, sử dụng kiến trúc **Modular Monolith với Plugin Architecture** mạnh mẽ. Backend phục vụ trực tiếp HTML (server-rendered) và cung cấp **401 web service APIs** cho mobile app.
>
> **Database:** 257+ bảng trên PostgreSQL/MySQL/MariaDB, truy cập qua DML layer (`$DB` global) — không có ORM.
>
> **Authentication:** Session-based với plugin chain (manual, email, LDAP, OAuth2, Shibboleth). **Authorization:** RBAC với 700+ capabilities, context hierarchy (System → Category → Course → Module), và 8 role archetypes.
>
> **Core pattern:** Mỗi page là một PHP script riêng, gọi `require_login()` + `require_capability()`, sau đó thực thi business logic qua library functions và `$DB` queries, render output bằng `$OUTPUT` renderer + Mustache templates.
>
> **Plugin system:** 15+ plugin types (mod, auth, enrol, block, theme, report, repository...). Mỗi plugin tuân theo convention: `version.php`, `lib.php`, `db/install.xml`, `db/access.php`, `classes/`, `tests/`.
>
> **Async:** 48+ scheduled tasks + ad-hoc task queue, chạy qua `admin/cli/cron.php`. Dùng cho email, backup, search indexing, completion check.
>
> **Caching:** MUC (Moodle Universal Cache) hỗ trợ File/Redis/Memcached. Session có thể lưu DB/File/Redis/Memcached.
>
> **Files:** Content-addressable storage (SHA1 hash). Metadata trong DB, binary trong filesystem.
>
> **Testing:** PHPUnit + Behat comprehensive. **Security:** Mature — parameterized queries, CSRF tokens, capability system, XSS filtering, password hashing (bcrypt).
>
> **Điểm mạnh:** Plugin extensibility, security model, test coverage, documentation.
> **Điểm yếu:** Global mutable state, god files, no clean architecture layers, legacy code.
