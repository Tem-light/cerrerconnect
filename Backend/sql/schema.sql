-- CareerConnect schema (MySQL / MariaDB)

CREATE DATABASE IF NOT EXISTS careerconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE careerconnect;

-- USERS
CREATE TABLE IF NOT EXISTS users (
  id CHAR(24) NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','recruiter','admin') NOT NULL,
  approved TINYINT(1) NOT NULL DEFAULT 0,
  blocked TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- STUDENT PROFILES
CREATE TABLE IF NOT EXISTS student_profiles (
  user_id CHAR(24) NOT NULL,
  university VARCHAR(255) NULL,
  degree VARCHAR(255) NULL,
  graduation_year INT NULL,
  phone VARCHAR(50) NULL,
  github_url VARCHAR(500) NULL,
  linkedin_url VARCHAR(500) NULL,
  avatar_url VARCHAR(500) NULL,
  resume_url VARCHAR(500) NULL,
  skills_json JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_student_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- RECRUITER PROFILES
CREATE TABLE IF NOT EXISTS recruiter_profiles (
  user_id CHAR(24) NOT NULL,
  company VARCHAR(255) NULL,
  company_description TEXT NULL,
  website VARCHAR(500) NULL,
  logo_url VARCHAR(500) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_recruiter_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- JOBS
CREATE TABLE IF NOT EXISTS jobs (
  id CHAR(24) NOT NULL,
  recruiter_id CHAR(24) NOT NULL,
  title VARCHAR(255) NOT NULL,
  company VARCHAR(255) NOT NULL,
  location VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL,
  category VARCHAR(100) NOT NULL,
  salary_min INT NULL,
  salary_max INT NULL,
  description TEXT NOT NULL,
  openings INT NOT NULL DEFAULT 1,
  application_start DATE NULL,
  application_end DATE NULL,
  contact_email VARCHAR(190) NULL,
  contact_website VARCHAR(500) NULL,
  status ENUM('active','closed') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_jobs_recruiter (recruiter_id),
  KEY idx_jobs_status (status),
  CONSTRAINT fk_jobs_recruiter FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS job_requirements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id CHAR(24) NOT NULL,
  requirement VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_job_req_job (job_id),
  CONSTRAINT fk_job_req_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- APPLICATIONS
CREATE TABLE IF NOT EXISTS applications (
  id CHAR(24) NOT NULL,
  job_id CHAR(24) NOT NULL,
  student_id CHAR(24) NOT NULL,
  cover_letter TEXT NOT NULL,
  status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_app_job_student (job_id, student_id),
  KEY idx_app_job (job_id),
  KEY idx_app_student (student_id),
  CONSTRAINT fk_app_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_app_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- NOTIFICATIONS
CREATE TABLE IF NOT EXISTS notifications (
  id CHAR(24) NOT NULL,
  user_id CHAR(24) NOT NULL,
  type VARCHAR(50) NOT NULL,
  message VARCHAR(500) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_notif_user (user_id),
  KEY idx_notif_unread (user_id, is_read),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed demo users (password: password123)
-- NOTE: this insert is safe to rerun only if ids/emails do not already exist.
-- You can remove these if you want a clean DB.
INSERT IGNORE INTO users (id, name, email, password_hash, role, approved, blocked, created_at, updated_at)
VALUES
  ('111111111111111111111111', 'Student Demo', 'student@test.com', '$2y$10$xWig8diMAb6Ao38MocFcUukg0GcjFqpu5cWMz3QoG.XSl3jGrLuu.', 'student', 0, 0, NOW(), NOW()),
  ('222222222222222222222222', 'Recruiter Demo', 'recruiter@test.com', '$2y$10$xWig8diMAb6Ao38MocFcUukg0GcjFqpu5cWMz3QoG.XSl3jGrLuu.', 'recruiter', 1, 0, NOW(), NOW()),
  ('333333333333333333333333', 'Admin Demo', 'admin@test.com', '$2y$10$xWig8diMAb6Ao38MocFcUukg0GcjFqpu5cWMz3QoG.XSl3jGrLuu.', 'admin', 1, 0, NOW(), NOW());

INSERT IGNORE INTO student_profiles (user_id, university, degree, graduation_year, skills_json, created_at, updated_at)
VALUES ('111111111111111111111111', 'Demo University', 'Computer Science', 2026, JSON_ARRAY('React', 'PHP', 'MySQL'), NOW(), NOW());

INSERT IGNORE INTO recruiter_profiles (user_id, company, company_description, website, created_at, updated_at)
VALUES ('222222222222222222222222', 'Demo Company', 'We hire great interns.', 'https://example.com', NOW(), NOW());
