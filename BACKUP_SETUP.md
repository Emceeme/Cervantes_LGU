# Database Backup Setup for Render

## Manual Backup

To manually backup the database on Render:

```bash
# SSH into your Render service
ssh render@your-app.onrender.com

# Navigate to the app directory
cd /var/www/html

# Run the backup script
php backup_database.php
```

The backup will be saved in the `backups/` directory with a timestamp filename.

## Automated Backups on Render

### Option 1: Render's Built-in PostgreSQL Backups (Recommended)

Render provides automated backups for PostgreSQL databases:

1. Go to your Render dashboard
2. Select your PostgreSQL database service
3. Navigate to "Backups" tab
4. Enable automatic backups (daily, weekly, etc.)
5. Set retention period (7-30 days)

**This is the recommended approach** as it's managed by Render and includes point-in-time recovery.

### Option 2: Cron Job via Render Cron Service

If you need custom backup schedules or want to use the backup script:

1. Create a new Cron service in Render
2. Set the schedule (e.g., `0 2 * * *` for daily at 2 AM)
3. Command: `cd /var/www/html && php backup_database.php`
4. Add environment variables for database credentials

### Option 3: External Cron Job

If you have an external server:

```bash
# Add to crontab (crontab -e)
0 2 * * * curl -X POST https://your-app.onrender.com/backup_database.php
```

**Note:** The backup script is protected to only run from CLI, so you would need to modify it to accept web requests with authentication.

## Backup Retention

The backup script automatically:
- Keeps backups for 7 days
- Compresses backups using gzip
- Cleans up old backups automatically

## Restore from Backup

To restore from a backup:

```bash
# SSH into your Render service
ssh render@your-app.onrender.com

# Navigate to the app directory
cd /var/www/html/backups

# Decompress if needed
gunzip backup_YYYY-MM-DD_HH-MM-SS.sql.gz

# Restore using psql
psql -h localhost -U postgres -d cervantes_lgu < backup_YYYY-MM-DD_HH-MM-SS.sql
```

## Security Notes

- The `backup_database.php` script is protected via `.htaccess` from web access
- Only CLI execution is allowed
- Database credentials are pulled from environment variables
- Backups are stored in the `backups/` directory (not web-accessible)

## Monitoring

Check backup logs by running the script manually or monitoring Render's cron job logs.
