<?php
// session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('admin');

$database = new Database();
$conn = $database->getConnection();

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $user_type = $_POST['user_type'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    
    try {
        $conn->beginTransaction();
        
        // Create user authentication record
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $salt = bin2hex(random_bytes(16));
        
        $auth_query = "INSERT INTO user_auth (username, password_hash, salt, user_role) VALUES (:username, :password_hash, :salt, :user_role)";
        $auth_stmt = $conn->prepare($auth_query);
        $auth_stmt->bindParam(':username', $username);
        $auth_stmt->bindParam(':password_hash', $password_hash);
        $auth_stmt->bindParam(':salt', $salt);
        $auth_stmt->bindParam(':user_role', $user_type);
        $auth_stmt->execute();
        
        $user_id = $conn->lastInsertId();
        
        // Create specific user record based on type
        if ($user_type === 'student') {
            $student_id_query = "SELECT COUNT(*) as count FROM Student_Table";
            $student_id_stmt = $conn->prepare($student_id_query);
            $student_id_stmt->execute();
            $count = $student_id_stmt->fetch()['count'];
            $student_id = 'S' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            
            $student_query = "INSERT INTO Student_Table (Student_ID, user_id, first_name, last_name, email, mobile_number, student_roll_number, course, department, year_of_study, gender, student_status) 
                             VALUES (:student_id, :user_id, :first_name, :last_name, :email, :mobile, :roll_number, :course, :department, :year, :gender, 'active')";
            $student_stmt = $conn->prepare($student_query);
            $student_stmt->bindParam(':student_id', $student_id);
            $student_stmt->bindParam(':user_id', $user_id);
            $student_stmt->bindParam(':first_name', $first_name);
            $student_stmt->bindParam(':last_name', $last_name);
            $student_stmt->bindParam(':email', $email);
            $student_stmt->bindParam(':mobile', $mobile);
            $student_stmt->bindParam(':roll_number', $_POST['roll_number']);
            $student_stmt->bindParam(':course', $_POST['course']);
            $student_stmt->bindParam(':department', $_POST['department']);
            $student_stmt->bindParam(':year', $_POST['year']);
            $student_stmt->bindParam(':gender', $_POST['gender']);
            $student_stmt->execute();
            
        } elseif ($user_type === 'warden') {
            $warden_id_query = "SELECT COUNT(*) as count FROM Warden_Table";
            $warden_id_stmt = $conn->prepare($warden_id_query);
            $warden_id_stmt->execute();
            $count = $warden_id_stmt->fetch()['count'];
            $warden_id = 'W' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
            
            $warden_query = "INSERT INTO Warden_Table (Warden_ID, user_id, first_name, last_name, email, mobile_number, role, shift_timing) 
                            VALUES (:warden_id, :user_id, :first_name, :last_name, :email, :mobile, :role, :shift)";
            $warden_stmt = $conn->prepare($warden_query);
            $warden_stmt->bindParam(':warden_id', $warden_id);
            $warden_stmt->bindParam(':user_id', $user_id);
            $warden_stmt->bindParam(':first_name', $first_name);
            $warden_stmt->bindParam(':last_name', $last_name);
            $warden_stmt->bindParam(':email', $email);
            $warden_stmt->bindParam(':mobile', $mobile);
            $warden_stmt->bindParam(':role', $_POST['warden_role']);
            $warden_stmt->bindParam(':shift', $_POST['shift_timing']);
            $warden_stmt->execute();
        }
        
        $conn->commit();
        $success_message = ucfirst($user_type) . " created successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to create user: " . $e->getMessage();
    }
}

// Fetch all users
$users_query = "SELECT 
                    ua.user_id, ua.username, ua.user_role, ua.is_active, ua.created_at,
                    CASE 
                        WHEN ua.user_role = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                        WHEN ua.user_role = 'warden' THEN CONCAT(w.first_name, ' ', w.last_name)
                        WHEN ua.user_role = 'admin' THEN CONCAT(a.first_name, ' ', a.last_name)
                    END as full_name,
                    CASE 
                        WHEN ua.user_role = 'student' THEN s.email
                        WHEN ua.user_role = 'warden' THEN w.email
                        WHEN ua.user_role = 'admin' THEN a.email
                    END as email,
                    CASE 
                        WHEN ua.user_role = 'student' THEN s.Student_ID
                        WHEN ua.user_role = 'warden' THEN w.Warden_ID
                        WHEN ua.user_role = 'admin' THEN a.Admin_ID
                    END as role_id
                FROM user_auth ua
                LEFT JOIN Student_Table s ON ua.user_id = s.user_id
                LEFT JOIN Warden_Table w ON ua.user_id = w.user_id
                LEFT JOIN Admin_Table a ON ua.user_id = a.user_id
                ORDER BY ua.created_at DESC";

$users_stmt = $conn->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - HMS</title>
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
            <h1 class="dashboard-title">User Management</h1>
            <p class="dashboard-subtitle">Manage system users and access controls</p>
        </div>

        <!-- User Statistics -->
        <div class="stats-grid">
            <?php
            $user_stats = array_count_values(array_column($users, 'user_role'));
            ?>
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $user_stats['student'] ?? 0; ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-info"><?php echo $user_stats['warden'] ?? 0; ?></div>
                <div class="stat-label">Wardens</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo $user_stats['admin'] ?? 0; ?></div>
                <div class="stat-label">Administrators</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo count($users); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row" style="margin-bottom: 2rem;">
            <div class="col-4">
                <button class="btn btn-primary btn-block" onclick="openModal('createUserModal')">
                    <i class="fas fa-user-plus"></i> Create New User
                </button>
            </div>
            <div class="col-4">
                <button class="btn btn-warning btn-block" onclick="filterUsers('inactive')">
                    <i class="fas fa-user-slash"></i> View Inactive Users
                </button>
            </div>
            <div class="col-4">
                <button class="btn btn-info btn-block" onclick="filterUsers('all')">
                    <i class="fas fa-users"></i> View All Users
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon primary">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="card-title">System Users (<?php echo count($users); ?>)</div>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr data-status="<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                <td><?php echo htmlspecialchars($user['role_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $user['user_role'] === 'admin' ? 'danger' : 
                                            ($user['user_role'] === 'warden' ? 'warning' : 'primary'); 
                                    ?>">
                                        <?php echo ucfirst($user['user_role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewUser(<?php echo $user['user_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>" 
                                            onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, <?php echo $user['is_active'] ? 'false' : 'true'; ?>)">
                                        <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
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

    <!-- Create User Modal -->
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Create New User</h2>
                <button class="modal-close" onclick="closeModal('createUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="createUserForm">
                    <div class="form-group">
                        <label for="user_type" class="form-label">User Type</label>
                        <select name="user_type" id="user_type" class="form-control" required onchange="toggleUserFields()">
                            <option value="">Select User Type</option>
                            <option value="student">Student</option>
                            <option value="warden">Warden</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" name="first_name" id="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" name="last_name" id="last_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="mobile" class="form-label">Mobile Number</label>
                                <input type="tel" name="mobile" id="mobile" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student-specific fields -->
                    <div id="studentFields" style="display: none;">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="roll_number" class="form-label">Roll Number</label>
                                    <input type="text" name="roll_number" id="roll_number" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="course" class="form-label">Course</label>
                                    <input type="text" name="course" id="course" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" name="department" id="department" class="form-control">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="year" class="form-label">Year</label>
                                    <select name="year" id="year" class="form-control">
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select name="gender" id="gender" class="form-control">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Warden-specific fields -->
                    <div id="wardenFields" style="display: none;">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="warden_role" class="form-label">Warden Role</label>
                                    <select name="warden_role" id="warden_role" class="form-control">
                                        <option value="Chief Warden">Chief Warden</option>
                                        <option value="Assistant Warden">Assistant Warden</option>
                                        <option value="Security Warden">Security Warden</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="shift_timing" class="form-label">Shift Timing</label>
                                    <select name="shift_timing" id="shift_timing" class="form-control">
                                        <option value="Day Shift">Day Shift (6 AM - 6 PM)</option>
                                        <option value="Night Shift">Night Shift (6 PM - 6 AM)</option>
                                        <option value="Full Time">Full Time</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="create_user" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Create User
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleUserFields() {
            const userType = document.getElementById('user_type').value;
            const studentFields = document.getElementById('studentFields');
            const wardenFields = document.getElementById('wardenFields');
            
            studentFields.style.display = userType === 'student' ? 'block' : 'none';
            wardenFields.style.display = userType === 'warden' ? 'block' : 'none';
            
            // Set required attributes
            const studentInputs = studentFields.querySelectorAll('input, select');
            const wardenInputs = wardenFields.querySelectorAll('input, select');
            
            studentInputs.forEach(input => {
                input.required = userType === 'student';
            });
            
            wardenInputs.forEach(input => {
                input.required = userType === 'warden';
            });
        }

        function filterUsers(status) {
            const rows = document.querySelectorAll('#usersTable tbody tr');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function viewUser(userId) {
            // Implementation for viewing user details
            alert('View user details for ID: ' + userId);
        }

        function toggleUserStatus(userId, newStatus) {
            if (confirm('Are you sure you want to ' + (newStatus === 'true' ? 'activate' : 'deactivate') + ' this user?')) {
                // Implementation for toggling user status
                window.location.href = 'toggle_user_status.php?user_id=' + userId + '&status=' + newStatus;
            }
        }
    </script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
