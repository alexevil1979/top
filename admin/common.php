<?php
/**
 * Admin Common Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/DB.php';

session_start();

// Check authentication
function checkAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// Login function
function login($username, $password) {
    if ($username === ADMIN_USER && password_verify($password, ADMIN_PASS_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        return true;
    }
    return false;
}

// Logout function
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get DB instance
function getDB() {
    return DB::getInstance();
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
