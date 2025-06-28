<?php
require_once '../config/database.php';
require_once '../auth/session_check.php';
checkRole('warden');

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    if (!$student_id) {
        echo json_encode(['success' => false, 'message' => 'Missing student selection']);
        exit();
    }

    try {
        $conn->beginTransaction();
        $unassign = $conn->prepare("UPDATE Student_Table SET room_id = NULL WHERE Student_ID = :student_id");
        $unassign->bindParam(':student_id', $student_id);
        $unassign->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Room unassigned successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
