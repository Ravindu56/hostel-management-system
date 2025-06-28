<?php
session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('warden');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_complaints.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$user_info = getUserInfo();
$user_id = $user_info['user_id'];

// Get warden details
$warden_query = "SELECT Warden_ID FROM Warden_Table WHERE user_id = :user_id";
$warden_stmt = $conn->prepare($warden_query);
$warden_stmt->bindParam(':user_id', $user_id);
$warden_stmt->execute();
$warden = $warden_stmt->fetch();

$complaint_id = $_POST['complaint_id'];
$action = $_POST['action'];

try {
    $conn->beginTransaction();
    
    switch ($action) {
        case 'assign':
            $assign_query = "UPDATE Complaint_Table SET 
                            assigned_to = :warden_id,
                            assigned_date = NOW(),
                            status = 'In_Progress'
                            WHERE Complaint_ID = :complaint_id";
            
            $assign_stmt = $conn->prepare($assign_query);
            $assign_stmt->bindParam(':warden_id', $warden['Warden_ID']);
            $assign_stmt->bindParam(':complaint_id', $complaint_id);
            $assign_stmt->execute();
            
            $success_message = "Complaint assigned successfully!";
            break;
            
        case 'resolve':
            $resolution_description = $_POST['resolution_description'];
            $actual_cost = $_POST['actual_cost'] ?? null;
            
            $resolve_query = "UPDATE Complaint_Table SET 
                             status = 'Resolved',
                             resolution_date = NOW(),
                             resolution_description = :resolution_description,
                             actual_cost = :actual_cost
                             WHERE Complaint_ID = :complaint_id";
            
            $resolve_stmt = $conn->prepare($resolve_query);
            $resolve_stmt->bindParam(':resolution_description', $resolution_description);
            $resolve_stmt->bindParam(':actual_cost', $actual_cost);
            $resolve_stmt->bindParam(':complaint_id', $complaint_id);
            $resolve_stmt->execute();
            
            $success_message = "Complaint resolved successfully!";
            break;
            
        case 'close':
            $close_query = "UPDATE Complaint_Table SET status = 'Closed' WHERE Complaint_ID = :complaint_id";
            $close_stmt = $conn->prepare($close_query);
            $close_stmt->bindParam(':complaint_id', $complaint_id);
            $close_stmt->execute();
            
            $success_message = "Complaint closed successfully!";
            break;
    }
    
    $conn->commit();
    header("Location: manage_complaints.php?success=" . urlencode($success_message));
    
} catch (Exception $e) {
    $conn->rollback();
    header("Location: manage_complaints.php?error=processing_failed");
}
?>
