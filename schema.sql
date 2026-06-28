-- MySQL database schema for "Đi Đâu" Travel Planner

CREATE DATABASE IF NOT EXISTS `didau` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `didau`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `fullname` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NULL,
    `avatar` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Trips Table
CREATE TABLE IF NOT EXISTS `trips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) UNIQUE NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Trip Members Table
CREATE TABLE IF NOT EXISTS `trip_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trip_id` INT NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_trip_member` (`trip_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Itinerary Days Table
CREATE TABLE IF NOT EXISTS `itinerary_days` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trip_id` INT NOT NULL,
    `day_date` DATE NOT NULL,
    `label` VARCHAR(100) NULL,
    FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Itinerary Items Table
CREATE TABLE IF NOT EXISTS `itinerary_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `day_id` INT NOT NULL,
    `item_time` TIME NULL,
    `title` VARCHAR(255) NOT NULL,
    `location` VARCHAR(255) NULL,
    `lat` DOUBLE NULL,
    `lng` DOUBLE NULL,
    `note` TEXT NULL,
    FOREIGN KEY (`day_id`) REFERENCES `itinerary_days`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `itinerary_items`
    ADD COLUMN IF NOT EXISTS `lat` DOUBLE NULL,
    ADD COLUMN IF NOT EXISTS `lng` DOUBLE NULL;

-- 6. Expenses Table
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trip_id` INT NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `payer_name` VARCHAR(50) NOT NULL,
    FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Checklist Items Table
CREATE TABLE IF NOT EXISTS `checklist_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trip_id` INT NOT NULL,
    `item_text` VARCHAR(255) NOT NULL,
    `is_checked` TINYINT(1) DEFAULT 0,
    `username` VARCHAR(50) NULL, -- NULL for group checklist, username for personal checklist
    FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Saved Locations Table
CREATE TABLE IF NOT EXISTS `saved_locations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trip_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `lat` DOUBLE NOT NULL,
    `lng` DOUBLE NOT NULL,
    `note` TEXT NULL,
    FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Transports Table
CREATE TABLE IF NOT EXISTS `transports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trip_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL, -- e.g., 'Máy bay', 'Tàu hỏa', 'Xe khách', 'Ô tô'
    `provider` VARCHAR(100) NULL, -- e.g., 'Vietnam Airlines', 'Phương Trang'
    `departure_place` VARCHAR(100) NOT NULL,
    `arrival_place` VARCHAR(100) NOT NULL,
    `departure_time` DATETIME NOT NULL,
    `arrival_time` DATETIME NULL,
    `ticket_code` VARCHAR(50) NULL,
    `price` DECIMAL(15, 2) DEFAULT 0,
    `note` TEXT NULL,
    FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. User <-> Trip membership link (which trips has this user joined, for sidebar "Nhóm của bạn")
CREATE TABLE IF NOT EXISTS `user_trips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `trip_id` INT NOT NULL,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_trip` (`user_id`, `trip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Random Wheel Options Table (lựa chọn cho vòng quay may mắn, theo từng trip)
CREATE TABLE IF NOT EXISTS `wheel_options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trip_id` INT NOT NULL,
    `text` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
