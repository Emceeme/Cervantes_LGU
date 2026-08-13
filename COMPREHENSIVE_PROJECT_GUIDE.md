# LGU Management System - Comprehensive Project Guide

**Version:** 3.0 | **Updated:** August 10, 2026  
**Developer:** [Your Name] (Starting Developer)  
**Partnered Assistant:** Cascade (AI Development Assistant)

---

## Overview

LGU Management System - comprehensive web platform for municipal operations including Treasury, LGU Administration, Public Portal, MSWD Portal, and Scholarship System.

**Tech Stack:** PHP 7.4+, MySQL 5.7+, HTML5, CSS3, Vanilla JavaScript

---

## Directory Structure

```
last/
├── admin/              # Super Admin module
├── config/             # Configuration files (security, db, app_config)
├── handler/            # Global handlers
├── lgu/                # LGU Department module
├── migrations/         # Database migrations
├── mswd/               # MSWD Portal module
├── public/             # Public portal
├── static_page/        # Static public pages
├── storage/            # Private storage (logs, rate_limits, docs)
├── treasury/           # Treasury Department module
├── .env                # Environment variables
├── login.php           # Authentication
├── deploy_check.php    # Pre-deployment validation
└── Documentation files (DEPLOYMENT.md, SECURITY_IMPLEMENTATION.md, etc.)
```

---

## Database Schema (Key Tables)

**users:** id, username, email, password (bcrypt), role, department, created_at  
**messages:** id, sender_id, receiver_id, subject, message, created_at, is_read  
**jobs:** id, title, description, requirements, salary, posted_by, created_at  
**news:** id, title, content, image, posted_by, created_at  
**procurement_posts:** id, title, category, file_path, award_winner, view_count, status  
**balances:** id, user_id, amount, updated_at  
**applications:** id, tracking_number, assistance_type_id, status, submitted_at  
**scholarship_posts:** id, title, description, requirements, deadline, is_active  
**scholarship_applications:** id, scholarship_id, first_name, email, file_path, status

---

## User Roles

- **SUPER_ADMIN:** Full system access → admin/dashboard.php
- **ADMIN:** Department admin → lgu/manage_employees.php
- **EMPLOYEE:** Department operations → lgu/dashboard.php
- **SOCIAL_WORKER:** MSWD processing → mswd/worker/dashboard.php
- **CITIZEN:** Public services → treasury/citizen/dashboard.php
- **APPLICANT:** MSWD applicants → mswd/applicant/my-applications.php (future)

---

## Security Features (Implemented)

### 1. CSRF Protection
```php
// Generate token
$csrf_token = generateCsrfToken();
<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

// Validate token
if (!validateCsrfToken($_POST['csrf_token'])) {
    die("Security validation failed");
}
```

### 2. SQL Injection Prevention
```php
// Always use prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
```

### 3. Secure Session Management
```php
function startSecureSession() {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
    
    // Regenerate every 30 minutes
    if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
    }
}
```

### 4. Password Security
```php
// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verify password
if (password_verify($input, $hash)) {
    // Valid
}
```

### 5. Rate Limiting
```php
// Track login attempts
trackLoginAttempt($username, $success);

// Check if blocked
if (isLoginBlocked($ip)) {
    die("Too many attempts");
}
```

### 6. Security Headers
```php
function setSecurityHeaders() {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Content-Security-Policy: default-src 'self'");
}
```

### 7. Input Sanitization
```php
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
```

### 8. Security Logging
```php
logSecurityEvent('login_success', $user_id, ['ip' => $ip]);
```

---

## Configuration (app_config.php)

```php
class AppConfig {
    public static function getBaseUrl() {
        // Load from .env or auto-detect
        $baseUrl = getenv('BASE_URL');
        if ($baseUrl) return rtrim($baseUrl, '/');
        
        // Auto-detect
        $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return "$protocol://$host";
    }
    
    public static function uploads($path) {
        return self::getBaseUrl() . '/lgu/uploads/' . ltrim($path, '/');
    }
}
```

**Usage:**
```php
require_once 'config/app_config.php';
$imageUrl = AppConfig::uploads('news/image.jpg');
```

---

## Security Improvements Needed

### High Priority
1. **Password Reset** (₱4,500) - Email-based reset with tokens
2. **MSWD Applicant Dashboard** (₱5,500) - Currently redirects to non-existent page
3. **Session Cookie HTTPS** (₱750) - Set cookie_secure=1 for production
4. **CSRF Token Regeneration** (₱1,250) - Regenerate after form submission
5. **Password Policy** (₱5,000) - Expiration, history, complexity enforcement
6. **Input Validation** (₱3,500) - Comprehensive server-side validation

### Medium Priority
7. **Error Handling** (₱3,500) - Try-catch blocks, generic messages
8. **XSS Protection** (₱2,500) - Output encoding, CSP strict mode
9. **File Upload Security** (₱4,500) - Magic numbers, malware scanning
10. **2FA** (₱7,000) - TOTP for privileged accounts
11. **Email Notifications** (₱4,500) - Status changes, alerts
12. **Account Lockout Notification** (₱2,500) - User-friendly lockout messages
13. **HTTPS Enforcement** (₱1,250) - Automatic redirect
14. **403 Page** (₱1,250) - Dedicated unauthorized page
15. **Automated Backups** (₱4,500) - Daily database/file backups

### Low Priority
16. **CAPTCHA** (₱2,500) - Public form protection
17. **API Rate Limiting** (₱3,500) - All endpoints
18. **Audit Trail** (₱3,500) - All admin actions
19. **API Documentation** (₱4,500) - OpenAPI/Swagger

**Total Security Improvements: ₱39,000**

---

## Functionality Improvements Needed

### High Priority
1. **Password Reset** (₱4,500) - Users can't reset passwords
2. **MSWD Applicant Dashboard** (₱5,500) - Missing applicant portal
3. **Session Cookie Security** (₱750) - Currently HTTP only
4. **CSRF Regeneration** (₱1,250) - Token reuse possible

### Medium Priority
5. **Lockout Notification** (₱2,500) - No user feedback
6. **HTTPS Enforcement** (₱1,250) - No auto-redirect
7. **403 Page** (₱1,250) - Generic errors
8. **Automated Backups** (₱4,500) - Manual only

### Low Priority
9. **Audit Trail** (₱3,500) - Limited logging
10. **API Documentation** (₱4,500) - None exists

**Total Functionality Improvements: ₱29,500**

---

## Pricing Audit (Philippine Peso)

### Development Costs
- **Initial Development:** ₱176,000
  - Treasury Module: ₱15,000
  - LGU Administration: ₱12,000
  - Public Portal: ₱10,000
  - Authentication: ₱8,000
  - MSWD Integration: ₱48,000
  - Security Framework: ₱31,000
  - Deployment Prep: ₱17,000
  - Scholarship System: ₱30,000

- **Security Improvements:** ₱39,000
- **Functionality Improvements:** ₱29,500
- **Annual Maintenance:** ₱38,000/year
- **One-Time Setup:** ₱12,000

### Total Cost Summary
| Category | Cost (PHP) |
|----------|-----------|
| Initial Development | ₱176,000 |
| Security Improvements | ₱39,000 |
| Functionality Improvements | ₱29,500 |
| Annual Maintenance | ₱38,000 |
| One-Time Setup | ₱12,000 |
| **Grand Total** | **₱294,500** |

### Production-Ready Investment (Recommended for Starting Developer)
**Phase 1 (Immediate - ₱12,500):**
- Session Cookie Security: ₱750
- CSRF Regeneration: ₱1,250
- HTTPS Enforcement: ₱1,250
- 403 Page: ₱1,250
- Server Config: ₱5,000
- SSL Certificate: ₱3,000

**Phase 2 (3 months - ₱23,500):**
- Password Reset: ₱4,500
- MSWD Dashboard: ₱5,500
- Password Policy: ₱5,000
- Input Validation: ₱3,500
- Session Config: ₱2,500
- Lockout Notification: ₱2,500

**Phase 3 (6 months - ₱23,000):**
- Error Handling: ₱3,500
- XSS Protection: ₱2,500
- File Upload Security: ₱4,500
- Email Notifications: ₱4,500
- Automated Backups: ₱4,500
- Audit Trail: ₱3,500

**Phase 4 (12 months - ₱17,500):**
- 2FA: ₱7,000
- CAPTCHA: ₱2,500
- API Rate Limiting: ₱3,500
- API Documentation: ₱4,500

**Total for Production-Ready: ₱76,500**

---

## Getting Started (For Beginners)

### 1. Setup XAMPP
- Download from apachefriends.org
- Install with defaults
- Start Apache & MySQL

### 2. Place Files
- Copy to `C:\xampp\htdocs\last\`
- Access: `http://localhost/last/`

### 3. Database Setup
```sql
-- Create database in phpMyAdmin
CREATE DATABASE lgu_system;

-- Run migrations via browser
http://localhost/last/migrations/run_mswd_migration.php
http://localhost/last/migrations/seed_assistance_types.php
```

### 4. Configure .env
```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=lgu_system
BASE_URL=http://localhost/last
APP_ENV=development
APP_DEBUG=true
```

### 5. Create Super Admin
```bash
http://localhost/last/create_super_admin.php
```
Default: admin / admin123 (change immediately!)

### 6. Test
- Login: `http://localhost/last/login.php`
- Public: `http://localhost/last/public/public.php`
- MSWD: `http://localhost/last/mswd/index.php`

---

## Common Issues (Beginners)

**Database Connection Failed:**
- Check MySQL running in XAMPP
- Verify .env credentials
- Ensure database exists

**File Upload Failed:**
- Check directory permissions (755)
- Verify php.ini upload_max_filesize
- Ensure directory exists

**CSRF Validation Failed:**
- Clear browser cookies
- Ensure session started
- Check token in form

**Login Not Redirecting:**
- Verify user in database
- Check password hash
- Verify role correct

---

## Deployment Guide

### Pre-Deployment
1. Run `deploy_check.php`
2. Set `APP_ENV=production` in .env
3. Set strong database credentials
4. Enable HTTPS
5. Configure backups

### Environment Variables (Production)
```env
DB_HOST=production-host
DB_USER=production-user
DB_PASS=strong-password
DB_NAME=lgu_system
BASE_URL=https://yourdomain.com
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE=true
```

### Directory Permissions
```bash
chmod 755 lgu/uploads
chmod 755 storage
chmod 755 storage/logs
```

---

## Module Code Examples

### Login Handler (login.php)
```php
require_once 'config/security.php';
startSecureSession();
setSecurityHeaders();
include 'config/db.php';

if (isset($_POST['login'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Security validation failed");
    }
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department'] = $user['department'];
            
            // Redirect based on role/department
            if ($user['role'] === 'SUPER_ADMIN') {
                header("Location: admin/dashboard.php");
            } elseif ($user['department'] === 'MSWD') {
                header("Location: mswd/worker/dashboard.php");
            } else {
                header("Location: lgu/dashboard.php");
            }
            exit();
        }
    }
    $error = "Invalid credentials";
}
```

### News Posting Handler (handler/post_news.php)
```php
require_once '../config/security.php';
require_once '../config/db.php';

startSecureSession();
setSecurityHeaders();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if (!validateCsrfToken($_POST['csrf_token'])) {
    die("Security validation failed");
}

// Handle image upload
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_type = finfo_file($finfo, $_FILES['image']['tmp_name']);
    finfo_close($finfo);
    
    if (in_array($detected_type, $allowed_types)) {
        $filename = uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['image']['tmp_name'], '../lgu/uploads/news/' . $filename);
        $image_path = 'news/' . $filename;
    }
}

$stmt = $conn->prepare("INSERT INTO news (title, content, image, posted_by) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", sanitizeInput($_POST['title']), sanitizeInput($_POST['content']), $image_path, $_SESSION['id']);
$stmt->execute();

header("Location: ../newsfeed.php?success=1");
```

### MSWD Application Handler (mswd/handler/submit_application.php)
```php
require_once '../../config/security.php';
require_once '../../config/db.php';

startSecureSession();
setSecurityHeaders();

if (!validateCsrfToken($_POST['csrf_token'])) {
    die("Security validation failed");
}

$tracking_number = 'MSWD-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 5));
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO applications (tracking_number, assistance_type_id, first_name, last_name, birthdate, gender, civil_status, contact_number, email, barangay, street_address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("sisssssssss", $tracking_number, (int)$_POST['assistance_type_id'], sanitizeInput($_POST['first_name']), sanitizeInput($_POST['last_name']), $_POST['birthdate'], $_POST['gender'], $_POST['civil_status'], sanitizeInput($_POST['contact_number']), sanitizeInput($_POST['email']), sanitizeInput($_POST['barangay']), sanitizeInput($_POST['street_address']));
    $stmt->execute();
    $application_id = $conn->insert_id;
    
    // Handle document uploads
    foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
        $filename = uniqid() . '_' . $application_id . '.' . pathinfo($_FILES['documents']['name'][$key], PATHINFO_EXTENSION);
        move_uploaded_file($tmp_name, '../../storage/mswd_documents/' . $filename);
        
        $doc_stmt = $conn->prepare("INSERT INTO application_documents (application_id, document_type, file_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)");
        $doc_stmt->bind_param("isssis", $application_id, sanitizeInput($_POST['document_types'][$key]), $_FILES['documents']['name'][$key], 'mswd_documents/' . $filename, $_FILES['documents']['size'][$key], $detected_type);
        $doc_stmt->execute();
    }
    
    $conn->commit();
    header("Location: confirmation.php?tracking=" . $tracking_number);
} catch (Exception $e) {
    $conn->rollback();
    die("Error: " . $e->getMessage());
}
```

---

## Maintenance

### Daily
- Check error logs in `storage/logs/`
- Monitor disk space
- Review security logs

### Weekly
- Backup database
- Review user accounts
- Check for updates

### Monthly
- Rotate log files
- Review access logs
- Update documentation

### Backup Commands
```bash
# Database backup
mysqldump -u root -p lgu_system > backup_$(date +%Y%m%d).sql

# File backup
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/last/

# Automated (cron)
0 2 * * * /usr/bin/mysqldump -u user -ppass db > /backups/db_$(date +\%Y\%m\%d).sql
```

---

## Support Resources

### Documentation Files
- `SYSTEM_DOCUMENTATION.md` - Full system docs
- `SECURITY_IMPLEMENTATION.md` - Security features
- `MSWD_INTEGRATION.md` - MSWD module
- `DEPLOYMENT.md` - Deployment guide
- `SYSTEM_AUDIT_REPORT.md` - Security audit

### Log Locations
- Security: `storage/logs/security_YYYY-MM-DD.log`
- Errors: `storage/logs/error_*.log`
- Rate limits: `storage/rate_limits/`
- Login attempts: `storage/login_attempts/`

### Troubleshooting
1. Check logs
2. Verify configuration
3. Test with browser dev tools
4. Run `deploy_check.php`

---

## Version History

- **v3.0** (Aug 2026) - Deployment prep, scholarship system, enhanced security
- **v2.0** (Jul 2024) - MSWD integration, social worker portal
- **v1.0** - Core LGU system, treasury, public portal

---

## Acknowledgments

**Primary Developer:** [Your Name]  
**Partnered Coder Assistant:** Cascade (AI Development Assistant)

This system was developed by a starting developer with AI assistance, demonstrating solid foundational practices with room for growth and enhancement.
