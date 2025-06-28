<?php
require_once '../config/database.php';
require_once '../auth/session_check.php';

// Check if user is logged in and is a warden
checkRole('warden');

$database = new Database();
$conn = $database->getConnection();

$user_info = getUserInfo();
$user_id = $user_info['user_id'];

// Fetch warden details
$warden_query = "SELECT * FROM Warden_Table WHERE user_id = :user_id";
$warden_stmt = $conn->prepare($warden_query);
$warden_stmt->bindParam(':user_id', $user_id);
$warden_stmt->execute();
$warden = $warden_stmt->fetch();

if (!$warden) {
    header("Location: ../auth/login.php?error=warden_not_found");
    exit();
}

// Dashboard statistics
$stats_queries = [
    'total_students' => "SELECT COUNT(*) as count FROM Student_Table WHERE student_status = 'active'",
    'total_rooms' => "SELECT COUNT(*) as count FROM Room_Table",
    'occupied_rooms' => "SELECT COUNT(*) as count FROM Room_Table WHERE room_status = 'occupied'",
    'pending_complaints' => "SELECT COUNT(*) as count FROM Complaint_Table WHERE status = 'Pending'",
    'pending_visitors' => "SELECT COUNT(*) as count FROM Visitor_Table WHERE visitor_status = 'checked_in'",
    'maintenance_requests' => "SELECT COUNT(*) as count FROM Complaint_Table WHERE category = 'Maintenance' AND status IN ('Pending', 'In_Progress')"
];

$stats = [];
foreach ($stats_queries as $key => $query) {
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $stats[$key] = $result['count'];
}

// Recent complaints assigned to this warden
$complaints_query = "SELECT c.*, s.first_name, s.last_name, s.room_id 
                     FROM Complaint_Table c 
                     JOIN Student_Table s ON c.student_id = s.Student_ID 
                     WHERE c.assigned_to = :warden_id 
                     ORDER BY c.created_at DESC LIMIT 5";
$complaints_stmt = $conn->prepare($complaints_query);
$complaints_stmt->bindParam(':warden_id', $warden['Warden_ID']);
$complaints_stmt->execute();
$recent_complaints = $complaints_stmt->fetchAll();

// Recent visitors needing approval
$visitors_query = "SELECT v.*, s.first_name, s.last_name, s.room_id 
                   FROM Visitor_Table v 
                   JOIN Student_Table s ON v.student_id = s.Student_ID 
                   WHERE v.visitor_status = 'checked_in' 
                   ORDER BY v.created_at DESC LIMIT 5";
$visitors_stmt = $conn->prepare($visitors_query);
$visitors_stmt->execute();
$recent_visitors = $visitors_stmt->fetchAll();

// Room occupancy overview
$rooms_query = "SELECT r.*, 
                       CASE WHEN r.occupied_count >= r.capacity THEN 'Full' 
                            WHEN r.occupied_count = 0 THEN 'Empty' 
                            ELSE 'Partial' END as occupancy_status
                FROM Room_Table r 
                ORDER BY r.room_number";
$rooms_stmt = $conn->prepare($rooms_query);
$rooms_stmt->execute();
$rooms_overview = $rooms_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Dashboard - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="#" class="nav-brand">
                <i class="fas fa-shield-alt"></i> HMS - Warden Portal
            </a>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="manage_complaints.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i> Complaints
                </a>
                <a href="room_management.php" class="nav-link">
                    <i class="fas fa-door-open"></i> Rooms
                </a>
                <a href="student_records.php" class="nav-link">
                    <i class="fas fa-users"></i> Students
                </a>
                <a href="visitor_management.php" class="nav-link">
                    <i class="fas fa-user-check"></i> Visitors
                </a>
            </div>
            <div class="nav-user">
                <div class="nav-user-info">
                    <span class="nav-username"><?php echo htmlspecialchars($warden['first_name'] . ' ' . $warden['last_name']); ?></span>
                    <span class="nav-role">Warden</span>
                </div>
                <a href="../auth/logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Warden Dashboard</h1>
            <p class="dashboard-subtitle">Manage hostel operations and student welfare</p>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">Active Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-info"><?php echo $stats['occupied_rooms']; ?>/<?php echo $stats['total_rooms']; ?></div>
                <div class="stat-label">Room Occupancy</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo $stats['pending_complaints']; ?></div>
                <div class="stat-label">Pending Complaints</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo $stats['pending_visitors']; ?></div>
                <div class="stat-label">Active Visitors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger"><?php echo $stats['maintenance_requests']; ?></div>
                <div class="stat-label">Maintenance Requests</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row" style="margin-bottom: 2rem;">
            <div class="col-3">
                <a href="manage_complaints.php" class="btn btn-warning btn-block">
                    <i class="fas fa-tools"></i> Process Complaints
                </a>
            </div>
            <div class="col-3">
                <a href="room_management.php" class="btn btn-primary btn-block">
                    <i class="fas fa-bed"></i> Manage Rooms
                </a>
            </div>
            <div class="col-3">
                <a href="visitor_management.php" class="btn btn-success btn-block">
                    <i class="fas fa-user-friends"></i> Check Visitors
                </a>
            </div>
            <div class="col-3">
                <button class="btn btn-info btn-block" onclick="openModal('assignRoomModal')">
                    <i class="fas fa-user-plus"></i> Assign Room
                </button>
            </div>
        </div>

        <!-- Dashboard Content Grid -->
        <div class="card-grid">
            <!-- Recent Complaints -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-title">Recent Complaints</div>
                </div>
                <div class="card-body">
                    <?php if (count($recent_complaints) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_complaints as $complaint): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($complaint['first_name'] . ' ' . $complaint['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                        <td><span class="badge badge-<?php echo $complaint['priority']; ?>"><?php echo ucfirst($complaint['priority']); ?></span></td>
                                        <td><span class="badge badge-<?php echo strtolower(str_replace('_', '-', $complaint['status'])); ?>"><?php echo $complaint['status']; ?></span></td>
                                        <td>
                                            <a href="manage_complaints.php?id=<?php echo $complaint['Complaint_ID']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No complaints assigned to you yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Room Overview -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon primary">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="card-title">Room Overview</div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Type</th>
                                    <th>Occupancy</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($rooms_overview, 0, 5) as $room): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($room['room_type'] . ' (' . $room['ac_type'] . ')'); ?></td>
                                    <td><?php echo $room['occupied_count']; ?>/<?php echo $room['capacity']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $room['occupancy_status'] == 'Full' ? 'danger' : 
                                                ($room['occupancy_status'] == 'Empty' ? 'success' : 'warning'); 
                                        ?>">
                                            <?php echo $room['occupancy_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Visitors -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon success">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-title">Active Visitors</div>
                </div>
                <div class="card-body">
                    <?php if (count($recent_visitors) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Visitor</th>
                                        <th>Student</th>
                                        <th>Time In</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_visitors as $visitor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($visitor['visitor_first_name'] . ' ' . $visitor['visitor_last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']); ?></td>
                                        <td><?php echo date('h:i A', strtotime($visitor['time_in'])); ?></td>
                                        <td>
                                            <a href="visitor_management.php?checkout=<?php echo $visitor['Visitor_ID']; ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-sign-out-alt"></i> Check Out
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No active visitors at the moment.</p>
                    <?php endif; ?>
                </div>
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
                <form action="assign_room.php" method="POST">
                    <div class="form-group">
                        <label for="student_id" class="form-label">Select Student</label>
                        <select name="student_id" id="student_id" class="form-control" required>
                            <option value="">Choose Student</option>
                            <?php
                            $unassigned_students_query = "SELECT Student_ID, first_name, last_name FROM Student_Table WHERE room_id IS NULL AND student_status = 'active'";
                            $unassigned_stmt = $conn->prepare($unassigned_students_query);
                            $unassigned_stmt->execute();
                            $unassigned_students = $unassigned_stmt->fetchAll();
                            foreach ($unassigned_students as $student): ?>
                                <option value="<?php echo $student['Student_ID']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['Student_ID'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="room_id" class="form-label">Select Available Room</label>
                        <select name="room_id" id="room_id" class="form-control" required>
                            <option value="">Choose Room</option>
                            <?php
                            $available_rooms_query = "SELECT Room_ID, room_number, room_type, ac_type, capacity, occupied_count FROM Room_Table WHERE occupied_count < capacity";
                            $available_stmt = $conn->prepare($available_rooms_query);
                            $available_stmt->execute();
                            $available_rooms = $available_stmt->fetchAll();
                            foreach ($available_rooms as $room): ?>
                                <option value="<?php echo $room['Room_ID']; ?>">
                                    Room <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?> (<?php echo $room['ac_type']; ?>) 
                                    [<?php echo $room['occupied_count']; ?>/<?php echo $room['capacity']; ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Assign Room
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/dashboard.js"></script>
</body>
</html>
