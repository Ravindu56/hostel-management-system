<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    require_once 'session_check.php';
    redirectToDashboard($_SESSION['role']);
}

// Handle success/error messages
$error_message = '';
$success_message = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'username_exists':
            $error_message = 'Username already exists. Please choose a different username.';
            break;
        case 'email_exists':
            $error_message = 'Email address is already registered. Please use a different email.';
            break;
        case 'password_mismatch':
            $error_message = 'Passwords do not match. Please try again.';
            break;
        case 'invalid_data':
            $error_message = 'Please fill in all required fields correctly.';
            break;
        case 'registration_failed':
            $error_message = 'Registration failed. Please try again later.';
            break;
    }
}

if (isset($_GET['success'])) {
    $success_message = 'Registration successful! You can now sign in with your credentials.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .register-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .tab-btn {
            flex: 1;
            padding: 1rem;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .register-form {
            display: none;
        }
        
        .register-form.active {
            display: block;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .register-card {
                padding: 1.5rem;
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="logo" style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: white; font-size: 1.5rem;">
                    <i class="fas fa-building"></i>
                </div>
                <h1 style="color: #333; margin-bottom: 0.5rem;">Join HMS</h1>
                <p style="color: #666;">Create your account to get started</p>
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
            
            <div class="register-tabs">
                <button class="tab-btn active" onclick="switchTab('student')">Student Registration</button>
                <button class="tab-btn" onclick="switchTab('warden')">Warden Registration</button>
            </div>
            
            <!-- Student Registration Form -->
            <form id="studentForm" class="register-form active" action="register_process.php" method="POST">
                <input type="hidden" name="user_type" value="student">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_first_name" class="form-label">First Name *</label>
                        <input type="text" name="first_name" id="student_first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="student_last_name" class="form-label">Last Name *</label>
                        <input type="text" name="last_name" id="student_last_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="student_roll_number" class="form-label">Student Roll Number *</label>
                    <input type="text" name="student_roll_number" id="student_roll_number" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_email" class="form-label">Email Address *</label>
                        <input type="email" name="email" id="student_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="student_mobile" class="form-label">Mobile Number *</label>
                        <input type="tel" name="mobile_number" id="student_mobile" class="form-control" required pattern="[0-9]{10}">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="course" class="form-label">Course *</label>
                        <input type="text" name="course" id="course" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="year_of_study" class="form-label">Year of Study *</label>
                        <select name="year_of_study" id="year_of_study" class="form-control" required>
                            <option value="">Select Year</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="department" class="form-label">Department *</label>
                        <input type="text" name="department" id="department" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="gender" class="form-label">Gender *</label>
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_username" class="form-label">Username *</label>
                        <input type="text" name="username" id="student_username" class="form-control" required minlength="3">
                    </div>
                    <div class="form-group">
                        <label for="student_password" class="form-label">Password *</label>
                        <input type="password" name="password" id="student_password" class="form-control" required minlength="6">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="student_confirm_password" class="form-label">Confirm Password *</label>
                    <input type="password" name="confirm_password" id="student_confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Register as Student
                </button>
            </form>
            
            <!-- Warden Registration Form -->
            <form id="wardenForm" class="register-form" action="register_process.php" method="POST">
                <input type="hidden" name="user_type" value="warden">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="warden_first_name" class="form-label">First Name *</label>
                        <input type="text" name="first_name" id="warden_first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="warden_last_name" class="form-label">Last Name *</label>
                        <input type="text" name="last_name" id="warden_last_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="warden_email" class="form-label">Email Address *</label>
                        <input type="email" name="email" id="warden_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="warden_mobile" class="form-label">Mobile Number *</label>
                        <input type="tel" name="mobile_number" id="warden_mobile" class="form-control" required pattern="[0-9]{10}">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="warden_role" class="form-label">Warden Role *</label>
                        <select name="warden_role" id="warden_role" class="form-control" required>
                            <option value="">Select Role</option>
                            <option value="Chief Warden">Chief Warden</option>
                            <option value="Assistant Warden">Assistant Warden</option>
                            <option value="Security Warden">Security Warden</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="shift_timing" class="form-label">Shift Timing *</label>
                        <select name="shift_timing" id="shift_timing" class="form-control" required>
                            <option value="">Select Shift</option>
                            <option value="Day Shift">Day Shift (6 AM - 6 PM)</option>
                            <option value="Night Shift">Night Shift (6 PM - 6 AM)</option>
                            <option value="Full Time">Full Time</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="warden_username" class="form-label">Username *</label>
                        <input type="text" name="username" id="warden_username" class="form-control" required minlength="3">
                    </div>
                    <div class="form-group">
                        <label for="warden_password" class="form-label">Password *</label>
                        <input type="password" name="password" id="warden_password" class="form-control" required minlength="6">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="warden_confirm_password" class="form-label">Confirm Password *</label>
                    <input type="password" name="confirm_password" id="warden_confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Register as Warden
                </button>
            </form>
            
            <div class="text-center" style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e9ecef;">
                <p>Already have an account? <a href="login.php" style="color: #667eea; text-decoration: none; font-weight: 500;">Sign in here</a></p>
                <p><a href="../index.php" style="color: #666; text-decoration: none;">← Back to Home</a></p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabType) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update forms
            document.querySelectorAll('.register-form').forEach(form => form.classList.remove('active'));
            document.getElementById(tabType + 'Form').classList.add('active');
        }
        
        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const password = this.querySelector('input[name="password"]').value;
                const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                // Add loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
            });
        });
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
