USE barangay_landayan;

-- Run this ONLY if you already imported an earlier database.sql. It adds anything that is missing
-- (login attempts and resident reports). It is safe to run more than once. In phpMyAdmin: select the database, click Import, choose this file.

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL,
    INDEX idx_attempt_lookup (identifier_hash, ip_address, attempted_at)
) ENGINE=InnoDB;

-- Resident reports (skipped if they already exist)
CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tracking_code VARCHAR(20) NULL UNIQUE,
  category VARCHAR(40) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(255) NOT NULL,
  reporter_name VARCHAR(100) NULL,
  contact VARCHAR(50) NULL,
  status ENUM('new','in_progress','resolved') NOT NULL DEFAULT 'new',
  admin_notes TEXT NULL,
  ip_hash CHAR(64) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_report_status (status, created_at),
  INDEX idx_report_ip (ip_hash, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS report_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
) ENGINE=InnoDB;
