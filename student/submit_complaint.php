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
$category = trim($_POST['category']);
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$priority = trim($_POST['priority']);

$errors = [];

if (empty($category)) {
    $errors[] = "Category is required";
}

if (empty($title)) {
    $errors[] = "Title is required";
} elseif (strlen($title) > 200) {
    $errors[] = "Title must be less than 200 characters";
}

if (empty($description)) {
    $errors[] = "Description is required";
}

if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
    $errors[] = "Invalid priority level";
}

if (!empty($errors)) {
    $error_message = implode(", ", $errors);
    header("Location: dashboard.php?error=" . urlencode($error_message));
    exit();
}

try {
    // Generate complaint ID
    $complaint_id_query = "SELECT COUNT(*) as count FROM Complaint_Table";
    $complaint_id_stmt = $conn->prepare($complaint_id_query);
    $complaint_id_stmt->execute();
    $count_result = $complaint_id_stmt->fetch();
    $complaint_id = 'C' . str_pad($count_result['count'] + 1, 3, '0', STR_PAD_LEFT);

    // Insert complaint
    $insert_query = "INSERT INTO Complaint_Table (Complaint_ID, student_id, category, title, description, priority, status, complaint_date) 
                     VALUES (:complaint_id, :student_id, :category, :title, :description, :priority, 'Pending', CURDATE())";
    
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bindParam(':complaint_id', $complaint_id);
    $insert_stmt->bindParam(':student_id', $student['Student_ID']);
    $insert_stmt->bindParam(':category', $category);
    $insert_stmt->bindParam(':title', $title);
    $insert_stmt->bindParam(':description', $description);
    $insert_stmt->bindParam(':priority', $priority);
    
    if ($insert_stmt->execute()) {
        header("Location: dashboard.php?success=complaint_submitted");
    } else {
        header("Location: dashboard.php?error=complaint_submission_failed");
    }
} catch (Exception $e) {
    header("Location: dashboard.php?error=database_error");
}
?>
