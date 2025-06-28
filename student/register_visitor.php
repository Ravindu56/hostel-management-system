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

// Get student ID
$student_query = "SELECT Student_ID FROM Student_Table WHERE user_id = :user_id";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bindParam(':user_id', $user_id);
$student_stmt->execute();
$student = $student_stmt->fetch();

if (!$student) {
    header("Location: dashboard.php?error=student_not_found");
    exit();
}

// Validate input
$visitor_first_name = trim($_POST['visitor_first_name']);
$visitor_last_name = trim($_POST['visitor_last_name']);
$visitor_mobile = trim($_POST['visitor_mobile']);
$visitor_email = trim($_POST['visitor_email']);
$relationship = trim($_POST['relationship']);
$visit_date = $_POST['visit_date'];
$time_in = $_POST['time_in'];
$purpose = trim($_POST['purpose']);

$errors = [];

if (empty($visitor_first_name)) {
    $errors[] = "Visitor first name is required";
}

if (empty($visitor_last_name)) {
    $errors[] = "Visitor last name is required";
}

if (empty($visitor_mobile)) {
    $errors[] = "Visitor mobile number is required";
} elseif (!preg_match('/^[0-9]{10}$/', $visitor_mobile)) {
    $errors[] = "Invalid mobile number format";
}

if (!empty($visitor_email) && !filter_var($visitor_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

if (empty($relationship)) {
    $errors[] = "Relationship is required";
}

if (empty($visit_date)) {
    $errors[] = "Visit date is required";
} elseif (strtotime($visit_date) < strtotime(date('Y-m-d'))) {
    $errors[] = "Visit date cannot be in the past";
}

if (empty($time_in)) {
    $errors[] = "Expected time in is required";
}

if (!empty($errors)) {
    $error_message = implode(", ", $errors);
    header("Location: dashboard.php?error=" . urlencode($error_message));
    exit();
}

try {
    // Generate visitor ID
    $visitor_id_query = "SELECT COUNT(*) as count FROM Visitor_Table";
    $visitor_id_stmt = $conn->prepare($visitor_id_query);
    $visitor_id_stmt->execute();
    $count_result = $visitor_id_stmt->fetch();
    $visitor_id = 'V' . str_pad($count_result['count'] + 1, 3, '0', STR_PAD_LEFT);

    // Insert visitor
    $insert_query = "INSERT INTO Visitor_Table (Visitor_ID, student_id, visitor_first_name, visitor_last_name, visitor_mobile, visitor_email, relationship_with_student, visit_date, time_in, purpose_of_visit, visitor_status) 
                     VALUES (:visitor_id, :student_id, :visitor_first_name, :visitor_last_name, :visitor_mobile, :visitor_email, :relationship, :visit_date, :time_in, :purpose, 'checked_in')";
    
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bindParam(':visitor_id', $visitor_id);
    $insert_stmt->bindParam(':student_id', $student['Student_ID']);
    $insert_stmt->bindParam(':visitor_first_name', $visitor_first_name);
    $insert_stmt->bindParam(':visitor_last_name', $visitor_last_name);
    $insert_stmt->bindParam(':visitor_mobile', $visitor_mobile);
    $insert_stmt->bindParam(':visitor_email', $visitor_email);
    $insert_stmt->bindParam(':relationship', $relationship);
    $insert_stmt->bindParam(':visit_date', $visit_date);
    $insert_stmt->bindParam(':time_in', $time_in);
    $insert_stmt->bindParam(':purpose', $purpose);
    
    if ($insert_stmt->execute()) {
        header("Location: dashboard.php?success=visitor_registered");
    } else {
        header("Location: dashboard.php?error=visitor_registration_failed");
    }
} catch (Exception $e) {
    header("Location: dashboard.php?error=database_error");
}
?>
