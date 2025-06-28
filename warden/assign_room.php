<?php
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('warden');

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $room_id = $_POST['room_id'] ?? null;

    // Validation
    if (!$student_id || !$room_id) {
        header("Location: room_management.php?error=Missing+student+or+room+selection");
        exit();
    }

    try {
        $conn->beginTransaction();

        // 1. Check if the student already has a room assigned
        $student_check = $conn->prepare("SELECT roomid FROM StudentTable WHERE StudentID = :student_id");
        $student_check->bindParam(':student_id', $student_id);
        $student_check->execute();
        $student = $student_check->fetch();

        if (!$student) {
            throw new Exception("Student not found.");
        }
        if ($student['roomid']) {
            throw new Exception("Student is already assigned to a room.");
        }

        // 2. Check room capacity
        $room_check = $conn->prepare("SELECT capacity, occupiedcount, roomstatus FROM RoomTable WHERE RoomID = :room_id");
        $room_check->bindParam(':room_id', $room_id);
        $room_check->execute();
        $room = $room_check->fetch();

        if (!$room) {
            throw new Exception("Room not found.");
        }
        if ($room['roomstatus'] !== 'available') {
            throw new Exception("Room is not available for assignment.");
        }
        if ($room['occupiedcount'] >= $room['capacity']) {
            throw new Exception("Room is at full capacity.");
        }

        // 3. Assign the room to the student
        $assign = $conn->prepare("UPDATE StudentTable SET roomid = :room_id WHERE StudentID = :student_id");
        $assign->bindParam(':room_id', $room_id);
        $assign->bindParam(':student_id', $student_id);
        if (!$assign->execute()) {
            throw new Exception("Failed to assign room to student.");
        }

        // The triggers in your DB will update RoomTable's occupiedcount and roomstatus automatically

        $conn->commit();
        header("Location: room_management.php?success=Room+assigned+successfully");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: room_management.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: room_management.php?error=Invalid+request");
    exit();
}
