<?php
session_start();
include 'config.php'; // Include your database connection

// Fetch all products, ordered by category
$products_query = "SELECT * FROM tbl_products ORDER BY Category";
$products_result = $conn->query($products_query);

$products = [];
if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fetch unique categories for navigation
$categories_query = "SELECT DISTINCT Category FROM tbl_products ORDER BY Category";
$categories_result = $conn->query($categories_query);

$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row['Category'];
    }
}

$conn->close();

// You can add PHP logic here if needed, similar to Dashboard.php
// Example: Fetch user data if using the profile dropdown
// $current_user_id = $_SESSION['user_id'] ?? null;
// $currentUser = null;
// if ($current_user_id) {
//    include '../config.php'; // Adjust path as necessary
//    $currentUser_query = "SELECT full_name, email, profile_picture FROM tbl_users WHERE user_id = ? LIMIT 1";
//    $stmt = $conn->prepare($currentUser_query);
//    $stmt->bind_param("i", $current_user_id);
//    $stmt->execute();
//    $currentUser_result = $stmt->get_result();
//    $currentUser = $currentUser_result->fetch_assoc();
//    $stmt->close();
//    // $conn.close(); // Close connection if opened here - depends on your config.php
// }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="Test.css">
     <!-- Link to custom CSS for card styling -->

</head>

<body>
    <div class="wrapper">
        <!-- Top Navigation Bar -->
        <nav class="top-nav">
            <div class="nav-left">
                <!-- Toggle button for Bootstrap Offcanvas -->
                <!-- Use data-bs-toggle and data-bs-target to link to the offcanvas -->
                <button class="btn btn-outline-secondary me-2 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#testOffcanvas" aria-controls="testOffcanvas">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="nav-title">Menu</h4>
            </div>

            <!-- Centered Navigation Links for larger screens (hidden on smaller screens) -->
            <!-- This div will contain the navigation links when not in offcanvas mode -->
            <div class="d-none d-lg-flex justify-content-center flex-grow-1 category-nav">
                 <button class="btn btn-link nav-link active" data-category="all">All Categories</button>
                 <?php foreach ($categories as $category): ?>
                    <button class="btn btn-link nav-link" data-category="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></button>
                 <?php endforeach; ?>
            </div>


            <!-- user profile -->
            <div class="nav-right">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="d-flex align-items-center px-3 py-2">
                            <!-- Display user's profile picture dynamically -->
                            <!-- Note: The PHP logic here requires $currentUser variable to be available -->
                            <img src="<?php echo htmlspecialchars(!empty($currentUser['profile_picture'] ?? '') ? $currentUser['profile_picture'] ?? '' : 'uploads/no-image.png'); ?>"  width="32" height="32" class="rounded-circle me-2">
                            <div>
                                <!-- Note: The PHP logic here requires $currentUser variable to be available -->
                                <p class="mb-0 user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?></p>
                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></p>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
                         <li>
                            <div class="dropdown-item text-wrap py-2">
                                <div class="d-flex align-items-center">
                                     <!-- Display user\'s profile picture dynamically -->
                                     <!-- Note: The PHP logic here requires $currentUser variable to be available -->
                                    <img src="<?php echo htmlspecialchars(!empty($currentUser['profile_picture'] ?? '') ? $currentUser['profile_picture'] ?? '' : 'uploads/no-image.png'); ?>" alt="Profile" width="40" height="40" class="rounded-circle me-2">
                                    <div>
                                        <!-- Note: The PHP logic here requires $currentUser variable to be available -->
                                        <p class="mb-0 user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?></p>
                                        <p class="mb-0 text-muted small"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                         <li><a class="dropdown-item" href="#"><i class="fas fa-shop fa-fw me-2"></i>Your Shop</a></li>
                         <li><a class="dropdown-item" href="#"><i class="fas fa-book fa-fw me-2"></i>Documentation</a></li>
                         <li><a class="dropdown-item" href="#"><i class="fas fa-handshake fa-fw me-2"></i>Affiliate</a></li>
                         <li><a class="dropdown-item" href="#"><i class="fas fa-cog fa-fw me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Log Out</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Bootstrap Offcanvas Sidebar -->
        <div class="offcanvas offcanvas-start bg-dark" tabindex="-1" id="testOffcanvas" aria-labelledby="testOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="testOffcanvasLabel">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <!-- Navigation Links (these are the same links as in the top nav, but in the sidebar) -->
                 <div class="nav flex-column" id="testNav">
                    <button class="btn btn-link nav-link active" data-category="all">All Categories</button>
                    <?php foreach ($categories as $category): ?>
                       <button class="btn btn-link nav-link" data-category="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></button>
                    <?php endforeach; ?>
                </div>
                 <!-- You can add more content here if needed -->
            </div>
        </div>


         <div class="main-content">
            <div class="row">
                <!-- Product Display Area -->
                <div class="col-md-9 ">
                    <div id="product-list" class="row">
                        <!-- Product cards will be loaded here by JavaScript -->
                    </div>
                </div>

                <!-- Add to Cart Container -->
                <div class="col-md-3">
                    <div class="card" id="cart-container">
                        <div class="card-header">
                            <h5>Shopping Cart (<span id="cart-count">0</span>)</h5>
                        </div>
                        <ul class="list-group list-group-flush" id="cart-items">
                            <!-- Cart items will be loaded here by JavaScript -->
                             <li class="list-group-item text-muted" id="empty-cart-message">Your cart is empty</li>
                        </ul>
                        <div class="card-body">
                             <h6 class="card-title">Total: ₱<span id="cart-total">0.00</span></h6>
                            <button class="btn btn-success btn-sm" id="checkout-btn" disabled>Checkout</button>
                            <button class="btn btn-danger btn-sm" id="clear-cart-btn" disabled>Clear Cart</button>
                        </div>
                    </div>
                </div>
            </div>
         </div>
    </div>

    <!-- Bootstrap Bundle JS (includes Popper and Offcanvas JS) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Embed product data from PHP (now fetching from tbl_products again)
        const allProducts = <?php echo json_encode($products); ?>;
        // Load cart from localStorage, checking for potential old structure and clearing if necessary
        let cart = JSON.parse(localStorage.getItem('customerCart')) || [];

        // Optional: Clear localStorage cart if it seems to be in the old format
        if (cart.length > 0 && cart[0].hasOwnProperty('id') && !cart[0].hasOwnProperty('product_id')) {
             console.warn('Clearing old format cart data from localStorage.');
             cart = [];
             localStorage.removeItem('customerCart');
        }

        // Function to render product cards
        function renderProducts(productsToRender) {
            const productListDiv = document.getElementById('product-list');
            productListDiv.innerHTML = ''; // Clear current products

            if (productsToRender.length === 0) {
                 productListDiv.innerHTML = '<p>No products found in this category.</p>';
                 return;
            }

            productsToRender.forEach(product => {
                const productCardHtml = `
                    <div class="col-md-4 mb-4">
                        <div class="custom-card">
                            <img src="${product.product_image && product.product_image !== '' ? product.product_image : 'uploads/no-image.png'}" class="card-img-top" alt="${product.product_name}">
                            <div class="card-body">
                                <h5 class="card-title">${product.product_name}</h5>
                                <p class="card-category">${product.Category}</p>
                                <p class="card-text">${product.description.substring(0, 100)}...</p>
                                <p class="card-price">₱${parseFloat(product.price).toFixed(2)}</p>
                                <button class="btn btn-primary btn-sm add-to-cart-btn" data-product-id="${product.product_id}">
                                    <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                productListDiv.innerHTML += productCardHtml;
            });

            // Add event listeners to the new buttons
            document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = parseInt(this.getAttribute('data-product-id'));
                    addProductToCart(productId);
                });
            });
        }

        // Function to render cart items
        function renderCart() {
            console.log('Starting renderCart function...');
            console.log('Current cart state:', JSON.stringify(cart));
            try {
                const cartItemsList = document.getElementById('cart-items');
                const cartCountSpan = document.getElementById('cart-count');
                const cartTotalSpan = document.getElementById('cart-total');
                const emptyCartMessage = document.getElementById('empty-cart-message');
                const checkoutBtn = document.getElementById('checkout-btn');
                const clearCartBtn = document.getElementById('clear-cart-btn');

                // Log state immediately after getting references
                console.log('State after getting references:');
                console.log('cartItemsList:', cartItemsList);
                console.log('cartCountSpan:', cartCountSpan);
                console.log('cartTotalSpan:', cartTotalSpan);
                console.log('emptyCartMessage:', emptyCartMessage);
                console.log('checkoutBtn:', checkoutBtn);
                console.log('clearCartBtn:', clearCartBtn);

                // Basic check to ensure necessary elements exist
                if (!cartItemsList || !cartCountSpan || !cartTotalSpan || !emptyCartMessage || !checkoutBtn || !clearCartBtn) {
                    console.error('Cart UI elements not found! Cannot render cart.');
                    // Log the state of each element
                    console.log('cartItemsList:', cartItemsList);
                    console.log('cartCountSpan:', cartCountSpan);
                    console.log('cartTotalSpan:', cartTotalSpan);
                    console.log('emptyCartMessage:', emptyCartMessage);
                    console.log('checkoutBtn:', checkoutBtn);
                    console.log('clearCartBtn:', clearCartBtn);
                    return;
                }

                // Clear only the dynamically added cart items, keep the empty message div
                // Find all list items that are NOT the empty message and remove them
                cartItemsList.querySelectorAll('li:not(#empty-cart-message)').forEach(item => item.remove());

                let total = 0;
                let itemCount = 0;

                console.log('Current cart state for rendering:', JSON.parse(JSON.stringify(cart)));

                if (cart.length === 0) {
                    emptyCartMessage.style.display = 'list-item';
                    checkoutBtn.disabled = true;
                    clearCartBtn.disabled = true;
                    console.log('Cart is empty. Updating UI elements.');
                } else {
                    emptyCartMessage.style.display = 'none';
                    checkoutBtn.disabled = false;
                    clearCartBtn.disabled = false;
                    console.log(`Cart has ${cart.length} items. Populating list...`);
                    cart.forEach(item => {
                         // Use the original item properties (product_id, product_name, price, quantity)
                        const itemName = item.product_name || 'Unknown Product';
                        const itemPrice = parseFloat(item.price) || 0;
                        const itemQuantity = parseInt(item.quantity) || 0;

                        if (itemQuantity <= 0) {
                           console.warn(`Skipping item with zero or negative quantity during render: ${itemName}`, item);
                           return; // Skip items with invalid quantity
                        }

                        const itemTotal = itemPrice * itemQuantity;
                        total += itemTotal;
                        itemCount += itemQuantity;
                        const cartItemHtml = `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                ${itemName}
                                <br>
                                <small class="text-muted">₱${itemPrice.toFixed(2)} x ${itemQuantity}</small>
                            </div>
                            <span>₱${itemTotal.toFixed(2)}</span>
                             <button class="btn btn-danger btn-sm ms-2 remove-from-cart-btn" data-product-id="${item.product_id}">
                                <i class="fas fa-trash"></i>
                             </button>
                        </li>
                    `;
                        cartItemsList.innerHTML += cartItemHtml;
                    });
                }

                cartCountSpan.textContent = itemCount;
                cartTotalSpan.textContent = total.toFixed(2);
                console.log(`Cart render successful. Displaying ${itemCount} item(s) with total ₱${total.toFixed(2)}.`);

                // Add event listeners to remove buttons *after* rendering the list
                console.log('Attaching remove button event listeners...');
                document.querySelectorAll('.remove-from-cart-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const productId = parseInt(this.getAttribute('data-product-id'));
                        console.log(`Remove button clicked for product ID: ${productId}`);
                        removeProductFromCart(productId);
                    });
                    console.log(`Listener attached for product ID: ${button.getAttribute('data-product-id')}`);
                });

                 // Ensure localStorage is updated *after* successful rendering
                 localStorage.setItem('customerCart', JSON.stringify(cart));
                 console.log('Cart state saved to localStorage after render.');

            } catch (error) {
                console.error('Error rendering cart:', error);
                alert('An error occurred while updating the cart display. Please check the console for details.');
            }
        }

        // Function to add product to cart
        function addProductToCart(productId) {
            console.log(`addProductToCart called with ID: ${productId}`);
            try {
                // Find the product in allProducts using product_id
                const product = allProducts.find(p => parseInt(p.product_id) === productId);
                if (!product) {
                    console.warn(`Product with ID ${productId} not found in allProducts.`);
                    throw new Error('Product not found in catalog');
                }
                console.log('Found product:', product);

                // Find the existing item in the cart using product_id
                const existingItemIndex = cart.findIndex(item => parseInt(item.product_id) === productId);
                console.log(`Existing item index for product ID ${productId}: ${existingItemIndex}`);

                if (existingItemIndex > -1) {
                    // Item already in cart, increase quantity
                    cart[existingItemIndex].quantity = parseInt(cart[existingItemIndex].quantity) + 1;
                    console.log(`Increased quantity for product ID ${productId}. New quantity: ${cart[existingItemIndex].quantity}.`);
                } else {
                    // Item not in cart, add it with initial quantity of 1
                    const newItem = {
                        product_id: productId, // Use the parsed productId
                        product_name: product.product_name,
                        price: parseFloat(product.price),
                        quantity: 1,
                        image: product.product_image || 'uploads/no-image.png'
                    };
                    cart.push(newItem);
                    console.log('Added new item to cart:', newItem);
                }

                console.log('Cart state after add/update:', JSON.parse(JSON.stringify(cart)));

                // Render and save cart state
                renderCart();
                console.log('renderCart() called after add/update.');

                // Show success message ONLY if renderCart completes without throwing an error
                alert('Product added to cart successfully!');

            } catch (error) {
                console.error('Error in addProductToCart:', error);
                alert('Failed to add product to cart: ' + error.message);
            }
        }

        // Function to remove product from cart
        function removeProductFromCart(productId) {
            console.log(`removeProductFromCart called with ID: ${productId}`);
            try {
                console.log('Cart state before removal:', JSON.parse(JSON.stringify(cart)));
                const initialLength = cart.length;
                // Filter cart based on product_id - ensure comparison is numerical
                const updatedCart = cart.filter(item => parseInt(item.product_id) !== productId);
                console.log('Cart state after filter attempt:', JSON.parse(JSON.stringify(updatedCart)));

                if (updatedCart.length !== initialLength) {
                     cart = updatedCart; // Update the main cart array
                    console.log(`Product with ID ${productId} removed. New cart length: ${cart.length}`);
                    // Render and save cart state
                    renderCart();
                     console.log('renderCart() called after removal.');
                    alert('Product removed from cart successfully!');
                } else {
                    console.warn(`Product with ID ${productId} not found in cart for removal.`);
                }
            } catch (error) {
                console.error('Error in removeProductFromCart:', error);
                alert('Failed to remove product from cart: ' + error.message);
            }
        }

        // Function to update cart item quantity (using product_id)
        function updateCartItemQuantity(productId, newQuantity) {
             console.log(`updateCartItemQuantity called for ID: ${productId}, Quantity: ${newQuantity}`);
            try {
                const parsedQuantity = parseInt(newQuantity);
                if (isNaN(parsedQuantity) || parsedQuantity < 1) {
                    console.log(`Invalid quantity: ${newQuantity}. Removing product ID ${productId} instead.`);
                    removeProductFromCart(productId);
                    return;
                }

                // Find item in cart using product_id
                const itemIndex = cart.findIndex(item => parseInt(item.product_id) === productId);
                if (itemIndex > -1) {
                    cart[itemIndex].quantity = parsedQuantity;
                     console.log(`Quantity updated for product ID ${productId}. New quantity: ${cart[itemIndex].quantity}`);
                    // Render and save cart state
                    renderCart();
                    console.log('renderCart() called after quantity update.');
                } else {
                     console.warn(`Product with ID ${productId} not found in cart for quantity update.`);
                }
            } catch (error) {
                 console.error('Error in updateCartItemQuantity:', error);
                 alert('Failed to update quantity: ' + error.message);
            }
        }

        // Function to filter and display products by category
        function filterProductsByCategory(category) {
            const filteredProducts = category === 'all'
                ? allProducts
                : allProducts.filter(product => product.Category === category);
            renderProducts(filteredProducts);

             // Update active button state for both top nav and offcanvas nav links
            document.querySelectorAll('button[data-category]').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll(`button[data-category="${category}"]`).forEach(btn => btn.classList.add('active'));
        }


        // Initial render of products and cart when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            filterProductsByCategory('all'); // Display all products initially
            renderCart(); // Render the cart

            // Add event listeners for category filters on buttons with data-category attribute
            document.querySelectorAll('button[data-category]').forEach(button => {
                button.addEventListener('click', function() {
                    const category = this.getAttribute('data-category');
                    filterProductsByCategory(category);

                    // Hide the offcanvas sidebar if it's open (on smaller screens)
                    const testOffcanvas = document.getElementById('testOffcanvas'); // Get the offcanvas element
                    // Check if offcanvasInstance exists before calling hide()
                    const offcanvasInstance = bootstrap.Offcanvas.getInstance(testOffcanvas);
                    if (offcanvasInstance) {
                        offcanvasInstance.hide();
                    }
                });
            });

             // Basic Checkout button functionality (can be expanded)
            document.getElementById('checkout-btn').addEventListener('click', function() {
                if (cart.length > 0) {
                    //alert('Proceeding to checkout with ' + cart.length + ' item(s). Total: ₱' + document.getElementById('cart-total').textContent);
                    // Here you would typically redirect to a checkout page or send data to backend

                    // Prepare data to send
                    const cartData = JSON.stringify(cart);

                    // Send cart data to backend using Fetch API
                    fetch('checkout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded', // Use this for $_POST
                        },
                         // Send as form data
                        body: 'cart=' + encodeURIComponent(cartData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Checkout successful! ' + data.message);
                            // Clear cart on success
                            cart = [];
                            renderCart(); // Update UI
                            localStorage.removeItem('customerCart'); // Clear localStorage
                        } else {
                            alert('Checkout failed: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error during checkout:', error);
                        alert('An error occurred during checkout.');
                    });

                } else {
                     alert('Your cart is empty!');
                }
            });

             // Clear Cart button functionality
            document.getElementById('clear-cart-btn').addEventListener('click', function() {
                if (confirm('Are you sure you want to clear your cart?')) {
                    cart = [];
                    renderCart();
                     // Clear localStorage after clearing cart
                    localStorage.removeItem('customerCart');
                }
            });

        });

    </script>
</body>

</html> 