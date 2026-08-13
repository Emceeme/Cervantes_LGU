# Cloud Deployment Guide

This guide covers deploying the LGU System to cloud platforms like Heroku, Render, or similar PaaS providers.

## Supported Platforms

- **Heroku** - Full-featured PaaS with free tier
- **Render** - Modern PaaS with generous free tier
- **Railway** - Simple deployment with managed databases
- **DigitalOcean App Platform** - Scalable with managed databases

## Pre-Deployment Checklist

- [ ] Git repository initialized
- [ ] All migrations tested locally
- [ ] Environment variables documented
- [ ] File storage strategy planned (cloud storage recommended)
- [ ] Database backup strategy planned

## Heroku Deployment

### 1. Create Heroku Account
- Sign up at [heroku.com](https://heroku.com)
- Install Heroku CLI: `npm install -g heroku`
- Login: `heroku login`

### 2. Create Heroku App
```bash
heroku create cervantes-lgu
```

### 3. Add PostgreSQL Database
```bash
heroku addons:create heroku-postgresql:mini
```

### 4. Set Environment Variables
```bash
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set FORCE_HTTPS=true
heroku config:set BASE_URL=https://your-app-name.herokuapp.com
heroku config:set EMAIL_NOTIFICATIONS_ENABLED=true
heroku config:set SMTP_FROM_EMAIL=mswd@cervantes.gov.ph
```

### 5. Configure Database Connection
The system automatically uses Heroku's `DATABASE_URL` environment variable. Update `config/db.php` to parse this:

```php
// Add to config/db.php
if (getenv('DATABASE_URL')) {
    $db_url = parse_url(getenv('DATABASE_URL'));
    $db_host = $db_url['host'];
    $db_user = $db_url['user'];
    $db_pass = $db_url['pass'];
    $db_name = ltrim($db_url['path'], '/');
    $db_port = $db_url['port'] ?? 5432;
}
```

### 6. Deploy Code
```bash
git init
git add .
git commit -m "Initial deployment"
heroku git:remote -a cervantes-lgu
git push heroku main
```

### 7. Run Migrations
```bash
heroku run php migrations/create_department_settings.php
heroku run php mswd/migrations/create_tables.php
heroku run php mswd/migrations/seed_assistance_types.php
```

### 8. Create Storage Directories
```bash
heroku run php -r "mkdir('storage/logs', 0755, true);"
heroku run php -r "mkdir('storage/mswd_documents', 0755, true);"
```

### 9. Create Super Admin
```bash
heroku run php create_super_admin.php
```

## Render Deployment

### 1. Create Render Account
- Sign up at [render.com](https://render.com)
- Connect your GitHub repository

### 2. Create PostgreSQL Database
- Go to Render Dashboard → New → PostgreSQL
- Name: `cervantes-lgu-db`
- Region: Choose nearest to your users
- Plan: Free tier available

### 3. Create Web Service
- Go to Render Dashboard → New → Web Service
- Connect your GitHub repository
- Configure:
  - **Build Command**: `composer install`
  - **Start Command**: `heroku-php-apache2 public/`
  - **Environment Variables**:
    ```
    APP_ENV=production
    APP_DEBUG=false
    FORCE_HTTPS=true
    BASE_URL=https://your-app.onrender.com
    DATABASE_URL=postgresql://user:pass@host:port/dbname
    ```

### 4. Deploy
- Render will auto-deploy on push to main branch
- View logs in Render dashboard

### 5. Run Migrations
- Use Render Shell or SSH to run migration scripts
- Or create a one-time deploy script

## File Storage Strategy

Cloud platforms have ephemeral filesystems. Use cloud storage for uploads:

### Option 1: AWS S3 (Recommended)
```php
// config/s3_config.php
require 'vendor/autoload.php';
use Aws\S3\S3Client;

$s3 = new S3Client([
    'region' => env('AWS_REGION', 'us-east-1'),
    'version' => 'latest',
    'credentials' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY')
    ]
]);
```

Set environment variables:
```bash
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_REGION=us-east-1
AWS_S3_BUCKET=your-bucket-name
```

### Option 2: Cloudinary (Easier)
- Sign up at [cloudinary.com](https://cloudinary.com)
- Use their PHP SDK for uploads
- Free tier available

### Option 3: Render Disk (Simpler)
- Render offers persistent disk storage
- Add disk to your web service
- Mount at `/var/data/uploads`

## Environment Variables Reference

### Required
- `APP_ENV` - Set to `production`
- `APP_DEBUG` - Set to `false`
- `BASE_URL` - Your deployed URL
- `DATABASE_URL` - Database connection string

### Security
- `FORCE_HTTPS` - Set to `true`
- `RATE_LIMIT_REQUESTS` - Default 100
- `RATE_LIMIT_WINDOW` - Default 60
- `MAX_LOGIN_ATTEMPTS` - Default 5

### Email (Optional)
- `EMAIL_NOTIFICATIONS_ENABLED` - Set to `true`
- `SMTP_FROM_EMAIL` - Sender email
- `SMTP_FROM_NAME` - Sender name

### SMS (Optional)
- `SMS_NOTIFICATIONS_ENABLED` - Set to `true`
- `SMS_API_KEY` - Your SMS gateway key
- `SMS_SENDER_ID` - Sender ID

## Database Backups

### Heroku
```bash
# Manual backup
heroku pg:backups:capture

# Restore
heroku pg:backups:restore

# Schedule automatic backups
heroku addons:create heroku-postgresql:standard --backup-follow
```

### Render
- Render PostgreSQL includes automatic backups
- View in database dashboard
- Manual backup available via pg_dump

## Monitoring

### Heroku
```bash
# View logs
heroku logs --tail

# View metrics
heroku metrics
```

### Render
- View logs in dashboard
- Metrics available in monitoring section

## Scaling

### Heroku
```bash
# Scale up
heroku ps:scale web=standard-1x

# Scale down
heroku ps:scale web=free
```

### Render
- Adjust resources in web service settings
- Auto-scaling available on paid plans

## SSL/HTTPS

Both Heroku and Render provide automatic SSL certificates:
- Heroku: Automatic ACM certificates
- Render: Automatic Let's Encrypt certificates
- Set `FORCE_HTTPS=true` in environment variables

## Troubleshooting

### Database Connection Issues
- Verify `DATABASE_URL` is set correctly
- Check database is running
- Test connection using platform CLI

### File Upload Issues
- Use cloud storage (S3, Cloudinary)
- Ephemeral filesystem will lose files on redeploy
- Configure proper permissions

### Migration Failures
- Run migrations manually via platform CLI
- Check database schema exists
- Verify database user has CREATE TABLE permissions

### Email Not Sending
- Verify SMTP settings in environment
- Check platform allows outbound emails
- Test with mail() function first

## Cost Estimation

### Heroku (Free Tier)
- Web Dyno: Free (sleeps after 30 min inactivity)
- PostgreSQL Mini: Free (5GB)
- **Total: $0/month**

### Heroku (Basic Production)
- Web Dyno: $7/month
- PostgreSQL Basic: $9/month
- **Total: ~$16/month**

### Render (Free Tier)
- Web Service: Free (spins down after inactivity)
- PostgreSQL: Free (90 days, then $7/month)
- **Total: $0/month (first 90 days)**

### Render (Basic Production)
- Web Service: $7/month
- PostgreSQL: $7/month
- **Total: ~$14/month**

## Post-Deployment Checklist

- [ ] Test all user flows
- [ ] Verify SSL certificate is active
- [ ] Test file uploads (if using cloud storage)
- [ ] Test email notifications
- [ ] Verify database backups are running
- [ ] Set up monitoring alerts
- [ ] Configure custom domain (optional)
- [ ] Update DNS records (if using custom domain)

## Custom Domain Setup

### Heroku
```bash
heroku domains:add www.cervantes.gov.ph
heroku addons:create heroku-postgresql:standard
```

### Render
- Add custom domain in web service settings
- Update DNS CNAME record to point to Render

## Security Notes

- Platform handles server security (OS patches, firewalls)
- Application security (CSRF, rate limiting) still active
- Keep environment variables secret
- Use strong database passwords
- Enable HTTPS (automatic on most platforms)
- Regularly update dependencies

## Support

- Heroku: [devcenter.heroku.com](https://devcenter.heroku.com)
- Render: [render.com/docs](https://render.com/docs)
- Application: Check DEPLOYMENT.md for application-specific issues
