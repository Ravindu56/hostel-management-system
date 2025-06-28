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

// Handle complaint status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_complaint'])) {
    $complaint_id = $_POST['complaint_id'];
    $status = $_POST['status'];
    $resolution_description = $_POST['resolution_description'];
    
    $update_query = "UPDATE Complaint_Table SET 
                     status = :status, 
                     resolution_description = :resolution_description,
                     resolution_date = CASE WHEN :status = 'Resolved' THEN NOW() ELSE NULL END,
                     assigned_to = :warden_id
                     WHERE Complaint_ID = :complaint_id";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':status', $status);
    $update_stmt->bindParam(':resolution_description', $resolution_description);
    $update_stmt->bindParam(':warden_id', $warden['Warden_ID']);
    $update_stmt->bindParam(':complaint_id', $complaint_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Complaint updated successfully!";
    } else {
        $error_message = "Failed to update complaint.";
    }
}

// Fetch all complaints with filters
$filter_status = $_GET['status'] ?? '';
$filter_category = $_GET['category'] ?? '';

$complaints_query = "SELECT c.*, s.first_name, s.last_name, s.room_id, r.room_number 
                     FROM Complaint_Table c 
                     JOIN Student_Table s ON c.student_id = s.Student_ID 
                     LEFT JOIN Room_Table r ON s.room_id = r.Room_ID 
                     WHERE 1=1";

if ($filter_status) {
    $complaints_query .= " AND c.status = :status";
}
if ($filter_category) {
    $complaints_query .= " AND c.category = :category";
}

$complaints_query .= " ORDER BY 
                       CASE c.priority 
                           WHEN 'urgent' THEN 1 
                           WHEN 'high' THEN 2 
                           WHEN 'medium' THEN 3 
                           WHEN 'low' THEN 4 
                       END, c.created_at DESC";

$complaints_stmt = $conn->prepare($complaints_query);
if ($filter_status) {
    $complaints_stmt->bindParam(':status', $filter_status);
}
if ($filter_category) {
    $complaints_stmt->bindParam(':category', $filter_category);
}
$complaints_stmt->execute();
$complaints = $complaints_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation (same as dashboard) -->
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
            <h1 class="dashboard-title">Complaint Management</h1>
            <p class="dashboard-subtitle">Process and resolve student complaints</p>
        </div>

        <!-- Filters -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-4">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="In_Progress" <?php echo $filter_status === 'In_Progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Resolved" <?php echo $filter_status === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label for="category" class="form-label">Filter by Category</label>
                        <select name="category" id="category" class="form-control">
                            <option value="">All Categories</option>
                            <option value="Maintenance" <?php echo $filter_category === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="Plumbing" <?php echo $filter_category === 'Plumbing' ? 'selected' : ''; ?>>Plumbing</option>
                            <option value="Electrical" <?php echo $filter_category === 'Electrical' ? 'selected' : ''; ?>>Electrical</option>
                            <option value="Cleaning" <?php echo $filter_category === 'Cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                        </select>
                    </div>
                    <div class="col-4" style="display: flex; align-items: end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="manage_complaints.php" class="btn btn-secondary" style="margin-left: 1rem;">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Complaints Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon warning">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="card-title">All Complaints (<?php echo count($complaints); ?>)</div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Room</th>
                                <th>Category</th>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $complaint): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($complaint['Complaint_ID']); ?></td>
                                <td><?php echo htmlspecialchars($complaint['first_name'] . ' ' . $complaint['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($complaint['room_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($complaint['category']); ?></td>
                                <td><?php echo htmlspecialchars(substr($complaint['title'], 0, 30) . '...'); ?></td>
                                <td><span class="badge badge-<?php echo $complaint['priority']; ?>"><?php echo ucfirst($complaint['priority']); ?></span></td>
                                <td><span class="badge badge-<?php echo strtolower(str_replace('_', '-', $complaint['status'])); ?>"><?php echo $complaint['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($complaint['complaint_date'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary btn-tb" onclick="viewComplaint('<?php echo $complaint['Complaint_ID']; ?>')">
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

    <!-- Complaint Detail Modal -->
    <div id="complaintModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Complaint Details</h2>
                <button class="modal-close" onclick="closeModal('complaintModal')">&times;</button>
            </div>
            <div class="modal-body" id="complaintDetails">
                <!-- Content will be loaded via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function viewComplaint(complaintId) {
            // Find complaint data
            const complaints = <?php echo json_encode($complaints); ?>;
            const complaint = complaints.find(c => c.Complaint_ID === complaintId);
            
            if (complaint) {
                document.getElementById('complaintDetails').innerHTML = `
                    <div class="row">
                        <div class="col-6">
                            <strong>Student:</strong> ${complaint.first_name} ${complaint.last_name}<br>
                            <strong>Room:</strong> ${complaint.room_number || 'N/A'}<br>
                            <strong>Category:</strong> ${complaint.category}<br>
                            <strong>Priority:</strong> <span class="badge badge-${complaint.priority}">${complaint.priority}</span>
                        </div>
                        <div class="col-6">
                            <strong>Status:</strong> <span class="badge badge-${complaint.status.toLowerCase().replace('_', '-')}">${complaint.status}</span><br>
                            <strong>Date:</strong> ${new Date(complaint.complaint_date).toLocaleDateString()}<br>
                            <strong>ID:</strong> ${complaint.Complaint_ID}
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <strong>Title:</strong>
                        <p>${complaint.title}</p>
                    </div>
                    <div class="form-group">
                        <strong>Description:</strong>
                        <p>${complaint.description}</p>
                    </div>
                    <hr>
                    <form method="POST">
                        <input type="hidden" name="complaint_id" value="${complaint.Complaint_ID}">
                        <div class="form-group">
                            <label for="status" class="form-label">Update Status</label>
                            <select name="status" class="form-control" required>
                                <option value="Pending" ${complaint.status === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="In_Progress" ${complaint.status === 'In_Progress' ? 'selected' : ''}>In Progress</option>
                                <option value="Resolved" ${complaint.status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                                <option value="Closed" ${complaint.status === 'Closed' ? 'selected' : ''}>Closed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="resolution_description" class="form-label">Resolution Notes</label>
                            <textarea name="resolution_description" class="form-control" rows="3" placeholder="Add resolution details...">${complaint.resolution_description || ''}</textarea>
                        </div>
                        <button type="submit" name="update_complaint" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Complaint
                        </button>
                    </form>
                `;
                openModal('complaintModal');
            }
        }
    </script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
