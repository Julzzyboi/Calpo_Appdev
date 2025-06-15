// Function to scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Scroll to top when page loads
window.addEventListener('load', scrollToTop);

// Function to show message modal
function showMessageModal(message, isSuccess) {
    const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
    const messageBody = document.getElementById('messageModalBody');
    messageBody.innerHTML = message;
    messageBody.className = isSuccess ? 'alert alert-success' : 'alert alert-danger';
    messageModal.show();
}

// Function to handle AJAX responses
function handleAjaxResponse(response, modalToHide) {
    return response.json().then(data => {
        // Hide the triggering modal before showing the message modal
        if (modalToHide) {
            const bsModal = bootstrap.Modal.getInstance(modalToHide);
            if (bsModal) {
                bsModal.hide();
            }
        }
        // Show the message modal
        showMessageModal(data.message, data.success);

        if (data.success) {
            // Reload the page after the message modal is closed if successful
            document.getElementById('messageModal').addEventListener('hidden.bs.modal', function () {
                window.location.reload();
            }, { once: true });
        }
    }).catch(error => {
        console.error('AJAX Error:', error);
         // Hide the triggering modal even on AJAX error
         if (modalToHide) {
            const bsModal = bootstrap.Modal.getInstance(modalToHide);
            if (bsModal) {
                bsModal.hide();
            }
        }
        // Show the error message modal
        showMessageModal('An error occurred during the operation.', false);
    });
}

// Handle navigation
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();

        // Remove active class from all links
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));

        // Add active class to clicked link
        this.classList.add('active');

        // Get the target section
        const targetId = this.getAttribute('href').substring(1);
        const targetSection = document.getElementById(targetId);

        // Add fade-out class to current active section
        const currentActive = document.querySelector('.content-section.active');
        if (currentActive) {
            currentActive.classList.add('fade-out');
            currentActive.classList.remove('active');
        }

        // Wait for fade-out animation to complete
        setTimeout(() => {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
                section.classList.remove('fade-out');
            });

            // Show and animate the target section
            targetSection.style.display = 'block';
            // Force reflow
            targetSection.offsetHeight;
            targetSection.classList.add('active');

            // Load content specific to the active section
            if (targetId === 'products') {
                // Assuming loadProducts function exists and loads products for productsTable
                // You would call it here if products are dynamically loaded
            } else if (targetId === 'transactions') {
                loadTransactions(); // Load transactions when the transactions tab is active
            }
            // Scroll to top after content change
            scrollToTop();
        }, 300); // Match this with CSS transition time

        // Close sidebar on mobile after clicking
        if (window.innerWidth < 992) {
            const offcanvas = document.querySelector('.offcanvas');
            const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
            if (bsOffcanvas) {
                bsOffcanvas.hide();
            }
        }
    });
});

// Function to edit user
function editUser(userId) {
    const users = window.users; // Access the users data from the global variable
    const user = users.find(u => u.user_id == userId);
    if (user) {
        document.getElementById('edit_user_id_for_edit_modal').value = user.user_id;
        document.getElementById('edit_full_name').value = user.full_name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role_id').value = user.role_id;
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }
}

// Function to edit role
function editRole(roleId) {
    const roles = window.roles; // Access the roles data from the global variable
    const role = roles.find(r => r.role_id == roleId);
    if (role) {
        document.getElementById('edit_role_modal_role_id_hidden').value = role.role_id;
        document.getElementById('edit_role_name').value = role.role_name;
        new bootstrap.Modal(document.getElementById('editRoleModal')).show();
    }
}

// Function to delete user
function deleteUser(userId) {
    document.getElementById('delete_action').value = 'delete_user';
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_role_id').value = ''; // Clear role_id
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Function to delete role
function deleteRole(roleId) {
    document.getElementById('delete_action').value = 'delete_role';
    document.getElementById('delete_role_id').value = roleId;
    document.getElementById('delete_user_id').value = ''; // Clear user_id
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Handle Add New Product button click
document.querySelector('.card-header button.btn-primary')?.addEventListener('click', function () {
    // Reset the form and clear any previous validation states when the modal is shown
    const addProductForm = document.getElementById('addProductForm');
    if (addProductForm) {
        addProductForm.reset();
        // Manually clear any Bootstrap validation classes if used
        addProductForm.classList.remove('was-validated');
    }
    const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
    addProductModal.show();
});

// Function to load transactions into the table
function loadTransactions() {
    console.log('Loading transactions...');
    fetch('api/transactions.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#transactionsTable tbody');
            if (!tbody) {
                console.error('Transactions table tbody not found.');
                return;
            }
            tbody.innerHTML = ''; // Clear existing rows

            if (data.success && data.data.length > 0) {
                data.data.forEach(transaction => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td>${transaction.sales_id || 'N/A'}</td>
                        <td>${transaction.transaction_id || 'N/A'}</td>
                        <td>${transaction.product_name || 'N/A'}</td>
                        <td>${transaction.quantity_sold || 'N/A'}</td>
                        <td>₱${parseFloat(transaction.item_price || 0).toFixed(2)}</td>
                        <td>${transaction.customer_name || 'N/A'}</td>
                        <td>${transaction.payment_method || 'N/A'}</td>
                        <td>₱${parseFloat(transaction.sale_total || 0).toFixed(2)}</td>
                        <td>${new Date(transaction.transaction_date).toLocaleString()}</td>
                    `;
                });
                console.log(`Loaded ${data.data.length} transactions.`);
            } else if (data.success && data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center">No transactions found.</td></tr>';
                console.log('No transactions found.');
            } else {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center">Error: ${data.message}</td></tr>`;
                console.error('Error loading transactions:', data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error for transactions:', error);
            const tbody = document.querySelector('#transactionsTable tbody');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center">Failed to load transactions. Please try again.</td></tr>`;
            }
        });
}

// Update all form submissions to use the new handler
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        }).then(handleAjaxResponse);
    });
});

// Handle Clear Logs button click
document.getElementById('clearLogs')?.addEventListener('click', function() {
    if (confirm('Are you sure you want to clear all logs?')) {
        const formData = new FormData();
        formData.append('action', 'clear_logs');
        
        fetch('', {
            method: 'POST',
            body: formData
        }).then(handleAjaxResponse);
    }
});

// Initialize the page
document.addEventListener('DOMContentLoaded', function () {
    // Explicitly activate the Users tab when the DOM is fully loaded
    var usersTab = document.getElementById('users-tab');
    if (usersTab) {
        var bsTab = new bootstrap.Tab(usersTab);
        bsTab.show();
    }
}); 