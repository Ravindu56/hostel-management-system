<?php
// session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('admin');

$database = new Database();
$conn = $database->getConnection();

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Generate report based on type
    switch ($report_type) {
        case 'occupancy':
            $report_data = generateOccupancyReport($conn, $start_date, $end_date);
            break;
        case 'financial':
            $report_data = generateFinancialReport($conn, $start_date, $end_date);
            break;
        case 'complaints':
            $report_data = generateComplaintsReport($conn, $start_date, $end_date);
            break;
        case 'visitors':
            $report_data = generateVisitorsReport($conn, $start_date, $end_date);
            break;
    }
}

function generateOccupancyReport($conn, $start_date, $end_date) {
    $query = "SELECT 
                r.room_number, r.room_type, r.ac_type, r.capacity, r.occupied_count,
                ROUND((r.occupied_count / r.capacity) * 100, 2) as occupancy_rate,
                GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') as occupants
              FROM Room_Table r
              LEFT JOIN Student_Table s ON r.Room_ID = s.room_id 
              WHERE s.created_at BETWEEN :start_date AND :end_date OR s.created_at IS NULL
              GROUP BY r.Room_ID
              ORDER BY r.room_number";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    return $stmt->fetchAll();
}

function generateFinancialReport($conn, $start_date, $end_date) {
    $query = "SELECT 
                p.payment_type,
                COUNT(*) as total_transactions,
                SUM(p.amount) as total_amount,
                SUM(p.penalty) as total_penalties,
                SUM(p.total_amount) as grand_total,
                SUM(CASE WHEN p.status = 'Paid' THEN p.total_amount ELSE 0 END) as collected_amount,
                SUM(CASE WHEN p.status = 'Pending' THEN p.total_amount ELSE 0 END) as pending_amount
              FROM Payment_Table p
              WHERE p.created_at BETWEEN :start_date AND :end_date
              GROUP BY p.payment_type";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    return $stmt->fetchAll();
}

function generateComplaintsReport($conn, $start_date, $end_date) {
    $query = "SELECT 
                c.category,
                COUNT(*) as total_complaints,
                SUM(CASE WHEN c.status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN c.status = 'Resolved' THEN 1 ELSE 0 END) as resolved_count,
                AVG(CASE WHEN c.resolution_date IS NOT NULL 
                    THEN DATEDIFF(c.resolution_date, c.complaint_date) 
                    ELSE NULL END) as avg_resolution_days,
                SUM(CASE WHEN c.priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count
              FROM Complaint_Table c
              WHERE c.complaint_date BETWEEN :start_date AND :end_date
              GROUP BY c.category";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    return $stmt->fetchAll();
}

function generateVisitorsReport($conn, $start_date, $end_date) {
    $query = "SELECT 
                DATE(v.visit_date) as visit_date,
                COUNT(*) as total_visitors,
                COUNT(DISTINCT v.student_id) as unique_students,
                SUM(CASE WHEN v.visitor_status = 'checked_in' THEN 1 ELSE 0 END) as currently_in,
                AVG(TIME_TO_SEC(TIMEDIFF(v.time_out, v.time_in))/3600) as avg_visit_hours
              FROM Visitor_Table v
              WHERE v.visit_date BETWEEN :start_date AND :end_date
              GROUP BY DATE(v.visit_date)
              ORDER BY visit_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get summary statistics
$summary_stats = [
    'total_students' => $conn->query("SELECT COUNT(*) as count FROM Student_Table WHERE student_status = 'active'")->fetch()['count'],
    'total_revenue' => $conn->query("SELECT SUM(total_amount) as total FROM Payment_Table WHERE status = 'Paid'")->fetch()['total'] ?? 0,
    'pending_complaints' => $conn->query("SELECT COUNT(*) as count FROM Complaint_Table WHERE status IN ('Pending', 'In_Progress')")->fetch()['count'],
    'occupancy_rate' => $conn->query("SELECT ROUND((SUM(occupied_count) / SUM(capacity)) * 100, 2) as rate FROM Room_Table")->fetch()['rate']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <h1 class="dashboard-title">System Reports & Analytics</h1>
            <p class="dashboard-subtitle">Generate comprehensive reports and analyze hostel operations</p>
        </div>

        <!-- Summary Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $summary_stats['total_students']; ?></div>
                <div class="stat-label">Active Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success">₹<?php echo number_format($summary_stats['total_revenue'], 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo $summary_stats['pending_complaints']; ?></div>
                <div class="stat-label">Pending Issues</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-info"><?php echo $summary_stats['occupancy_rate']; ?>%</div>
                <div class="stat-label">Occupancy Rate</div>
            </div>
        </div>

        <!-- Report Generation Form -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div class="card-icon primary">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="card-title">Generate Custom Report</div>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-3">
                            <div class="form-group">
                                <label for="report_type" class="form-label">Report Type</label>
                                <select name="report_type" id="report_type" class="form-control" required>
                                    <option value="">Select Report Type</option>
                                    <option value="occupancy">Room Occupancy Report</option>
                                    <option value="financial">Financial Summary Report</option>
                                    <option value="complaints">Complaints Analysis Report</option>
                                    <option value="visitors">Visitor Activity Report</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-3" style="display: flex; align-items: end;">
                            <button type="submit" name="generate_report" class="btn btn-secondary ">
                                <i class="fas fa-chart-line"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Report Buttons -->
        <div class="row" style="margin-bottom: 2rem;">
            <div class="col-3">
                <button class="btn btn-info btn-block" onclick="generateQuickReport('monthly_summary')">
                    <i class="fas fa-calendar-alt"></i> Monthly Summary
                </button>
            </div>
            <div class="col-3">
                <button class="btn btn-success btn-block" onclick="generateQuickReport('revenue_analysis')">
                    <i class="fas fa-money-bill-trend-up"></i> Revenue Analysis
                </button>
            </div>
            <div class="col-3">
                <button class="btn btn-warning btn-block" onclick="generateQuickReport('maintenance_report')">
                    <i class="fas fa-tools"></i> Maintenance Report
                </button>
            </div>
            <div class="col-3">
                <button class="btn btn-primary btn-block" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </div>
        </div>

        <!-- Report Results -->
        <?php if (isset($report_data) && !empty($report_data)): ?>
        <div class="card">
            <div class="card-header">
                <div class="card-icon success">
                    <i class="fas fa-table"></i>
                </div>
                <div class="card-title">Report Results - <?php echo ucfirst(str_replace('_', ' ', $report_type)); ?></div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <?php if ($report_type === 'occupancy'): ?>
                                    <th>Room Number</th>
                                    <th>Type</th>
                                    <th>AC Type</th>
                                    <th>Capacity</th>
                                    <th>Occupied</th>
                                    <th>Occupancy Rate</th>
                                    <th>Current Occupants</th>
                                <?php elseif ($report_type === 'financial'): ?>
                                    <th>Payment Type</th>
                                    <th>Transactions</th>
                                    <th>Base Amount</th>
                                    <th>Penalties</th>
                                    <th>Total Amount</th>
                                    <th>Collected</th>
                                    <th>Pending</th>
                                <?php elseif ($report_type === 'complaints'): ?>
                                    <th>Category</th>
                                    <th>Total</th>
                                    <th>Pending</th>
                                    <th>Resolved</th>
                                    <th>Avg Resolution Days</th>
                                    <th>Urgent</th>
                                <?php elseif ($report_type === 'visitors'): ?>
                                    <th>Date</th>
                                    <th>Total Visitors</th>
                                    <th>Unique Students</th>
                                    <th>Currently In</th>
                                    <th>Avg Visit Hours</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <?php if ($report_type === 'occupancy'): ?>
                                    <td><strong><?php echo htmlspecialchars($row['room_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ac_type']); ?></td>
                                    <td><?php echo $row['capacity']; ?></td>
                                    <td><?php echo $row['occupied_count']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $row['occupancy_rate'] >= 100 ? 'danger' : ($row['occupancy_rate'] >= 80 ? 'warning' : 'success'); ?>">
                                            <?php echo $row['occupancy_rate']; ?>%
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['occupants'] ?: 'Empty'); ?></td>
                                <?php elseif ($report_type === 'financial'): ?>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $row['payment_type'])); ?></td>
                                    <td><?php echo $row['total_transactions']; ?></td>
                                    <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td>₹<?php echo number_format($row['total_penalties'], 2); ?></td>
                                    <td><strong>₹<?php echo number_format($row['grand_total'], 2); ?></strong></td>
                                    <td class="text-success">₹<?php echo number_format($row['collected_amount'], 2); ?></td>
                                    <td class="text-warning">₹<?php echo number_format($row['pending_amount'], 2); ?></td>
                                <?php elseif ($report_type === 'complaints'): ?>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo $row['total_complaints']; ?></td>
                                    <td class="text-warning"><?php echo $row['pending_count']; ?></td>
                                    <td class="text-success"><?php echo $row['resolved_count']; ?></td>
                                    <td><?php echo round($row['avg_resolution_days'], 1); ?> days</td>
                                    <td class="text-danger"><?php echo $row['urgent_count']; ?></td>
                                <?php elseif ($report_type === 'visitors'): ?>
                                    <td><?php echo date('M d, Y', strtotime($row['visit_date'])); ?></td>
                                    <td><?php echo $row['total_visitors']; ?></td>
                                    <td><?php echo $row['unique_students']; ?></td>
                                    <td><?php echo $row['currently_in']; ?></td>
                                    <td><?php echo round($row['avg_visit_hours'], 1); ?>h</td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 2rem; text-align: center;">
                    <button class="btn btn-success" onclick="exportReport('<?php echo $report_type; ?>')">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                    <button class="btn btn-primary" onclick="printReport()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Set default date range (last 30 days)
        document.addEventListener('DOMContentLoaded', function() {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            
            document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
            document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
        });

        function generateQuickReport(type) {
            const endDate = new Date().toISOString().split('T')[0];
            const startDate = new Date();
            
            switch(type) {
                case 'monthly_summary':
                    startDate.setMonth(startDate.getMonth() - 1);
                    break;
                case 'revenue_analysis':
                    startDate.setMonth(startDate.getMonth() - 3);
                    break;
                case 'maintenance_report':
                    startDate.setDate(startDate.getDate() - 7);
                    break;
            }
            
            document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
            document.getElementById('end_date').value = endDate;
            document.getElementById('report_type').value = type === 'maintenance_report' ? 'complaints' : 'financial';
        }

        function exportReport(reportType) {
            // Implementation for exporting report data
            alert('Export functionality for ' + reportType + ' report');
        }

        function printReport() {
            window.print();
        }

        function exportToExcel() {
            // Implementation for Excel export
            alert('Excel export functionality');
        }
    </script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
