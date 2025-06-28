<?php
// session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

// Check if user is logged in and is a student
checkRole('student');

$database = new Database();
$conn = $database->getConnection();

$user_info = getUserInfo();
$user_id = $user_info['user_id'];

// Fetch student details
$student_query = "SELECT s.*, r.room_number, r.room_type, r.ac_type, r.monthly_rent 
                  FROM Student_Table s 
                  LEFT JOIN Room_Table r ON s.room_id = r.Room_ID 
                  WHERE s.user_id = :user_id";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bindParam(':user_id', $user_id);
$student_stmt->execute();
$student = $student_stmt->fetch();

if (!$student) {
    header("Location: ../auth/login.php?error=student_not_found");
    exit();
}

// Fetch payment history
$payment_query = "SELECT * FROM Payment_Table WHERE student_id = :student_id ORDER BY created_at DESC LIMIT 5";
$payment_stmt = $conn->prepare($payment_query);
$payment_stmt->bindParam(':student_id', $student['Student_ID']);
$payment_stmt->execute();
$payments = $payment_stmt->fetchAll();

// Fetch complaint history
$complaint_query = "SELECT * FROM Complaint_Table WHERE student_id = :student_id ORDER BY created_at DESC LIMIT 5";
$complaint_stmt = $conn->prepare($complaint_query);
$complaint_stmt->bindParam(':student_id', $student['Student_ID']);
$complaint_stmt->execute();
$complaints = $complaint_stmt->fetchAll();

// Fetch visitor history
$visitor_query = "SELECT * FROM Visitor_Table WHERE student_id = :student_id ORDER BY created_at DESC LIMIT 5";
$visitor_stmt = $conn->prepare($visitor_query);
$visitor_stmt->bindParam(':student_id', $student['Student_ID']);
$visitor_stmt->execute();
$visitors = $visitor_stmt->fetchAll();

// Calculate outstanding dues
$dues_query = "SELECT SUM(total_amount) as total_dues FROM Payment_Table WHERE student_id = :student_id AND status = 'Pending'";
$dues_stmt = $conn->prepare($dues_query);
$dues_stmt->bindParam(':student_id', $student['Student_ID']);
$dues_stmt->execute();
$dues_result = $dues_stmt->fetch();
$outstanding_dues = $dues_result['total_dues'] ?? 0;

// Count statistics
$total_complaints = count($complaints);
$total_visitors = count($visitors);
$total_payments = count($payments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="#" class="nav-brand">
                <i class="fas fa-building"></i> HMS - Student Portal
            </a>
            <div class="nav-user">
                <div class="nav-user-info">
                    <span class="nav-username"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                    <span class="nav-role">Student</span>
                </div>
                <a href="../auth/logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Room Information -->
        <?php if ($student['room_id']): ?>
        <div class="card bg-primary" style="color: white; margin-bottom: 2rem;">
            <div class="card-body">
                <h2><i class="fas fa-home"></i> Your Room Information</h2>
                <div class="stats-grid" style="margin-top: 1rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 600;"><?php echo htmlspecialchars($student['room_number']); ?></div>
                        <div style="opacity: 0.8;">Room Number</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 600;"><?php echo htmlspecialchars($student['room_type']); ?></div>
                        <div style="opacity: 0.8;">Room Type</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 600;"><?php echo htmlspecialchars($student['ac_type']); ?></div>
                        <div style="opacity: 0.8;">AC Type</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 600;">₹<?php echo number_format($student['monthly_rent'], 2); ?></div>
                        <div style="opacity: 0.8;">Monthly Rent</div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            No room assigned yet. Please contact the administration.
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-<?php echo $outstanding_dues > 0 ? 'danger' : 'success'; ?>">
                    ₹<?php echo number_format($outstanding_dues, 2); ?>
                </div>
                <div class="stat-label">Outstanding Dues</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $total_payments; ?></div>
                <div class="stat-label">Total Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo $total_complaints; ?></div>
                <div class="stat-label">Complaints Filed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-info"><?php echo $total_visitors; ?></div>
                <div class="stat-label">Visitors Registered</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row" style="margin-bottom: 2rem;">
            <div class="col-3">
                <button class="btn btn-primary btn-block" onclick="openModal('complaintModal')">
                    <i class="fas fa-exclamation-circle"></i> File Complaint
                </button>
            </div>
            <div class="col-3">
                <button class="btn btn-success btn-block" onclick="openModal('visitorModal')">
                    <i class="fas fa-user-plus"></i> Register Visitor
                </button>
            </div>
            <div class="col-3">
                <button class="btn btn-warning btn-block" onclick="openModal('profileModal')">
                    <i class="fas fa-user-edit"></i> Update Profile
                </button>
            </div>
            <div class="col-3">
                <a href="payment_history.php" class="btn btn-info btn-block">
                    <i class="fas fa-history"></i> Payment History
                </a>
            </div>
        </div>

        <!-- Dashboard Content Grid -->
        <div class="card-grid">
            <!-- Recent Payments -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon success">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="card-title">Recent Payments</div>
                </div>
                <div class="card-body">
                    <?php if (count($payments) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?></td>
                                        <td>₹<?php echo number_format($payment['total_amount'], 2); ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($payment['status']); ?>"><?php echo $payment['status']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No payment history found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Complaints -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon warning">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="card-title">Recent Complaints</div>
                </div>
                <div class="card-body">
                    <?php if (count($complaints) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complaints as $complaint): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($complaint['complaint_date'])); ?></td>
                                        <td><span class="badge badge-<?php echo $complaint['priority']; ?>"><?php echo ucfirst($complaint['priority']); ?></span></td>
                                        <td><span class="badge badge-<?php echo strtolower(str_replace('_', '-', $complaint['status'])); ?>"><?php echo $complaint['status']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No complaints filed yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Visitors -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-title">Recent Visitors</div>
                </div>
                <div class="card-body">
                    <?php if (count($visitors) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>Time In</th>
                                        <th>Relationship</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visitors as $visitor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($visitor['visitor_first_name'] . ' ' . $visitor['visitor_last_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($visitor['visit_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($visitor['time_in'])); ?></td>
                                        <td><?php echo htmlspecialchars($visitor['relationship_with_student']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No visitors registered yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php include 'modals.php'; ?>

    <script src="../js/dashboard.js"></script>
</body>
</html>
