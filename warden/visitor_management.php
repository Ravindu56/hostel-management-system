<?php
// session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('warden');

$database = new Database();
$conn = $database->getConnection();

$user_info = getUserInfo();
$user_id = $user_info['user_id'];

// Get warden details
$warden_query = "SELECT Warden_ID FROM Warden_Table WHERE user_id = :user_id";
$warden_stmt = $conn->prepare($warden_query);
$warden_stmt->bindParam(':user_id', $user_id);
$warden_stmt->execute();
$warden = $warden_stmt->fetch();

// Handle visitor checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_visitor'])) {
    $visitor_id = $_POST['visitor_id'];
    $time_out = date('H:i:s');
    
    $checkout_query = "UPDATE Visitor_Table SET 
                       time_out = :time_out, 
                       visitor_status = 'checked_out',
                       approved_by = :warden_id
                       WHERE Visitor_ID = :visitor_id";
    
    $checkout_stmt = $conn->prepare($checkout_query);
    $checkout_stmt->bindParam(':time_out', $time_out);
    $checkout_stmt->bindParam(':warden_id', $warden['Warden_ID']);
    $checkout_stmt->bindParam(':visitor_id', $visitor_id);
    
    if ($checkout_stmt->execute()) {
        $success_message = "Visitor checked out successfully!";
    } else {
        $error_message = "Failed to check out visitor.";
    }
}

// Fetch visitors with filters
$filter_status = $_GET['status'] ?? 'checked_in';
$filter_date = $_GET['date'] ?? '';

$visitors_query = "SELECT v.*, s.first_name, s.last_name, s.room_id, r.room_number 
                   FROM Visitor_Table v 
                   JOIN Student_Table s ON v.student_id = s.Student_ID 
                   LEFT JOIN Room_Table r ON s.room_id = r.Room_ID 
                   WHERE 1=1";

$params = [];

if ($filter_status) {
    $visitors_query .= " AND v.visitor_status = :status";
    $params[':status'] = $filter_status;
}

if ($filter_date) {
    $visitors_query .= " AND v.visit_date = :date";
    $params[':date'] = $filter_date;
}

$visitors_query .= " ORDER BY v.visit_date DESC, v.time_in DESC";

$visitors_stmt = $conn->prepare($visitors_query);
foreach ($params as $key => $value) {
    $visitors_stmt->bindValue($key, $value);
}
$visitors_stmt->execute();
$visitors = $visitors_stmt->fetchAll();

// Visitor statistics
$stats_query = "SELECT 
                    COUNT(*) as total_today,
                    SUM(CASE WHEN visitor_status = 'checked_in' THEN 1 ELSE 0 END) as currently_in,
                    SUM(CASE WHEN visitor_status = 'checked_out' THEN 1 ELSE 0 END) as checked_out_today
                FROM Visitor_Table 
                WHERE visit_date = CURDATE()";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$visitor_stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Management - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            <h1 class="dashboard-title">Visitor Management</h1>
            <p class="dashboard-subtitle">Monitor and manage hostel visitors</p>
        </div>

        <!-- Visitor Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $visitor_stats['total_today']; ?></div>
                <div class="stat-label">Total Visitors Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo $visitor_stats['currently_in']; ?></div>
                <div class="stat-label">Currently In Hostel</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-info"><?php echo $visitor_stats['checked_out_today']; ?></div>
                <div class="stat-label">Checked Out Today</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-4">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="checked_in" <?php echo $filter_status === 'checked_in' ? 'selected' : ''; ?>>Currently In</option>
                            <option value="checked_out" <?php echo $filter_status === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                            <option value="overstayed" <?php echo $filter_status === 'overstayed' ? 'selected' : ''; ?>>Overstayed</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label for="date" class="form-label">Filter by Date</label>
                        <input type="date" name="date" id="date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    <div class="col-4" style="display: flex; align-items: end; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="visitor_management.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Visitors Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon success">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-title">Visitor Log (<?php echo count($visitors); ?>)</div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Visitor ID</th>
                                <th>Visitor Name</th>
                                <th>Student</th>
                                <th>Room</th>
                                <th>Relationship</th>
                                <th>Visit Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitors as $visitor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($visitor['Visitor_ID']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($visitor['visitor_first_name'] . ' ' . $visitor['visitor_last_name']); ?></strong>
                                    <?php if ($visitor['visitor_mobile']): ?>
                                        <br><small><?php echo htmlspecialchars($visitor['visitor_mobile']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']); ?></td>
                                <td>
                                    <?php if ($visitor['room_number']): ?>
                                        <span class="badge badge-primary">Room <?php echo $visitor['room_number']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">No Room</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($visitor['relationship_with_student']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($visitor['visit_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($visitor['time_in'])); ?></td>
                                <td>
                                    <?php if ($visitor['time_out']): ?>
                                        <?php echo date('h:i A', strtotime($visitor['time_out'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Still In</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $visitor['visitor_status'] == 'checked_in' ? 'success' : 
                                            ($visitor['visitor_status'] == 'checked_out' ? 'primary' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $visitor['visitor_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($visitor['visitor_status'] == 'checked_in'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="visitor_id" value="<?php echo $visitor['Visitor_ID']; ?>">
                                            <button type="submit" name="checkout_visitor" class="btn btn-sm btn-warning btn-tb" 
                                                    onclick="return confirm('Check out this visitor?')">
                                                <i class="fas fa-sign-out-alt"></i> Check Out
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-primary btn-tb" onclick="viewVisitor('<?php echo $visitor['Visitor_ID']; ?>')">
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

    <!-- Visitor Detail Modal -->
    <div id="visitorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Visitor Details</h2>
                <button class="modal-close" onclick="closeModal('visitorModal')">&times;</button>
            </div>
            <div class="modal-body" id="visitorDetails">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        const visitors = <?php echo json_encode($visitors); ?>;

        function viewVisitor(visitorId) {
            const visitor = visitors.find(v => v.Visitor_ID === visitorId);
            if (visitor) {
                const duration = visitor.time_out ? 
                    calculateDuration(visitor.time_in, visitor.time_out) : 
                    calculateDuration(visitor.time_in, new Date().toTimeString().slice(0, 8));

                document.getElementById('visitorDetails').innerHTML = `
                    <div class="row">
                        <div class="col-6">
                            <h4>Visitor Information</h4>
                            <p><strong>Name:</strong> ${visitor.visitor_first_name} ${visitor.visitor_last_name}</p>
                            <p><strong>Mobile:</strong> ${visitor.visitor_mobile || 'N/A'}</p>
                            <p><strong>Email:</strong> ${visitor.visitor_email || 'N/A'}</p>
                            <p><strong>Relationship:</strong> ${visitor.relationship_with_student}</p>
                            <p><strong>ID Proof:</strong> ${visitor.id_proof_type || 'N/A'}</p>
                            <p><strong>ID Number:</strong> ${visitor.id_proof_number || 'N/A'}</p>
                        </div>
                        <div class="col-6">
                            <h4>Visit Details</h4>
                            <p><strong>Student:</strong> ${visitor.first_name} ${visitor.last_name}</p>
                            <p><strong>Room:</strong> ${visitor.room_number ? 'Room ' + visitor.room_number : 'No Room'}</p>
                            <p><strong>Visit Date:</strong> ${new Date(visitor.visit_date).toLocaleDateString()}</p>
                            <p><strong>Time In:</strong> ${new Date('1970-01-01T' + visitor.time_in + 'Z').toLocaleTimeString()}</p>
                            <p><strong>Time Out:</strong> ${visitor.time_out ? new Date('1970-01-01T' + visitor.time_out + 'Z').toLocaleTimeString() : 'Still In'}</p>
                            <p><strong>Duration:</strong> ${duration}</p>
                            <p><strong>Status:</strong> <span class="badge badge-${visitor.visitor_status === 'checked_in' ? 'success' : 'primary'}">${visitor.visitor_status.replace('_', ' ')}</span></p>
                        </div>
                    </div>
                    ${visitor.purpose_of_visit ? `
                        <hr>
                        <h4>Purpose of Visit</h4>
                        <p>${visitor.purpose_of_visit}</p>
                    ` : ''}
                    ${visitor.security_remarks ? `
                        <hr>
                        <h4>Security Remarks</h4>
                        <p>${visitor.security_remarks}</p>
                    ` : ''}
                `;
                openModal('visitorModal');
            }
        }

        function calculateDuration(timeIn, timeOut) {
            const start = new Date('1970-01-01T' + timeIn + 'Z');
            const end = new Date('1970-01-01T' + timeOut + 'Z');
            const diff = Math.abs(end - start);
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            return `${hours}h ${minutes}m`;
        }

        // Set today's date as default
        document.getElementById('date').value = new Date().toISOString().split('T')[0];
    </script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
