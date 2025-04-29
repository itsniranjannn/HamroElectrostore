<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$name = $_SESSION['name'];
$role = $_SESSION['role'];

// Database connection
$conn = new mysqli("localhost", "root", "", "electronics_store");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch featured products by category
$categories = ['Laptops', 'Smartphones', 'Accessories', 'Tablets'];
$featured_products = [];

foreach ($categories as $category) {
    $sql = "SELECT * FROM gadgets WHERE category = '$category' ORDER BY created_at DESC LIMIT 3";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $featured_products[$category] = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="fav.png" type="image/x-icon">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            background: linear-gradient(to right, #0f0c29, #302b63, #24243e);
            color: white;
            font-family: 'Roboto', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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
        .welcome-msg {
            color: #ffffff;
            font-size: 1rem;
        }
        .hero {
            text-align: center;
            padding: 100px 20px;
            background: linear-gradient(to right, #4e54c8, #8f94fb);
            border-radius: 0 0 50% 50% / 10%;
            margin-bottom: 30px;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .hero p {
            font-size: 1.2rem;
        }
        .buttons .btn {
            margin: 10px;
            padding: 12px 24px;
            font-size: 1rem;
            border-radius: 30px;
        }
        .btn-outline-light:hover {
            background-color: white;
            color: #4e54c8;
        }
        .role-badge {
            background: white;
            color: #4e54c8;
            font-weight: bold;
            padding: 5px 20px;
            border-radius: 50px;
            display: inline-block;
            margin: 15px 0;
        }
        .search-bar {
            max-width: 700px;
            margin: 40px auto;
        }
        .input-group input {
            height: 50px;
            font-size: 1rem;
            border-radius: 30px 0 0 30px;
            border: none;
            padding-left: 20px;
        }
        .input-group .btn {
            font-size: 1rem;
            border-radius: 0 30px 30px 0;
            padding: 0 25px;
            background: #4e54c8;
            border: none;
        }
        .input-group .btn:hover {
            background: #8f94fb;
        }
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 50px 0 20px;
            text-align: center;
        }
        .category-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4e54c8;
        }
        .card {
            background-color: #1e1e2f;
            color: white;
            border: none;
            border-radius: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .card img {
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            height: 200px;
            object-fit: cover;
        }
        .card-body {
            display: flex;
            flex-direction: column;
        }
        .card-title {
            font-weight: 700;
        }
        .card-text {
            flex-grow: 1;
        }
        .price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #8f94fb;
        }
        .btn-view {
            background-color: #4e54c8;
            border: none;
            width: 100%;
        }
        .btn-view:hover {
            background-color: #8f94fb;
        }
        footer {
            background-color: #1e1e2f;
            color: white;
            padding: 50px 0 20px;
            margin-top: auto;
        }
        .footer-logo {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 20px;
            display: inline-block;
        }
        .footer-links h5 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #8f94fb;
        }
        .footer-links ul {
            list-style: none;
            padding-left: 0;
        }
        .footer-links li {
            margin-bottom: 10px;
        }
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }
        .footer-links a:hover {
            color: #8f94fb;
        }
        .social-icons a {
            color: white;
            font-size: 1.5rem;
            margin-right: 15px;
            transition: color 0.3s;
        }
        .social-icons a:hover {
            color: #8f94fb;
        }
        .copyright {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
        }
        .no-products {
            text-align: center;
            padding: 30px;
            background-color: rgba(30, 30, 47, 0.5);
            border-radius: 15px;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-laptop me-2"></i>Hamro ElectroStore</a>
        <span class="welcome-msg d-none d-lg-block">Welcome, <strong><?php echo htmlspecialchars($name); ?></strong></span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <?php if ($role === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <h1>Welcome to Hamro ElectroStore</h1>
    <div class="role-badge"><?php echo strtoupper($role); ?></div>
    <p>Your one-stop destination for quality electronics.</p>
    <div class="buttons">
        <?php if ($role === 'admin'): ?>
            <a href="admin_dashboard.php" class="btn btn-light"><i class="fas fa-chart-line"></i> Admin Dashboard</a>
        <?php else: ?>
            <a href="products.php" class="btn btn-light"><i class="fas fa-shopping-cart"></i> Shop Now</a>
        <?php endif; ?>
        <a href="#featured" class="btn btn-outline-light"><i class="fas fa-star"></i> Featured Products</a>
    </div>
</section>

<!-- Search Bar -->
<div class="container search-bar">
    <form action="search.php" method="GET" class="input-group">
        <input type="text" name="query" class="form-control" placeholder="Search for electronics..." required>
        <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
    </form>
</div>

<!-- Featured Section -->
<div class="container" id="featured">
    <h2 class="section-title">Featured Products</h2>
    
    <?php foreach ($categories as $category): ?>
        <?php if (isset($featured_products[$category])): ?>
            <h3 class="category-title"><?php echo $category; ?></h3>
            <div class="row g-4 mb-5">
                <?php foreach ($featured_products[$category] as $product): ?>
                    <div class="col-md-4">
                        <div class="card">
                            <?php if ($product['image_path']): ?>
                                <img src="<?php echo $product['image_path']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <img src="images/placeholder.jpg" class="card-img-top" alt="Product Image">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                                <div class="price mb-3">Rs.<?php echo number_format($product['price'], 2); ?></div>
                                <a href="products.php"<?php echo $product['id']; ?>" class="btn btn-view">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-products">
                <h3 class="category-title"><?php echo $category; ?></h3>
                <p>No products available in this category yet.</p>
                <?php if ($role === 'admin'): ?>
                    <a href="admin_dashboard.php#addGadget" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Products
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="footer-logo"><i class="fas fa-laptop me-2"></i>Hamro ElectroStore</div>
                <p>Your trusted destination for quality electronics and gadgets at competitive prices.</p>
                <div class="social-icons mt-3">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4">
                <div class="footer-links">
                    <h5>Shop</h5>
                    <ul>
                        <li><a href="products.php?category=Laptops">Laptops</a></li>
                        <li><a href="products.php?category=Smartphones">Smartphones</a></li>
                        <li><a href="products.php?category=Tablets">Tablets</a></li>
                        <li><a href="products.php?category=Accessories">Accessories</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-2 mb-4">
                <div class="footer-links">
                    <h5>Help</h5>
                    <ul>
                        <li><a href="home.php">FAQs</a></li>
                        <li><a href="user_orders.php">Shipping</a></li>
                        <li><a href="products.php">Returns</a></li>
                        <li><a href="about.php">Contact Us</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="footer-links">
                    <h5>Contact Info</h5>
                    <ul>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Kapan, Kathmandu, Nepal</li>
                        <li><i class="fas fa-phone me-2"></i> +977 9816767996</li>
                        <li><i class="fas fa-envelope me-2"></i> info@hamroelectro.com</li>
                        <li><i class="fas fa-clock me-2"></i> Mon-Sat: 9AM - 6PM</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> Hamro ElectroStore. All Rights Reserved.</p>
            </div>
    </div>
</footer>

<!-- Bootstrap Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>