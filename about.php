<?php
session_start();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            background: linear-gradient(to right, #0f0c29, #302b63, #24243e);
            color: white;
            font-family: 'Roboto', sans-serif;
        }
        .navbar {
            background-color: #1e1e2f;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            color: #ffffff;
        }
        .nav-link {
            color: #ccc !important;
            transition: color 0.3s;
        }
        .nav-link:hover {
            color: #ffffff !important;
        }
        .content-section {
            background-color: #2c2f48;
            border-radius: 20px;
            padding: 40px;
            margin: 30px 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        .section-title {
            margin-bottom: 30px;
            font-size: 2rem;
            font-weight: bold;
            position: relative;
            padding-bottom: 15px;
        }
        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 80px;
            height: 4px;
            background: #4e44ce;
        }
        .team-card {
            background-color: #3a3a5a;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .team-card:hover {
            transform: translateY(-5px);
        }
        .team-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #4e44ce;
            margin: 0 auto 15px;
        }
        .contact-info {
            background-color: #3a3a5a;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #4e44ce;
            margin-bottom: 15px;
        }
        .timeline {
            position: relative;
            padding-left: 50px;
            margin: 30px 0;
        }
        .timeline:before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #4e44ce;
        }
        .timeline-step {
            position: relative;
            margin-bottom: 30px;
        }
        .timeline-step:last-child {
            margin-bottom: 0;
        }
        .timeline-icon {
            position: absolute;
            left: -40px;
            top: 0;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #4e44ce;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .map-container {
            border-radius: 15px;
            overflow: hidden;
            height: 300px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="home.php"><i class="fas fa-laptop me-2"></i>Hamro ElectroStore</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="home.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="about.php">About Us</a>
                </li>
                <li class="nav-item">
                        <a class="nav-link active" href="user_orders.php">My Orders</a>
                </li>
            </ul>
            <div class="d-flex">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cart.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-shopping-cart"></i> Cart
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger me-2">Logout</a>
                    <a href="profile.php" class="btn btn-outline-light">
                        <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['name']); ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container py-5">
    <div class="content-section">
        <h1 class="section-title">About Hamro ElectroStore</h1>
        <div class="row">
            <div class="col-lg-6">
                <h3 class="mb-4">Our Story</h3>
                <p>Founded in 2024, Hamro ElectroStore began as a small electronics shop in Kathmandu with a big vision - to bring the latest technology to every home in Nepal at affordable prices.</p>
                <p>What started as a single retail outlet has now grown into one of Nepal's leading e-commerce platforms for electronics and gadgets, serving thousands of satisfied customers across the country.</p>
                <p>We take pride in offering genuine products with warranty, competitive prices, and exceptional customer service that has become our trademark.</p>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1556740738-b6a63e27c4df?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80" class="img-fluid rounded" alt="Our Store">
            </div>
        </div>
    </div>

    <div class="content-section">
        <h2 class="section-title">Why Choose Us</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>Authentic Products</h4>
                    <p>100% genuine products with manufacturer warranty. No fakes, no compromises.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <div class="feature-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h4>Fast Delivery</h4>
                    <p>Same-day delivery in Kathmandu valley, 2-5 days nationwide shipping.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h4>24/7 Support</h4>
                    <p>Dedicated customer support team available round the clock to assist you.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="content-section">
        <h2 class="section-title">Our Journey</h2>
        <div class="timeline">
            <div class="timeline-step">
                <div class="timeline-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="timeline-content">
                    <h4>2024 October - Humble Beginnings</h4>
                    <p>Opened our first physical store in Kapan, Kathmandu with just 3 employees.</p>
                </div>
            </div>
            <div class="timeline-step">
                <div class="timeline-icon">
                    <i class="fas fa-award"></i>
                </div>
                <div class="timeline-content">
                    <h4>2024 December - Award Winning</h4>
                    <p>Recognized as "Best Emerging Electronics Retailer" by Nepal Business Awards.</p>
                </div>
            </div>
            <div class="timeline-step">
                <div class="timeline-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="timeline-content">
                    <h4>2025s January - Online Expansion</h4>
                    <p>Launched our e-commerce platform to serve customers across Nepal.</p>
                </div>
            </div>
            <div class="timeline-step">
                <div class="timeline-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="timeline-content">
                    <h4>2025 April - 10,00+ Customers</h4>
                    <p>Served over 10,00 satisfied customers with 96% positive feedback.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="content-section">
        <h2 class="section-title">Meet Our Team</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="team-card text-center">
                    <img src="n.jpg" class="team-img" alt="Team Member">
                    <h4>Niranjan Katwal</h4>
                    <p class="text-muted">Founder & CEO</p>
                    <p>With 3 years in electronics retail, Niranjan leads our vision for technological accessibility. He is the one with broad vision.</p>
                </div>
            </div>
            <!-- <div class="col-md-4">
                <div class="team-card text-center">
                    <img src="https://randomuser.me/api/portraits/women/44.jpg" class="team-img" alt="Team Member">
                    <h4>Shrishika Shrestha</h4>
                    <p class="text-muted">Operations Manager</p>
                    <p>Ensures seamless operations and customer satisfaction across all channels.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-card text-center">
                    <img src="https://randomuser.me/api/portraits/men/67.jpg" class="team-img" alt="Team Member">
                    <h4>Laxman Shrestha</h4>
                    <p class="text-muted">Tech Specialist</p>
                    <p>Our product expert who tests and verifies every gadget before it reaches you.</p>
                </div>
            </div> -->
        </div>
    </div>

    <div class="content-section">
        <div class="row">
            <div class="col-md-6">
                <h4 class="mb-4"><i class="fas fa-question-circle me-2"></i>Need Help?</h4>
                <p>If you have any questions about your order, please contact our customer support team.</p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-phone me-2"></i> +977 9816767996</li>
                    <li class="mb-2"><i class="fas fa-envelope me-2"></i> support@hamroelectro.com</li>
                    <li><i class="fas fa-clock me-2"></i> Support Hours: 9AM - 6PM (Sun-Fri)</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h4 class="mb-4"><i class="fas fa-store me-2"></i>Visit Us</h4>
                <address>
                    <strong>Hamro ElectroStore</strong><br>
                    Kapan, Faika Chowk<br>
                    Kathmandu, Nepal<br>
                </address>
                <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3531.5201176413075!2d85.36119179361708!3d27.732099260885263!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb1b1ff0cd1baf%3A0x70b1345604d7465d!2sFaika%20Chowk%2C%20Kapan!5e0!3m2!1sen!2snp!4v1745575234885!5m2!1sen!2snp"
                     width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>