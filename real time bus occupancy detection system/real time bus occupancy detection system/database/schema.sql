-- Food Delivery Time Prediction System
-- Import via phpMyAdmin or: mysql -u root < database/schema.sql

CREATE DATABASE IF NOT EXISTS delivery_time_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE delivery_time_db;

CREATE TABLE IF NOT EXISTS predictions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  distance_km DECIMAL(8, 2) NOT NULL,
  weather VARCHAR(32) NOT NULL,
  traffic_level VARCHAR(32) NOT NULL,
  time_of_day VARCHAR(32) NOT NULL,
  vehicle_type VARCHAR(32) NOT NULL,
  preparation_time_min SMALLINT UNSIGNED NOT NULL,
  courier_experience_yrs DECIMAL(4, 1) NOT NULL,
  predicted_delivery_min DECIMAL(6, 1) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
