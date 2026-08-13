# LGU Management System - Full System Documentation

**Version:** 3.0 (with Deployment Preparation & Scholarship System)  
**Last Updated:** 2024-08-05

## Table of Contents
1. [System Overview](#system-overview)
2. [Architecture](#architecture)
3. [Installation & Setup](#installation--setup)
4. [Database Schema](#database-schema)
5. [User Roles & Permissions](#user-roles--permissions)
6. [Module Documentation](#module-documentation)
7. [Security Features](#security-features)
8. [API Reference](#api-reference)
9. [Configuration](#configuration)
10. [Deployment](#deployment)
11. [Troubleshooting](#troubleshooting)

---

## System Overview

The LGU (Local Government Unit) Management System is a comprehensive web-based platform for managing municipal operations, including:
- **Treasury Department** - Tax collection, balance management, citizen accounts
- **LGU Administration** - Job postings, news, procurement, employee management
- **Public Portal** - Job applications, citizen inquiries, scholarship applications, procurement viewing
- **MSWD Portal** - Social welfare assistance applications and processing
- **Super Admin** - System-wide administration and user management
- **Scholarship System** - Scholarship program management and application processing

### Technology Stack
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Security:** Custom security framework with CSRF protection, prepared statements, secure sessions
- **Deployment:** Environment-based configuration with pre-deployment validation

---

## Architecture

### Directory Structure
```
last/
├── admin/                      # Super Admin module
│   ├── dashboard.php          # Admin dashboard
│   ├── lgu_list.php           # LGU account management
│   ├── create_social_worker.php # MSWD worker creation
│   └── handler/              # Admin handlers
│       └── post_create_social_worker.php
├── config/                    # Configuration files
│   ├── db.php                # Database connection
│   ├── security.php          # Security functions
│   ├── env.php               # Environment variable loader
│   ├── app_config.php        # Application configuration (NEW v3.0)
│   └── directories.php       # Directory management (NEW v3.0)
├── handler/                   # Global handlers
│   ├── post_job.php          # Job posting handler
│   ├── post_news.php         # News posting handler
│   ├── upload_procurement.php # Procurement upload
│   └── delete_procurement.php # Procurement deletion
├── lgu/                       # LGU Department module
│   ├── dashboard.php         # LGU dashboard
│   ├── newsfeed.php          # News management
│   ├── procurement.php       # Procurement management (UPDATED v3.0)
│   ├── applicants.php        # Job applicant management
│   ├── manage_employees.php  # Employee management
│   ├── scholarship_applications.php # Scholarship admin (NEW v3.0)
│   └── update_scholarship_status.php # Status handler (NEW v3.0)
├── migrations/                # Database migrations
│   ├── migrate_mswd.sql      # MSWD schema
│   ├── run_mswd_migration.php
│   ├── seed_assistance_types.php
│   ├── create_scholarship_posts.php # Scholarship posts (NEW v3.0)
│   ├── create_scholarship_applications.php # Applications (NEW v3.0)
│   └── add_view_count_procurement.php # View tracking (NEW v3.0)
├── mswd/                      # MSWD Portal module
│   ├── index.php             # Public landing
│   ├── apply.php             # Application form
│   ├── confirmation.php      # Confirmation page
│   ├── track.php             # Status tracker
│   ├── handler/
│   │   └── submit_application.php
│   └── worker/
│       ├── dashboard.php     # Worker dashboard
│       ├── review.php        # Application review
│       └── view_document.php # Document viewer
├── public/                    # Public portal
│   ├── public.php            # Public landing
│   ├── apply_job.php         # Job application handler
│   ├── procurement.php       # Procurement viewing (UPDATED v3.0)
│   ├── scholarship.php       # Scholarship programs (NEW v3.0)
│   ├── apply_scholarship.php # Application handler (NEW v3.0)
│   ├── download_procurement.php # Download handler (NEW v3.0)
│   ├── news.php              # News viewing (UPDATED v3.0)
│   ├── news.js               # News scripts (UPDATED v3.0)
│   ├── sidebar.php           # Shared sidebar (NEW v3.0)
│   └── procurement.css       # Shared styles (NEW v3.0)
├── static_page/               # Static public pages
│   ├── public/               # Public portal pages
│   │   ├── public.php        # Job postings
│   │   ├── apply_job.php     # Job application
│   │   ├── procurement.php   # Procurement categories
│   │   ├── philgeps.php      # PhilGEPS
│   │   ├── bids_awards.php   # Bids and Awards
│   │   ├── invitation_to_bid.php
│   │   ├── bid_bulletin.php
│   │   ├── notice_of_award.php
│   │   ├── notice_to_proceed.php
│   │   ├── news.php          # News
│   │   ├── scholarship.php   # Scholarship
│   │   ├── apply_scholarship.php
│   │   └── sidebar.php       # Shared sidebar
│   ├── home.html             # Main homepage
│   ├── about.html            # About page
│   ├── services.html         # Services page
│   ├── map.html              # Map page
│   └── tourism.html          # Tourism page
├── storage/                   # Private storage
│   ├── logs/                 # Security logs
│   ├── rate_limits/          # Rate limit data
│   ├── login_attempts/       # Login attempt tracking
│   └── mswd_documents/       # MSWD document storage
├── treasury/                  # Treasury Department module
│   ├── dashboard.php         # Treasury dashboard
│   ├── update_balance.php    # Balance update
│   ├── create_public_user.php # Citizen account creation
│   ├── manage_employees.php  # Treasury employee management
│   ├── messages.php          # Messaging system
│   ├── citizen/
│   │   ├── dashboard.php     # Citizen dashboard
│   │   ├── messages.php     # Citizen messages
│   │   └── send_inquiry.php  # Inquiry form
│   └── handler/
│       ├── post_update_balance.php
│       └── post_create_employee.php
├── lgu/                       # LGU uploads
│   └── uploads/              # File storage
│       ├── procurement/      # Procurement documents
│       ├── news/             # News images
│       ├── scholarship/      # Scholarship documents
│       └── employees/        # Employee photos
├── .env                       # Environment variables
├── .env.example              # Environment template
├── .gitignore                # Git ignore rules
├── login.php                 # Authentication
├── logout.php                # Logout
├── create_super_admin.php    # Initial admin setup
├── deploy_check.php          # Pre-deployment validation (NEW v3.0)
├── DEPLOYMENT.md             # Deployment guide (NEW v3.0)
├── SECURITY_IMPLEMENTATION.md # Security documentation
├── SYSTEM_AUDIT_REPORT.md    # Security audit report
└── SYSTEM_DOCUMENTATION.md   # This file
```

---

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server
- PHP extensions: mysqli, mbstring, json, fileinfo

### Step 1: Clone/Extract Files
Place the system files in your web server's document root (e.g., `htdocs/last/`).

### Step 2: Configure Database
Create a MySQL database and update the connection in `config/db.php`:

```php
$conn = new mysqli(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    getenv('DB_NAME') ?: 'lgu_system'
);
```

### Step 3: Environment Configuration
Copy `.env.example` to `.env` and configure:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=lgu_system

# Super Admin Credentials
SUPER_ADMIN_USERNAME=admin
SUPER_ADMIN_EMAIL=admin@lgu.gov.ph
SUPER_ADMIN_PASSWORD=YourSecurePassword123!

# Security Settings
SESSION_LIFETIME=3600
MAX_LOGIN_ATTEMPTS=5
LOGIN_ATTEMPT_WINDOW=900
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60
```

### Step 4: Create Super Admin
Run the super admin creation script:

```bash
php create_super_admin.php
```

Or visit via browser (localhost only):
```
http://localhost/last/create_super_admin.php
```

### Step 5: Set Permissions
Ensure storage directories are writable:

```bash
chmod -R 755 storage/
chmod -R 755 uploads/
```

### Step 6: Run MSWD Migration (Optional)
If using MSWD features:

```bash
php migrations/run_mswd_migration.php
php migrations/seed_assistance_types.php
```

### Step 7: Access the System
- **Super Admin:** `http://localhost/last/login.php` (use super admin credentials)
- **Public Portal:** `http://localhost/last/public/public.php`
- **MSWD Portal:** `http://localhost/last/mswd/index.php`

---

## Database Schema

### Core Tables

#### users
Stores all system users with role-based access.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | User ID |
| first_name | VARCHAR(100) | First name |
| last_name | VARCHAR(100) | Last name |
| username | VARCHAR(50) | Login username (unique) |
| email | VARCHAR(255) | Email address (unique) |
| password | VARCHAR(255) | Bcrypt hashed password |
| role | ENUM | User role (SUPER_ADMIN, ADMIN, EMPLOYEE, CITIZEN, SOCIAL_WORKER, APPLICANT) |
| department | VARCHAR(100) | Department assignment |
| created_at | TIMESTAMP | Account creation time |

#### messages
Internal messaging system.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Message ID |
| sender_id | INT | Sender user ID |
| receiver_id | INT | Receiver user ID |
| subject | VARCHAR(255) | Message subject |
| message | TEXT | Message content |
| created_at | TIMESTAMP | Sent time |
| is_read | TINYINT | Read status |

#### jobs
Job postings for LGU employment.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Job ID |
| title | VARCHAR(255) | Job title |
| description | TEXT | Job description |
| requirements | TEXT | Job requirements |
| salary | VARCHAR(100) | Salary range |
| posted_by | INT | Posted by user ID |
| created_at | TIMESTAMP | Posting time |

#### news
News and announcements.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | News ID |
| title | VARCHAR(255) | News title |
| content | TEXT | News content |
| image | VARCHAR(255) | Image path |
| posted_by | INT | Posted by user ID |
| created_at | TIMESTAMP | Posting time |

#### procurement
Procurement listings.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Procurement ID |
| title | VARCHAR(255) | Title |
| description | TEXT | Description |
| document | VARCHAR(255) | Document path |
| posted_by | INT | Posted by user ID |
| created_at | TIMESTAMP | Posting time |

#### balances
Treasury account balances.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Balance ID |
| user_id | INT | User ID |
| amount | DECIMAL(10,2) | Balance amount |
| updated_at | TIMESTAMP | Last update |

### MSWD Tables

#### assistance_types
Available social welfare assistance programs.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Type ID |
| name | VARCHAR(255) | Assistance name |
| description | TEXT | Description |
| eligibility_requirements | TEXT | Eligibility criteria |
| process_steps | TEXT | Application steps |
| required_documents | JSON | Required docs array |
| is_active | TINYINT | Active status |

#### applications
Submitted assistance applications.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Application ID |
| tracking_number | VARCHAR(50) | Unique tracking # |
| assistance_type_id | INT | Assistance type ID |
| first_name | VARCHAR(100) | Applicant name |
| last_name | VARCHAR(100) | Applicant surname |
| birthdate | DATE | Birth date |
| gender | ENUM | Gender |
| civil_status | ENUM | Civil status |
| contact_number | VARCHAR(20) | Contact number |
| email | VARCHAR(255) | Email |
| barangay | VARCHAR(100) | Barangay |
| street_address | TEXT | Street address |
| status | ENUM | Application status |
| remarks | TEXT | Review remarks |
| assigned_worker_id | INT | Assigned worker |
| submitted_at | TIMESTAMP | Submission time |
| reviewed_at | TIMESTAMP | Review time |

#### application_documents
Uploaded application documents.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | Document ID |
| application_id | INT | Application ID |
| document_type | VARCHAR(100) | Document type |
| file_name | VARCHAR(255) | Original filename |
| file_path | VARCHAR(500) | Storage path |
| file_size | INT | File size (bytes) |
| mime_type | VARCHAR(100) | MIME type |
| uploaded_at | TIMESTAMP | Upload time |

#### application_status_history
Status change audit trail.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK) | History ID |
| application_id | INT | Application ID |
| old_status | VARCHAR(50) | Previous status |
| new_status | VARCHAR(50) | New status |
| changed_by | INT | User who changed |
| remarks | TEXT | Change remarks |
| changed_at | TIMESTAMP | Change time |

---

## User Roles & Permissions

### SUPER_ADMIN
**Access:** Full system administration
**Redirect:** `admin/dashboard.php`

**Permissions:**
- Create and manage LGU accounts
- Create SOCIAL_WORKER accounts
- View all system data
- Manage system settings
- Access all modules

### ADMIN
**Access:** Department administration
**Redirect:** `lgu/manage_employees.php`

**Permissions:**
- Manage department employees
- Post jobs, news, procurement
- View department data
- Manage department messages

### EMPLOYEE
**Access:** Department operations
**Redirect:** `lgu/dashboard.php`

**Permissions:**
- View department information
- Post jobs, news, procurement
- Send/receive messages
- View assigned tasks

### SOCIAL_WORKER (MSWD)
**Access:** MSWD application processing
**Redirect:** `mswd/worker/dashboard.php`

**Permissions:**
- View and review applications
- Update application status
- View uploaded documents
- Add review remarks
- Filter applications by status/barangay/type

### CITIZEN
**Access:** Public services
**Redirect:** `treasury/citizen/dashboard.php`

**Permissions:**
- View public information
- Send inquiries
- Apply for jobs
- View messages
- Track applications

### APPLICANT (MSWD)
**Access:** MSWD applicant portal
**Redirect:** `mswd/applicant/my-applications.php` (future)

**Permissions:**
- Submit assistance applications
- Track application status
- View application history
- Upload documents

---

## Module Documentation

### Treasury Module (`treasury/`)

#### Dashboard (`treasury/dashboard.php`)
- Overview of treasury operations
- Balance management
- Employee list
- Recent activities

#### Balance Management (`treasury/update_balance.php`)
- Update citizen account balances
- Transaction logging
- CSRF protection

#### Citizen Management (`treasury/create_public_user.php`)
- Create citizen accounts
- Assign initial balances
- Account verification

#### Messaging System (`treasury/messages.php`)
- Internal messaging
- Message history
- Read/unread tracking

### LGU Module (`lgu/`)

#### Dashboard (`lgu/dashboard.php`)
- Department overview
- Job postings management
- Quick actions

#### News Management (`lgu/newsfeed.php`)
- Post news and announcements
- Image uploads
- News history

#### Procurement (`lgu/procurement.php`)
- Post procurement listings
- Document uploads
- Procurement history

#### Employee Management (`lgu/manage_employees.php`)
- Create department employees
- Manage employee roles
- Employee directory

### Public Portal (`public/`)

#### Landing Page (`public/public.php`)
- Public information display
- Job listings
- News feed
- Job application modal

#### Job Application (`public/apply_job.php`)
- Submit job applications
- Document upload
- Application tracking

### MSWD Portal (`mswd/`)

#### Public Landing (`mswd/index.php`)
- Assistance types catalog
- Eligibility information
- Application links
- Status tracker access

#### Application Form (`mswd/apply.php`)
- Multi-step application form
- Document upload (PDF, PNG, JPG)
- Form validation
- CSRF protection

#### Status Tracker (`mswd/track.php`)
- Public tracking by tracking number
- Status history display
- Remarks viewing

#### Worker Dashboard (`mswd/worker/dashboard.php`)
- Application statistics
- Filtering and search
- Application list
- Quick review access

#### Application Review (`mswd/worker/review.php`)
- Full applicant details
- Document viewing
- Status updates
- Remarks entry
- History timeline

### Super Admin (`admin/`)

#### Dashboard (`admin/dashboard.php`)
- System overview
- Quick links
- User management access

#### LGU Management (`admin/lgu_list.php`)
- View LGU accounts
- Account status
- Account actions

#### Social Worker Creation (`admin/create_social_worker.php`)
- Create SOCIAL_WORKER accounts
- Password validation
- Department assignment

---

## Security Features

### Implemented Security Measures

#### 1. CSRF Protection
- Token generation on all forms
- Token validation on all handlers
- Automatic token regeneration
- Security event logging

#### 2. SQL Injection Prevention
- All queries use prepared statements
- Parameter binding for all inputs
- Input sanitization
- Type validation

#### 3. Session Security
- Secure session configuration
- Session lifetime management
- Secure cookie flags
- Session regeneration on login

#### 4. Password Security
- Bcrypt hashing (PASSWORD_DEFAULT)
- Strong password validation
- Secure password storage
- No plaintext passwords

#### 5. File Upload Security
- File type validation (MIME type)
- File size limits (5MB max)
- Secure filename generation
- Private storage outside web root
- Authorization required for access

#### 6. Rate Limiting
- Login attempt tracking
- IP-based rate limiting
- Account lockout after failures
- Configurable thresholds

#### 7. Security Headers
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Content-Security-Policy
- Strict-Transport-Security

#### 8. Authorization Checks
- Role-based access control
- Endpoint-specific guards
- Session validation
- Automatic logout on session expiry

#### 9. Security Logging
- Unauthorized access attempts
- CSRF validation failures
- Login failures and successes
- File access logging
- Status change logging

#### 10. Input Validation
- Email validation
- Username validation
- Password strength validation
- Sanitization of all inputs
- Type checking

### Security Configuration

Located in `config/security.php`:

```php
// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// Rate Limiting
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900); // 15 minutes
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 60); // 1 minute
```

---

## API Reference

### Authentication Endpoints

#### POST /login.php
Authenticate user and create session.

**Request:**
```php
POST /login.php
Content-Type: application/x-www-form-urlencoded

username=admin
password=Password123!
```

**Response:** Redirects to role-specific dashboard

#### GET /logout.php
Terminate user session.

**Response:** Redirects to login page

### Treasury Endpoints

#### POST /treasury/handler/post_update_balance.php
Update citizen account balance.

**Request:**
```php
POST /treasury/handler/post_update_balance.php
Content-Type: application/x-www-form-urlencoded

csrf_token=token
user_id=123
amount=5000.00
remarks=Balance update
```

**Response:** Redirects to treasury dashboard

#### POST /treasury/handler/post_create_employee.php
Create treasury employee account.

**Request:**
```php
POST /treasury/handler/post_create_employee.php
Content-Type: application/x-www-form-urlencoded

csrf_token=token
first_name=Juan
last_name=Dela Cruz
username=jdelacruz
email=juan@lgu.gov.ph
password=Password123!
department=Treasury
```

**Response:** Redirects to employee management

### LGU Endpoints

#### POST /handler/post_job.php
Post a job listing.

**Request:**
```php
POST /handler/post_job.php
Content-Type: application/x-www-form-urlencoded

csrf_token=token
title=Administrative Assistant
description=Job description...
requirements=Requirements...
salary=15000-20000
```

**Response:** Redirects to LGU dashboard

#### POST /handler/post_news.php
Post news/announcement.

**Request:**
```php
POST /handler/post_news.php
Content-Type: application/x-www-form-urlencoded

csrf_token=token
title=Holiday Announcement
content=News content...
```

**Response:** Redirects to newsfeed

### MSWD Endpoints

#### POST /mswd/handler/submit_application.php
Submit assistance application.

**Request:**
```php
POST /mswd/handler/submit_application.php
Content-Type: multipart/form-data

csrf_token=token
assistance_type_id=1
first_name=Juan
last_name=Dela Cruz
birthdate=1990-01-01
gender=Male
civil_status=Single
contact_number=09123456789
email=juan@email.com
barangay=Poblacion
street_address=Block 5 Lot 12
documents[]=file1.pdf
documents[]=file2.jpg
```

**Response:** Redirects to confirmation page with tracking number

#### GET /mswd/worker/view_document.php
View uploaded document (Social Worker only).

**Request:**
```php
GET /mswd/worker/view_document.php?id=123
```

**Response:** File download with proper headers

### Admin Endpoints

#### POST /admin/handler/post_create_social_worker.php
Create SOCIAL_WORKER account.

**Request:**
```php
POST /admin/handler/post_create_social_worker.php
Content-Type: application/x-www-form-urlencoded

csrf_token=token
first_name=Maria
last_name=Santos
username=msantos
email=maria@lgu.gov.ph
password=Password123!
department=MSWD
```

**Response:** Redirects to social worker creation page

---

## Configuration

### Environment Variables (.env)

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=lgu_system

# Super Admin Credentials
SUPER_ADMIN_USERNAME=admin
SUPER_ADMIN_EMAIL=admin@lgu.gov.ph
SUPER_ADMIN_PASSWORD=YourSecurePassword123!

# Security Settings
SESSION_LIFETIME=3600
MAX_LOGIN_ATTEMPTS=5
LOGIN_ATTEMPT_WINDOW=900
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60
```

### Database Configuration (config/db.php)

```php
$conn = new mysqli(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    getenv('DB_NAME') ?: 'lgu_system'
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
```

### Security Configuration (config/security.php)

Key security functions:
- `setSecurityHeaders()` - Set HTTP security headers
- `generateCsrfToken()` - Generate CSRF token
- `validateCsrfToken($token)` - Validate CSRF token
- `sanitizeInput($input)` - Sanitize user input
- `validateEmail($email)` - Validate email format
- `validateUsername($username)` - Validate username format
- `validatePassword($password)` - Validate password strength
- `trackLoginAttempt($identifier, $success)` - Track login attempts
- `logSecurityEvent($event, $user_id, $context)` - Log security events
- `logError($message)` - Log system errors

---

## Deployment

### Pre-Deployment Validation

Run the pre-deployment validation script to ensure the server environment is ready:

```bash
# Visit in browser
http://yourdomain.com/deploy_check.php

# Or run via CLI
php deploy_check.php
```

This script checks:
- PHP version compatibility
- Required PHP extensions
- Database connection
- Directory permissions
- Security settings
- Configuration files

### Environment Configuration

1. **Copy environment template:**
```bash
cp .env.example .env
```

2. **Configure .env file:**
```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=lgu_system

# Application Configuration
BASE_URL=https://yourdomain.com
APP_ENV=production
APP_DEBUG=false

# Security Configuration
SESSION_SECURE=true
PASSWORD_MIN_LENGTH=8
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60
MAX_LOGIN_ATTEMPTS=5
LOGIN_ATTEMPT_WINDOW=900
```

### Database Setup

Run all migration files in order:

```bash
# MSWD tables
php migrations/run_mswd_migration.php
php migrations/seed_assistance_types.php

# Scholarship tables
php migrations/create_scholarship_posts.php
php migrations/create_scholarship_applications.php

# Procurement enhancement
php migrations/add_view_count_procurement.php
```

### Directory Permissions

Ensure proper permissions for writable directories:

```bash
chmod 755 lgu/uploads
chmod 755 storage
chmod 755 storage/logs
chmod 755 storage/rate_limits
chmod 755 storage/login_attempts
```

### HTTPS Configuration

For production deployment:

1. Install SSL certificate
2. Set `SESSION_SECURE=true` in .env
3. Update `config/security.php`:
```php
ini_set('session.cookie_secure', 1);
```

### Backup Strategy

Set up automated backups:

```bash
# Database backup (daily)
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# File backup (weekly)
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/project/
```

### Monitoring

Monitor these locations:
- `storage/logs/error_*.log` - Error logs
- `storage/logs/security_*.log` - Security events
- PHP error logs
- Web server access logs

For complete deployment guide, see `DEPLOYMENT.md`.

---

## Troubleshooting

### Common Issues

#### 1. Database Connection Failed
**Symptoms:** "Connection failed" error
**Solutions:**
- Check `.env` database credentials
- Verify MySQL service is running
- Ensure database exists
- Check PHP MySQL extensions

#### 2. Login Not Working
**Symptoms:** Invalid credentials or no redirect
**Solutions:**
- Verify user exists in database
- Check password hash format
- Verify role is correct
- Check session configuration
- Clear browser cookies

#### 3. File Upload Fails
**Symptoms:** Upload error or file not saved
**Solutions:**
- Check directory permissions (755)
- Verify PHP upload_max_filesize
- Check disk space
- Verify file type is allowed
- Check MIME type validation

#### 4. CSRF Validation Failed
**Symptoms:** "Security validation failed" error
**Solutions:**
- Clear browser cache/cookies
- Ensure form includes CSRF token
- Check session is active
- Verify token generation

#### 5. Access Denied
**Symptoms:** 403 error or "Access Denied" message
**Solutions:**
- Verify user role is correct
- Check session is valid
- Ensure user is logged in
- Verify endpoint permissions

#### 6. MSWD Migration Fails
**Symptoms:** Migration script errors
**Solutions:**
- Check database credentials
- Verify MySQL user has CREATE TABLE privileges
- Check for existing tables
- Review SQL syntax
- Check PHP MySQL extensions

### Debug Mode

Enable error reporting for debugging:

```php
// Add to top of PHP files
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

### Log Files

Check logs in `storage/logs/`:
- Security events
- Login attempts
- System errors
- Application errors

### Browser Console

Check browser console for:
- JavaScript errors
- Network request failures
- CORS issues
- Resource loading errors

---

## Maintenance

### Regular Tasks

1. **Daily**
   - Check error logs
   - Monitor disk space
   - Review security logs

2. **Weekly**
   - Backup database
   - Review user accounts
   - Check for updates

3. **Monthly**
   - Rotate log files
   - Review access logs
   - Update documentation

### Backup Strategy

```bash
# Database backup
mysqldump -u root -p lgu_system > backup_$(date +%Y%m%d).sql

# File backup
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/last/

# Restore database
mysql -u root -p lgu_system < backup_20240101.sql
```

### Performance Optimization

1. **Database**
   - Add indexes to frequently queried columns
   - Optimize slow queries
   - Regular database maintenance

2. **PHP**
   - Enable OPcache
   - Optimize session handling
   - Use persistent connections

3. **Files**
   - Compress static assets
   - Enable browser caching
   - Use CDN for static files

---

## Support & Resources

### Documentation Files
- `SYSTEM_DOCUMENTATION.md` - This file
- `MSWD_INTEGRATION.md` - MSWD module documentation
- `SECURITY_IMPLEMENTATION.md` - Security features documentation
- `.env.example` - Environment configuration template

### Contact
For issues or questions:
- Review documentation files
- Check log files
- Verify configuration
- Test with browser dev tools

---

## Version History

### v2.0 (2024-07-29)
- Added MSWD Portal integration
- Added SOCIAL_WORKER role
- Added application tracking system
- Enhanced security features
- Updated documentation

### v1.0 (Initial Release)
- Core LGU system
- Treasury module
- LGU administration
- Public portal
- Super admin functionality
