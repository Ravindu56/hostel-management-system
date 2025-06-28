<?php
// session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('admin');

$database = new Database();
$conn = $database->getConnection();

$user_info = getUserInfo();
$user_id = $user_info['user_id'];

// Fetch admin details
$admin_query = "SELECT * FROM Admin_Table WHERE user_id = :user_id";
$admin_stmt = $conn->prepare($admin_query);
$admin_stmt->bindParam(':user_id', $user_id);
$admin_stmt->execute();
$admin = $admin_stmt->fetch();

// System-wide statistics
$stats_queries = [
    'total_students' => "SELECT COUNT(*) as count FROM Student_Table WHERE student_status = 'active'",
    'total_rooms' => "SELECT COUNT(*) as count FROM Room_Table",
    'occupied_rooms' => "SELECT COUNT(*) as count FROM Room_Table WHERE room_status = 'occupied'",
    'total_wardens' => "SELECT COUNT(*) as count FROM Warden_Table",
    'pending_payments' => "SELECT COUNT(*) as count FROM Payment_Table WHERE status = 'Pending'",
    'total_revenue' => "SELECT SUM(total_amount) as total FROM Payment_Table WHERE status = 'Paid'",
    'pending_complaints' => "SELECT COUNT(*) as count FROM Complaint_Table WHERE status IN ('Pending', 'In_Progress')",
    'active_visitors' => "SELECT COUNT(*) as count FROM Visitor_Table WHERE visitor_status = 'checked_in'",
    'outstanding_dues' => "SELECT SUM(total_amount) as total FROM Payment_Table WHERE status = 'Pending'"
];

$stats = [];
foreach ($stats_queries as $key => $query) {
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $stats[$key] = $result['count'] ?? $result['total'] ?? 0;
}

// Recent activities
$recent_students_query = "SELECT Student_ID, first_name, last_name, created_at FROM Student_Table ORDER BY created_at DESC LIMIT 5";
$recent_students_stmt = $conn->prepare($recent_students_query);
$recent_students_stmt->execute();
$recent_students = $recent_students_stmt->fetchAll();

$recent_payments_query = "SELECT p.*, s.first_name, s.last_name FROM Payment_Table p 
                          JOIN Student_Table s ON p.student_id = s.Student_ID 
                          ORDER BY p.created_at DESC LIMIT 5";
$recent_payments_stmt = $conn->prepare($recent_payments_query);
$recent_payments_stmt->execute();
$recent_payments = $recent_payments_stmt->fetchAll();

$urgent_complaints_query = "SELECT c.*, s.first_name, s.last_name FROM Complaint_Table c 
                           JOIN Student_Table s ON c.student_id = s.Student_ID 
                           WHERE c.priority = 'urgent' AND c.status != 'Resolved' 
                           ORDER BY c.created_at DESC LIMIT 5";
$urgent_complaints_stmt = $conn->prepare($urgent_complaints_query);
$urgent_complaints_stmt->execute();
$urgent_complaints = $urgent_complaints_stmt->fetchAll();

// Monthly revenue chart data
$monthly_revenue_query = "SELECT 
                            MONTH(payment_date) as month,
                            YEAR(payment_date) as year,
                            SUM(total_amount) as revenue
                          FROM Payment_Table 
                          WHERE status = 'Paid' AND payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                          GROUP BY YEAR(payment_date), MONTH(payment_date)
                          ORDER BY year, month";
$monthly_revenue_stmt = $conn->prepare($monthly_revenue_query);
$monthly_revenue_stmt->execute();
$monthly_revenue = $monthly_revenue_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="#" class="nav-brand">
                <i class="fas fa-crown"></i> HMS - Admin Portal
            </a>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="user_management.php" class="nav-link">
                    <i class="fas fa-users-cog"></i> Users
                </a>
                <a href="payment_management.php" class="nav-link">
                    <i class="fas fa-money-bill-wave"></i> Payments
                </a>
                <a href="system_reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="system_settings.php" class="nav-link">
                    <i class="fas fa-cogs"></i> Settings
                </a>
            </div>
            <div class="nav-user">
                <div class="nav-user-info">
                    <span class="nav-username"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></span>
                    <span class="nav-role">Administrator</span>
                </div>
                <a href="../auth/logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">System Overview</h1>
            <p class="dashboard-subtitle">Complete hostel management and administration</p>
        </div>

        <!-- Key Performance Indicators -->
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
                <div class="stat-number text-success">₹<?php echo number_format($stats['total_revenue'], 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning">₹<?php echo number_format($stats['outstanding_dues'], 0); ?></div>
                <div class="stat-label">Outstanding Dues</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger"><?php echo $stats['pending_complaints']; ?></div>
                <div class="stat-label">Pending Issues</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $stats['total_wardens']; ?></div>
                <div class="stat-label">Active Wardens</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row" style="margin-bottom: 2rem;">
            <div class="col-3">
                <a href="user_management.php?action=add_student" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Add Student
                </a>
            </div>
            <div class="col-3">
                <a href="payment_management.php?filter=pending" class="btn btn-warning btn-block">
                    <i class="fas fa-credit-card"></i> Approve Payments
                </a>
            </div>
            <div class="col-3">
                <a href="system_reports.php" class="btn btn-info btn-block">
                    <i class="fas fa-file-alt"></i> Generate Reports
                </a>
            </div>
            <div class="col-3">
                <button class="btn btn-success btn-block" onclick="openModal('announcementModal')">
                    <i class="fas fa-bullhorn"></i> Send Announcement
                </button>
            </div>
        </div>

        <!-- Dashboard Content Grid -->
        <div class="card-grid">
            <!-- Revenue Chart -->
            <div class="card" style="grid-column: span 2;">
                <div class="card-header">
                    <div class="card-icon success">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-title">Monthly Revenue Trend</div>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Recent Students -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon primary">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="card-title">Recent Registrations</div>
                </div>
                <div class="card-body">
                    <?php if (count($recent_students) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo date('M d', strtotime($student['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No recent registrations.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon success">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-title">Recent Payments</div>
                </div>
                <div class="card-body">
                    <?php if (count($recent_payments) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                        <td>₹<?php echo number_format($payment['total_amount'], 2); ?></td>
                                        <td><span class="badge badge-<?php echo strtolower($payment['status']); ?>"><?php echo $payment['status']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No recent payments.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Urgent Complaints -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="card-title">Urgent Issues</div>
                </div>
                <div class="card-body">
                    <?php if (count($urgent_complaints) > 0): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($urgent_complaints as $complaint): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($complaint['first_name'] . ' ' . $complaint['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                        <td><?php echo date('M d', strtotime($complaint['complaint_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No urgent complaints.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Announcement Modal -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Send System Announcement</h2>
                <button class="modal-close" onclick="closeModal('announcementModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="send_announcement.php" method="POST">
                    <div class="form-group">
                        <label for="recipient_type" class="form-label">Send To</label>
                        <select name="recipient_type" id="recipient_type" class="form-control" required>
                            <option value="all">All Users</option>
                            <option value="students">All Students</option>
                            <option value="wardens">All Wardens</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="message" class="form-label">Message</label>
                        <textarea name="message" id="message" rows="5" class="form-control" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Announcement
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_revenue); ?>;
        
        const labels = monthlyData.map(item => {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return months[item.month - 1] + ' ' + item.year;
        });
        
        const data = monthlyData.map(item => parseFloat(item.revenue));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: data,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
