<?php
// session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('admin');

if (!isset($_GET['user_id']) || !isset($_GET['status'])) {
    header("Location: user_management.php?error=invalid_parameters");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$user_id = $_GET['user_id'];
$new_status = $_GET['status'] === 'true' ? 1 : 0;

try {
    $update_query = "UPDATE user_auth SET is_active = :status WHERE user_id = :user_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':status', $new_status);
    $update_stmt->bindParam(':user_id', $user_id);
    
    if ($update_stmt->execute()) {
        $action = $new_status ? 'activated' : 'deactivated';
        header("Location: user_management.php?success=user_" . $action);
    } else {
        header("Location: user_management.php?error=update_failed");
    }
} catch (Exception $e) {
    header("Location: user_management.php?error=database_error");
}
?>
