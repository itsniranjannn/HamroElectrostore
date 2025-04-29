<?php
session_start();

include 'db.php';

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Please login to add items to cart";
        header("Location: login.php");
        exit();
    }

    $gadget_id = $conn->real_escape_string($_POST['gadget_id']);
    $quantity = $conn->real_escape_string($_POST['quantity']);
    $user_id = $_SESSION['user_id'];

    // Check if item already exists in cart
    $check_sql = "SELECT * FROM cart WHERE user_id = '$user_id' AND gadget_id = '$gadget_id'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        // Update existing cart item
        $update_sql = "UPDATE cart SET quantity = quantity + '$quantity' 
                      WHERE user_id = '$user_id' AND gadget_id = '$gadget_id'";
        if ($conn->query($update_sql)) {
            $_SESSION['message'] = "Cart updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating cart: " . $conn->error;
        }
    } else {
        // Insert new cart item
        $insert_sql = "INSERT INTO cart (user_id, gadget_id, quantity) 
                      VALUES ('$user_id', '$gadget_id', '$quantity')";
        if ($conn->query($insert_sql)) {
            $_SESSION['message'] = "Item added to cart successfully!";
        } else {
            $_SESSION['error'] = "Error adding to cart: " . $conn->error;
        }
    }
    
    header("Location: products.php");
    exit();
}

// Handle direct purchase (Buy Now)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buy_now'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Please login to make a purchase";
        header("Location: login.php");
        exit();
    }

    $gadget_id = $conn->real_escape_string($_POST['gadget_id']);
    $quantity = $conn->real_escape_string($_POST['quantity']);
    $user_id = $_SESSION['user_id'];

    // Get gadget details
    $gadget_result = $conn->query("SELECT id, name, price, stock FROM gadgets WHERE id = '$gadget_id'");
    if ($gadget_result->num_rows == 0) {
        $_SESSION['error'] = "Product not found";
        header("Location: products.php");
        exit();
    }

    $gadget = $gadget_result->fetch_assoc();
    
    // Check stock availability
    if ($gadget['stock'] < $quantity) {
        $_SESSION['error'] = "Not enough stock available";
        header("Location: products.php");
        exit();
    }

    // Store the purchase details in session for checkout
    $_SESSION['checkout_items'] = [
        [
            'gadget_id' => $gadget['id'],
            'name' => $gadget['name'],
            'quantity' => $quantity,
            'price' => $gadget['price'],
            'total' => $gadget['price'] * $quantity
        ]
    ];
    
    // Redirect to checkout page
    header("Location: checkout.php");
    exit();
}

// Get all gadgets with filtering
$category_filter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'newest';

$sql = "SELECT * FROM gadgets WHERE stock > 0";

if (!empty($category_filter) && $category_filter != 'All') {
    $sql .= " AND category = '$category_filter'";
}

if (!empty($search_query)) {
    $sql .= " AND (name LIKE '%$search_query%' OR description LIKE '%$search_query%')";
}

// Add sorting
switch ($sort_by) {
    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY name ASC";
        break;
    default: // newest
        $sql .= " ORDER BY created_at DESC";
        break;
}

$gadgets = $conn->query($sql);

// Get categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM gadgets");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products - Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="fav.png" type="image/x-icon">
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
        .content {
            padding: 40px;
        }
        .section-title {
            margin-bottom: 30px;
            font-size: 1.8rem;
            font-weight: bold;
        }
        .card {
            background-color: #2c2f48;
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            color: white;
            transition: transform 0.3s;
            height: 100%;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-img-top {
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            height: 200px;
            object-fit: cover;
        }
        .card-body {
            display: flex;
            flex-direction: column;
        }
        .card-title {
            font-weight: 700;
            margin-bottom: 10px;
        }
        .card-text {
            flex-grow: 1;
            margin-bottom: 15px;
        }
        .badge-category {
            background-color: #4e44ce;
        }
        .badge-stock {
            background-color: #28a745;
        }
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 300px;
        }
        .search-box {
            max-width: 400px;
        }
        .product-img {
            max-height: 300px;
            object-fit: contain;
        }
        .quantity-input {
            width: 70px;
            display: inline-block;
        }
        .sort-dropdown .dropdown-menu {
            background-color: #2c2f48;
            border: 1px solid #4e44ce;
        }
        .sort-dropdown .dropdown-item {
            color: #ccc;
        }
        .sort-dropdown .dropdown-item:hover {
            background-color: #4e44ce;
            color: white;
        }
        .filter-btn.active {
            background-color: #4e44ce;
            color: white;
        }
        .btn-cart {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .btn-buy {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
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
                    <a class="nav-link active" href="products.php">Products</a>
                </li>
                <li class="nav-item">
                        <a class="nav-link active" href="user_orders.php">My Orders</a>
                    </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">About Us</a>
                </li>
            </ul>
            <div class="d-flex">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cart.php" class="btn btn-outline-light me-2 position-relative">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php 
                        $cart_count = 0;
                        if (isset($_SESSION['user_id'])) {
                            $user_id = $_SESSION['user_id'];
                            $count_result = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = '$user_id'");
                            if ($count_result && $count_row = $count_result->fetch_assoc()) {
                                $cart_count = $count_row['total'] ? $count_row['total'] : 0;
                            }
                        }
                        if ($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cart_count; ?>
                            </span>
                        <?php endif; ?>
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

<!-- Content -->
<div class="content">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="section-title">Our Products</div>
    
    <!-- Search and Filter Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="" method="GET" class="mb-3">
                <div class="input-group search-box">
                    <input type="text" class="form-control" placeholder="Search products..." name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    <?php if (!empty($search_query) || !empty($category_filter)): ?>
                        <a href="products.php" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-sort me-1"></i>
                    <?php 
                        echo match($sort_by) {
                            'price_low' => 'Price: Low to High',
                            'price_high' => 'Price: High to Low',
                            'name' => 'Name: A-Z',
                            default => 'Sort By'
                        };
                    ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end sort-dropdown">
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>">Newest First</a></li>
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>">Price: Low to High</a></li>
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>">Price: High to Low</a></li>
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name'])); ?>">Name: A-Z</a></li>
                </ul>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-filter me-1"></i>
                    <?php echo !empty($category_filter) ? $category_filter : 'Filter by Category'; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_diff_key($_GET, ['category' => ''])); ?>">All Categories</a></li>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['category' => $cat['category']])); ?>"><?php echo htmlspecialchars($cat['category']); ?></a></li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Product Grid -->
    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
        <?php if ($gadgets->num_rows > 0): ?>
            <?php while ($gadget = $gadgets->fetch_assoc()): ?>
                <div class="col">
                    <div class="card h-100">
                        <?php if ($gadget['image_path']): ?>
                            <img src="<?php echo $gadget['image_path']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($gadget['name']); ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-dark d-flex align-items-center justify-content-center">
                                <i class="fas fa-image fa-5x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title"><?php echo htmlspecialchars($gadget['name']); ?></h5>
                                <span class="badge badge-category"><?php echo htmlspecialchars($gadget['category']); ?></span>
                            </div>
                            <p style="color: #ccc;"><?php echo htmlspecialchars(substr($gadget['description'], 0, 100)); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Rs.<?php echo number_format($gadget['price'], 2); ?></h6>
                                <span class="badge bg-<?php echo $gadget['stock'] > 0 ? 'success' : 'danger'; ?>">
                                    <?php echo $gadget['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                </span>
                            </div>
                            
                            <!-- View Details Button (triggers modal) -->
                            <button type="button" class="btn btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#productModal<?php echo $gadget['id']; ?>">
                                <i class="fas fa-eye me-1"></i> View Details
                            </button>
                            
                            <!-- Purchase Form (only if in stock) -->
                            <?php if ($gadget['stock'] > 0): ?>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <form method="POST" class="mt-auto">
                                        <input type="hidden" name="gadget_id" value="<?php echo $gadget['id']; ?>">
                                        <div class="input-group mb-2">
                                            <input type="number" class="form-control quantity-input" name="quantity" value="1" min="1" max="<?php echo $gadget['stock']; ?>">
                                            <button type="submit" name="add_to_cart" class="btn btn-outline-primary btn-cart">
                                                <i class="fas fa-cart-plus me-1"></i> Add
                                            </button>
                                            <button type="submit" name="buy_now" class="btn btn-primary btn-buy">
                                                <i class="fas fa-bolt me-1"></i> Buy
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary mt-auto">
                                        <i class="fas fa-shopping-cart me-1"></i> Login to Purchase
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-secondary mt-auto" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Product Modal -->
                <div class="modal fade" id="productModal<?php echo $gadget['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content bg-dark text-white">
                            <div class="modal-header border-0">
                                <h5 class="modal-title"><?php echo htmlspecialchars($gadget['name']); ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?php if ($gadget['image_path']): ?>
                                            <img src="<?php echo $gadget['image_path']; ?>" class="img-fluid product-img rounded" alt="<?php echo htmlspecialchars($gadget['name']); ?>">
                                        <?php else: ?>
                                            <div class="bg-secondary d-flex align-items-center justify-content-center" style="height: 300px;">
                                                <i class="fas fa-image fa-5x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <p><span class="badge badge-category"><?php echo htmlspecialchars($gadget['category']); ?></span></p>
                                        <h3 class="mb-3">Rs.<?php echo number_format($gadget['price'], 2); ?></h3>
                                        <p class="text-muted">Available: <?php echo $gadget['stock']; ?> in stock</p>
                                        <hr>
                                        <h6>Description</h6>
                                        <p><?php echo nl2br(htmlspecialchars($gadget['description'])); ?></p>
                                        <hr>
                                        <?php if ($gadget['stock'] > 0): ?>
                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="gadget_id" value="<?php echo $gadget['id']; ?>">
                                                    <div class="input-group mb-3">
                                                        <input type="number" class="form-control quantity-input" name="quantity" value="1" min="1" max="<?php echo $gadget['stock']; ?>">
                                                        <button type="submit" name="add_to_cart" class="btn btn-outline-primary btn-cart">
                                                            <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                                        </button>
                                                        <button type="submit" name="buy_now" class="btn btn-primary btn-buy">
                                                            <i class="fas fa-bolt me-1"></i> Buy Now
                                                        </button>
                                                    </div>
                                                </form>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-primary">
                                                    <i class="fas fa-shopping-cart me-1"></i> Login to Purchase
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" disabled>Out of Stock</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card text-center p-5">
                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                    <h3>No products found</h3>
                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                    <a href="products.php" class="btn btn-primary">Clear Filters</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>
</body>
</html>
<?php $conn->close(); ?>