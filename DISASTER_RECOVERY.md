# Disaster Recovery Guide

This document provides comprehensive procedures for recovering the LGU Portal system from various failure scenarios.

## Table of Contents

- [Backup Strategy](#backup-strategy)
- [Recovery Procedures](#recovery-procedures)
- [File Persistence](#file-persistence)
- [Testing Recovery](#testing-recovery)
- [Emergency Contacts](#emergency-contacts)

## Backup Strategy

### Database Backups

**Automated Backups (Recommended)**
- **Frequency**: Daily at 2 AM UTC
- **Retention**: 7 days
- **Location**: `/backups/` directory on server
- **Format**: Compressed SQL (.sql.gz)

**Manual Backups**
```bash
php scripts/backup_database.php
```

**Backup Contents**
- All database tables (users, jobs, applicants, news, scholarship, procurement)
- Schema and data
- No passwords (uses environment variables)

### File Backups

**Current Issue**: Render Free tier wipes uploaded files on redeploy.

**Solutions**:
1. **Render Disk (Recommended)** - Upgrade to Standard plan with persistent disk
2. **External Storage** - Use AWS S3, Cloudflare R2, or similar
3. **Database Storage** - Store files as BLOB (not recommended for large files)

## Recovery Procedures

### Scenario 1: Database Corruption

**Symptoms**
- Database connection errors
- Data inconsistencies
- Query failures

**Recovery Steps**

1. **Identify the corruption**
   ```bash
   # Check database logs
   # Test connection
   php -r "require 'config/db.php'; echo 'Connected';"
   ```

2. **Stop application access**
   - Put site in maintenance mode
   - Disable web access if needed

3. **Restore from latest backup**
   ```bash
   # Find latest backup
   ls -lt backups/
   
   # Decompress if needed
   gunzip backups/backup_YYYY-MM-DD_HH-MM-SS.sql.gz
   
   # Restore to database
   psql -h host -U user -d dbname < backups/backup_YYYY-MM-DD_HH-MM-SS.sql
   ```

4. **Verify restoration**
   - Check critical tables (users, jobs, applicants)
   - Test login functionality
   - Verify data integrity

5. **Resume operations**
   - Remove maintenance mode
   - Monitor for errors

### Scenario 2: Complete Server Failure

**Symptoms**
- Server completely inaccessible
- Render service down
- No SSH access

**Recovery Steps**

1. **Assess the situation**
   - Check Render dashboard status
   - Verify if it's a regional outage
   - Check if data is recoverable

2. **Deploy to new environment**
   ```bash
   # Create new Render service
   # Clone repository
   # Configure environment variables
   # Run migrations
   curl -X POST -d 'auth_key=run_migrations_secure_key' https://new-app.onrender.com/run_migrations.php
   ```

3. **Restore database**
   - If backups are on Render: Download from old service
   - If backups are external: Download from backup location
   - Restore to new database

4. **Restore uploaded files**
   - If using Render Disk: Disk should be attached to new service
   - If using external storage: Files already available
   - If no persistent storage: Files are lost (re-upload required)

5. **Update DNS**
   - Point domain to new service
   - Update any hardcoded URLs

6. **Test thoroughly**
   - All user flows
   - File uploads/downloads
   - Authentication

### Scenario 3: Accidental Data Deletion

**Symptoms**
- User reports missing data
- Tables appear empty
- Recent changes lost

**Recovery Steps**

1. **Identify what was deleted**
   - Check application logs
   - Identify affected tables
   - Determine time of deletion

2. **Stop further damage**
   - Revoke admin access if needed
   - Disable delete operations temporarily

3. **Restore from backup**
   - Choose backup from before deletion
   - Restore to a temporary database
   ```bash
   # Create temporary database
   createdb lgu_portal_recovery
   
   # Restore backup
   psql -h host -U user -d lgu_portal_recovery < backup.sql
   ```

4. **Extract and merge data**
   ```sql
   -- Copy missing data back to production
   INSERT INTO production_table
   SELECT * FROM recovery_table WHERE id NOT IN (SELECT id FROM production_table);
   ```

5. **Verify and cleanup**
   - Check data integrity
   - Drop temporary database
   - Document incident

### Scenario 4: Security Breach

**Symptoms**
- Unauthorized access detected
- Suspicious activity in logs
- Data theft suspected

**Recovery Steps**

1. **Immediate containment**
   - Change all admin passwords
   - Disable compromised accounts
   - Revoke all sessions
   ```php
   // Force logout all users
   TRUNCATE TABLE sessions;
   ```

2. **Assess damage**
   - Review security event logs
   - Check for data exfiltration
   - Identify compromised accounts

3. **Secure the system**
   - Update all passwords
   - Review and tighten access controls
   - Update security headers
   - Enable additional monitoring

4. **Restore from clean backup**
   - Use backup from before breach
   - Ensure no backdoors exist
   - Re-apply security patches

5. **Notify stakeholders**
   - Inform users if data was exposed
   - Document the incident
   - Implement preventive measures

## File Persistence

### Current State (Render Free Tier)
- **Problem**: Files are wiped on every redeploy
- **Impact**: Lost uploaded resumes, news images, scholarship documents
- **Status**: 🔴 Critical Issue

### Recommended Solutions

#### Option 1: Render Disk (Recommended)

**Pros**
- Simple to implement
- Integrated with Render
- Automatic backups
- Good performance

**Cons**
- Requires Standard plan ($7/month minimum)
- Limited to single region

**Implementation**
```yaml
# Update render.yaml
services:
  - type: web
    name: cervantes-lgu
    plan: standard  # Upgrade from free
    disk:
      name: uploads
      mountPath: /opt/render/project/lgu/uploads
      sizeGB: 10
```

**Migration Steps**
1. Upgrade service plan in Render dashboard
2. Add disk configuration
3. Redeploy application
4. Files will persist across redeploys

#### Option 2: Cloudflare R2 (Cost-Effective)

**Pros**
- Free egress (no download fees)
- S3-compatible API
- Very cheap storage
- Global distribution

**Cons**
- Requires code changes
- Additional service to manage
- Slight latency increase

**Implementation**
1. Create R2 bucket
2. Install AWS SDK for PHP
3. Update file upload handlers
4. Update file display logic

#### Option 3: AWS S3

**Pros**
- Industry standard
- High reliability
- Many integrations

**Cons**
- Egress fees
- More expensive than R2
- Requires AWS account

### Temporary Workaround

For immediate relief while evaluating options:
1. Manually backup `/lgu/uploads/` directory before redeploy
2. Restore after redeploy
3. Not sustainable long-term

## Testing Recovery

### Regular Testing Schedule

**Monthly**
- Test database restore from latest backup
- Verify backup integrity
- Check backup rotation

**Quarterly**
- Full disaster recovery drill
- Test complete server failure scenario
- Verify all procedures work

**Annually**
- Review and update recovery procedures
- Test new backup solutions
- Train staff on recovery procedures

### Testing Checklist

- [ ] Can restore database from backup?
- [ ] Can access backup files?
- [ ] Are backups recent enough?
- [ ] Do restored backups work correctly?
- [ ] Is documentation up to date?
- [ ] Are contact details current?

## Emergency Contacts

### Technical Support
- **Database Administrator**: [Contact Info]
- **Render Support**: https://render.com/support
- **Hosting Provider**: [Contact Info]

### LGU Contacts
- **IT Department Head**: [Contact Info]
- **Mayor's Office**: [Contact Info]
- **System Administrator**: [Contact Info]

### Service Providers
- **Render**: https://dashboard.render.com
- **PostgreSQL**: https://www.postgresql.org/support/
- **PHP Support**: https://www.php.net/support.php

## Prevention

### Best Practices

1. **Regular Backups**
   - Daily automated backups
   - Weekly backup verification
   - Monthly full system backup

2. **Monitoring**
   - Database health checks
   - Disk space monitoring
   - Error log monitoring
   - Security event logging

3. **Documentation**
   - Keep this document updated
   - Document all changes
   - Maintain change log

4. **Testing**
   - Regular recovery drills
   - Backup verification
   - Security audits

5. **Security**
   - Regular password updates
   - Access reviews
   - Security patches
   - Employee training

## Appendix

### Backup File Naming Convention
```
backup_YYYY-MM-DD_HH-MM-SS.sql.gz
Example: backup_2024-08-31_14-30-00.sql.gz
```

### Database Connection Information
- **Host**: From DATABASE_URL environment variable
- **Port**: 5432 (PostgreSQL default)
- **Database**: cervantes_lgu (or as configured)
- **User**: From DATABASE_URL environment variable

### Critical Tables
- `users` - User accounts and authentication
- `jobs` - Job postings
- `applicants` - Job applications
- `news_posts` - News and announcements
- `scholarship_applications` - Scholarship applications
- `procurement_posts` - Procurement documents

### Recovery Time Objectives
- **RPO (Recovery Point Objective)**: 24 hours (max data loss)
- **RTO (Recovery Time Objective)**: 4 hours (max downtime)

---

**Last Updated**: August 31, 2026
**Version**: 1.0
**Maintained By**: LGU IT Department
