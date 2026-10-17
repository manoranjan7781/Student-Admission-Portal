-- =====================================================================
--  Student Admission Portal - database schema (MySQL 8 / MariaDB 10.5+)
--  Used by BOTH the PHP module and the Java Servlet module.
--  Import with:  mysql -u root -p < database/schema.sql
-- =====================================================================
CREATE DATABASE IF NOT EXISTS student_portal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_portal;

-- Child tables first so the script can be re-run during development.
-- WARNING: this wipes existing data. Remove these 3 lines in production.
DROP TABLE IF EXISTS student_files;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS students;

-- ---------------------------------------------------------------------
-- students: one row per admitted/registered student
-- ---------------------------------------------------------------------
CREATE TABLE students (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  roll_number VARCHAR(20)  NOT NULL,
  email       VARCHAR(150) NOT NULL,
  course      VARCHAR(100) NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_students_roll  (roll_number),   -- no duplicate roll numbers
  UNIQUE KEY uq_students_email (email),         -- no duplicate emails
  KEY idx_students_course (course)              -- speeds up course filtering
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- users: login accounts. role 'admin' manages everyone; role 'student'
-- may only see their own profile/files (student_id links to the owner).
-- password_hash holds a bcrypt hash ($2y$...) written by PHP's
-- password_hash(); Java verifies the same hash with jBCrypt.
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(150) NOT NULL,          -- admin name or the student's email
  password_hash VARCHAR(255) NOT NULL,          -- NEVER plain text
  role          ENUM('admin','student') NOT NULL DEFAULT 'student',
  student_id    INT UNSIGNED NULL,              -- NULL for admins
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_student  (student_id),    -- one login per student
  CONSTRAINT fk_users_student FOREIGN KEY (student_id)
    REFERENCES students(id) ON DELETE CASCADE   -- deleting a student removes the login
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- student_files: metadata of every uploaded file. The file itself lives
-- on disk (uploads folder); only its metadata is stored here.
-- ---------------------------------------------------------------------
CREATE TABLE student_files (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id        INT UNSIGNED NOT NULL,
  category          ENUM('profile_image','id_proof','certificate','other') NOT NULL,
  original_filename VARCHAR(255) NOT NULL,      -- name on the user's computer (display only)
  stored_filename   VARCHAR(64)  NOT NULL,      -- random server-generated name
  file_path         VARCHAR(255) NOT NULL,      -- path RELATIVE to the uploads folder
  mime_type         VARCHAR(100) NOT NULL,      -- detected on the server, not trusted from browser
  file_size         INT UNSIGNED NOT NULL,      -- bytes
  uploaded_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_files_stored (stored_filename), -- guarantees no overwrite/collision
  KEY idx_files_student (student_id, category),
  CONSTRAINT fk_files_student FOREIGN KEY (student_id)
    REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;
