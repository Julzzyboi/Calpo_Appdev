document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));

    // Show error in modal
    function showError(message) {
        document.getElementById('errorMessage').textContent = message;
        errorModal.show();
    }

    // Show success in modal and redirect
    function showSuccess(message, redirectUrl) {
        document.getElementById('successMessage').textContent = message;
        successModal.show();
        
        // Redirect to appropriate dashboard after 2 seconds
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 2000);
    }

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        // Basic validation
        if (!email) {
            showError('Please enter your email address');
            return;
        }

        if (!password) {
            showError('Please enter your password');
            return;
        }

        // Email format validation
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            showError('Please enter a valid email address');
            return;
        }

        // Submit form via AJAX
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess(data.message, data.redirect_url);
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            showError('An error occurred during login. Please try again.');
        });
    });
}); 