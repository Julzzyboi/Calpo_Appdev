document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const inputs = {
        full_name: {
            element: document.getElementById('full_name'),
            error: document.querySelector('#full_name').closest('.mb-3').querySelector('.error'),
            validate: function(value) {
                if (!value.trim()) return 'Full name is required';
                if (value.length < 2) return 'Full name must be at least 2 characters';
                if (!/^[a-zA-Z\s]*$/.test(value)) return 'Full name should only contain letters and spaces';
                return '';
            }
        },
        username: {
            element: document.getElementById('username'),
            error: document.querySelector('#username').closest('.mb-3').querySelector('.error'),
            validate: function(value) {
                if (!value.trim()) return 'Username is required';
                if (value.length < 3) return 'Username must be at least 3 characters';
                if (!/^[a-zA-Z0-9_]*$/.test(value)) return 'Username can only contain letters, numbers, and underscores';
                return '';
            }
        },
        email: {
            element: document.getElementById('email'),
            error: document.querySelector('#email').closest('.mb-3').querySelector('.error'),
            validate: function(value) {
                if (!value.trim()) return 'Email is required';
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Please enter a valid email address';
                return '';
            }
        },
        password: {
            element: document.getElementById('password'),
            error: document.querySelector('#password').closest('.mb-3').querySelector('.error'),
            validate: function(value) {
                if (!value) return 'Password is required';
                if (value.length < 8) return 'Password must be at least 8 characters';
                if (!/[A-Z]/.test(value)) return 'Password must contain at least one uppercase letter';
                if (!/[a-z]/.test(value)) return 'Password must contain at least one lowercase letter';
                if (!/[0-9]/.test(value)) return 'Password must contain at least one number';
                return '';
            }
        },
        confirm_password: {
            element: document.getElementById('confirm_password'),
            error: document.querySelector('#confirm_password').closest('.mb-3').querySelector('.error'),
            validate: function(value) {
                const password = document.getElementById('password').value;
                if (!value) return 'Please confirm your password';
                if (value !== password) return 'Passwords do not match';
                return '';
            }
        },
        address: {
            element: document.getElementById('address'),
            error: document.querySelector('#address').closest('.mb-3').querySelector('.error'),
            validate: function(value) {
                if (!value.trim()) return 'Address is required';
                if (value.length < 5) return 'Please enter a valid address';
                return '';
            }
        },
        contact_number: {
            element: document.getElementById('contact_number'),
            error: document.querySelector('#contact_number').closest('.mb-3').querySelector('.error'),
            validate: function(value) {
                if (!value.trim()) return 'Contact number is required';
                if (!/^09\d{9}$/.test(value)) return 'Please enter a valid 11-digit mobile number starting with 09';
                return '';
            }
        }
    };

    // Add input event listeners to all fields
    Object.keys(inputs).forEach(key => {
        const input = inputs[key];
        input.element.addEventListener('input', function() {
            validateField(key);
        });
    });

    // Function to validate a single field
    function validateField(fieldName) {
        const field = inputs[fieldName];
        const value = field.element.value;
        const errorMessage = field.validate(value);

        if (errorMessage) {
            field.element.classList.add('is-invalid');
            field.element.classList.remove('is-valid');
            field.error.textContent = errorMessage;
            field.error.style.display = 'block';
        } else {
            field.element.classList.remove('is-invalid');
            field.element.classList.add('is-valid');
            field.error.textContent = '';
            field.error.style.display = 'none';
        }
    }

    // Form submission handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let isValid = true;

        // Validate all fields
        Object.keys(inputs).forEach(key => {
            validateField(key);
            if (inputs[key].element.classList.contains('is-invalid')) {
                isValid = false;
            }
        });

        if (isValid) {
            // If all validations pass, submit the form
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success modal
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    document.getElementById('successMessage').textContent = data.message;
                    successModal.show();
                } else {
                    // Show error modal
                    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    document.getElementById('errorMessage').textContent = data.message;
                    errorModal.show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                document.getElementById('errorMessage').textContent = 'An error occurred. Please try again.';
                errorModal.show();
            });
        }
    });

    // Profile picture preview
    const profilePicture = document.getElementById('profile_picture');
    const preview = document.getElementById('preview');

    profilePicture.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            }
            reader.readAsDataURL(file);
        }
    });

    // Show/hide password functionality
    document.querySelectorAll('.password-field-wrapper').forEach(wrapper => {
        const input = wrapper.querySelector('input[type="password"], input[type="text"]');
        const toggle = wrapper.querySelector('.password-toggle');
        const icon = toggle.querySelector('i');
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});