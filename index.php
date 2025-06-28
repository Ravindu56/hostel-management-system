<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    require_once 'auth/session_check.php';
    redirectToDashboard($_SESSION['role']);
}

// Handle success/error messages
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'registration_successful':
            $success_message = 'Registration successful! You can now sign in with your credentials.';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'registration_failed':
            $error_message = 'Registration failed. Please try again.';
            break;
        case 'invalid_credentials':
            $error_message = 'Invalid username or password.';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management System - Welcome</title>
    <link rel="stylesheet" href="css/welcome-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-building"></i>
                <span>HMS</span>
            </div>
            <div class="nav-menu" id="navMenu">
                <a href="#home" class="nav-link">Home</a>
                <a href="#about" class="nav-link">About</a>
                <a href="#features" class="nav-link">Features</a>
                <a href="#contact" class="nav-link">Contact</a>
            </div>
            <div class="nav-actions">
                <a href="auth/login.php" class="btn btn-outline">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
                <a href="auth/register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </div>
            <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Display Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px;">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px;">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    Welcome to <span class="gradient-text">Hostel Management System</span>
                </h1>
                <p class="hero-subtitle">
                    Streamline your hostel operations with our comprehensive digital solution. 
                    Manage students, rooms, payments, and complaints efficiently.
                </p>
                <div class="hero-actions">
                    <div class="hero-actions">
                        <a href="auth/register.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-rocket"></i> Get Started
                        </a>
                        <a href="auth/login.php" class="btn btn-outline btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </a>
                    </div>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Students</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Rooms</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Support</div>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="floating-card">
                    <i class="fas fa-home"></i>
                    <h3>Smart Room Management</h3>
                    <p>Real-time occupancy tracking</p>
                </div>
                <div class="floating-card">
                    <i class="fas fa-credit-card"></i>
                    <h3>Digital Payments</h3>
                    <p>Secure fee management</p>
                </div>
                <div class="floating-card">
                    <i class="fas fa-headset"></i>
                    <h3>24/7 Support</h3>
                    <p>Instant complaint resolution</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2>Powerful Features</h2>
                <p>Everything you need to manage your hostel efficiently</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Student Management</h3>
                    <p>Complete student registration, profile management, and accommodation tracking</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h3>Room Allocation</h3>
                    <p>Smart room assignment with real-time availability and occupancy monitoring</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Payment Tracking</h3>
                    <p>Digital fee management with automated calculations and payment history</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3>Complaint System</h3>
                    <p>Streamlined maintenance requests with priority-based resolution tracking</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <h3>Visitor Management</h3>
                    <p>Secure visitor registration with check-in/check-out monitoring</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Analytics & Reports</h3>
                    <p>Comprehensive reporting with insights for better decision making</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About Our System</h2>
                    <p>
                        Our Hostel Management System is designed to digitize and streamline hostel operations, 
                        replacing manual paper-based processes with an efficient web-based solution.
                    </p>
                    <div class="about-features">
                        <div class="about-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>90% reduction in manual paperwork</span>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Real-time room availability tracking</span>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Secure role-based access control</span>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Comprehensive complaint management</span>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <div class="dashboard-preview">
                        <div class="preview-header">
                            <div class="preview-dots">
                                <span></span><span></span><span></span>
                            </div>
                            <span>HMS Dashboard</span>
                        </div>
                        <div class="preview-content">
                            <div class="preview-sidebar">
                                <div class="sidebar-item active">Dashboard</div>
                                <div class="sidebar-item">Students</div>
                                <div class="sidebar-item">Rooms</div>
                                <div class="sidebar-item">Payments</div>
                            </div>
                            <div class="preview-main">
                                <div class="preview-stats">
                                    <div class="preview-stat">
                                        <div class="stat-value">250</div>
                                        <div class="stat-name">Students</div>
                                    </div>
                                    <div class="preview-stat">
                                        <div class="stat-value">45</div>
                                        <div class="stat-name">Rooms</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-header">
                <h2>Get In Touch</h2>
                <p>Have questions? We're here to help!</p>
            </div>
            <div class="contact-grid">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>admin@hms.com</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Phone</h4>
                            <p>+94 70 56 25 156</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Address</h4>
                            <p>University of Jaffna, Kilinochchi, Sri Lanka</p>
                        </div>
                    </div>
                </div>
                <div class="contact-form">
                    <form>
                        <div class="form-row">
                            <input type="text" placeholder="Your Name" required>
                            <input type="email" placeholder="Your Email" required>
                        </div>
                        <input type="text" placeholder="Subject" required>
                        <textarea placeholder="Your Message" rows="5" required></textarea>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="nav-brand">
                        <i class="fas fa-building"></i>
                        <span>HMS</span>
                    </div>
                    <p>Streamlining hostel management for the digital age.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-section">
                        <h4>Quick Links</h4>
                        <a href="#home">Home</a>
                        <a href="#about">About</a>
                        <a href="#features">Features</a>
                        <a href="#contact">Contact</a>
                    </div>
                    <div class="footer-section">
                        <h4>Support</h4>
                        <a href="#">Help Center</a>
                        <a href="#">Documentation</a>
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Hostel Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Sign In to HMS</h2>
                <button class="modal-close" onclick="closeModal('loginModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="auth/login.php" method="POST">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" placeholder="Username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
                <div class="demo-credentials">
                    <h4>Demo Credentials:</h4>
                    <div class="demo-grid">
                        <div class="demo-item">
                            <strong>Student:</strong> nimal / password123
                        </div>
                        <div class="demo-item">
                            <strong>Warden:</strong> warden1 / password123
                        </div>
                        <div class="demo-item">
                            <strong>Admin:</strong> admin / password123
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <p>Don't have an account? <a href="#" onclick="switchModal('loginModal', 'registerModal')">Register here</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Join HMS</h2>
                <button class="modal-close" onclick="closeModal('registerModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="register-tabs">
                    <button class="tab-btn active" onclick="switchTab('student')">Student Registration</button>
                    <button class="tab-btn" onclick="switchTab('warden')">Warden Registration</button>
                </div>
                
                <!-- Student Registration Form -->
                <form id="studentForm" class="register-form active" action="auth/register.php" method="POST">
                    <input type="hidden" name="user_type" value="student">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="first_name" placeholder="First Name" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="last_name" placeholder="Last Name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="student_roll_number" placeholder="Student Roll Number" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Email Address" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" name="mobile_number" placeholder="Mobile Number" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="course" placeholder="Course" required>
                        </div>
                        <div class="form-group">
                            <select name="year_of_study" required>
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
                            <input type="text" name="department" placeholder="Department" required>
                        </div>
                        <div class="form-group">
                            <select name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus"></i> Register as Student
                    </button>
                </form>
                
                <!-- Warden Registration Form -->
                <form id="wardenForm" class="register-form" action="auth/register.php" method="POST">
                    <input type="hidden" name="user_type" value="warden">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="first_name" placeholder="First Name" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="last_name" placeholder="Last Name" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Email Address" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" name="mobile_number" placeholder="Mobile Number" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <select name="warden_role" required>
                                <option value="">Select Role</option>
                                <option value="Chief Warden">Chief Warden</option>
                                <option value="Assistant Warden">Assistant Warden</option>
                                <option value="Security Warden">Security Warden</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="shift_timing" required>
                                <option value="">Select Shift</option>
                                <option value="Day Shift">Day Shift (6 AM - 6 PM)</option>
                                <option value="Night Shift">Night Shift (6 PM - 6 AM)</option>
                                <option value="Full Time">Full Time</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus"></i> Register as Warden
                    </button>
                </form>
                
                <div class="modal-footer">
                    <p>Already have an account? <a href="#" onclick="switchModal('registerModal', 'loginModal')">Sign in here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/welcome.js"></script>
</body>
</html>
