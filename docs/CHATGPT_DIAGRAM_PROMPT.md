# ChatGPT Prompt for System Diagram Generation

**Copy and paste this prompt into ChatGPT to generate visual diagrams for the LGU Cervantes Integrated Management System:**

---

```
I need you to create visual diagrams for a municipal government management system called "LGU Cervantes Integrated Management System". Please generate the following diagrams as images. For each diagram, provide a clear, professional visualization suitable for technical documentation.

## System Overview

The LGU Cervantes Integrated Management System is a web-based platform built with PHP and PostgreSQL that streamlines municipal operations across three main departments:

1. **LGU (Local Government Unit)** - Manages job applications, news, procurement, and scholarships
2. **MSWD (Municipal Social Welfare and Development)** - Processes social welfare assistance applications
3. **Treasury** - Manages citizen accounts and financial transactions

The system also has an **Admin** module for system-wide administration and **Public** portals for citizen access.

---

## Diagram 1: System Architecture Diagram

Create a high-level architecture diagram showing:
- **Frontend Layer**: Web browsers accessing the system
- **Application Layer**: PHP application with modules (Admin, LGU, MSWD, Treasury, Public)
- **Data Layer**: PostgreSQL database
- **Storage Layer**: File storage (Render Persistent Disk)
- **Deployment Platform**: Render cloud platform

Show the connections between layers with arrows indicating data flow.

---

## Diagram 2: Database Schema Diagram

Create an entity-relationship (ER) diagram showing the main tables and their relationships:

**Core Tables:**
- users (id, username, email, password, role, department, is_active)
- jobs (id, title, description, requirements, salary_range)
- applicants (id, job_id, full_name, email, phone, resume_path, status)
- applications (id, tracking_number, assistance_type_id, applicant_id, status, amount)
- assistance_types (id, name, description, max_amount)
- scholarship_applications (id, full_name, email, gpa, family_income, status)
- procurement_posts (id, title, description, budget, deadline)
- news_posts (id, title, content, image_path, user_id)
- messages (id, sender_id, receiver_id, subject, message, status)
- department_settings (department, settings_json)

**Relationships to show:**
- users → applicants (one-to-many)
- jobs → applicants (one-to-many)
- users → news_posts (one-to-many)
- assistance_types → applications (one-to-many)
- users → messages (one-to-many)
- department_settings (single row per department)

Use standard ERD notation with crow's foot notation for cardinality.

---

## Diagram 3: User Roles and Permissions Flowchart

Create a flowchart showing the 5 user roles and their access paths:

**Roles:**
1. SUPER_ADMIN - Full system access
2. ADMIN - Department management
3. EMPLOYEE - Department operations
4. APPLICANT - MSWD applications
5. CITIZEN - Treasury services

Show the login flow and how each role is routed to their appropriate dashboard:
- SUPER_ADMIN → admin/dashboard.php
- LGU Department → lgu/dashboard.php
- MSWD Department → mswd/worker/dashboard.php
- Treasury Department → treasury/dashboard.php
- APPLICANT → mswd/applicant/my-applications.php
- CITIZEN → treasury/citizen/dashboard.php

Include decision diamonds for role and department checks.

---

## Diagram 4: Application Workflow Diagram

Create a swimlane diagram showing the MSWD assistance application workflow:

**Swimlanes:**
- Citizen/Applicant
- MSWD Worker
- MSWD Admin
- Database

**Process Steps:**
1. Citizen fills out application form
2. System validates input
3. Documents uploaded
4. Application saved to database
5. MSWD Worker reviews application
6. Status updated (pending → under_review)
7. MSWD Admin approves/rejects
8. Status updated (approved/rejected)
9. Citizen notified of decision
10. If approved, assistance processed

Show the flow with decision points for approval/rejection.

---

## Diagram 5: Security Architecture Diagram

Create a diagram showing the security layers:

**Layers to include:**
1. **Authentication Layer**: Session management, password hashing, rate limiting
2. **Authorization Layer**: Role-based access control (RBAC), department restrictions
3. **Input Validation Layer**: Form validation, file type checking, SQL injection prevention
4. **CSRF Protection**: Token generation and validation
5. **Security Headers**: CSP, X-Frame-Options, HSTS
6. **Logging Layer**: Security event logging, error logging
7. **File Upload Security**: Secure filenames, MIME validation, directory protection

Show how each layer protects the system with arrows indicating the flow of a request through security checks.

---

## Diagram 6: Data Flow Diagram (DFD) - Level 0

Create a context diagram showing the system as a single process with external entities:

**External Entities:**
- Citizens (applicants, job seekers, scholarship applicants)
- LGU Staff (employees, admins)
- MSWD Workers
- Treasury Staff
- Super Admin

**Data Flows:**
- Citizens → System: Applications, inquiries, documents
- System → Citizens: Status updates, confirmations
- Staff → System: Reviews, approvals, updates
- System → Staff: Dashboards, reports, notifications

Show the system as a central circle with external entities around it and labeled arrows for data flows.

---

## Diagram 7: Deployment Architecture Diagram

Create a diagram showing the Render deployment architecture:

**Components:**
- GitHub Repository (source code)
- Render Web Service (PHP application)
- Render PostgreSQL (database)
- Render Persistent Disk (file storage)
- Domain/DNS (custom domain)
- End Users (browsers)

Show the deployment pipeline:
1. Code pushed to GitHub
2. Render auto-deploys
3. Application connects to PostgreSQL
4. File uploads stored on Persistent Disk
5. Users access via domain

---

## Diagram 8: Module Interaction Diagram

Create a diagram showing how the different modules interact:

**Modules:**
- Public Portal (static_page/public/)
- LGU Module (lgu/)
- MSWD Module (mswd/)
- Treasury Module (treasury/)
- Admin Module (admin/)
- Shared Config (config/)

**Interactions to show:**
- All modules share config/ for database and security
- Public forms submit to respective module handlers
- Admin module can view data from all modules
- Modules have independent dashboards but share authentication

Use boxes for modules and arrows showing dependencies and data sharing.

---

## Formatting Instructions

For each diagram:
- Use professional, clean design
- Use consistent color scheme (blues, grays, whites)
- Include clear labels and legends
- Ensure text is readable at normal size
- Use standard diagramming conventions (UML, ERD, flowchart)
- Make diagrams suitable for inclusion in technical documentation

Please generate these 8 diagrams as separate images. Thank you!
```

---

## Usage Instructions

1. Copy the entire prompt above
2. Paste it into ChatGPT
3. ChatGPT will generate the diagrams as images
4. Download and save the images to `docs/diagrams/`
5. Reference the images in the capstone paper

**Note**: If ChatGPT cannot generate images directly, ask it to provide Mermaid.js code or PlantUML code that can be rendered using online tools.
