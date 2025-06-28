<?php
// session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('admin');

$database = new Database();
$conn = $database->getConnection();

$user_info = getUserInfo();
$admin_user_id = $user_info['user_id'];

// Get admin ID
$admin_query = "SELECT Admin_ID FROM Admin_Table WHERE user_id = :user_id";
$admin_stmt = $conn->prepare($admin_query);
$admin_stmt->bindParam(':user_id', $admin_user_id);
$admin_stmt->execute();
$admin = $admin_stmt->fetch();

// Handle payment approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_payment'])) {
    $payment_id = $_POST['payment_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    
    $new_status = $action === 'approve' ? 'Paid' : 'Cancelled';
    $approval_date = $action === 'approve' ? date('Y-m-d H:i:s') : null;
    
    $update_query = "UPDATE Payment_Table SET 
                     status = :status,
                     approved_by = :admin_id,
                     approval_date = :approval_date
                     WHERE Payment_ID = :payment_id";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':status', $new_status);
    $update_stmt->bindParam(':admin_id', $admin['Admin_ID']);
    $update_stmt->bindParam(':approval_date', $approval_date);
    $update_stmt->bindParam(':payment_id', $payment_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Payment " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!";
    } else {
        $error_message = "Failed to update payment status.";
    }
}

// Handle new payment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_payment'])) {
    $student_id = $_POST['student_id'];
    $payment_type = $_POST['payment_type'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $remarks = $_POST['remarks'];
    
    try {
        // Generate payment ID
        $payment_id_query = "SELECT COUNT(*) as count FROM Payment_Table";
        $payment_id_stmt = $conn->prepare($payment_id_query);
        $payment_id_stmt->execute();
        $count = $payment_id_stmt->fetch()['count'];
        $payment_id = 'P' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        
        $insert_query = "INSERT INTO Payment_Table (Payment_ID, student_id, payment_type, amount, due_date, status, admin_id, remarks) 
                         VALUES (:payment_id, :student_id, :payment_type, :amount, :due_date, 'Pending', :admin_id, :remarks)";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bindParam(':payment_id', $payment_id);
        $insert_stmt->bindParam(':student_id', $student_id);
        $insert_stmt->bindParam(':payment_type', $payment_type);
        $insert_stmt->bindParam(':amount', $amount);
        $insert_stmt->bindParam(':due_date', $due_date);
        $insert_stmt->bindParam(':admin_id', $admin['Admin_ID']);
        $insert_stmt->bindParam(':remarks', $remarks);
        
        if ($insert_stmt->execute()) {
            $success_message = "Payment record created successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Failed to create payment record.";
    }
}

// Fetch payments with filters
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$payments_query = "SELECT p.*, s.first_name, s.last_name, s.student_roll_number, r.room_number,
                          a.first_name as admin_first_name, a.last_name as admin_last_name
                   FROM Payment_Table p 
                   JOIN Student_Table s ON p.student_id = s.Student_ID 
                   LEFT JOIN Room_Table r ON s.room_id = r.Room_ID
                   LEFT JOIN Admin_Table a ON p.approved_by = a.Admin_ID
                   WHERE 1=1";

$params = [];

if ($filter_status) {
    $payments_query .= " AND p.status = :status";
    $params[':status'] = $filter_status;
}

if ($filter_type) {
    $payments_query .= " AND p.payment_type = :type";
    $params[':type'] = $filter_type;
}

if ($search) {
    $payments_query .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.student_roll_number LIKE :search)";
    $params[':search'] = "%$search%";
}

$payments_query .= " ORDER BY p.created_at DESC";

$payments_stmt = $conn->prepare($payments_query);
foreach ($params as $key => $value) {
    $payments_stmt->bindValue($key, $value);
}
$payments_stmt->execute();
$payments = $payments_stmt->fetchAll();

// Payment statistics
$stats_query = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_payments,
                    SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) as total_collected,
                    SUM(CASE WHEN status = 'Pending' THEN total_amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN status = 'Overdue' THEN total_amount ELSE 0 END) as overdue_amount
                FROM Payment_Table";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$payment_stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-brand">
                <i class="fas fa-crown"></i> HMS - Admin Portal
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
            <h1 class="dashboard-title">Payment Management</h1>
            <p class="dashboard-subtitle">Manage hostel fee payments and financial records</p>
        </div>

        <!-- Payment Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $payment_stats['total_payments']; ?></div>
                <div class="stat-label">Total Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo $payment_stats['pending_payments']; ?></div>
                <div class="stat-label">Pending Approvals</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success">₹<?php echo number_format($payment_stats['total_collected'], 0); ?></div>
                <div class="stat-label">Total Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning">₹<?php echo number_format($payment_stats['pending_amount'], 0); ?></div>
                <div class="stat-label">Pending Amount</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger">₹<?php echo number_format($payment_stats['overdue_amount'], 0); ?></div>
                <div class="stat-label">Overdue Amount</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row" style="margin-bottom: 2rem;">
            <div class="col-3">
                <button class="btn btn-primary btn-block" onclick="openModal('createPaymentModal')">
                    <i class="fas fa-plus"></i> Create Payment Record
                </button>
            </div>
            <div class="col-3">
                <a href="?status=Pending" class="btn btn-warning btn-block">
                    <i class="fas fa-clock"></i> Pending Approvals
                </a>
            </div>
            <div class="col-3">
                <a href="?status=Overdue" class="btn btn-danger btn-block">
                    <i class="fas fa-exclamation-triangle"></i> Overdue Payments
                </a>
            </div>
            <div class="col-3">
                <a href="payment_reports.php" class="btn btn-info btn-block">
                    <i class="fas fa-chart-bar"></i> Payment Reports
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-3">
                        <label for="search" class="form-label">Search Student</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               placeholder="Name or Roll Number" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Paid" <?php echo $filter_status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="Overdue" <?php echo $filter_status === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                            <option value="Cancelled" <?php echo $filter_status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-3">
                        <label for="type" class="form-label">Payment Type</label>
                        <select name="type" id="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="monthly_rent" <?php echo $filter_type === 'monthly_rent' ? 'selected' : ''; ?>>Monthly Rent</option>
                            <option value="security_deposit" <?php echo $filter_type === 'security_deposit' ? 'selected' : ''; ?>>Security Deposit</option>
                            <option value="mess_fee" <?php echo $filter_type === 'mess_fee' ? 'selected' : ''; ?>>Mess Fee</option>
                            <option value="penalty" <?php echo $filter_type === 'penalty' ? 'selected' : ''; ?>>Penalty</option>
                        </select>
                    </div>
                    <div class="col-3" style="display: flex; align-items: end; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="payment_management.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon success">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-title">Payment Records (<?php echo count($payments); ?>)</div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Student</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Approved By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['Payment_ID']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($payment['student_roll_number']); ?></small>
                                </td>
                                <td>
                                    <?php if ($payment['room_number']): ?>
                                        <span class="badge badge-primary">Room <?php echo $payment['room_number']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">No Room</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?></td>
                                <td>
                                    <strong>₹<?php echo number_format($payment['total_amount'], 2); ?></strong>
                                    <?php if ($payment['penalty'] > 0): ?>
                                        <br><small class="text-danger">Penalty: ₹<?php echo number_format($payment['penalty'], 2); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($payment['due_date']): ?>
                                        <?php echo date('M d, Y', strtotime($payment['due_date'])); ?>
                                        <?php if (strtotime($payment['due_date']) < time() && $payment['status'] === 'Pending'): ?>
                                            <br><small class="text-danger">Overdue</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($payment['status']); ?>">
                                        <?php echo $payment['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($payment['admin_first_name']): ?>
                                        <?php echo htmlspecialchars($payment['admin_first_name'] . ' ' . $payment['admin_last_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($payment['status'] === 'Pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['Payment_ID']; ?>">
                                            <button type="submit" name="approve_payment" value="approve" class="btn btn-sm btn-success" 
                                                    onclick="return confirm('Approve this payment?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <input type="hidden" name="action" value="approve">
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['Payment_ID']; ?>">
                                            <button type="submit" name="approve_payment" value="reject" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Reject this payment?')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <input type="hidden" name="action" value="reject">
                                        </form>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-primary" onclick="viewPayment('<?php echo $payment['Payment_ID']; ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Payment Modal -->
    <div id="createPaymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Create Payment Record</h2>
                <button class="modal-close" onclick="closeModal('createPaymentModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="student_id" class="form-label">Select Student</label>
                        <select name="student_id" id="student_id" class="form-control" required>
                            <option value="">Choose Student</option>
                            <?php
                            $students_query = "SELECT Student_ID, first_name, last_name, student_roll_number FROM Student_Table WHERE student_status = 'active' ORDER BY first_name";
                            $students_stmt = $conn->prepare($students_query);
                            $students_stmt->execute();
                            $students = $students_stmt->fetchAll();
                            foreach ($students as $student): ?>
                                <option value="<?php echo $student['Student_ID']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_roll_number'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="payment_type" class="form-label">Payment Type</label>
                                <select name="payment_type" id="payment_type" class="form-control" required>
                                    <option value="monthly_rent">Monthly Rent</option>
                                    <option value="security_deposit">Security Deposit</option>
                                    <option value="mess_fee">Mess Fee</option>
                                    <option value="penalty">Penalty</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="amount" class="form-label">Amount (₹)</label>
                                <input type="number" name="amount" id="amount" class="form-control" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" name="due_date" id="due_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" rows="3" class="form-control" placeholder="Optional remarks"></textarea>
                    </div>
                    <button type="submit" name="create_payment" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Payment Record
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function viewPayment(paymentId) {
            // Implementation for viewing payment details
            alert('View payment details for: ' + paymentId);
        }

        // Set minimum due date to today
        document.getElementById('due_date').min = new Date().toISOString().split('T')[0];
    </script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
