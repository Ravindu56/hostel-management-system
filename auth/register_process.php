<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get form data
$user_type = $_POST['user_type'];
$username = trim($_POST['username']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$mobile_number = trim($_POST['mobile_number']);

// Validation
$errors = [];

// Basic validation
if (empty($username) || strlen($username) < 3) {
    $errors[] = "Username must be at least 3 characters long";
}

if (empty($password) || strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters long";
}

if ($password !== $confirm_password) {
    header("Location: register.php?error=password_mismatch");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

if (!preg_match('/^[0-9]{10}$/', $mobile_number)) {
    $errors[] = "Mobile number must be 10 digits";
}

// User type specific validation
if ($user_type === 'student') {
    $student_roll_number = trim($_POST['student_roll_number']);
    $course = trim($_POST['course']);
    $year_of_study = $_POST['year_of_study'];
    $department = trim($_POST['department']);
    $gender = $_POST['gender'];
    
    if (empty($student_roll_number) || empty($course) || empty($department) || empty($gender)) {
        $errors[] = "All student fields are required";
    }
} elseif ($user_type === 'warden') {
    $warden_role = $_POST['warden_role'];
    $shift_timing = $_POST['shift_timing'];
    
    if (empty($warden_role) || empty($shift_timing)) {
        $errors[] = "All warden fields are required";
    }
}

if (!empty($errors)) {
    header("Location: register.php?error=invalid_data");
    exit();
}

try {
    $conn->beginTransaction();
    
    // Check if username already exists
    $username_check = "SELECT username FROM user_auth WHERE username = :username";
    $username_stmt = $conn->prepare($username_check);
    $username_stmt->bindParam(':username', $username);
    $username_stmt->execute();
    
    if ($username_stmt->rowCount() > 0) {
        header("Location: register.php?error=username_exists");
        exit();
    }
    
    // Check if email already exists
    $email_check_queries = [
        "SELECT email FROM Student_Table WHERE email = :email",
        "SELECT email FROM Warden_Table WHERE email = :email",
        "SELECT email FROM Admin_Table WHERE email = :email"
    ];
    
    foreach ($email_check_queries as $query) {
        $email_stmt = $conn->prepare($query);
        $email_stmt->bindParam(':email', $email);
        $email_stmt->execute();
        
        if ($email_stmt->rowCount() > 0) {
            header("Location: register.php?error=email_exists");
            exit();
        }
    }
    
    // Create user authentication record
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $salt = bin2hex(random_bytes(16));
    
    $auth_query = "INSERT INTO user_auth (username, password_hash, salt, user_role, is_active) VALUES (:username, :password_hash, :salt, :user_role, 1)";
    $auth_stmt = $conn->prepare($auth_query);
    $auth_stmt->bindParam(':username', $username);
    $auth_stmt->bindParam(':password_hash', $password_hash);
    $auth_stmt->bindParam(':salt', $salt);
    $auth_stmt->bindParam(':user_role', $user_type);
    $auth_stmt->execute();
    
    $user_id = $conn->lastInsertId();
    
    // Create specific user record based on type
    if ($user_type === 'student') {
        // Generate student ID
        $student_id_query = "SELECT COUNT(*) as count FROM Student_Table";
        $student_id_stmt = $conn->prepare($student_id_query);
        $student_id_stmt->execute();
        $count = $student_id_stmt->fetch()['count'];
        $student_id = 'S' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        
        $student_query = "INSERT INTO Student_Table (Student_ID, user_id, first_name, last_name, email, mobile_number, student_roll_number, course, department, year_of_study, gender, student_status, admission_date) 
                         VALUES (:student_id, :user_id, :first_name, :last_name, :email, :mobile, :roll_number, :course, :department, :year, :gender, 'active', CURDATE())";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->bindParam(':student_id', $student_id);
        $student_stmt->bindParam(':user_id', $user_id);
        $student_stmt->bindParam(':first_name', $first_name);
        $student_stmt->bindParam(':last_name', $last_name);
        $student_stmt->bindParam(':email', $email);
        $student_stmt->bindParam(':mobile', $mobile_number);
        $student_stmt->bindParam(':roll_number', $student_roll_number);
        $student_stmt->bindParam(':course', $course);
        $student_stmt->bindParam(':department', $department);
        $student_stmt->bindParam(':year', $year_of_study);
        $student_stmt->bindParam(':gender', $gender);
        $student_stmt->execute();
        
    } elseif ($user_type === 'warden') {
        // Generate warden ID
        $warden_id_query = "SELECT COUNT(*) as count FROM Warden_Table";
        $warden_id_stmt = $conn->prepare($warden_id_query);
        $warden_id_stmt->execute();
        $count = $warden_id_stmt->fetch()['count'];
        $warden_id = 'W' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
        
        $warden_query = "INSERT INTO Warden_Table (Warden_ID, user_id, first_name, last_name, email, mobile_number, role, shift_timing) 
                        VALUES (:warden_id, :user_id, :first_name, :last_name, :email, :mobile, :role, :shift)";
        $warden_stmt = $conn->prepare($warden_query);
        $warden_stmt->bindParam(':warden_id', $warden_id);
        $warden_stmt->bindParam(':user_id', $user_id);
        $warden_stmt->bindParam(':first_name', $first_name);
        $warden_stmt->bindParam(':last_name', $last_name);
        $warden_stmt->bindParam(':email', $email);
        $warden_stmt->bindParam(':mobile', $mobile_number);
        $warden_stmt->bindParam(':role', $warden_role);
        $warden_stmt->bindParam(':shift', $shift_timing);
        $warden_stmt->execute();
    }
    
    $conn->commit();
    header("Location: register.php?success=registration_successful");
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Registration error: " . $e->getMessage());
    header("Location: register.php?error=registration_failed");
}
?>
