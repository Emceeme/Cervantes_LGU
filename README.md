# LGU Portal System

A comprehensive web-based portal for Local Government Unit (LGU) operations, including job postings, scholarship programs, news management, and procurement services.

## Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Running Migrations](#running-migrations)
- [Deployment](#deployment)
- [File Structure](#file-structure)
- [Security](#security)
- [Maintenance](#maintenance)
- [Troubleshooting](#troubleshooting)

## Features

### LGU Admin Panel (`/lgu/`)
- **Dashboard**: Overview of system statistics and quick access to modules
- **Job Management**: Post, view, and manage job openings with employment type tracking
- **Applicant Management**: Review job applications and download resumes
- **Procurement**: Manage procurement documents and bid announcements
- **News Feed**: Publish news posts with images for public viewing
- **Scholarship Applications**: Review and manage scholarship applications
- **Employee Management**: Create and manage employee accounts (Admin/Super Admin only)
- **Settings**: Configure LGU department settings

### Public Portal (`/static_page/public/`)
- **Career Opportunities**: Browse and apply for open positions
- **News & Announcements**: View latest LGU news and updates
- **Scholarship Programs**: View available scholarships and submit applications
- **Procurement**: Access bid bulletins, awards, and procurement documents

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: PostgreSQL (Production) / MySQL (Development)
- **Frontend**: HTML5, CSS3, JavaScript
- **Database Access**: PDO (PostgreSQL) / MySQLi (MySQL)
- **Deployment**: Render (recommended)

## Installation

### Prerequisites
- PHP 7.4 or higher
- PostgreSQL or MySQL database
- Web server (Apache/Nginx)
- Composer (optional, for dependency management)

### Local Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/Emceeme/Cervantes_LGU.git
   cd Cervantes_LGU
   ```

2. **Configure database**
   - Copy `config/db.php` and update database credentials
   - For PostgreSQL: Set `DATABASE_URL` environment variable
   - For MySQL: Update connection parameters in `config/db.php`

3. **Set up upload directories**
   ```bash
   php setup_uploads.php
   ```

4. **Run migrations**
   ```bash
   php run_migrations.php
   ```

5. **Configure web server**
   - Point document root to project directory
   - Ensure `lgu/uploads/` directory is writable

## Configuration

### Database Configuration

Edit `config/db.php`:

```php
// For PostgreSQL (Render)
$DATABASE_URL = getenv('DATABASE_URL');

// For MySQL (Local)
$host = 'localhost';
$dbname = 'lgu_portal';
$username = 'root';
$password = '';
```

### Application Configuration

Edit `config/app_config.php` to customize:
- Base URL
- Upload directories
- File URL generation

### Security Configuration

Edit `config/security.php` to customize:
- Session settings
- CSRF token generation
- Security headers
- Access control rules

## Database Setup

### Running Migrations

**Option 1: Run all migrations automatically**
```bash
php run_migrations.php
```

**Option 2: Run individual migrations**
```bash
php migrations/create_users_table.php
php migrations/create_jobs_table.php
php migrations/create_news_posts_table.php
php migrations/create_scholarship_posts_table.php
php migrations/create_scholarship_applications.php
php migrations/create_procurement_posts_table.php
php migrations/create_applicants_table.php
```

**Option 3: Run via HTTP (Production)**
```bash
curl -X POST https://your-app.onrender.com/run_migrations.php
```

### Adding New Columns

To add the tracking number column for scholarship applications:
```bash
php migrations/add_tracking_number_to_scholarship.php
```

## Deployment

### Render Deployment

1. **Create a new Web Service on Render**
   - Connect your GitHub repository
   - Select PHP as the runtime
   - Set build command: `php setup_uploads.php && php run_migrations.php`
   - Set start command: `php -S 0.0.0.0:10000 -t .`

2. **Environment Variables**
   - `DATABASE_URL`: PostgreSQL connection string
   - `APP_ENV`: `production`

3. **Post-Deployment Steps**
   - Run `setup_uploads.php` to create upload directories
   - Run `run_migrations.php` to set up database tables
   - Run `add_tracking_number_to_scholarship.php` for tracking number support
   - Delete `run_migrations.php` and `cleanup.php` for security

4. **Automated Backups (Recommended)**
   - Upgrade to Render Standard plan for cron jobs
   - Configure daily database backups via `render.yaml`
   - Backups stored in `/backups/` directory with 7-day retention

5. **File Persistence (Critical)**
   - Render Free tier wipes files on redeploy
   - **Recommended**: Upgrade to Standard plan with Render Disk
   - **Alternative**: Use Cloudflare R2 or AWS S3 for external storage
   - See `DISASTER_RECOVERY.md` for detailed options

### Upload Directory Setup

The application requires the following directory structure:
```
lgu/
├── uploads/
│   ├── news/
│   ├── scholarship/
│   ├── resumes/
│   └── procurement/
```

Run `setup_uploads.php` to create these directories automatically.

## File Structure

```
Cervantes_LGU/
├── config/
│   ├── app_config.php       # Application configuration
│   ├── db.php               # Database connection
│   └── security.php         # Security functions
├── lgu/
│   ├── dashboard.php        # Admin dashboard
│   ├── applicants.php       # Job applicant management
│   ├── procurement.php      # Procurement management
│   ├── newsfeed.php         # News post management
│   ├── scholarship_applications.php  # Scholarship application review
│   ├── manage_employee.php  # Employee account management
│   ├── settings.php        # LGU settings
│   ├── handler/             # Form handlers
│   │   ├── post_job.php
│   │   ├── post_news.php
│   │   ├── post_scholarship.php
│   │   ├── post_procurement.php
│   │   ├── post_employee.php
│   │   └── delete_applicant.php
│   └── uploads/             # File upload directories
├── static_page/
│   └── public/
│       ├── public.php       # Job listings
│       ├── news.php         # News feed
│       ├── scholarship.php  # Scholarship programs
│       ├── procurement.php  # Procurement documents
│       ├── apply_job.php    # Job application handler
│       └── apply_scholarship.php  # Scholarship application handler
├── migrations/              # Database migration scripts
├── login.php               # Login page
├── logout.php              # Logout handler
├── setup_uploads.php       # Upload directory setup
├── cleanup.php             # Database/file cleanup utility
└── run_migrations.php      # Migration runner
```

## Security

### Access Control

The system implements role-based access control:
- **SUPER_ADMIN**: Full access to all modules and employee management
- **ADMIN**: Access to department-specific modules and employee management
- **EMPLOYEE**: Access to assigned department modules only

### Security Features

- CSRF token validation on all forms
- SQL injection prevention using prepared statements
- XSS protection via output escaping
- File upload validation (type, size, extension)
- Session-based authentication
- Security headers (CSP, X-Frame-Options, etc.)
- Access logging for unauthorized attempts

### File Upload Security

- File type validation using MIME type
- File extension whitelist
- File size limits (5MB for most uploads)
- Unique filename generation
- Directory traversal prevention

## Maintenance

### Backup Database

```bash
# PostgreSQL
pg_dump $DATABASE_URL > backup.sql

# MySQL
mysqldump -u root -p lgu_portal > backup.sql
```

### Cleanup System

To reset the database and delete all uploaded files:
```bash
php cleanup.php
```

**Warning**: This will permanently delete all data and files. Type "DELETE" to confirm.

### Log Files

Check application logs for:
- Security events (unauthorized access attempts)
- Database errors
- File upload failures

## Troubleshooting

### Job Postings Not Displaying

1. Check database connection in `config/db.php`
2. Verify jobs table has records with `status = 'OPEN'`
3. Check error logs for SQL errors
4. Ensure employment type validation matches form options

### File Upload Issues

1. Verify upload directories exist and are writable
2. Check PHP upload_max_filesize and post_max_size settings
3. Ensure file type validation passes
4. Run `setup_uploads.php` to recreate directories

### Database Connection Errors

1. Verify `DATABASE_URL` environment variable (PostgreSQL)
2. Check MySQL credentials in `config/db.php`
3. Ensure database server is running
4. Test connection using migration scripts

### Images Not Displaying

1. Verify files exist in upload directories
2. Check `AppConfig` helper functions in `config/app_config.php`
3. Ensure correct base URL is configured
4. Check file permissions

### Migration Errors

1. Check database connection before running migrations
2. Ensure tables don't already exist (or use IF NOT EXISTS)
3. Verify database user has CREATE TABLE permissions
4. Check for syntax errors in migration scripts

## Support

For issues or questions:
- Check the troubleshooting section above
- Review error logs
- Verify configuration files
- Ensure all migrations have been run

## License

This project is proprietary software for LGU use.
