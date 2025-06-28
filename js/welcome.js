// Modal Functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

function switchModal(fromModal, toModal) {
    closeModal(fromModal);
    setTimeout(() => openModal(toModal), 300);
}

// Registration Tab Switching
function switchTab(tabType) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Update forms
    document.querySelectorAll('.register-form').forEach(form => form.classList.remove('active'));
    document.getElementById(tabType + 'Form').classList.add('active');
}

// Mobile Menu Toggle
function toggleMobileMenu() {
    const navMenu = document.querySelector('.nav-menu');
    navMenu.classList.toggle('active');
}

// Smooth Scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Handle URL parameters for messages
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');
    
    if (success) {
        showNotification(getSuccessMessage(success), 'success');
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    if (error) {
        showNotification(getErrorMessage(error), 'error');
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// Notification system
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '400px';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function getSuccessMessage(type) {
    const messages = {
        'registration_successful': 'Registration successful! You can now sign in with your credentials.'
    };
    return messages[type] || 'Operation completed successfully!';
}

function getErrorMessage(type) {
    const messages = {
        'registration_failed': 'Registration failed. Please try again.',
        'invalid_credentials': 'Invalid username or password.',
        'account_inactive': 'Your account is inactive. Please contact administration.'
    };
    return messages[type] || 'An error occurred. Please try again.';
}

function redirectToLogin() {
    window.location.href = 'auth/login.php';
}
// Navbar scroll effect
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.style.background = 'rgba(102, 126, 234, 0.95)';
        navbar.style.backdropFilter = 'blur(10px)';
    } else {
        navbar.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        navbar.style.backdropFilter = 'none';
    }
});

// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = '#dc3545';
            } else {
                field.style.borderColor = '#e9ecef';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showNotification('Please fill in all required fields', 'error');
        }
    });
});

// Add loading state to buttons
document.querySelectorAll('button[type="submit"]').forEach(button => {
    button.addEventListener('click', function() {
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        this.disabled = true;
    });
});


