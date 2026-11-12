-- Resident reports feature. Import this in phpMyAdmin (Import tab) AFTER database.sql.
USE barangay_landayan;

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
