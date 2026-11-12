-- Barangay Landayan Information and Public Announcement Management System
-- Import this file in phpMyAdmin. The admin account is created by setup.php (not here).

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('administrator','editor') NOT NULL DEFAULT 'editor',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE announcement_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  summary VARCHAR(300) NOT NULL,
  content MEDIUMTEXT NOT NULL,
  category_id INT NOT NULL,
  image_path VARCHAR(255) NULL,
  attachment_path VARCHAR(255) NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  is_urgent TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  created_by INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status_date (status, published_at),
  FULLTEXT KEY ft_search (title, summary),
  FOREIGN KEY (category_id) REFERENCES announcement_categories(id),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  venue VARCHAR(200) NOT NULL,
  event_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NULL,
  poster_path VARCHAR(255) NULL,
  status ENUM('upcoming','completed') NOT NULL DEFAULT 'upcoming',
  created_by INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_event_date (event_date),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- One row per setting, e.g. 'mission', 'office_hours'. Editable from the admin panel.
CREATE TABLE barangay_information (
  id INT AUTO_INCREMENT PRIMARY KEY,
  info_key VARCHAR(50) NOT NULL UNIQUE,
  info_value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE barangay_officials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  position VARCHAR(100) NOT NULL,
  photo_path VARCHAR(255) NULL,
  display_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE barangay_services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  requirements TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  display_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(255) NOT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created (created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Sample data (all editable later from the admin panel) ----------
INSERT INTO announcement_categories (name) VALUES
('General Announcement'),('Public Advisory'),('Emergency Notice'),('Health and Safety'),
('Community Activities'),('Government Programs'),('Service Updates');

INSERT INTO announcements (title, summary, content, category_id, status, is_urgent, published_at) VALUES
('Water interruption this Thursday','Scheduled maintenance will affect several puroks from 8 AM to 5 PM.','<p>Residents are advised to store enough water ahead of the scheduled maintenance.</p>',2,'published',1,DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Free medical mission for residents','Bring a valid ID to the barangay hall on Saturday morning.','<p>Free check-ups and basic medicines will be available while supplies last.</p>',4,'published',0,DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Draft: Clean-up drive schedule','Volunteer schedule for the monthly clean-up drive.','<p>Schedule to be finalized.</p>',5,'draft',0,NULL);

INSERT INTO events (title, description, venue, event_date, start_time, end_time, status) VALUES
('Barangay Assembly','Quarterly assembly for all residents.','Landayan Barangay Hall', DATE_ADD(CURDATE(), INTERVAL 14 DAY),'09:00:00','12:00:00','upcoming'),
('Tree Planting Activity','Community tree planting along the main road.','Landayan Main Road', DATE_SUB(CURDATE(), INTERVAL 20 DAY),'07:00:00','10:00:00','completed');

INSERT INTO barangay_information (info_key, info_value) VALUES
('name','Barangay Landayan'),
('description','Barangay Landayan is a community in San Pedro City, Laguna.'),
('mission','To deliver responsive, transparent and people-centered public service.'),
('vision','A safe, progressive and united community.'),
('address','Barangay Hall, Landayan, San Pedro City, Laguna'),
('office_hours','Monday to Friday, 8:00 AM to 5:00 PM'),
('phone','(000) 000-0000'),
('email','barangay@example.com');

INSERT INTO barangay_officials (full_name, position, display_order) VALUES
('Punong Barangay Name','Punong Barangay',1),('Kagawad Name','Barangay Kagawad',2),('Secretary Name','Barangay Secretary',3);

INSERT INTO barangay_services (name, description, requirements, display_order) VALUES
('Barangay Clearance','Issued in person at the barangay hall.','Valid ID; proof of residency',1),
('Certificate of Residency','Issued in person at the barangay hall.','Valid ID',2),
('Certificate of Indigency','Issued in person after verification.','Valid ID; proof of residency',3),
('Community Assistance','Ask the barangay office about available programs.',NULL,4);

-- ============================================================
-- Resident reports (report.php). Included here so one import is enough.
-- ============================================================

-- Resident reports feature. Import this in phpMyAdmin (Import tab) AFTER database.sql.

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

-- ------------------------------------------------------------
-- Login attempt tracking (used by the login rate limit).
-- The app also creates this table by itself if it is missing.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL,
    INDEX idx_attempt_lookup (identifier_hash, ip_address, attempted_at)
) ENGINE=InnoDB;
