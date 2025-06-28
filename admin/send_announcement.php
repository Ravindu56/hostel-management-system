<?php
// session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$recipient_type = $_POST['recipient_type'];
$subject = $_POST['subject'];
$message = $_POST['message'];

// Create announcements table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS System_Announcements (
    announcement_id INT PRIMARY KEY AUTO_INCREMENT,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    recipient_type ENUM('all', 'students', 'wardens') NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES user_auth(user_id)
)";
$conn->exec($create_table_query);

try {
    $user_info = getUserInfo();
    $admin_user_id = $user_info['user_id'];
    
    // Insert announcement
    $insert_query = "INSERT INTO System_Announcements (subject, message, recipient_type, created_by) 
                     VALUES (:subject, :message, :recipient_type, :created_by)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bindParam(':subject', $subject);
    $insert_stmt->bindParam(':message', $message);
    $insert_stmt->bindParam(':recipient_type', $recipient_type);
    $insert_stmt->bindParam(':created_by', $admin_user_id);
    
    if ($insert_stmt->execute()) {
        // In a real implementation, you would send emails or notifications here
        header("Location: dashboard.php?success=announcement_sent");
    } else {
        header("Location: dashboard.php?error=announcement_failed");
    }
} catch (Exception $e) {
    header("Location: dashboard.php?error=database_error");
}
?>
