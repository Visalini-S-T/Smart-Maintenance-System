CREATE DATABASE IF NOT EXISTS smart_maintenance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_maintenance;

CREATE TABLE IF NOT EXISTS users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','supervisor','technician') NOT NULL DEFAULT 'technician',
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS machines (
 id INT AUTO_INCREMENT PRIMARY KEY,
 machine_code VARCHAR(50) NOT NULL UNIQUE,
 name VARCHAR(120) NOT NULL,
 location VARCHAR(120) NOT NULL,
 category VARCHAR(80) DEFAULT 'Production',
 installation_date DATE NULL,
 operating_hours DECIMAL(12,2) NOT NULL DEFAULT 0,
 planned_hours DECIMAL(12,2) NOT NULL DEFAULT 720,
 status ENUM('running','maintenance','breakdown','inactive') NOT NULL DEFAULT 'running',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS breakdown_logs (
 id INT AUTO_INCREMENT PRIMARY KEY,
 machine_id INT NOT NULL,
 failure_date DATETIME NOT NULL,
 failure_type VARCHAR(120) NOT NULL,
 description TEXT,
 downtime_hours DECIMAL(10,2) NOT NULL DEFAULT 0,
 created_by INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(machine_id) REFERENCES machines(id) ON DELETE CASCADE,
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS maintenance_schedule (
 id INT AUTO_INCREMENT PRIMARY KEY,
 machine_id INT NOT NULL,
 maintenance_type VARCHAR(120) NOT NULL,
 scheduled_date DATE NOT NULL,
 status ENUM('pending','completed','overdue') NOT NULL DEFAULT 'pending',
 notes TEXT,
 FOREIGN KEY(machine_id) REFERENCES machines(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS spare_parts (
 id INT AUTO_INCREMENT PRIMARY KEY,
 part_name VARCHAR(120) NOT NULL,
 part_code VARCHAR(50) NOT NULL UNIQUE,
 quantity INT NOT NULL DEFAULT 0,
 reorder_level INT NOT NULL DEFAULT 5,
 unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS work_orders (
 id INT AUTO_INCREMENT PRIMARY KEY,
 machine_id INT NOT NULL,
 title VARCHAR(180) NOT NULL,
 description TEXT,
 priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
 status ENUM('reported','assigned','in_progress','closed') NOT NULL DEFAULT 'reported',
 assigned_to INT NULL,
 spare_part_id INT NULL,
 spare_qty INT NOT NULL DEFAULT 0,
 reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 closed_at TIMESTAMP NULL,
 FOREIGN KEY(machine_id) REFERENCES machines(id) ON DELETE CASCADE,
 FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY(spare_part_id) REFERENCES spare_parts(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notifications (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NULL,
 title VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 type VARCHAR(40) DEFAULT 'info',
 is_read TINYINT(1) DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT IGNORE INTO machines(machine_code,name,location,category,installation_date,operating_hours,planned_hours,status) VALUES
('CNC-01','CNC Machining Center','Block A','CNC','2022-03-10',6800,720,'running'),
('LAT-02','Precision Lathe','Block B','Lathe','2021-06-21',7900,720,'running'),
('CON-03','Main Conveyor','Assembly','Conveyor','2023-01-12',4200,720,'maintenance'),
('PKG-04','Packaging Unit','Packaging','Packaging','2022-11-05',5100,720,'running');

INSERT IGNORE INTO spare_parts(part_name,part_code,quantity,reorder_level,unit_cost) VALUES
('Drive Belt','BELT-001',12,5,850),
('Motor Bearing','BRG-002',8,3,1200),
('Cooling Fan','FAN-003',4,2,2200),
('Lubricant Kit','LUB-004',20,5,450);

INSERT IGNORE INTO maintenance_schedule(machine_id,maintenance_type,scheduled_date,status,notes) VALUES
(1,'Oil & lubrication','2026-10-08','pending','Routine service'),
(2,'Bearing inspection','2026-10-02','overdue','Inspect vibration'),
(3,'Conveyor belt service','2026-10-06','pending','Replace worn belt'),
(4,'Electrical inspection','2026-10-15','pending','Check control panel');

INSERT IGNORE INTO breakdown_logs(machine_id,failure_date,failure_type,description,downtime_hours) VALUES
(1,'2026-09-02 10:00:00','Motor overheating','Temperature exceeded threshold',3.5),
(1,'2026-09-18 15:00:00','Motor overheating','Repeated overheating',4.0),
(2,'2026-09-11 09:00:00','Bearing noise','Abnormal vibration',5.0),
(3,'2026-09-04 13:00:00','Belt failure','Belt snapped',6.5),
(3,'2026-09-23 08:00:00','Belt failure','Belt slipped again',4.5);

INSERT IGNORE INTO work_orders(machine_id,title,description,priority,status,assigned_to) VALUES
(1,'Inspect repeated motor overheating','Check cooling system and motor load','high','in_progress',3),
(3,'Replace conveyor belt','Replace worn drive belt','critical','assigned',3);
