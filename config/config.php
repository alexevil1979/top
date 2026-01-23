<?php
/**
 * Production Configuration
 * YouTube Shorts Collector Service
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'shorts_collector');
define('DB_USER', 'shorts_user');
define('DB_PASS', 'qweasd333123');
define('DB_CHARSET', 'utf8mb4');

// YouTube API
define('YOUTUBE_API_KEY', ''); // Установить в админке

// Paths
define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . '/storage');
define('FRAMES_PATH', STORAGE_PATH . '/frames');
define('API_PATH', BASE_PATH . '/api');
define('ADMIN_PATH', BASE_PATH . '/admin');

// URLs
define('BASE_URL', 'https://top.1tlt.ru');
define('ADMIN_URL', BASE_URL . '/admin');
define('API_URL', BASE_URL . '/api');

// Admin
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2y$10$ctTVsyUOY27pfDOYNgCWUOYtDG.tY4hwPsIxUsUOxD.HIwp1NYi/G'); // password: password (изменить!)

// System
define('TIMEZONE', 'Europe/Moscow');
define('MAX_EXECUTION_TIME', 3600); // 1 hour
define('MEMORY_LIMIT', '512M');

// YouTube Settings
define('SHORTS_MAX_DURATION', 60); // seconds
define('SHORTS_MAX_AGE_HOURS', 48);
define('DEFAULT_LIMIT_PER_RUN', 10);

// Error reporting (production: off, dev: on)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/error.log');

// Timezone
date_default_timezone_set(TIMEZONE);

// Create directories if not exist
if (!is_dir(FRAMES_PATH)) {
    mkdir(FRAMES_PATH, 0755, true);
}
if (!is_dir(STORAGE_PATH)) {
    mkdir(STORAGE_PATH, 0755, true);
}
