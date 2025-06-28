<?php
// FIXED: Proper session handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('warden');

$database = new Database();
$conn = $database->getConnection();

$user_info = getUserInfo();
$user_id = $user_info['user_id'];

// Get warden details using exact schema field names
$warden_query = "SELECT Warden_ID FROM Warden_Table WHERE user_id = :user_id";
$warden_stmt = $conn->prepare($warden_query);
$warden_stmt->bindParam(':user_id', $user_id);
$warden_stmt->execute();
$warden = $warden_stmt->fetch();

if (!$warden) {
    header("Location: dashboard.php?error=warden_not_found");
    exit();
}

// Filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_student = isset($_GET['student']) ? $_GET['student'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter_amount_min = isset($_GET['amount_min']) ? $_GET['amount_min'] : '';
$filter_amount_max = isset($_GET['amount_max']) ? $_GET['amount_max'] : '';

// Build query with filters using exact field names from your ERD
$where_conditions = [];
$params = [];

// Base query using exact field names from Payment_Table
$base_query = "SELECT p.Payment_ID, p.student_id, p.amount, p.aenalty, p.status,
                      s.first_name as student_name, s.room_id,
                      r.room_type, r.ac_type,
                      a.first_name as approved_by
               FROM Payment_Table p
               LEFT JOIN Student_Table s ON p.student_id = s.Student_ID
               LEFT JOIN Room_Table r ON s.room_id = r.room_id
               LEFT JOIN Admin_Table a ON p.admin_id = a.Admin_ID
               WHERE r.warden_id = :warden_id";

$params[':warden_id'] = $warden['Warden_ID'];

// Apply filters
if (!empty($filter_status)) {
    $where_conditions[] = "p.status = :status";
    $params[':status'] = $filter_status;
}

if (!empty($filter_student)) {
    $where_conditions[] = "(s.first_name LIKE :student_name OR s.Student_ID LIKE :student_id)";
    $params[':student_name'] = "%$filter_student%";
    $params[':student_id'] = "%$filter_student%";
}

if (!empty($filter_date_from)) {
    $where_conditions[] = "DATE(p.created_at) >= :date_from";
    $params[':date_from'] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "DATE(p.created_at) <= :date_to";
    $params[':date_to'] = $filter_date_to;
}

if (!empty($filter_amount_min)) {
    $where_conditions[] = "p.amount >= :amount_min";
    $params[':amount_min'] = $filter_amount_min;
}

if (!empty($filter_amount_max)) {
    $where_conditions[] = "p.amount <= :amount_max";
    $params[':amount_max'] = $filter_amount_max;
}

// Combine conditions
if (!empty($where_conditions)) {
    $base_query .= " AND " . implode(" AND ", $where_conditions);
}

$base_query .= " ORDER BY p.Payment_ID DESC";

// Execute query
$payments_stmt = $conn->prepare($base_query);
foreach ($params as $key => $value) {
    $payments_stmt->bindValue($key, $value);
}
$payments_stmt->execute();
$payments = $payments_stmt->fetchAll();

// Calculate statistics using exact field names
$stats_query = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN p.status = 'paid' THEN 1 ELSE 0 END) as paid_payments,
                    SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                    SUM(CASE WHEN p.status = 'overdue' THEN 1 ELSE 0 END) as overdue_payments,
                    SUM(p.amount) as total_amount,
                    SUM(CASE WHEN p.status = 'paid' THEN p.Amount ELSE 0 END) as paid_amount,
                    SUM(p.Penalty) as total_penalties
                FROM Payment_Table p
                LEFT JOIN Student_Table s ON p.student_id = s.Student_ID
                LEFT JOIN Room_Table r ON s.Room_ID = r.Room_ID
                WHERE r.Warden_ID = :warden_id";

// Apply same filters to stats
if (!empty($where_conditions)) {
    $stats_query .= " AND " . implode(" AND ", $where_conditions);
}

$stats_stmt = $conn->prepare($stats_query);
foreach ($params as $key => $value) {
    $stats_stmt->bindValue($key, $value);
}
$stats_stmt->execute();
$payment_stats = $stats_stmt->fetch();

// Get students for filter dropdown
$students_query = "SELECT DISTINCT s.Student_ID, s.Name 
                   FROM Student_Table s
                   LEFT JOIN Room_Table r ON s.Room_ID = r.Room_ID
                   WHERE r.Warden_ID = :warden_id
                   ORDER BY s.Name";
$students_stmt = $conn->prepare($students_query);
$students_stmt->bindParam(':warden_id', $warden['Warden_ID']);
$students_stmt->execute();
$students = $students_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reports - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .filter-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .export-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .payment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .amount-cell {
            text-align: right;
            font-weight: 600;
        }
        
        .penalty-cell {
            text-align: right;
            color: #dc3545;
            font-weight: 500;
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
            <h1 class="dashboard-title">Payment Reports</h1>
            <p class="dashboard-subtitle">Monitor and analyze payment transactions</p>
        </div>

        <!-- Payment Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $payment_stats['total_payments']; ?></div>
                <div class="stat-label">Total Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo $payment_stats['paid_payments']; ?></div>
                <div class="stat-label">Paid</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo $payment_stats['pending_payments']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-danger"><?php echo $payment_stats['overdue_payments']; ?></div>
                <div class="stat-label">Overdue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-info">₹<?php echo number_format($payment_stats['total_amount'], 2); ?></div>
                <div class="stat-label">Total Amount</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success">₹<?php echo number_format($payment_stats['paid_amount'], 2); ?></div>
                <div class="stat-label">Collected</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3><i class="fas fa-filter"></i> Filter Payments</h3>
            <form method="GET" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="overdue" <?php echo $filter_status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Student</label>
                        <select name="student" class="form-control">
                            <option value="">All Students</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['Student_ID']); ?>" 
                                        <?php echo $filter_student === $student['Student_ID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['Name'] . ' (' . $student['Student_ID'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="form-label">Min Amount (₹)</label>
                        <input type="number" name="amount_min" class="form-control" step="0.01" 
                               value="<?php echo htmlspecialchars($filter_amount_min); ?>">
                    </div>
                    <div class="filter-group">
                        <label class="form-label">Max Amount (₹)</label>
                        <input type="number" name="amount_max" class="form-control" step="0.01" 
                               value="<?php echo htmlspecialchars($filter_amount_max); ?>">
                    </div>
                    <div class="filter-group">
                        <label class="form-label">&nbsp;</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="payment_reports.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <button class="btn btn-success" onclick="exportToCSV()">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button class="btn btn-info" onclick="printReport()">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>

        <!-- Payments Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon primary">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-title">Payment Records</div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Student</th>
                                <th>Room</th>
                                <th>Amount</th>
                                <th>Penalty</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Approved By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No payment records found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($payment['Payment_ID']); ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($payment['student_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($payment['Student_ID']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($payment['Room_ID']): ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($payment['Room_ID']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($payment['Room_Type'] . ' - ' . $payment['AC_Type']); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No Room</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="amount-cell">₹<?php echo number_format($payment['Amount'], 2); ?></td>
                                    <td class="penalty-cell">
                                        <?php if ($payment['Penalty'] > 0): ?>
                                            ₹<?php echo number_format($payment['Penalty'], 2); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="amount-cell">
                                        <strong>₹<?php echo number_format($payment['Amount'] + $payment['Penalty'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="payment-status status-<?php echo $payment['Status']; ?>">
                                            <?php echo ucfirst($payment['Status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($payment['approved_by']): ?>
                                            <?php echo htmlspecialchars($payment['approved_by']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewPaymentDetails('<?php echo $payment['Payment_ID']; ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($payment['Status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-success" onclick="markAsPaid('<?php echo $payment['Payment_ID']; ?>')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function exportToCSV() {
            const table = document.getElementById('paymentsTable');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cols = row.querySelectorAll('td, th');
                let csvRow = [];
                
                for (let j = 0; j < cols.length - 1; j++) { // Exclude actions column
                    let cellText = cols[j].innerText.replace(/"/g, '""');
                    csvRow.push('"' + cellText + '"');
                }
                csv.push(csvRow.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'payment_report_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        function printReport() {
            const printContent = document.querySelector('.dashboard-container').innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <html>
                <head>
                    <title>Payment Report</title>
                    <link rel="stylesheet" href="../css/main-style.css">
                    <style>
                        @media print {
                            .export-buttons, .filter-section, .navbar { display: none; }
                            .btn { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <h1>Payment Report</h1>
                    <p>Generated on: ${new Date().toLocaleDateString()}</p>
                    ${printContent}
                </body>
                </html>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        }

        function viewPaymentDetails(paymentId) {
            // Implement payment details modal
            alert('Payment details for ID: ' + paymentId);
        }

        function markAsPaid(paymentId) {
            if (confirm('Mark this payment as paid?')) {
                // Implement mark as paid functionality
                window.location.href = `update_payment_status.php?payment_id=${paymentId}&status=paid`;
            }
        }

        // Auto-submit form on filter change
        document.querySelectorAll('#filterForm select, #filterForm input').forEach(element => {
            element.addEventListener('change', function() {
                if (this.type !== 'submit') {
                    // Auto-submit after a short delay for better UX
                    setTimeout(() => {
                        document.getElementById('filterForm').submit();
                    }, 300);
                }
            });
        });
    </script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
