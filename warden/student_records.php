<?php
// session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('warden');

$database = new Database();
$conn = $database->getConnection();

// Search functionality
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_room = $_GET['room'] ?? '';

$students_query = "SELECT s.*, r.room_number, r.room_type, r.ac_type,
                          (SELECT SUM(total_amount) FROM Payment_Table p WHERE p.student_id = s.Student_ID AND p.status = 'Pending') as outstanding_dues,
                          (SELECT COUNT(*) FROM Complaint_Table c WHERE c.student_id = s.Student_ID AND c.status = 'Pending') as pending_complaints
                   FROM Student_Table s 
                   LEFT JOIN Room_Table r ON s.room_id = r.Room_ID 
                   WHERE 1=1";

$params = [];

if ($search) {
    $students_query .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.student_roll_number LIKE :search OR s.email LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($filter_status) {
    $students_query .= " AND s.student_status = :status";
    $params[':status'] = $filter_status;
}

if ($filter_room) {
    $students_query .= " AND s.room_id = :room";
    $params[':room'] = $filter_room;
}

$students_query .= " ORDER BY s.first_name, s.last_name";

$students_stmt = $conn->prepare($students_query);
foreach ($params as $key => $value) {
    $students_stmt->bindValue($key, $value);
}
$students_stmt->execute();
$students = $students_stmt->fetchAll();

// Get all rooms for filter
$rooms_query = "SELECT Room_ID, room_number FROM Room_Table ORDER BY room_number";
$rooms_stmt = $conn->prepare($rooms_query);
$rooms_stmt->execute();
$all_rooms = $rooms_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records - HMS</title>
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
            <h1 class="dashboard-title">Student Records</h1>
            <p class="dashboard-subtitle">Manage student information and accommodation details</p>
        </div>

        <!-- Search and Filters -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-4">
                        <label for="search" class="form-label">Search Students</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               placeholder="Name, Roll Number, or Email" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="graduated" <?php echo $filter_status === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                        </select>
                    </div>
                    <div class="col-3">
                        <label for="room" class="form-label">Room</label>
                        <select name="room" id="room" class="form-control">
                            <option value="">All Rooms</option>
                            <?php foreach ($all_rooms as $room): ?>
                                <option value="<?php echo $room['Room_ID']; ?>" <?php echo $filter_room === $room['Room_ID'] ? 'selected' : ''; ?>>
                                    Room <?php echo $room['room_number']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-2" style="display: flex; align-items: end; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="student_records.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Students Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-title">Student Records (<?php echo count($students); ?>)</div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Roll Number</th>
                                <th>Room</th>
                                <th>Course</th>
                                <th>Contact</th>
                                <th>Outstanding</th>
                                <th>Complaints</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['Student_ID']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($student['student_roll_number']); ?></td>
                                <td>
                                    <?php if ($student['room_number']): ?>
                                        <span class="badge badge-primary">
                                            Room <?php echo $student['room_number']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['course']); ?></td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars($student['mobile_number']); ?><br>
                                        <?php echo htmlspecialchars($student['email']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($student['outstanding_dues'] > 0): ?>
                                        <span class="badge badge-danger">
                                            ₹<?php echo number_format($student['outstanding_dues'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Clear</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($student['pending_complaints'] > 0): ?>
                                        <span class="badge badge-warning">
                                            <?php echo $student['pending_complaints']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $student['student_status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($student['student_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewStudent('<?php echo $student['Student_ID']; ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="viewHistory('<?php echo $student['Student_ID']; ?>')">
                                        <i class="fas fa-history"></i>
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

    <!-- Student Detail Modal -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Student Details</h2>
                <button class="modal-close" onclick="closeModal('studentModal')">&times;</button>
            </div>
            <div class="modal-body" id="studentDetails">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        const students = <?php echo json_encode($students); ?>;

        function viewStudent(studentId) {
            const student = students.find(s => s.Student_ID === studentId);
            if (student) {
                document.getElementById('studentDetails').innerHTML = `
                    <div class="row">
                        <div class="col-6">
                            <h4>Personal Information</h4>
                            <p><strong>Name:</strong> ${student.first_name} ${student.last_name}</p>
                            <p><strong>Roll Number:</strong> ${student.student_roll_number}</p>
                            <p><strong>Email:</strong> ${student.email}</p>
                            <p><strong>Mobile:</strong> ${student.mobile_number}</p>
                            <p><strong>Course:</strong> ${student.course}</p>
                            <p><strong>Department:</strong> ${student.department}</p>
                            <p><strong>Year:</strong> ${student.year_of_study}</p>
                            <p><strong>Gender:</strong> ${student.gender}</p>
                        </div>
                        <div class="col-6">
                            <h4>Accommodation Details</h4>
                            <p><strong>Room:</strong> ${student.room_number ? 'Room ' + student.room_number : 'Not Assigned'}</p>
                            <p><strong>Room Type:</strong> ${student.room_type || 'N/A'}</p>
                            <p><strong>AC Type:</strong> ${student.ac_type || 'N/A'}</p>
                            <p><strong>Admission Date:</strong> ${student.admission_date ? new Date(student.admission_date).toLocaleDateString() : 'N/A'}</p>
                            <p><strong>Duration:</strong> ${student.duration_of_stay} months</p>
                            <p><strong>Status:</strong> <span class="badge badge-${student.student_status === 'active' ? 'success' : 'secondary'}">${student.student_status}</span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <h4>Emergency Contacts</h4>
                            <p><strong>Parent Mobile:</strong> ${student.parent_mobile || 'N/A'}</p>
                            <p><strong>Emergency Contact:</strong> ${student.emergency_contact || 'N/A'}</p>
                            <p><strong>Local Guardian:</strong> ${student.local_guardian_name || 'N/A'}</p>
                            <p><strong>Guardian Mobile:</strong> ${student.local_guardian_mobile || 'N/A'}</p>
                        </div>
                        <div class="col-6">
                            <h4>Financial Status</h4>
                            <p><strong>Outstanding Dues:</strong> 
                                ${student.outstanding_dues > 0 ? 
                                    '<span class="badge badge-danger">₹' + parseFloat(student.outstanding_dues).toLocaleString() + '</span>' : 
                                    '<span class="badge badge-success">Clear</span>'}
                            </p>
                            <p><strong>Pending Complaints:</strong> 
                                <span class="badge badge-${student.pending_complaints > 0 ? 'warning' : 'success'}">
                                    ${student.pending_complaints}
                                </span>
                            </p>
                        </div>
                    </div>
                `;
                openModal('studentModal');
            }
        }

        function viewHistory(studentId) {
            // Redirect to a detailed history page
            window.open(`student_history.php?id=${studentId}`, '_blank');
        }
    </script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
