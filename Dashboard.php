<?php
session_start()
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="Dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="wrapper">
        <!-- Top Navigation Bar -->
        <nav class="top-nav">
            <div class="nav-left">
                <i class="fas fa-bars toggle-sidebar" data-bs-toggle="offcanvas" data-bs-target="#offcanvasScrolling" aria-controls="offcanvasScrolling"></i>
                <h4 class="nav-title">Dashboard</h4>
            </div>
            <div class="nav-right">
              
                <div class="user-profile">
                    <!-- <img src="https://via.placeholder.com/40" alt="User" class="user-avatar"> -->
                    <span class="user-name">John Doe</span>
                </div>
            </div>
        </nav>

        <div class="offcanvas offcanvas-start" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="offcanvasScrolling" aria-labelledby="offcanvasScrollingLabel">
            <div class="offcanvas-header">s
                <h5 class="offcanvas-title" id="offcanvasScrollingLabel">Dashboard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="nav-menu">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="#dashboard" class="nav-link active">
                               
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#profile" class="nav-link">
                               
                                <span>Profile</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#orders" class="nav-link">
                             
                                <span>Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#settings" class="nav-link">
                                
                                <span>Settings</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <main>
            <div class="container1">
                <div id="dashboard" class="content-section active">
                    <h1>Dashboard</h1>
                    <p>Welcome to your dashboard</p>
                </div>
                <div id="profile" class="content-section">
                    <h1>Profile</h1>
                    <p>Your profile information</p>
                </div>
                <div id="orders" class="content-section">
                    <h1>Orders</h1>
                    <p>Your order history</p>
                </div>
                <div id="settings" class="content-section">
                    <h1>Settings</h1>
                    <p>Account settings</p>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to scroll to top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Scroll to top when page loads
        window.addEventListener('load', scrollToTop);

        // Handle navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
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
    </script>
</body>

</html>