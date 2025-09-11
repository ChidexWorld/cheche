<?php
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