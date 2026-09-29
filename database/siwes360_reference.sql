-- SIWES360 reference schema
-- IMPORTANT: The running phpMyAdmin database is the source of truth.
-- This file is provided only as a clean reference/backup. Do NOT import it over
-- an existing database that already contains your SIWES360 data.

CREATE DATABASE IF NOT EXISTS siwes360 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE siwes360;

CREATE TABLE IF NOT EXISTS departments (
    department_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    faculty VARCHAR(150) NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','student','supervisor','coordinator') NOT NULL,
    status ENUM('active','suspended','inactive') NOT NULL DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    matric_no VARCHAR(50) NOT NULL UNIQUE,
    department_id INT UNSIGNED NOT NULL,
    level VARCHAR(20) NOT NULL,
    phone VARCHAR(30),
    gender ENUM('male','female','other'),
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_student_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS organizations (
    organization_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100),
    state VARCHAR(100),
    phone VARCHAR(30),
    email VARCHAR(150),
    industry VARCHAR(100),
    description TEXT,
    status ENUM('pending','approved','suspended','inactive') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS supervisors (
    supervisor_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    organization_id INT UNSIGNED NOT NULL,
    position VARCHAR(100),
    phone VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_supervisor_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_supervisor_organization FOREIGN KEY (organization_id) REFERENCES organizations(organization_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS coordinators (
    coordinator_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    department_id INT UNSIGNED,
    phone VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_coordinator_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_coordinator_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS it_sessions (
    session_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('upcoming','active','completed','cancelled') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS placements (
    placement_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    organization_id INT UNSIGNED NOT NULL,
    supervisor_id INT UNSIGNED,
    session_id INT UNSIGNED NOT NULL,
    position VARCHAR(100),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending','approved','active','completed','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_placement_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_placement_organization FOREIGN KEY (organization_id) REFERENCES organizations(organization_id) ON DELETE RESTRICT,
    CONSTRAINT fk_placement_supervisor FOREIGN KEY (supervisor_id) REFERENCES supervisors(supervisor_id) ON DELETE SET NULL,
    CONSTRAINT fk_placement_session FOREIGN KEY (session_id) REFERENCES it_sessions(session_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_id INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    clock_in DATETIME NULL,
    clock_out DATETIME NULL,
    status ENUM('present','late','absent','incomplete') DEFAULT 'present',
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_daily_attendance (placement_id, attendance_date),
    CONSTRAINT fk_attendance_placement FOREIGN KEY (placement_id) REFERENCES placements(placement_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS daily_activities (
    activity_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_id INT UNSIGNED NOT NULL,
    activity_date DATE NOT NULL,
    description TEXT NOT NULL,
    skills_learned TEXT,
    tools_used VARCHAR(500),
    challenges TEXT,
    lessons_learned TEXT,
    status ENUM('draft','submitted','reviewed','rejected') DEFAULT 'draft',
    supervisor_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_placement FOREIGN KEY (placement_id) REFERENCES placements(placement_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS weekly_reports (
    report_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_id INT UNSIGNED NOT NULL,
    week_number INT UNSIGNED NOT NULL,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    activities TEXT NOT NULL,
    skills_acquired TEXT,
    challenges TEXT,
    solutions TEXT,
    observations TEXT,
    status ENUM('draft','submitted','under_review','approved','rejected') DEFAULT 'draft',
    submitted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_weekly_report (placement_id, week_number),
    CONSTRAINT fk_report_placement FOREIGN KEY (placement_id) REFERENCES placements(placement_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS report_reviews (
    review_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    supervisor_id INT UNSIGNED NOT NULL,
    decision ENUM('approved','rejected','revision_requested') NOT NULL,
    comment TEXT,
    reviewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_report FOREIGN KEY (report_id) REFERENCES weekly_reports(report_id) ON DELETE CASCADE,
    CONSTRAINT fk_review_supervisor FOREIGN KEY (supervisor_id) REFERENCES supervisors(supervisor_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evaluations (
    evaluation_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_id INT UNSIGNED NOT NULL,
    evaluator_id INT UNSIGNED NOT NULL,
    evaluator_role ENUM('supervisor','coordinator') NOT NULL,
    technical_skills DECIMAL(5,2) DEFAULT 0,
    punctuality DECIMAL(5,2) DEFAULT 0,
    communication DECIMAL(5,2) DEFAULT 0,
    teamwork DECIMAL(5,2) DEFAULT 0,
    problem_solving DECIMAL(5,2) DEFAULT 0,
    professionalism DECIMAL(5,2) DEFAULT 0,
    overall_score DECIMAL(5,2) DEFAULT 0,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evaluation_placement FOREIGN KEY (placement_id) REFERENCES placements(placement_id) ON DELETE CASCADE,
    CONSTRAINT fk_evaluation_user FOREIGN KEY (evaluator_id) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
