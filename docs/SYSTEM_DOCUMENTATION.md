# LGU Cervantes Integrated Management System
## Complete Technical Documentation

---

## Table of Contents
1. [System Overview](#system-overview)
2. [Architecture](#architecture)
3. [Database Schema](#database-schema)
4. [User Roles & Permissions](#user-roles--permissions)
5. [Module Descriptions](#module-descriptions)
6. [Security Features](#security-features)
7. [Deployment Guide](#deployment-guide)
8. [API Endpoints](#api-endpoints)
9. [Configuration](#configuration)
10. [Troubleshooting](#troubleshooting)

---

## System Overview

The LGU Cervantes Integrated Management System is a web-based platform designed to streamline municipal operations across multiple departments. Built with PHP and PostgreSQL, it provides a centralized solution for:

- **LGU Operations**: Job applications, news management, procurement, scholarship programs
- **MSWD Services**: Social welfare assistance applications and processing
- **Treasury Operations**: Citizen accounts, balance management, transaction processing
- **System Administration**: User management, security settings, department configuration

### Technology Stack
- **Backend**: PHP 8.x
- **Database**: PostgreSQL (Production) / MySQL (Local)
- **Frontend**: HTML5, CSS3, JavaScript
- **Deployment**: Render (Cloud Platform)
- **File Storage**: Render Persistent Disk

---

## Architecture

### Directory Structure
```
last/
├── admin/                    # Super Admin module
│   ├── dashboard.php
│   ├── settings.php
│   └── handler/
├── lgu/                      # LGU Department module
│   ├── dashboard.php
│   ├── newsfeed.php
│   ├── procurement.php
│   ├── applicants.php
│   ├── scholarship_applications.php
│   ├── manage_employee.php
│   ├── settings.php
│   ├── uploads/
│   └── handler/
├── mswd/                     # MSWD Department module
│   ├── worker/              # MSWD Staff interface
│   ├── applicant/           # Public applicant interface
│   ├── public/              # Public application form
│   └── handler/
├── treasury/                # Treasury Department module
│   ├── dashboard.php
│   ├── citizen/             # Citizen portal
│   ├── messages.php
│   ├── settings.php
│   └── handler/
├── static_page/             # Public-facing pages
│   ├── public/              # Public application forms
│   └── images/
├── config/                  # Configuration files
│   ├── env.php             # Environment loader
│   ├── db.php              # Database connection
│   ├── security.php        # Security functions
│   └── department_settings.php
├── storage/                 # File storage
│   ├── logs/               # Security & error logs
│   ├── login_attempts/     # Rate limiting data
│   └── mswd_documents/     # MSWD application docs
├── migrations/              # Database migrations
├── database/                # SQL scripts
└── .env                     # Environment variables
```

### Data Flow
```
User Request → Authentication → Authorization → Business Logic → Database → Response
```

---

## Database Schema

### Core Tables

#### users
```sql
- id (INT, Primary Key)
- username (VARCHAR, Unique)
- email (VARCHAR, Unique)
- password (VARCHAR, Hashed)
- first_name (VARCHAR)
- last_name (VARCHAR)
- role (ENUM: SUPER_ADMIN, ADMIN, EMPLOYEE, APPLICANT, CITIZEN)
- department (VARCHAR)
- is_active (BOOLEAN)
- created_at (TIMESTAMP)
```

#### jobs
```sql
- id (INT, Primary Key)
- title (VARCHAR)
- description (TEXT)
- requirements (TEXT)
- salary_range (VARCHAR)
- location (VARCHAR)
- created_at (TIMESTAMP)
```

#### applicants
```sql
- id (INT, Primary Key)
- job_id (INT, Foreign Key)
- full_name (VARCHAR)
- email (VARCHAR)
- phone (VARCHAR)
- resume_path (VARCHAR)
- message (TEXT)
- status (ENUM: pending, reviewed, accepted, rejected)
- created_at (TIMESTAMP)
```

#### applications (MSWD)
```sql
- id (INT, Primary Key)
- tracking_number (VARCHAR, Unique)
- assistance_type_id (INT)
- applicant_id (INT)
- first_name (VARCHAR)
- middle_name (VARCHAR)
- last_name (VARCHAR)
- birthdate (DATE)
- gender (VARCHAR)
- civil_status (VARCHAR)
- contact_number (VARCHAR)
- email (VARCHAR)
- barangay (VARCHAR)
- street_address (TEXT)
- status (ENUM: pending, under_review, approved, rejected)
- amount (DECIMAL)
- created_at (TIMESTAMP)
```

#### assistance_types
```sql
- id (INT, Primary Key)
- name (VARCHAR)
- description (TEXT)
- max_amount (DECIMAL)
- is_active (BOOLEAN)
```

#### scholarship_applications
```sql
- id (INT, Primary Key)
- full_name (VARCHAR)
- email (VARCHAR)
- phone (VARCHAR)
- gpa (DECIMAL)
- year_level (INT)
- family_income (DECIMAL)
- essay (TEXT)
- document_path (VARCHAR)
- status (ENUM: pending, under_review, approved, rejected)
- created_at (TIMESTAMP)
```

#### procurement_posts
```sql
- id (INT, Primary Key)
- title (VARCHAR)
- description (TEXT)
- budget (DECIMAL)
- deadline (DATE)
- document_path (VARCHAR)
- created_by (INT)
- created_at (TIMESTAMP)
```

#### news_posts
```sql
- id (INT, Primary Key)
- title (VARCHAR)
- content (TEXT)
- image_path (VARCHAR)
- user_id (INT)
- created_at (TIMESTAMP)
```

#### messages
```sql
- id (INT, Primary Key)
- sender_id (INT)
- receiver_id (INT, Nullable)
- parent_id (INT, Nullable)
- department (VARCHAR)
- subject (VARCHAR)
- message (TEXT)
- status (ENUM: read, unread)
- created_at (TIMESTAMP)
```

#### department_settings
```sql
- department (VARCHAR, Primary Key)
- settings_json (JSON)
```

---

## User Roles & Permissions

### SUPER_ADMIN
- **Access**: All modules and settings
- **Permissions**: 
  - Manage all users
  - Configure system-wide settings
  - View all department data
  - Access admin dashboard

### ADMIN (Sub-Admin)
- **Access**: Department-specific management
- **Permissions**:
  - Manage employees in their department
  - Configure department settings
  - View department data
  - Restricted to their department

### EMPLOYEE
- **Access**: Department-specific operations
- **Permissions**:
  - Process applications
  - View assigned tasks
  - Update records
  - Restricted to their department

### APPLICANT
- **Access**: MSWD application portal
- **Permissions**:
  - Submit assistance applications
  - View application status
  - Upload documents
  - Track application progress

### CITIZEN
- **Access**: Treasury citizen portal
- **Permissions**:
  - View account balance
  - Send inquiries
  - View messages
  - Access public services

---

## Module Descriptions

### LGU Module
**Purpose**: Manage LGU operations including employment, news, procurement, and scholarships.

**Features**:
- Job posting and application management
- News feed management
- Procurement announcements
- Scholarship application processing
- Employee management (for admins)
- Department settings configuration

**Key Files**:
- `lgu/dashboard.php` - Main dashboard
- `lgu/newsfeed.php` - News management
- `lgu/procurement.php` - Procurement posts
- `lgu/applicants.php` - Job applicants
- `lgu/scholarship_applications.php` - Scholarship applications
- `lgu/manage_employee.php` - Employee management

### MSWD Module
**Purpose**: Process social welfare assistance applications.

**Features**:
- Public assistance application form
- Application review and approval workflow
- Document management
- Bulk status updates
- Application reports
- Department settings

**Key Files**:
- `mswd/public/apply.php` - Public application form
- `mswd/worker/dashboard.php` - Staff dashboard
- `mswd/worker/review.php` - Application review
- `mswd/worker/reports.php` - Application reports
- `mswd/worker/bulk_update.php` - Bulk status updates
- `mswd/applicant/my-applications.php` - Applicant portal

### Treasury Module
**Purpose**: Manage citizen accounts and treasury operations.

**Features**:
- Citizen account creation
- Balance management
- Transaction processing
- Messaging system
- Department settings

**Key Files**:
- `treasury/dashboard.php` - Staff dashboard
- `treasury/citizen/dashboard.php` - Citizen portal
- `treasury/messages.php` - Messaging system
- `treasury/create_public_user.php` - Create citizen accounts
- `treasury/update_balance.php` - Balance updates

### Admin Module
**Purpose**: System-wide administration and configuration.

**Features**:
- User management
- System settings
- Department configuration
- Security monitoring
- Backup management

**Key Files**:
- `admin/dashboard.php` - Admin dashboard
- `admin/settings.php` - System settings
- `admin/lgu_list.php` - User management

---

## Security Features

### Authentication
- Secure session management with 30-minute timeout
- Password hashing using `password_hash()` (bcrypt)
- Rate limiting on login attempts (5 attempts max)
- IP-based blocking for repeated failures

### Authorization
- Role-based access control (RBAC)
- Department-based access restrictions
- Session validation on each request
- Automatic redirect to login on unauthorized access

### Input Validation
- Server-side validation on all forms
- File type validation using MIME type detection
- File size limits (5MB default)
- SQL injection prevention using prepared statements
- XSS prevention through output escaping

### CSRF Protection
- CSRF token generation and validation
- Token validation on all form submissions
- Automatic token regeneration

### Security Headers
- Content Security Policy (CSP)
- X-Frame-Options
- X-Content-Type-Options
- Strict-Transport-Security (HTTPS enforcement)

### Logging
- Security event logging
- Error logging to file
- Login attempt tracking
- Unauthorized access logging

### File Upload Security
- Secure filename generation (random + timestamp)
- MIME type validation
- File extension validation
- Directory access prevention
- Render persistent disk support

---

## Deployment Guide

### Prerequisites
- PHP 8.x
- PostgreSQL 14+ (Production) or MySQL 8+ (Local)
- Render account (for production deployment)
- Domain name (optional)

### Local Development Setup

1. **Clone Repository**
```bash
git clone https://github.com/Emceeme/Cervantes_LGU.git
cd Cervantes_LGU
```

2. **Configure Environment**
```bash
cp .env.example .env
```

3. **Edit .env File**
```
APP_ENV=development
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=lgu_system
UPLOAD_DIR=storage/uploads
```

4. **Set Up Database**
```bash
# For MySQL
mysql -u root -p < database/schema.sql

# For PostgreSQL
psql -U postgres -d lgu_system < database/schema_postgres.sql
```

5. **Start Local Server**
```bash
php -S localhost:8000
```

### Render Deployment

1. **Create Render Service**
- Web Service type
- Build Command: `php -v`
- Start Command: `php -S 0.0.0.0:$PORT`

2. **Configure Environment Variables**
```
APP_ENV=production
DATABASE_URL=postgresql://user:pass@host:port/dbname
UPLOAD_DIR=/var/data/uploads
```

3. **Add Persistent Disk**
- Mount path: `/var/data`
- Size: 10GB (recommended)

4. **Connect PostgreSQL**
- Use Render's managed PostgreSQL
- Add DATABASE_URL to environment variables

5. **Deploy**
- Push to GitHub
- Render auto-deploys on push

---

## API Endpoints

### Authentication
- `POST /login.php` - User login
- `GET /logout.php` - User logout

### LGU Module
- `GET /lgu/dashboard.php` - LGU dashboard
- `GET /lgu/newsfeed.php` - News feed
- `POST /lgu/handler/post_news.php` - Create news post
- `DELETE /lgu/handler/delete_procurement.php` - Delete procurement
- `GET /lgu/applicants.php` - View job applicants
- `GET /lgu/scholarship_applications.php` - View scholarship applications
- `POST /lgu/update_scholarship_status.php` - Update scholarship status

### MSWD Module
- `GET /mswd/worker/dashboard.php` - MSWD dashboard
- `POST /mswd/handler/submit_application.php` - Submit application
- `GET /mswd/worker/review.php` - Review application
- `POST /mswd/worker/bulk_update.php` - Bulk status update
- `GET /mswd/applicant/my-applications.php` - Applicant portal

### Treasury Module
- `GET /treasury/dashboard.php` - Treasury dashboard
- `GET /treasury/citizen/dashboard.php` - Citizen dashboard
- `POST /treasury/handler/post_create_public_user.php` - Create citizen
- `POST /treasury/handler/post_update_balance.php` - Update balance
- `GET /treasury/messages.php` - Messages

### Admin Module
- `GET /admin/dashboard.php` - Admin dashboard
- `GET /admin/settings.php` - System settings
- `GET /admin/lgu_list.php` - User management

### Public Forms
- `POST /static_page/public/apply_job.php` - Submit job application
- `POST /static_page/public/apply_scholarship.php` - Submit scholarship application
- `POST /mswd/public/apply.php` - Submit MSWD application

---

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| APP_ENV | Environment (development/production) | development |
| DB_HOST | Database host | localhost |
| DB_USER | Database username | root |
| DB_PASS | Database password | (empty) |
| DB_NAME | Database name | lgu_system |
| DATABASE_URL | PostgreSQL connection string | (from Render) |
| UPLOAD_DIR | (Render) Persistent disk path | storage/uploads |

### Department Settings

Each department has configurable settings stored in `department_settings` table:

**LGU Settings**:
- Department name
- Contact information
- Office hours
- File upload limits
- Scholarship deadlines
- Procurement notice periods

**MSWD Settings**:
- Assistance amount ranges
- Document verification requirements
- Review deadlines
- Monthly application limits

**Treasury Settings**:
- Balance thresholds
- Transaction rules
- Notification settings

**Admin Settings**:
- Maintenance mode
- Backup retention days
- Log retention days
- Login attempt limits
- Session timeout
- Password requirements

---

## Troubleshooting

### Common Issues

**Database Connection Failed**
- Check DATABASE_URL in environment variables
- Verify database is running
- Check database credentials

**File Upload Failed**
- Verify UPLOAD_DIR is set correctly
- Check directory permissions (755)
- Ensure persistent disk is mounted (Render)

**Login Redirect Loop**
- Clear browser cookies
- Check session timeout settings
- Verify session storage is writable

**Error Page Shows in Production**
- Check APP_ENV is set to "production"
- Verify error_log directory exists and is writable
- Check error logs in storage/logs/

**Rate Limiting Issues**
- Check storage/login_attempts directory permissions
- Clear rate limit files if needed
- Verify IP detection is working

---

## Maintenance

### Database Backups
```bash
# PostgreSQL backup
pg_dump -U postgres lgu_system > backup_$(date +%Y%m%d).sql

# Restore backup
psql -U postgres lgu_system < backup_20240830.sql
```

### Log Rotation
- Security logs: `storage/logs/security.log`
- Error logs: `storage/logs/php_errors.log`
- Recommended retention: 90 days

### Performance Monitoring
- Monitor database query performance
- Check file storage usage
- Review error logs regularly
- Monitor login attempt patterns

---

## Support

For technical support or issues:
1. Check error logs in `storage/logs/`
2. Review this documentation
3. Check Render deployment logs
4. Contact system administrator

---

## Version History

- **v1.0** (2024-08-30): Initial production release
  - Core LGU, MSWD, Treasury modules
  - User authentication and authorization
  - File upload system with persistent storage
  - Production error handling
  - Security features implemented

---

## License

Proprietary - Municipality of Cervantes, Ilocos Sur

---

## Credits

Developed for the Municipality of Cervantes, Ilocos Sur
