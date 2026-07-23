CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,

    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    role ENUM(
        'SUPER_ADMIN',
        'ADMIN',
        'EMPLOYEE'
    ) DEFAULT 'EMPLOYEE',

    department VARCHAR(150) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,

    job_title VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,

    employment_type VARCHAR(100) NOT NULL,

    salary VARCHAR(100),
    location VARCHAR(255),

    description TEXT NOT NULL,

    status ENUM(
        'OPEN',
        'CLOSED'
    ) DEFAULT 'OPEN',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,

    job_id INT NOT NULL,

    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(100) NOT NULL,

    message TEXT,

    resume VARCHAR(500) NOT NULL,

    application_status ENUM(
        'PENDING',
        'SHORTLISTED',
        'HIRED',
        'REJECTED'
    ) DEFAULT 'PENDING',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_applicant_job
        FOREIGN KEY (job_id)
        REFERENCES jobs(id)
        ON DELETE CASCADE
);

CREATE TABLE procurement_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(255) NOT NULL,

    file_path VARCHAR(500) NOT NULL,

    status ENUM(
        'OPEN',
        'CLOSED',
        'AWARDED'
    ) DEFAULT 'OPEN',

    award_winner VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- The initial SUPER_ADMIN account is intentionally NOT seeded here.
-- Create it with the CLI bootstrap script so no password/hash is committed:
--   SUPER_ADMIN_PASSWORD='<strong-password>' php create_super_admin.php