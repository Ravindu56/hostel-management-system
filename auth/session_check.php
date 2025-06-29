<?php
session_start();
function checkLogin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}
function checkRole($required_role) {
    checkLogin();
    if ($_SESSION['role'] !== $required_role) {
        header("Location: ../auth/login.php?error=unauthorized");
        exit();
    }
}
function redirectToDashboard($role) {
    switch($role) {
        case 'student':
            header("Location: ../student/dashboard.php");
            break;
        case 'warden':
            header("Location: ../warden/dashboard.php");
            break;
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        default:
            header("Location: ../auth/login.php");
    }
    exit();
}
function getUserInfo() {
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null
    ];
}
?>
