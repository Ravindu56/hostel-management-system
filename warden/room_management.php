<?php

// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('warden');

$database = new Database();
$conn = $database->getConnection();

$user_info = getUserInfo();
$user_id = $user_info['user_id'];

// Get warden details using exact SQL schema field names
$warden_query = "SELECT Warden_ID FROM Warden_Table WHERE user_id = :user_id";
$warden_stmt = $conn->prepare($warden_query);
$warden_stmt->bindParam(':user_id', $user_id);
$warden_stmt->execute();
$warden = $warden_stmt->fetch();

if (!$warden) {
    header("Location: dashboard.php?error=warden_not_found");
    exit();
}

// ENHANCED: Room update with editable Room_ID and all fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
    $original_room_id = isset($_POST['original_room_id']) ? $_POST['original_room_id'] : $_POST['room_id'];
    $room_id = trim($_POST['room_id']); // New editable Room_ID
    $room_number = trim($_POST['room_number']);
    $room_type = $_POST['room_type'];
    $ac_type = $_POST['ac_type'];
    $capacity = (int)$_POST['capacity'];
    $occupied_count = (int)($_POST['occupied_count'] ?: 0);
    $floor_number = $_POST['floor_number'] ?: null;
    $wing = $_POST['wing'] ?: null;
    $monthly_rent = (float)$_POST['monthly_rent'];
    $security_deposit = (float)($_POST['security_deposit'] ?: 0);
    $amenities = trim($_POST['amenities']) ?: null;
    $room_status = $_POST['room_status'];
    
    $errors = [];
    
    // Enhanced validation for all fields
    if (empty($room_id)) {
        $errors[] = "Room ID is required";
    } elseif (!preg_match('/^R[0-9]{3}$/', $room_id)) {
        $errors[] = "Room ID must be in format R001, R002, etc.";
    }
    
    if (empty($room_number)) $errors[] = "Room number is required";
    if (!in_array($room_type, ['Single', 'Double', 'Triple'])) $errors[] = "Valid room type is required";
    if (!in_array($ac_type, ['AC', 'Non-AC'])) $errors[] = "Valid AC type is required";
    if ($capacity < 1 || $capacity > 4) $errors[] = "Capacity must be between 1 and 4";
    if ($occupied_count < 0 || $occupied_count > $capacity) $errors[] = "Occupied count must be between 0 and capacity";
    if ($monthly_rent < 1000 || $monthly_rent > 50000) $errors[] = "Monthly rent must be between ₹1,000 and ₹50,000";
    if (!in_array($room_status, ['available', 'occupied', 'maintenance', 'reserved'])) $errors[] = "Invalid room status";
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Authorization check - verify warden owns the original room
            $auth_check_query = "SELECT Room_ID FROM Room_Table WHERE Room_ID = :original_room_id AND warden_id = :warden_id";
            $auth_check_stmt = $conn->prepare($auth_check_query);
            $auth_check_stmt->bindParam(':original_room_id', $original_room_id);
            $auth_check_stmt->bindParam(':warden_id', $warden['Warden_ID']);
            $auth_check_stmt->execute();
            
            if ($auth_check_stmt->rowCount() === 0) {
                throw new Exception("Unauthorized: You can only modify your own rooms");
            }
            
            // Check if new Room_ID already exists (if changed)
            if ($room_id !== $original_room_id) {
                $check_room_id_query = "SELECT Room_ID FROM Room_Table WHERE Room_ID = :room_id";
                $check_room_id_stmt = $conn->prepare($check_room_id_query);
                $check_room_id_stmt->bindParam(':room_id', $room_id);
                $check_room_id_stmt->execute();
                
                if ($check_room_id_stmt->rowCount() > 0) {
                    throw new Exception("Room ID already exists. Please choose a different Room ID.");
                }
            }
            
            // Check room number uniqueness
            $check_room_number_query = "SELECT room_number FROM Room_Table WHERE room_number = :room_number AND Room_ID != :original_room_id";
            $check_room_number_stmt = $conn->prepare($check_room_number_query);
            $check_room_number_stmt->bindParam(':room_number', $room_number);
            $check_room_number_stmt->bindParam(':original_room_id', $original_room_id);
            $check_room_number_stmt->execute();
            
            if ($check_room_number_stmt->rowCount() > 0) {
                throw new Exception("Room number already exists for another room.");
            }
            
            // If Room_ID changed, update student assignments first
            if ($room_id !== $original_room_id) {
                $update_students_query = "UPDATE Student_Table SET room_id = :new_room_id WHERE room_id = :original_room_id";
                $update_students_stmt = $conn->prepare($update_students_query);
                $update_students_stmt->bindParam(':new_room_id', $room_id);
                $update_students_stmt->bindParam(':original_room_id', $original_room_id);
                $update_students_stmt->execute();
            }
            
            // Update room with all editable fields including Room_ID
            $update_query = "UPDATE Room_Table SET 
                            Room_ID = :room_id,
                            room_number = :room_number,
                            room_type = :room_type,
                            ac_type = :ac_type,
                            capacity = :capacity,
                            occupied_count = :occupied_count,
                            floor_number = :floor_number,
                            wing = :wing,
                            monthly_rent = :monthly_rent,
                            security_deposit = :security_deposit,
                            amenities = :amenities,
                            room_status = :room_status,
                            updated_at = NOW()
                            WHERE Room_ID = :original_room_id";
            
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':room_id', $room_id);
            $update_stmt->bindParam(':room_number', $room_number);
            $update_stmt->bindParam(':room_type', $room_type);
            $update_stmt->bindParam(':ac_type', $ac_type);
            $update_stmt->bindParam(':capacity', $capacity);
            $update_stmt->bindParam(':occupied_count', $occupied_count);
            $update_stmt->bindParam(':floor_number', $floor_number);
            $update_stmt->bindParam(':wing', $wing);
            $update_stmt->bindParam(':monthly_rent', $monthly_rent);
            $update_stmt->bindParam(':security_deposit', $security_deposit);
            $update_stmt->bindParam(':amenities', $amenities);
            $update_stmt->bindParam(':room_status', $room_status);
            $update_stmt->bindParam(':original_room_id', $original_room_id);
            
            if ($update_stmt->execute()) {
                $conn->commit();
                $success_message = "Room updated successfully! Room ID: $room_id";
            } else {
                throw new Exception("Failed to update room");
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to update room: " . $e->getMessage();
        }
    } else {
        $error_message = implode(", ", $errors);
    }
}

// FIXED: Room assignment with proper authorization and correct logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_room'])) {
    $student_id = $_POST['student_id'];
    $room_id = $_POST['room_id'];
    
    try {
        $conn->beginTransaction();
        
        // Authorization check - verify warden owns this room
        $auth_check_query = "SELECT Room_ID FROM Room_Table WHERE Room_ID = :room_id AND warden_id = :warden_id";
        $auth_check_stmt = $conn->prepare($auth_check_query);
        $auth_check_stmt->bindParam(':room_id', $room_id);
        $auth_check_stmt->bindParam(':warden_id', $warden['Warden_ID']);
        $auth_check_stmt->execute();
        
        if ($auth_check_stmt->rowCount() === 0) {
            throw new Exception("Unauthorized: You can only assign students to your own rooms");
        }
        
        // Check if student exists and is not already assigned
        $student_check_query = "SELECT room_id FROM Student_Table WHERE Student_ID = :student_id";
        $student_check_stmt = $conn->prepare($student_check_query);
        $student_check_stmt->bindParam(':student_id', $student_id);
        $student_check_stmt->execute();
        $student_info = $student_check_stmt->fetch();
        
        if (!$student_info) {
            throw new Exception("Student not found");
        }
        
        if ($student_info['room_id']) {
            throw new Exception("Student is already assigned to a room");
        }
        
        // Check room capacity and availability
        $capacity_query = "SELECT capacity, occupied_count, room_status FROM Room_Table WHERE Room_ID = :room_id";
        $capacity_stmt = $conn->prepare($capacity_query);
        $capacity_stmt->bindParam(':room_id', $room_id);
        $capacity_stmt->execute();
        $room_info = $capacity_stmt->fetch();
        
        if (!$room_info) {
            throw new Exception("Room not found");
        }
        
        if ($room_info['room_status'] !== 'available') {
            throw new Exception("Room is not available for assignment");
        }
        
        if ($room_info['occupied_count'] >= $room_info['capacity']) {
            throw new Exception("Room is at full capacity");
        }
        
        // Update student's room assignment
        $assign_query = "UPDATE Student_Table SET room_id = :room_id WHERE Student_ID = :student_id";
        $assign_stmt = $conn->prepare($assign_query);
        $assign_stmt->bindParam(':room_id', $room_id);
        $assign_stmt->bindParam(':student_id', $student_id);
        
        // FIXED: Correct room status logic
        $update_room_query = "UPDATE Room_Table SET 
                             occupied_count = occupied_count + 1,
                             room_status = CASE 
                                 WHEN room_status IN ('maintenance', 'reserved') THEN room_status
                                 WHEN occupied_count + 1 >= capacity THEN 'occupied'
                                 ELSE 'available'
                             END
                             WHERE Room_ID = :room_id";
        $update_room_stmt = $conn->prepare($update_room_query);
        $update_room_stmt->bindParam(':room_id', $room_id);
        
        if ($assign_stmt->execute() && $update_room_stmt->execute()) {
            $conn->commit();
            $success_message = "Room assigned successfully!";
        } else {
            throw new Exception("Failed to assign room - Database error");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to assign room: " . $e->getMessage();
    }
}

// Handle unassign room functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unassign_room'])) {
    $student_id = $_POST['student_id'];
    
    try {
        $conn->beginTransaction();
        
        // Get current room assignment
        $current_room_query = "SELECT room_id FROM Student_Table WHERE Student_ID = :student_id";
        $current_room_stmt = $conn->prepare($current_room_query);
        $current_room_stmt->bindParam(':student_id', $student_id);
        $current_room_stmt->execute();
        $current_room = $current_room_stmt->fetch();
        
        if (!$current_room || !$current_room['room_id']) {
            throw new Exception("Student is not assigned to any room");
        }
        
        $room_id = $current_room['room_id'];
        
        // Authorization check - verify warden owns this room
        $auth_check_query = "SELECT Room_ID FROM Room_Table WHERE Room_ID = :room_id AND warden_id = :warden_id";
        $auth_check_stmt = $conn->prepare($auth_check_query);
        $auth_check_stmt->bindParam(':room_id', $room_id);
        $auth_check_stmt->bindParam(':warden_id', $warden['Warden_ID']);
        $auth_check_stmt->execute();
        
        if ($auth_check_stmt->rowCount() === 0) {
            throw new Exception("Unauthorized: You can only unassign students from your own rooms");
        }
        
        // Unassign student from room
        $unassign_query = "UPDATE Student_Table SET room_id = NULL WHERE Student_ID = :student_id";
        $unassign_stmt = $conn->prepare($unassign_query);
        $unassign_stmt->bindParam(':student_id', $student_id);
        
        // FIXED: Correct unassign logic
        $update_room_query = "UPDATE Room_Table SET 
                             occupied_count = GREATEST(0, occupied_count - 1),
                             room_status = CASE 
                                 WHEN room_status IN ('maintenance', 'reserved') THEN room_status
                                 WHEN GREATEST(0, occupied_count - 1) <= 0 THEN 'available'
                                 ELSE room_status
                             END
                             WHERE Room_ID = :room_id";
        $update_room_stmt = $conn->prepare($update_room_query);
        $update_room_stmt->bindParam(':room_id', $room_id);
        
        if ($unassign_stmt->execute() && $update_room_stmt->execute()) {
            $conn->commit();
            $success_message = "Student unassigned from room successfully!";
        } else {
            throw new Exception("Failed to unassign room");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to unassign room: " . $e->getMessage();
    }
}

//  Room status updates with proper parameter binding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room_status'])) {
    $room_id = $_POST['room_id'];
    $room_status = $_POST['room_status'];
    
    try {
        $conn->beginTransaction();
        
        // Authorization check
        $auth_check_query = "SELECT Room_ID FROM Room_Table WHERE Room_ID = :room_id AND warden_id = :warden_id";
        $auth_check_stmt = $conn->prepare($auth_check_query);
        $auth_check_stmt->bindParam(':room_id', $room_id);
        $auth_check_stmt->bindParam(':warden_id', $warden['Warden_ID']);
        $auth_check_stmt->execute();
        
        if ($auth_check_stmt->rowCount() === 0) {
            throw new Exception("Unauthorized: You can only modify your own rooms");
        }
        
        // Proper status update with parameter binding
        $update_query = "UPDATE Room_Table SET room_status = :status WHERE Room_ID = :room_id";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':status', $room_status);
        $update_stmt->bindParam(':room_id', $room_id);
        
        if ($update_stmt->execute()) {
            $conn->commit();
            $success_message = "Room status updated successfully!";
        } else {
            throw new Exception("Failed to update room status");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to update room status: " . $e->getMessage();
    }
}

// Fetch all rooms using exact field names - only rooms managed by this warden
$rooms_query =$rooms_query = "SELECT r.*, 
                       COALESCE(r.occupied_count, 0) as occupied_count,
                       GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') as occupants,
                       GROUP_CONCAT(s.Student_ID SEPARATOR ', ') as student_ids
                FROM Room_Table r 
                LEFT JOIN Student_Table s ON r.Room_ID = s.room_id 
                WHERE r.warden_id = :warden_id
                GROUP BY r.Room_ID 
                ORDER BY r.room_number";
$rooms_stmt = $conn->prepare($rooms_query);
$rooms_stmt->bindParam(':warden_id', $warden['Warden_ID']);
$rooms_stmt->execute();
$rooms = $rooms_stmt->fetchAll();

// Room statistics using exact field names - only for this warden's rooms
$stats_query = "SELECT 
                    COUNT(*) as total_rooms,
                    SUM(CASE WHEN room_status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                    SUM(CASE WHEN room_status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
                    SUM(CASE WHEN room_status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms,
                    SUM(occupied_count) as total_occupants,
                    SUM(capacity) as total_capacity
                FROM Room_Table
                WHERE warden_id = :warden_id";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bindParam(':warden_id', $warden['Warden_ID']);
$stats_stmt->execute();
$room_stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            transition: opacity 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: translateY(-50px);
            transition: all 0.3s ease;
        }

        .modal[style*="block"] .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }

        .modal-title {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: #dc3545;
        }

        .modal-body {
            padding: 2rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        .assign-unassign-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .student-assignment {
            margin-bottom: 0.5rem;
            padding: 0.25rem;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-row .form-group {
            flex: 1;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-brand">
                <i class="fas fa-shield-alt"></i> HMS - Warden Portal
            </a>
            <div class="nav-user">
                <a href="../auth/logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Room Management</h1>
            <p class="dashboard-subtitle">Monitor and manage hostel room allocations</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Room Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $room_stats['total_rooms']; ?></div>
                <div class="stat-label">Total Rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo $room_stats['available_rooms']; ?></div>
                <div class="stat-label">Available Rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo $room_stats['occupied_rooms']; ?></div>
                <div class="stat-label">Occupied Rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger"><?php echo $room_stats['maintenance_rooms']; ?></div>
                <div class="stat-label">Under Maintenance</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-info"><?php echo $room_stats['total_occupants']; ?>/<?php echo $room_stats['total_capacity']; ?></div>
                <div class="stat-label">Occupancy Rate</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row" style="margin-bottom: 2rem;">
            <div class="col-4">
                <button class="btn btn-success btn-block" onclick="openModal('assignRoomModal')">
                    <i class="fas fa-user-plus"></i> Assign Room
                </button>
            </div>
            <div class="col-4">
                <button class="btn btn-warning btn-block" onclick="filterRooms('maintenance')">
                    <i class="fas fa-tools"></i> Maintenance Rooms
                </button>
            </div>
            <div class="col-4">
                <button class="btn btn-info btn-block" onclick="filterRooms('all')">
                    <i class="fas fa-list"></i> All Rooms
                </button>
            </div>
        </div>

        <!-- Rooms Grid -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon primary">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="card-title">Room Overview</div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table" id="roomsTable">
                        <thead>
                            <tr>
                                <th>Room ID</th>
                                <th>Room No.</th>
                                <th>Type</th>
                                <th>AC Type</th>
                                <th>Capacity</th>
                                <th>Occupancy</th>
                                <th>Occupants</th>
                                <th>Rent</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                            <tr data-status="<?php echo $room['room_status']; ?>">
                                <td><strong><?php echo htmlspecialchars($room['Room_ID']); ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                <td><?php echo htmlspecialchars($room['ac_type']); ?></td>
                                <td><?php echo $room['capacity']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo ($room['occupied_count'] ?? 0) >= $room['capacity'] ? 'danger' : (($room['occupied_count'] ?? 0) > 0 ? 'warning' : 'success'); ?>">
                                        <?php echo ($room['occupied_count'] ?? 0); ?>/<?php echo $room['capacity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($room['occupants']): ?>
                                        <div class="assign-unassign-buttons">
                                            <?php 
                                            $student_names = explode(', ', $room['occupants']);
                                            $student_ids = explode(', ', $room['student_ids']);
                                            for ($i = 0; $i < count($student_names); $i++): 
                                            ?>
                                                <div class="student-assignment">
                                                    <small><?php echo htmlspecialchars($student_names[$i]); ?></small>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_ids[$i]); ?>">
                                                        <button type="submit" name="unassign_room" class="btn btn-danger btn-sm" onclick="return confirm('Unassign this student?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Empty</span>
                                    <?php endif; ?>
                                </td>
                                <td>₹<?php echo number_format($room['monthly_rent'], 0); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $room['room_status'] == 'available' ? 'success' : 
                                            ($room['room_status'] == 'occupied' ? 'primary' : 
                                            ($room['room_status'] == 'maintenance' ? 'danger' : 'warning')); 
                                    ?>">
                                        <?php echo ucfirst($room['room_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary btn-tb" onclick="editRoom('<?php echo htmlspecialchars($room['Room_ID']); ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <?php if (($room['occupied_count'] ?? 0) < $room['capacity'] && $room['room_status'] == 'available'): ?>
                                    <button class="btn btn-sm btn-success btn-tb" onclick="showAssignModal('<?php echo htmlspecialchars($room['Room_ID']); ?>')">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!--  Edit Room Modal with all fields including editable Room_ID -->
    <div id="editRoomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Room - Complete Details</h2>
                <button class="modal-close" onclick="closeModal('editRoomModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editRoomForm">
                    <input type="hidden" name="original_room_id" id="original_room_id">
                    
                    <!-- Room ID and Room Number -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_room_id" class="form-label">Room ID *</label>
                            <input type="text" name="room_id" id="edit_room_id" class="form-control" 
                                   required maxlength="10" pattern="^R[0-9]{3}$" 
                                   title="Room ID format: R001, R002, etc.">
                            <small class="form-text text-muted">Format: R + 3 digits (e.g., R001)</small>
                        </div>
                        <div class="form-group">
                            <label for="edit_room_number" class="form-label">Room Number *</label>
                            <input type="text" name="room_number" id="edit_room_number" class="form-control" 
                                   required maxlength="10">
                        </div>
                    </div>
                    
                    <!-- Room Type and AC Type -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_room_type" class="form-label">Room Type *</label>
                            <select name="room_type" id="edit_room_type" class="form-control" required>
                                <option value="">Select Room Type</option>
                                <option value="Single">Single</option>
                                <option value="Double">Double</option>
                                <option value="Triple">Triple</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_ac_type" class="form-label">AC Type *</label>
                            <select name="ac_type" id="edit_ac_type" class="form-control" required>
                                <option value="">Select AC Type</option>
                                <option value="AC">AC</option>
                                <option value="Non-AC">Non-AC</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Capacity and Occupied Count -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_capacity" class="form-label">Capacity *</label>
                            <select name="capacity" id="edit_capacity" class="form-control" required>
                                <option value="">Select Capacity</option>
                                <option value="1">1 Person</option>
                                <option value="2">2 Persons</option>
                                <option value="3">3 Persons</option>
                                <option value="4">4 Persons</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_occupied_count" class="form-label">Current Occupancy *</label>
                            <input type="number" name="occupied_count" id="edit_occupied_count" 
                                   class="form-control" min="0" max="4" required>
                        </div>
                    </div>
                    
                    <!-- Floor and Wing -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_floor_number" class="form-label">Floor Number</label>
                            <select name="floor_number" id="edit_floor_number" class="form-control">
                                <option value="">Select Floor</option>
                                <option value="1">Ground Floor</option>
                                <option value="2">1st Floor</option>
                                <option value="3">2nd Floor</option>
                                <option value="4">3rd Floor</option>
                                <option value="5">4th Floor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_wing" class="form-label">Wing</label>
                            <select name="wing" id="edit_wing" class="form-control">
                                <option value="">Select Wing</option>
                                <option value="A">Wing A</option>
                                <option value="B">Wing B</option>
                                <option value="C">Wing C</option>
                                <option value="D">Wing D</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Monthly Rent and Security Deposit -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_monthly_rent" class="form-label">Monthly Rent (₹) *</label>
                            <input type="number" name="monthly_rent" id="edit_monthly_rent" class="form-control" 
                                   step="100" min="1000" max="50000" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_security_deposit" class="form-label">Security Deposit (₹)</label>
                            <input type="number" name="security_deposit" id="edit_security_deposit" class="form-control" 
                                   step="100" min="0" max="20000">
                        </div>
                    </div>
                    
                    <!-- Amenities -->
                    <div class="form-group">
                        <label for="edit_amenities" class="form-label">Amenities</label>
                        <textarea name="amenities" id="edit_amenities" rows="3" class="form-control" 
                                  placeholder="e.g., WiFi, Study Table, Wardrobe, Attached Bathroom"></textarea>
                    </div>
                    
                    <!-- Room Status -->
                    <div class="form-group">
                        <label for="edit_room_status" class="form-label">Room Status *</label>
                        <select name="room_status" id="edit_room_status" class="form-control" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Under Maintenance</option>
                            <option value="reserved">Reserved</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="update_room" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Update Room
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Room Assignment Modal -->
    <div id="assignRoomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Assign Room to Student</h2>
                <button class="modal-close" onclick="closeModal('assignRoomModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="student_id" class="form-label">Select Student</label>
                        <select name="student_id" id="student_id" class="form-control" required>
                            <option value="">Choose Student</option>
                            <?php
                            // Query for unassigned students using exact field names
                            $unassigned_query = "SELECT Student_ID, first_name, last_name, student_roll_number FROM Student_Table WHERE room_id IS NULL AND student_status = 'active'";
                            $unassigned_stmt = $conn->prepare($unassigned_query);
                            $unassigned_stmt->execute();
                            $unassigned_students = $unassigned_stmt->fetchAll();
                            foreach ($unassigned_students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['Student_ID']); ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_roll_number'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="room_id" class="form-label">Select Available Room</label>
                        <select name="room_id" id="room_id" class="form-control" required>
                            <option value="">Choose Room</option>
                            <?php
                            // Query for available rooms using exact field names - only this warden's rooms
                            $available_query = "SELECT Room_ID, room_number, room_type, ac_type, capacity, occupied_count, monthly_rent 
                                               FROM Room_Table 
                                               WHERE (occupied_count IS NULL OR occupied_count < capacity) 
                                               AND room_status = 'available'
                                               AND warden_id = :warden_id";
                            $available_stmt = $conn->prepare($available_query);
                            $available_stmt->bindParam(':warden_id', $warden['Warden_ID']);
                            $available_stmt->execute();
                            $available_rooms = $available_stmt->fetchAll();
                            foreach ($available_rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['Room_ID']); ?>">
                                    <?php echo htmlspecialchars($room['Room_ID']); ?> - Room <?php echo htmlspecialchars($room['room_number']); ?> - <?php echo htmlspecialchars($room['room_type']); ?> (<?php echo htmlspecialchars($room['ac_type']); ?>) 
                                    [<?php echo ($room['occupied_count'] ?? 0); ?>/<?php echo $room['capacity']; ?>] - ₹<?php echo number_format($room['monthly_rent']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="assign_room" class="btn btn-primary">
                        <i class="fas fa-check"></i> Assign Room
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Assign Modal for specific room -->
    <div id="quickAssignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Assign Student to Room</h2>
                <button class="modal-close" onclick="closeModal('quickAssignModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="room_id" id="quick_assign_room_id">
                    <div class="form-group">
                        <label for="quick_student_id" class="form-label">Select Student</label>
                        <select name="student_id" id="quick_student_id" class="form-control" required>
                            <option value="">Choose Student</option>
                            <?php foreach ($unassigned_students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['Student_ID']); ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_roll_number'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="assign_room" class="btn btn-primary">
                        <i class="fas fa-check"></i> Assign Student
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const rooms = <?php echo json_encode($rooms); ?>;

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.opacity = '1';
                }, 10);
            } else {
                console.error('Modal not found:', modalId);
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }, 300);
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                const modalId = event.target.id;
                closeModal(modalId);
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const openModals = document.querySelectorAll('.modal[style*="block"]');
                openModals.forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });

        function filterRooms(status) {
            const rows = document.querySelectorAll('#roomsTable tbody tr');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // ENHANCED: Edit room functionality with all fields including Room_ID
        function editRoom(roomId) {
            const room = rooms.find(r => r.Room_ID === roomId);
            if (room) {
                // Set original Room_ID for reference
                document.getElementById('original_room_id').value = room.Room_ID;
                
                // Populate all fields
                document.getElementById('edit_room_id').value = room.Room_ID;
                document.getElementById('edit_room_number').value = room.room_number;
                document.getElementById('edit_room_type').value = room.room_type;
                document.getElementById('edit_ac_type').value = room.ac_type;
                document.getElementById('edit_capacity').value = room.capacity;
                document.getElementById('edit_occupied_count').value = room.occupied_count || 0;
                document.getElementById('edit_floor_number').value = room.floor_number || '';
                document.getElementById('edit_wing').value = room.wing || '';
                document.getElementById('edit_monthly_rent').value = room.monthly_rent;
                document.getElementById('edit_security_deposit').value = room.security_deposit || '';
                document.getElementById('edit_amenities').value = room.amenities || '';
                document.getElementById('edit_room_status').value = room.room_status;
                
                openModal('editRoomModal');
            }
        }

        function showAssignModal(roomId) {
            document.getElementById('quick_assign_room_id').value = roomId;
            document.getElementById('quick_student_id').selectedIndex = 0;
            openModal('quickAssignModal');
        }

        // Enhanced form validation
        document.getElementById('editRoomForm')?.addEventListener('submit', function(e) {
            const roomId = document.getElementById('edit_room_id').value.trim();
            const roomNumber = document.getElementById('edit_room_number').value.trim();
            const capacity = parseInt(document.getElementById('edit_capacity').value);
            const occupiedCount = parseInt(document.getElementById('edit_occupied_count').value);
            const monthlyRent = parseFloat(document.getElementById('edit_monthly_rent').value);
            const roomType = document.getElementById('edit_room_type').value;
            const acType = document.getElementById('edit_ac_type').value;
            
            // Validate Room_ID format
            if (!/^R[0-9]{3}$/.test(roomId)) {
                e.preventDefault();
                alert('Room ID must be in format R001, R002, R003, etc.');
                return;
            }
            
            if (!/^[A-Z0-9]{1,10}$/i.test(roomNumber)) {
                e.preventDefault();
                alert('Room number should be 1-10 characters long and contain only letters and numbers');
                return;
            }
            
            if (occupiedCount > capacity) {
                e.preventDefault();
                alert('Occupied count cannot be greater than capacity');
                return;
            }
            
            if (monthlyRent < 1000 || monthlyRent > 50000) {
                e.preventDefault();
                alert('Monthly rent should be between ₹1,000 and ₹50,000');
                return;
            }
            
            if (!roomType || !acType || !capacity) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }
            
            if (!['AC', 'Non-AC'].includes(acType)) {
                e.preventDefault();
                alert('Please select a valid AC type');
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating Room...';
            submitBtn.disabled = true;
        });

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        // Auto-calculate security deposit
        document.getElementById('edit_monthly_rent')?.addEventListener('input', function() {
            const monthlyRent = parseFloat(this.value);
            const securityDepositField = document.getElementById('edit_security_deposit');
            
            if (monthlyRent && !securityDepositField.value) {
                const suggestedDeposit = Math.round(monthlyRent * 0.5 / 100) * 100;
                securityDepositField.value = suggestedDeposit;
            }
        });

        // Validate occupied count against capacity
        document.getElementById('edit_capacity')?.addEventListener('change', function() {
            const capacity = parseInt(this.value);
            const occupiedCountField = document.getElementById('edit_occupied_count');
            const currentOccupied = parseInt(occupiedCountField.value);
            
            if (currentOccupied > capacity) {
                occupiedCountField.value = capacity;
                alert('Occupied count adjusted to match new capacity');
            }
            
            occupiedCountField.max = capacity;
        });

        function editRoom(roomId) {
            try {
                // FIXED: Use correct field name from your schema - Room_ID not RoomID
                const room = rooms.find(r => r.Room_ID === roomId);
                
                if (!room) {
                    console.error('Room not found:', roomId);
                    alert('Room data not found. Please refresh the page and try again.');
                    return;
                }
                
                // FIXED: Set both original_room_id and current values using exact schema field names
                const originalRoomIdField = document.getElementById('original_room_id');
                const editRoomIdField = document.getElementById('edit_room_id');
                const editRoomNumberField = document.getElementById('edit_room_number');
                const editRoomTypeField = document.getElementById('edit_room_type');
                const editAcTypeField = document.getElementById('edit_ac_type');
                const editCapacityField = document.getElementById('edit_capacity');
                const editOccupiedCountField = document.getElementById('edit_occupied_count');
                const editFloorNumberField = document.getElementById('edit_floor_number');
                const editWingField = document.getElementById('edit_wing');
                const editMonthlyRentField = document.getElementById('edit_monthly_rent');
                const editSecurityDepositField = document.getElementById('edit_security_deposit');
                const editAmenitiesField = document.getElementById('edit_amenities');
                const editRoomStatusField = document.getElementById('edit_room_status');
                
                // Check if all required fields exist
                if (!originalRoomIdField || !editRoomIdField) {
                    console.error('Required form fields not found');
                    alert('Form fields not found. Please refresh the page.');
                    return;
                }
                
                // FIXED: Populate all fields using exact schema field names from your ERD
                originalRoomIdField.value = room.Room_ID || '';
                editRoomIdField.value = room.Room_ID || '';
                
                // Use correct field names based on your relational mapping
                if (editRoomNumberField) editRoomNumberField.value = room.room_number || '';
                if (editRoomTypeField) editRoomTypeField.value = room.Room_Type || '';
                if (editAcTypeField) editAcTypeField.value = room.AC_Type || '';
                if (editCapacityField) editCapacityField.value = room.Capacity || '';
                if (editOccupiedCountField) editOccupiedCountField.value = room.Occupied_Count || 0;
                if (editFloorNumberField) editFloorNumberField.value = room.floor_number || '';
                if (editWingField) editWingField.value = room.wing || '';
                if (editMonthlyRentField) editMonthlyRentField.value = room.monthly_rent || '';
                if (editSecurityDepositField) editSecurityDepositField.value = room.security_deposit || '';
                if (editAmenitiesField) editAmenitiesField.value = room.amenities || '';
                if (editRoomStatusField) editRoomStatusField.value = room.room_status || '';
                
                // Open the modal
                openModal('editRoomModal');
                
            } catch (error) {
                console.error('Error in editRoom function:', error);
                alert('An error occurred while loading room data. Please try again.');
            }
        }

    </script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
