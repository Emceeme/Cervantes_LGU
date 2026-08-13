# Deployment Guide

## Pre-Deployment Checklist

### 1. Server Requirements
- [ ] PHP 7.4 or higher
- [ ] MySQL 5.7 or higher / MariaDB 10.2+
- [ ] Apache or Nginx web server
- [ ] Required PHP extensions: mysqli, mbstring, json, fileinfo, session

### 2. Configuration
- [ ] Copy `.env.example` to `.env`
- [ ] Set `BASE_URL` to your production domain
- [ ] Configure database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME)
- [ ] Set `APP_DEBUG=false` for production
- [ ] Set `FORCE_HTTPS=true` if using HTTPS (enforces HTTPS redirect)
- [ ] Session security is now automatic - detects HTTPS and sets `session.cookie_secure` accordingly
- [ ] For load balancers/proxies, set `FORCE_HTTPS=true` in `.env` to force HTTPS detection

### 3. Database Setup
- [ ] Create MySQL database
- [ ] Run migration files in order:
  - `migrations/create_users_table.php`
  - `migrations/create_news_posts.php`
  - `migrations/create_procurement_posts.php`
  - `migrations/create_scholarship_posts.php`
  - `migrations/create_scholarship_applications.php`
  - `migrations/add_view_count_procurement.php`
- [ ] Create super admin account using `create_super_admin.php`

### 4. File Uploads
- [ ] Ensure `lgu/uploads/` directory exists and is writable
- [ ] Ensure `storage/` directory exists and is writable
- [ ] Set proper permissions (755 for directories, 644 for files)
- [ ] Consider moving uploads outside webroot for enhanced security

### 5. Security
- [ ] Run `deploy_check.php` to validate server environment
- [ ] Enable HTTPS/SSL certificate
- [ ] Set `FORCE_HTTPS=true` in `.env` to enforce HTTPS redirects
- [ ] Set `display_errors=Off` in php.ini
- [ ] Configure firewall rules
- [ ] Set up regular backups
- [ ] Security headers are now automatically set (HSTS, CSP, X-Frame-Options, etc.)
- [ ] Rate limiting is enabled by default (configurable via RATE_LIMIT_REQUESTS and RATE_LIMIT_WINDOW)
- [ ] Login attempt tracking is enabled (configurable via MAX_LOGIN_ATTEMPTS and LOGIN_ATTEMPT_WINDOW)
- [ ] All file paths now use dynamic base URLs via AppConfig for portability

### 6. Testing
- [ ] Test login functionality
- [ ] Test file upload functionality
- [ ] Test all public pages (procurement, news, scholarship, job posting)
- [ ] Test admin panel functionality
- [ ] Test scholarship application submission
- [ ] Test download handler and view count tracking

### 7. Post-Deployment
- [ ] Monitor error logs in `storage/logs/`
- [ ] Monitor security logs in `storage/logs/security_*.log`
- [ ] Set up automated backups
- [ ] Configure monitoring/alerting
- [ ] Document any custom configurations

## Quick Deployment Steps

1. **Upload files** to server document root
2. **Configure .env** with production settings
3. **Run migrations** to create database tables
4. **Create super admin** account
5. **Run deploy_check.php** to validate setup
6. **Test functionality** before going live
7. **Enable HTTPS** for secure connections

## Troubleshooting

### File Upload Issues
- Check directory permissions: `chmod 755 lgu/uploads`
- Check PHP upload limits in php.ini (upload_max_filesize, post_max_size)

### Database Connection Issues
- Verify database credentials in .env
- Check MySQL server is running
- Ensure database user has proper permissions

### Session Issues
- Check session.save_path is writable
- Verify session cookie settings in config/security.php

### Path Issues
- Set BASE_URL in .env to match your domain
- Clear browser cache if old paths are cached

## Environment Variables (.env)

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=lgu_database

# Application Configuration
BASE_URL=https://yourdomain.com
APP_DEBUG=false

# Security Configuration
FORCE_HTTPS=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60
MAX_LOGIN_ATTEMPTS=5
LOGIN_ATTEMPT_WINDOW=900

# Password Requirements
PASSWORD_MIN_LENGTH=8
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBER=true
PASSWORD_REQUIRE_SPECIAL=true
```

## Production Hardening Checklist

### HTTPS Configuration
- [ ] Install valid SSL certificate
- [ ] Set `FORCE_HTTPS=true` in `.env`
- [ ] Verify HSTS header is sent (automatic when HTTPS detected)
- [ ] Test HTTPS redirect functionality

### Session Security
- [ ] Session cookies are now automatically secure when HTTPS is detected
- [ ] Session ID regeneration occurs every 30 minutes
- [ ] SameSite cookie policy set to Strict (HTTPS) or Lax (HTTP)

### Security Headers (Automatic)
- [ ] X-Frame-Options: DENY
- [ ] X-Content-Type-Options: nosniff
- [ ] X-XSS-Protection: 1; mode=block
- [ ] Strict-Transport-Security: max-age=31536000 (HTTPS only)
- [ ] Content-Security-Policy: Configured for self and trusted CDNs
- [ ] Referrer-Policy: strict-origin-when-cross-origin
- [ ] Permissions-Policy: Restricted access to sensitive APIs

### Rate Limiting
- [ ] Default: 100 requests per 60 seconds per IP
- [ ] Configurable via RATE_LIMIT_REQUESTS and RATE_LIMIT_WINDOW
- [ ] Automatic blocking when limit exceeded (2x window duration)

### Login Protection
- [ ] Default: 5 failed attempts blocks for 15 minutes
- [ ] Configurable via MAX_LOGIN_ATTEMPTS and LOGIN_ATTEMPT_WINDOW
- [ ] Automatic reset on successful login

### File Upload Security
- [ ] All upload paths now use absolute paths via __DIR__
- [ ] File type validation enabled
- [ ] File size limits enforced (5MB default)
- [ ] Unique filename generation prevents overwrites

### Logging
- [ ] Error logs: `storage/logs/error_YYYY-MM-DD.log`
- [ ] Security logs: `storage/logs/security_YYYY-MM-DD.log`
- [ ] Rate limit logs: `storage/rate_limits/`
- [ ] Login attempt logs: `storage/login_attempts/`

## Backup Strategy

### Database Backup
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### File Backup
```bash
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/project/
```

### Automated Backup (Cron)
```bash
# Daily database backup at 2 AM
0 2 * * * /usr/bin/mysqldump -u username -ppassword database_name > /backups/db_$(date +\%Y\%m\%d).sql

# Weekly file backup on Sunday at 3 AM
0 3 * * 0 /usr/bin/tar -czf /backups/files_$(date +\%Y\%m\%d).tar.gz /path/to/project/
```

## Support

For issues or questions, check:
- Error logs: `storage/logs/error_*.log`
- Security logs: `storage/logs/security_*.log`
- Run `deploy_check.php` for environment validation
