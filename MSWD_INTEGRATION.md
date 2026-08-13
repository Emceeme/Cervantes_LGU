# MSWD Portal Integration Documentation

## Overview
The Municipal Social Welfare and Development (MSWD) Online Portal has been integrated into the existing LGU system. This portal allows citizens to apply for social welfare assistance online, track their application status, and enables social workers to review and process applications.

## Installation & Setup

### 1. Database Migration
Run the migration script to create MSWD tables:

```bash
# Via browser (localhost only)
http://localhost/last/migrations/run_mswd_migration.php

# Via CLI
php migrations/run_mswd_migration.php
```

### 2. Seed Assistance Types
Populate the assistance types table with available services:

```bash
# Via browser (localhost only)
http://localhost/last/migrations/seed_assistance_types.php

# Via CLI
php migrations/seed_assistance_types.php
```

### 3. Verify Storage Directories
Ensure the following directories exist and are writable:
- `storage/mswd_documents/` - For uploaded application documents

## Database Schema

### New Tables

#### assistance_types
Stores available social welfare assistance programs:
- `id` - Primary key
- `name` - Assistance type name (e.g., AICS Medical Aid)
- `description` - Detailed description
- `eligibility_requirements` - Eligibility criteria
- `process_steps` - Application process steps
- `required_documents` - JSON array of required documents
- `is_active` - Active status flag
- `created_at`, `updated_at` - Timestamps

#### applications
Stores submitted applications:
- `id` - Primary key
- `tracking_number` - Unique tracking number (e.g., MSWD-2024-X89A1)
- `assistance_type_id` - Foreign key to assistance_types
- `first_name`, `middle_name`, `last_name` - Applicant name
- `birthdate`, `gender`, `civil_status` - Personal details
- `contact_number`, `email` - Contact information
- `barangay`, `street_address` - Address
- `status` - Application status (pending, under_review, approved, rejected)
- `remarks` - Review remarks
- `assigned_worker_id` - Assigned social worker
- `submitted_at`, `reviewed_at`, `updated_at` - Timestamps

#### application_documents
Stores uploaded documents:
- `id` - Primary key
- `application_id` - Foreign key to applications
- `document_type` - Document type name
- `file_name` - Original filename
- `file_path` - Secure storage path
- `file_size` - File size in bytes
- `mime_type` - MIME type
- `uploaded_at` - Upload timestamp

#### application_status_history
Audit trail for status changes:
- `id` - Primary key
- `application_id` - Foreign key to applications
- `old_status`, `new_status` - Status values
- `changed_by` - User who made the change
- `remarks` - Change remarks
- `changed_at` - Timestamp

## User Roles

### New Roles
- **SOCIAL_WORKER** - MSWD staff who review and process applications
- **APPLICANT** - Citizens who submit applications (optional, can apply without account)

### Role-Based Redirection
Updated in `login.php`:
- `SUPER_ADMIN` → `admin/dashboard.php`
- `SOCIAL_WORKER` → `mswd/worker/dashboard.php`
- `APPLICANT` → `mswd/applicant/my-applications.php` (future)
- Existing roles unchanged

## Public Portal Features

### 1. Landing Page (`mswd/index.php`)
- Displays available assistance types
- Shows eligibility requirements
- Links to application form and status tracker
- Modern, accessible UI with high contrast

### 2. Application Form (`mswd/apply.php`)
Multi-step form with:
- **Step 1:** Select assistance type
- **Step 2:** Personal information
- **Step 3:** Document upload (PDF, PNG, JPG, max 5MB)
- **Step 4:** Review and submit

Features:
- CSRF protection
- Client-side validation
- File type and size validation
- Secure file upload to private storage
- Database transactions for atomic submission

### 3. Confirmation Page (`mswd/confirmation.php`)
- Displays tracking number
- Shows application summary
- Print functionality
- Links to status tracker

### 4. Status Tracker (`mswd/track.php`)
- Public tracking by tracking number
- Shows current status and remarks
- Displays status history timeline
- No authentication required

## Social Worker Portal Features

### 1. Dashboard (`mswd/worker/dashboard.php`)
- Statistics overview (pending, under review, approved, rejected)
- Filter by status, barangay, assistance type
- Applications list with quick actions
- Responsive design

### 2. Application Review (`mswd/worker/review.php`)
- View applicant details
- Download/view uploaded documents
- Update application status
- Add review remarks
- Status history timeline
- CSRF-protected status updates

### 3. Document Viewer (`mswd/worker/view_document.php`)
- Secure document access
- MIME type validation
- Authorization checks (Social Worker only)
- Security logging

## Super Admin Features

### 1. Create Social Worker (`admin/create_social_worker.php`)
- Create SOCIAL_WORKER accounts
- Password validation
- Email uniqueness check
- Username uniqueness check
- Department assignment
- View existing social workers

### 2. Handler (`admin/handler/post_create_social_worker.php`)
- CSRF validation
- Input sanitization
- Password hashing (bcrypt)
- Security event logging

## Security Features

### Implemented
- CSRF protection on all forms
- Security headers on all pages
- Input sanitization and validation
- Prepared statements for all queries
- Secure file upload (type validation, size limits, private storage)
- Authorization checks for all protected routes
- Security event logging
- Database transactions for data integrity

### File Upload Security
- Allowed types: PDF, PNG, JPG
- Maximum size: 5MB per file
- Secure filename generation (unique + timestamp)
- Private storage outside web root
- MIME type validation
- Authorization required for access

## File Structure

```
last/
├── migrations/
│   ├── migrate_mswd.sql          # Database schema
│   ├── run_mswd_migration.php    # Migration runner
│   └── seed_assistance_types.php # Data seeder
├── mswd/
│   ├── index.php                 # Public landing page
│   ├── apply.php                 # Application form
│   ├── confirmation.php          # Confirmation page
│   ├── track.php                 # Status tracker
│   ├── handler/
│   │   └── submit_application.php # Application submission
│   └── worker/
│       ├── dashboard.php         # Worker dashboard
│       ├── review.php            # Application review
│       └── view_document.php     # Secure document viewer
├── admin/
│   ├── dashboard.php             # Updated with MSWD link
│   ├── create_social_worker.php  # Create worker accounts
│   └── handler/
│       └── post_create_social_worker.php
├── login.php                     # Updated with MSWD redirection
├── storage/
│   └── mswd_documents/           # Private document storage
└── config/
    └── security.php              # Security functions
```

## Assistance Types

Seeded assistance types include:
1. AICS Medical Aid
2. Burial Assistance
3. Educational Assistance
4. Senior Citizen ID Application
5. PWD ID Application
6. Solo Parent ID Application
7. Livelihood Assistance
8. Food Assistance

## Workflow

### Citizen Application Flow
1. Citizen visits `mswd/index.php`
2. Clicks "Apply Now"
3. Selects assistance type
4. Fills personal information
5. Uploads required documents
6. Reviews and submits
7. Receives tracking number
8. Can track status anytime at `mswd/track.php`

### Social Worker Review Flow
1. Social Worker logs in
2. Redirected to `mswd/worker/dashboard.php`
3. Views pending applications
4. Filters by status/barangay/type
5. Clicks "Review" on application
6. Views applicant details
7. Downloads/reviews documents
8. Updates status (pending → under_review → approved/rejected)
9. Adds remarks
10. Status change logged in history

### Super Admin Flow
1. Super Admin logs in
2. Navigates to "Create Social Worker"
3. Fills worker details
4. Submits form
5. Worker account created with SOCIAL_WORKER role
6. Worker can now log in and review applications

## Testing Checklist

### Database
- [ ] Run migration successfully
- [ ] Seed assistance types
- [ ] Verify tables created
- [ ] Check foreign key constraints

### Public Portal
- [ ] Landing page loads correctly
- [ ] Assistance types display
- [ ] Application form submits
- [ ] File upload works
- [ ] Confirmation page shows tracking number
- [ ] Status tracker finds applications
- [ ] Status history displays

### Social Worker Portal
- [ ] Login redirects correctly
- [ ] Dashboard loads with statistics
- [ ] Filters work properly
- [ ] Application review page loads
- [ ] Documents can be viewed
- [ ] Status updates work
- [ ] Remarks are saved
- [ ] Status history updates

### Super Admin
- [ ] Social worker creation works
- [ ] Validation works (email, username, password)
- [ ] Workers appear in list
- [ ] Created workers can log in

### Security
- [ ] CSRF protection active
- [ ] Security headers present
- [ ] Authorization checks work
- [ ] File upload validation works
- [ ] Document access requires auth
- [ ] Security events logged

## Troubleshooting

### Migration Fails
- Check database credentials in `.env`
- Ensure MySQL user has CREATE TABLE privileges
- Verify PHP MySQL extensions are installed

### File Upload Fails
- Check `storage/mswd_documents/` directory permissions
- Verify PHP upload_max_filesize and post_max_size settings
- Ensure disk space is available

### Social Worker Cannot Login
- Verify role is set to 'SOCIAL_WORKER' in database
- Check login redirection logic in `login.php`
- Ensure password was hashed correctly

### Documents Not Accessible
- Verify file paths in database
- Check file exists in storage directory
- Ensure user has SOCIAL_WORKER role
- Check authorization in `view_document.php`

## Future Enhancements

### Planned Features
- Applicant account system for history tracking
- Email notifications for status changes
- SMS notifications for important updates
- Advanced reporting and analytics
- Bulk status updates
- Document OCR for automated data extraction
- Integration with LGU payment system
- Mobile app version

### Security Enhancements
- Two-factor authentication for workers
- IP whitelisting for worker access
- Advanced document scanning for malware
- Rate limiting on application submissions
- CAPTCHA on public forms

## Support

For issues or questions:
1. Check this documentation
2. Review security logs in `storage/logs/`
3. Check PHP error logs
4. Verify database connection
5. Test with browser dev tools

## Version History

- **v1.1** (2024-08-05)
  - Updated for system v3.0 compatibility
  - Added deployment preparation integration
  - Enhanced security with rate limiting improvements
  - Updated documentation references

- **v1.0** (2024-07-29)
  - Initial MSWD integration
  - Public portal with application form
  - Social worker dashboard and review system
  - Super Admin worker creation
  - Full security implementation
  - Database migrations and seeding
