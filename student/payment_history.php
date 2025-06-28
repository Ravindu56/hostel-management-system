<?php
session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

// Check if user is logged in and is a student
checkRole('student');

$database = new Database();
$conn = $database->getConnection();

$user_info = getUserInfo();
$user_id = $user_info['user_id'];

// Get student ID
$student_query = "SELECT Student_ID, first_name, last_name FROM Student_Table WHERE user_id = :user_id";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bindParam(':user_id', $user_id);
$student_stmt->execute();
$student = $student_stmt->fetch();

if (!$student) {
    header("Location: ../auth/login.php?error=student_not_found");
    exit();
}

// Fetch all payments
$payment_query = "SELECT * FROM Payment_Table WHERE student_id = :student_id ORDER BY created_at DESC";
$payment_stmt = $conn->prepare($payment_query);
$payment_stmt->bindParam(':student_id', $student['Student_ID']);
$payment_stmt->execute();
$payments = $payment_stmt->fetchAll();

// Calculate totals
$total_paid = 0;
$total_pending = 0;
$total_overdue = 0;

foreach ($payments as $payment) {
    switch ($payment['status']) {
        case 'Paid':
            $total_paid += $payment['total_amount'];
            break;
        case 'Pending':
            $total_pending += $payment['total_amount'];
            break;
        case 'Overdue':
            $total_overdue += $payment['total_amount'];
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-brand">
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
        <div class="dashboard-header">
            <h1 class="dashboard-title">Payment History</h1>
            <p class="dashboard-subtitle">Track all your hostel fee payments and dues</p>
        </div>

        <!-- Payment Summary -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-success">₹<?php echo number_format($total_paid, 2); ?></div>
                <div class="stat-label">Total Paid</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning">₹<?php echo number_format($total_pending, 2); ?></div>
                <div class="stat-label">Pending Amount</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger">₹<?php echo number_format($total_overdue, 2); ?></div>
                <div class="stat-label">Overdue Amount</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo count($payments); ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
        </div>

        <!-- Back Button -->
        <div style="margin-bottom: 2rem;">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Payment History Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon primary">
                    <i class="fas fa-history"></i>
                </div>
                <div class="card-title">Complete Payment History</div>
            </div>
            <div class="card-body">
                <?php if (count($payments) > 0): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Penalty</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['Payment_ID']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?></td>
                                    <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td>₹<?php echo number_format($payment['penalty'], 2); ?></td>
                                    <td><strong>₹<?php echo number_format($payment['total_amount'], 2); ?></strong></td>
                                    <td><span class="badge badge-<?php echo strtolower($payment['status']); ?>"><?php echo $payment['status']; ?></span></td>
                                    <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center" style="padding: 3rem;">
                        <i class="fas fa-receipt" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <h3>No Payment History</h3>
                        <p class="text-muted">You don't have any payment records yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
