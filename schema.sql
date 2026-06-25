-- ============================================================
-- SU-Housing Database Schema
-- Strathmore University Off-Campus Accommodation System
-- ============================================================

CREATE DATABASE IF NOT EXISTS suhousing
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE suhousing;

-- ── ADMINS ──
-- Admins are created directly in the database
-- No registration endpoint — login only
CREATE TABLE IF NOT EXISTS admins (
  adminId      INT          NOT NULL AUTO_INCREMENT,
  fullName     VARCHAR(255) NOT NULL,
  email        VARCHAR(255) NOT NULL,
  passwordHash VARCHAR(256) NOT NULL,
  createdAt    DATETIME     NOT NULL
               DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (adminId),
  UNIQUE KEY uq_email (email)
);

-- ── STUDENTS ──
-- Students register through the system
CREATE TABLE IF NOT EXISTS students (
  studentId       INT          NOT NULL AUTO_INCREMENT,
  fullName        VARCHAR(255) NOT NULL,
  admissionNumber VARCHAR(20)  NOT NULL,
  passwordHash    VARCHAR(256) NOT NULL,
  programme       VARCHAR(255) NULL,
  createdAt       DATETIME     NOT NULL
                  DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (studentId),
  UNIQUE KEY uq_admissionNumber (admissionNumber)
);

-- ── STUDENT PREFERENCE PROFILES ──
CREATE TABLE IF NOT EXISTS student_preference_profiles (
  profileId          INT           NOT NULL AUTO_INCREMENT,
  studentId          INT           NOT NULL,
  studyHabits        ENUM('early_riser','night_owl','flexible')
                     NULL,
  sleepSchedule      ENUM('before_10pm','10pm_12am',
                          'after_midnight')
                     NULL,
  noiseTolerance     ENUM('quiet','moderate','lively')
                     NULL,
  genderPreference   ENUM('male_only','female_only',
                          'mixed','no_preference')
                     NULL,
  roomTypePreference ENUM('single','shared','ensuite',
                          'studio','no_preference')
                     NULL,
  budgetMin          DECIMAL(10,2) NULL,
  budgetMax          DECIMAL(10,2) NULL,
  preferredLocation  VARCHAR(100)  NULL,
  updatedAt          DATETIME      NOT NULL
                     DEFAULT CURRENT_TIMESTAMP
                     ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (profileId),
  UNIQUE KEY uq_studentId (studentId),
  FOREIGN KEY (studentId)
    REFERENCES students(studentId)
    ON DELETE CASCADE
);

-- ── HOSTEL LISTINGS ──
CREATE TABLE IF NOT EXISTS hostel_listings (
  hostelId        INT           NOT NULL AUTO_INCREMENT,
  hostelName      VARCHAR(255)  NOT NULL,
  physicalAddress VARCHAR(500)  NOT NULL,
  neighbourhood   VARCHAR(100)  NOT NULL,
  description     TEXT          NOT NULL,
  priceMin        DECIMAL(10,2) NOT NULL,
  priceMax        DECIMAL(10,2) NOT NULL,
  roomType        ENUM('single','shared','ensuite','studio')
                  NOT NULL,
  roomsAvailable  INT           NOT NULL DEFAULT 0,
  amenities       JSON          NOT NULL,
  landlordName    VARCHAR(255)  NOT NULL,
  landlordPhone   VARCHAR(50)   NOT NULL,
  latitude        DECIMAL(10,8) NULL,
  longitude       DECIMAL(11,8) NULL,
  isActive        TINYINT(1)    NOT NULL DEFAULT 1,
  createdAt       DATETIME      NOT NULL
                  DEFAULT CURRENT_TIMESTAMP,
  updatedAt       DATETIME      NOT NULL
                  DEFAULT CURRENT_TIMESTAMP
                  ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (hostelId)
);

-- ── FEEDBACK ──
CREATE TABLE IF NOT EXISTS feedback (
  feedbackId     INT      NOT NULL AUTO_INCREMENT,
  hostelId       INT      NOT NULL,
  studentId      INT      NOT NULL,
  submissionText TEXT     NOT NULL,
  submittedAt    DATETIME NOT NULL
                 DEFAULT CURRENT_TIMESTAMP,
  reviewedAt     DATETIME NULL,
  PRIMARY KEY (feedbackId),
  FOREIGN KEY (hostelId)
    REFERENCES hostel_listings(hostelId),
  FOREIGN KEY (studentId)
    REFERENCES students(studentId)
);

-- ── SENTIMENT CLASSIFICATIONS ──
CREATE TABLE IF NOT EXISTS sentiment_classifications (
  classificationId INT      NOT NULL AUTO_INCREMENT,
  feedbackId       INT      NOT NULL,
  sentiment        ENUM('positive','negative') NOT NULL,
  classifiedBy     INT      NOT NULL,
  classifiedAt     DATETIME NOT NULL
                   DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (classificationId),
  UNIQUE KEY uq_feedbackId (feedbackId),
  FOREIGN KEY (feedbackId)
    REFERENCES feedback(feedbackId),
  FOREIGN KEY (classifiedBy)
    REFERENCES admins(adminId)
);

-- ── DEFAULT ADMIN ACCOUNT ──
-- Password is: admin123 (bcrypt hash)
-- Michelle should change this immediately after first login
INSERT INTO admins (fullName, email, passwordHash)
VALUES (
  'Dean of Students',
  'admin@strathmore.edu',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);