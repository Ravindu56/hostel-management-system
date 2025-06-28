// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Set minimum date for visitor registration to today
document.addEventListener('DOMContentLoaded', function() {
    const visitDateInput = document.getElementById('visit_date');
    if (visitDateInput) {
        visitDateInput.min = new Date().toISOString().split('T')[0];
    }
    
    // Handle success/error messages
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

// Form validation
document.getElementById('complaintForm')?.addEventListener('submit', function(e) {
    const category = document.getElementById('category').value;
    const title = document.getElementById('title').value;
    const description = document.getElementById('description').value;
    
    if (!category || !title || !description) {
        e.preventDefault();
        showNotification('Please fill in all required fields', 'error');
    }
});

document.getElementById('visitorForm')?.addEventListener('submit', function(e) {
    const firstName = document.getElementById('visitor_first_name').value;
    const lastName = document.getElementById('visitor_last_name').value;
    const mobile = document.getElementById('visitor_mobile').value;
    const relationship = document.getElementById('relationship').value;
    const visitDate = document.getElementById('visit_date').value;
    const timeIn = document.getElementById('time_in').value;
    
    if (!firstName || !lastName || !mobile || !relationship || !visitDate || !timeIn) {
        e.preventDefault();
        showNotification('Please fill in all required fields', 'error');
    }
    
    // Validate mobile number
    if (mobile && !/^[0-9]{10}$/.test(mobile)) {
        e.preventDefault();
        showNotification('Please enter a valid 10-digit mobile number', 'error');
    }
});

// Notification system
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    // Insert at top of dashboard container
    const container = document.querySelector('.dashboard-container');
    container.insertBefore(notification, container.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function getSuccessMessage(type) {
    const messages = {
        'complaint_submitted': 'Complaint submitted successfully! You will be notified when it is assigned to a warden.',
        'visitor_registered': 'Visitor registered successfully! They can now visit during the specified time.',
        'profile_updated': 'Profile updated successfully!'
    };
    return messages[type] || 'Operation completed successfully!';
}

function getErrorMessage(type) {
    const messages = {
        'complaint_submission_failed': 'Failed to submit complaint. Please try again.',
        'visitor_registration_failed': 'Failed to register visitor. Please try again.',
        'profile_update_failed': 'Failed to update profile. Please try again.',
        'database_error': 'Database error occurred. Please try again later.',
        'student_not_found': 'Student record not found.',
        'email_already_exists': 'Email address is already in use by another student.'
    };
    return messages[type] || 'An error occurred. Please try again.';
}

// Mobile menu toggle (if needed)
function toggleMobileMenu() {
    const navMenu = document.querySelector('.nav-menu');
    navMenu.classList.toggle('active');
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
