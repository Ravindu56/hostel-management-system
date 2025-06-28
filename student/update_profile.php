<?php
session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

// Check if user is logged in and is a student
checkRole('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$user_info = getUserInfo();
$user_id = $user_info['user_id'];

// Validate input
$mobile_number = trim($_POST['mobile_number']);
$email = trim($_POST['email']);
$parent_mobile = trim($_POST['parent_mobile']);
$emergency_contact = trim($_POST['emergency_contact']);
$local_guardian_name = trim($_POST['local_guardian_name']);
$local_guardian_mobile = trim($_POST['local_guardian_mobile']);

$errors = [];

if (!empty($mobile_number) && !preg_match('/^[0-9]{10}$/', $mobile_number)) {
    $errors[] = "Invalid mobile number format";
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

if (!empty($parent_mobile) && !preg_match('/^[0-9]{10}$/', $parent_mobile)) {
    $errors[] = "Invalid parent mobile number format";
}

if (!empty($emergency_contact) && !preg_match('/^[0-9]{10}$/', $emergency_contact)) {
    $errors[] = "Invalid emergency contact format";
}

if (!empty($local_guardian_mobile) && !preg_match('/^[0-9]{10}$/', $local_guardian_mobile)) {
    $errors[] = "Invalid local guardian mobile format";
}

if (!empty($errors)) {
    $error_message = implode(", ", $errors);
    header("Location: dashboard.php?error=" . urlencode($error_message));
    exit();
}

try {
    // Check if email is already taken by another student
    if (!empty($email)) {
        $email_check_query = "SELECT Student_ID FROM Student_Table WHERE email = :email AND user_id != :user_id";
        $email_check_stmt = $conn->prepare($email_check_query);
        $email_check_stmt->bindParam(':email', $email);
        $email_check_stmt->bindParam(':user_id', $user_id);
        $email_check_stmt->execute();
        
        if ($email_check_stmt->rowCount() > 0) {
            header("Location: dashboard.php?error=email_already_exists");
            exit();
        }
    }

    // Update student profile
    $update_query = "UPDATE Student_Table SET 
                     mobile_number = COALESCE(NULLIF(:mobile_number, ''), mobile_number),
                     email = COALESCE(NULLIF(:email, ''), email),
                     parent_mobile = COALESCE(NULLIF(:parent_mobile, ''), parent_mobile),
                     emergency_contact = COALESCE(NULLIF(:emergency_contact, ''), emergency_contact),
                     local_guardian_name = COALESCE(NULLIF(:local_guardian_name, ''), local_guardian_name),
                     local_guardian_mobile = COALESCE(NULLIF(:local_guardian_mobile, ''), local_guardian_mobile)
                     WHERE user_id = :user_id";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':mobile_number', $mobile_number);
    $update_stmt->bindParam(':email', $email);
    $update_stmt->bindParam(':parent_mobile', $parent_mobile);
    $update_stmt->bindParam(':emergency_contact', $emergency_contact);
    $update_stmt->bindParam(':local_guardian_name', $local_guardian_name);
    $update_stmt->bindParam(':local_guardian_mobile', $local_guardian_mobile);
    $update_stmt->bindParam(':user_id', $user_id);
    
    if ($update_stmt->execute()) {
        header("Location: dashboard.php?success=profile_updated");
    } else {
        header("Location: dashboard.php?error=profile_update_failed");
    }
} catch (Exception $e) {
    header("Location: dashboard.php?error=database_error");
}
?>
