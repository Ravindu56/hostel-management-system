<?php
session_start();
require_once '../config/database.php';


// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    require_once 'session_check.php';
    redirectToDashboard($_SESSION['role']);
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Enhanced query based on your ERD structure
            $query = "SELECT ua.user_id, ua.username, ua.password_hash, ua.user_role, ua.is_active, ua.failed_attempts,
                             CASE 
                                 WHEN ua.user_role = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                                 WHEN ua.user_role = 'warden' THEN CONCAT(w.first_name, ' ', w.last_name)
                                 WHEN ua.user_role = 'admin' THEN CONCAT(a.first_name, ' ', a.last_name)
                             END as full_name,
                             CASE 
                                 WHEN ua.user_role = 'student' THEN s.Student_ID
                                 WHEN ua.user_role = 'warden' THEN w.Warden_ID
                                 WHEN ua.user_role = 'admin' THEN a.Admin_ID
                             END as role_id,
                             CASE 
                                 WHEN ua.user_role = 'student' THEN s.email
                                 WHEN ua.user_role = 'warden' THEN w.email
                                 WHEN ua.user_role = 'admin' THEN a.email
                             END as email
                      FROM user_auth ua
                      LEFT JOIN Student_Table s ON ua.user_id = s.user_id
                      LEFT JOIN Warden_Table w ON ua.user_id = w.user_id
                      LEFT JOIN Admin_Table a ON ua.user_id = a.user_id
                      WHERE ua.username = :username";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                
                // Check if account is active
                if (!$user['is_active']) {
                    $error_message = "Your account has been deactivated. Please contact administration.";
                } 
                // Check for account lockout (after 5 failed attempts)
                elseif ($user['failed_attempts'] >= 5) {
                    $error_message = "Account locked due to multiple failed login attempts. Please contact administration.";
                }
                else {
                    // Verify password (demo password or hashed password)
                    $password_valid = false;
                    if ($password === 'password123') {
                        $password_valid = true;
                    } elseif (!empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                        $password_valid = true;
                    }
                    
                    if ($password_valid) {
                        // Reset failed attempts and update last login
                        $update_query = "UPDATE user_auth SET 
                                        last_login = NOW(), 
                                        failed_attempts = 0,
                                        locked_until = NULL 
                                        WHERE user_id = :user_id";
                        $update_stmt = $conn->prepare($update_query);
                        $update_stmt->bindParam(':user_id', $user['user_id']);
                        $update_stmt->execute();
                        
                        // Set comprehensive session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['user_role'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role_id'] = $user['role_id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['login_time'] = time();
                        
                        // Log successful login (optional audit trail)
                        $audit_query = "INSERT INTO System_Audit_Log (user_id, action_type, table_affected, ip_address, user_agent) 
                                       VALUES (:user_id, 'LOGIN_SUCCESS', 'user_auth', :ip, :user_agent)";
                        try {
                            $audit_stmt = $conn->prepare($audit_query);
                            $audit_stmt->bindParam(':user_id', $user['user_id']);
                            $audit_stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
                            $audit_stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
                            $audit_stmt->execute();
                        } catch (Exception $e) {
                            // Audit logging is optional, don't break login if it fails
                        }
                        
                        // Redirect based on role
                        require_once 'session_check.php';
                        redirectToDashboard($user['user_role']);
                    } else {
                        // Increment failed attempts
                        $failed_query = "UPDATE user_auth SET failed_attempts = failed_attempts + 1 WHERE user_id = :user_id";
                        $failed_stmt = $conn->prepare($failed_query);
                        $failed_stmt->bindParam(':user_id', $user['user_id']);
                        $failed_stmt->execute();
                        
                        $error_message = "Invalid username or password.";
                    }
                }
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error_message = "Login failed. Please try again.";
        }
    }
}

// Handle error messages from URL parameters
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'unauthorized':
            $error_message = "You don't have permission to access that page.";
            break;
        case 'session_expired':
            $error_message = "Your session has expired. Please login again.";
            break;
        case 'account_inactive':
            $error_message = "Your account is inactive. Please contact administration.";
            break;
        case 'invalid_credentials':
            $error_message = "Invalid username or password.";
            break;
    }
}

if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success_message = "You have been successfully logged out.";
}

if (isset($_GET['success']) && $_GET['success'] === 'registration_successful') {
    $success_message = "Registration successful! You can now sign in with your credentials.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMS - Login</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .demo-credentials {
            margin-top: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            border: 1px solid #dee2e6;
        }
        
        .demo-grid {
            display: grid;
            gap: 0.75rem;
        }
        
        .demo-item {
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #495057;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .demo-item:hover {
            background: #667eea;
            color: white;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .demo-item strong {
            color: inherit;
        }
        
        .demo-item a {
            color: inherit;
            text-decoration: none;
            font-weight: 600;
        }
        
        .home-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .home-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .home-link a:hover {
            color: #764ba2;
            transform: translateX(-3px);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-building"></i>
                </div>
                <h1 style="color: #333; margin-bottom: 0.5rem; font-size: 1.8rem;">Hostel Management System</h1>
                <p style="color: #666; margin: 0;">Welcome back! Please sign in to your account</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" class="form-control" placeholder="Username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                        <span class="input-action" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="demo-credentials">
                <h3 style="color: #333; margin-bottom: 1rem; font-size: 1rem; text-align: center;">Demo Credentials:</h3>
                <div class="demo-grid">
                    <div class="demo-item" onclick="fillCredentials('nimal', 'password123')">
                        <strong>Student:</strong> nimal / password123
                    </div>
                    <div class="demo-item" onclick="fillCredentials('warden1', 'password123')">
                        <strong>Warden:</strong> warden1 / password123
                    </div>
                    <div class="demo-item" onclick="fillCredentials('admin', 'password123')">
                        <strong>Admin:</strong> admin / password123
                    </div>
                </div>
            </div>
            
            <div class="home-link">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
                <span style="margin: 0 1rem; color: #ccc;">|</span>
                <a href="register.php">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.querySelector('input[name="password"]');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
        
        function fillCredentials(username, password) {
            document.querySelector('input[name="username"]').value = username;
            document.querySelector('input[name="password"]').value = password;
            
            // Visual feedback
            const usernameInput = document.querySelector('input[name="username"]');
            const passwordInput = document.querySelector('input[name="password"]');
            
            usernameInput.style.borderColor = '#28a745';
            passwordInput.style.borderColor = '#28a745';
            
            setTimeout(() => {
                usernameInput.style.borderColor = '';
                passwordInput.style.borderColor = '';
            }, 2000);
        }
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
        
        // Add loading state to login button
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
