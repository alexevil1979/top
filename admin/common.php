<?php
/**
 * Admin Common Functions
 */

require_once __DIR__ . '/../config/config.php';

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load DB class (will be initialized when needed)
if (file_exists(__DIR__ . '/../core/DB.php')) {
    require_once __DIR__ . '/../core/DB.php';
}

// Check authentication
function checkAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// Login function
function login($username, $password) {
    try {
        if (empty($username) || empty($password)) {
            return false;
        }
        
        if ($username === ADMIN_USER) {
            if (password_verify($password, ADMIN_PASS_HASH)) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                return true;
            }
        }
        return false;
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

// Logout function
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get DB instance
function getDB() {
    if (!class_exists('DB')) {
        require_once __DIR__ . '/../core/DB.php';
    }
    try {
        return DB::getInstance();
    } catch (Exception $e) {
        error_log("DB Error: " . $e->getMessage());
        // Return null instead of throwing to allow graceful error handling
        return null;
    }
}

// Check if DB is available
function isDBAvailable() {
    try {
        $db = getDB();
        return $db !== null;
    } catch (Exception $e) {
        return false;
    }
}

// Escape HTML
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Format number
function formatNumber($num) {
    if ($num >= 1000000) {
        return number_format($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return number_format($num / 1000, 1) . 'K';
    }
    return number_format($num);
}
