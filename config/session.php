<?php
require_once __DIR__ . '/env.php';

// Configure session settings from environment
ini_set('session.gc_maxlifetime', Env::getInt('SESSION_TIMEOUT', 86400));
ini_set('session.cookie_httponly', Env::getBool('SESSION_HTTPONLY', true) ? 1 : 0);
ini_set('session.cookie_secure', Env::getBool('SESSION_SECURE', false) ? 1 : 0);

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isInstructor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'instructor';
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireInstructor() {
    requireLogin();
    if (!isInstructor()) {
        header('Location: student-dashboard.php');
        exit();
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: instructor-dashboard.php');
        exit();
    }
}
?>