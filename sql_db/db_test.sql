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

INSERT INTO users (
    first_name,
    last_name,
    username,
    email,
    password,
    role
)
VALUES (
    'System',
    'Administrator',
    'superadmin',
    'admin@lgu.gov.ph',
    '$2y$10$J6m0L7e0b5d1i1QyI0M5wO2h2u1wM7nV7N4n1rD3W4l2P6m9a8b7K',
    'SUPER_ADMIN'
);