CREATE DATABASE myapp CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE myapp;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `email_verified` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('user', 'admin') DEFAULT 'user',
    `active` BOOLEAN DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `users_mails` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user` INT NOT NULL,
    `mail_hash` VARCHAR(255) NOT NULL,
    `new_password_hash` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `used` BOOLEAN DEFAULT 0,
    `expiry_at` DATETIME NOT NULL,
    FOREIGN KEY (`user`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user` INT NOT NULL,
    `curr_name` VARCHAR(64) NOT NULL,
    `orig_name` VARCHAR(255) NOT NULL,
    `size` INT NOT NULL,
    `mime_type` VARCHAR(255) NOT NULL,
    `description` VARCHAR(500) NULL,
    `modify_time` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user`) REFERENCES `users`(`id`) ON DELETE no action
);
