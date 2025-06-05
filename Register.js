class RegisterHandler {
    constructor() {
        console.log('RegisterHandler initialized');
        this.modal = null;
        this.initializeModal();
        this.attachFormListener();
        this.attachImagePreview();
        this.attachInputListeners();
        this.initializePasswordToggles();
    }

    initializeModal() {
        console.log('Initializing modal');
        // Create modal HTML
        const modalHTML = `
            <div class="custom-modal" id="messageModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Error:</h5>
                        <button type="button" class="close-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="error-message"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="modal-button">OK</button>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('messageModal');
        
        // Add event listeners
        this.modal.querySelector('.close-modal').addEventListener('click', () => this.hideModal());
        this.modal.querySelector('.modal-button').addEventListener('click', () => this.hideModal());
    }

    showModal(message, isSuccess = false) {
        console.log('Showing modal:', message, 'Success:', isSuccess);
        const modalContent = this.modal.querySelector('.modal-content');
        const errorIcon = this.modal.querySelector('.error-icon i');
        const errorMessage = this.modal.querySelector('.error-message');

        // Update modal content
        errorMessage.textContent = message;
        errorIcon.className = isSuccess ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        modalContent.classList.toggle('success-modal', isSuccess);

        // Show modal
        this.modal.style.display = 'flex';
    }

    hideModal() {
        console.log('Hiding modal');
        this.modal.style.display = 'none';
    }

    attachInputListeners() {
        console.log('Attaching input listeners');
        const form = document.getElementById('registerForm');
        if (!form) {
            console.error('Register form not found!');
            return;
        }

        // Add input listeners to all form fields
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                // Clear error for this field
                const errorElement = input.nextElementSibling;
                if (errorElement && errorElement.classList.contains('error')) {
                    errorElement.textContent = '';
                    input.classList.remove('is-invalid');
                }
            });
        });
    }

    initializePasswordToggles() {
        console.log('Initializing password toggles');
        const passwordFields = ['password', 'confirm_password'];
        
        passwordFields.forEach(fieldId => {
            const passwordInput = document.getElementById(fieldId);
            if (!passwordInput) return;

            // Create wrapper div
            const wrapper = document.createElement('div');
            wrapper.className = 'password-field-wrapper';
            
            // Move the input into the wrapper
            passwordInput.parentNode.insertBefore(wrapper, passwordInput);
            wrapper.appendChild(passwordInput);

            // Create error div if it doesn't exist
            let errorDiv = passwordInput.nextElementSibling;
            if (!errorDiv || !errorDiv.classList.contains('error')) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'error';
                wrapper.appendChild(errorDiv);
            }

            // Create password toggle button
            const toggleButton = document.createElement('button');
            toggleButton.type = 'button';
            toggleButton.className = 'password-toggle';
            toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
            wrapper.appendChild(toggleButton);

            // Add click event to toggle password visibility
            toggleButton.addEventListener('click', () => {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                toggleButton.innerHTML = `<i class="fas fa-eye${type === 'password' ? '' : '-slash'}"></i>`;
            });
        });
    }

    attachFormListener() {
        console.log('Attaching form listener');
        const form = document.getElementById('registerForm');
        if (!form) {
            console.error('Register form not found!');
            return;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('Form submitted');

            try {
                const formData = new FormData(form);
                console.log('Form data:', Object.fromEntries(formData));

                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                console.log('Response received:', response);
                const data = await response.json();
                console.log('Response data:', data);

                // Clear previous errors
                document.querySelectorAll('.error').forEach(el => el.textContent = '');
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                if (data.success) {
                    this.showModal(data.message, true);
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    if (data.errors) {
                        // Show field-specific errors
                        Object.entries(data.errors).forEach(([field, message]) => {
                            const input = form.querySelector(`[name="${field}"]`);
                            if (input) {
                                const wrapper = input.closest('.password-field-wrapper') || input.parentNode;
                                const errorElement = wrapper.querySelector('.error');
                                if (errorElement) {
                                    errorElement.textContent = message;
                                    input.classList.add('is-invalid');
                                }
                            }
                        });
                    }
                    // Show general error message
                    this.showModal(data.message || 'Please correct the errors in the form.');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showModal('An error occurred. Please try again later.');
            }
        });
    }

    attachImagePreview() {
        console.log('Attaching image preview');
        const fileInput = document.getElementById('profile_picture');
        const preview = document.getElementById('preview');

        if (!fileInput || !preview) {
            console.error('File input or preview element not found!');
            return;
        }

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('d-none');
            }
        });
    }
}

// Initialize the handler when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing RegisterHandler');
    new RegisterHandler();
}); 