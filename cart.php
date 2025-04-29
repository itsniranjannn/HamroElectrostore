<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$role = $_SESSION['role'];

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_quantity'])) {
        $gadget_id = $conn->real_escape_string($_POST['gadget_id']);
        $quantity = $conn->real_escape_string($_POST['quantity']);
        
        // Validate quantity
        if ($quantity > 0) {
            $update_sql = "UPDATE cart SET quantity = '$quantity' 
                          WHERE user_id = '$user_id' AND gadget_id = '$gadget_id'";
            $conn->query($update_sql);
            $_SESSION['message'] = "Cart updated successfully!";
        } else {
            // Remove item if quantity is 0
            $delete_sql = "DELETE FROM cart 
                          WHERE user_id = '$user_id' AND gadget_id = '$gadget_id'";
            $conn->query($delete_sql);
            $_SESSION['message'] = "Item removed from cart!";
        }
    } elseif (isset($_POST['remove_item'])) {
        $gadget_id = $conn->real_escape_string($_POST['gadget_id']);
        $delete_sql = "DELETE FROM cart 
                      WHERE user_id = '$user_id' AND gadget_id = '$gadget_id'";
        $conn->query($delete_sql);
        $_SESSION['message'] = "Item removed from cart!";
    }
    
    header("Location: cart.php");
    exit();
}

// Fetch cart items from database
$cartItems = [];
$total = 0;

$sql = "SELECT g.id, g.name, g.price, g.image_path, c.quantity 
        FROM cart c 
        JOIN gadgets g ON c.gadget_id = g.id 
        WHERE c.user_id = '$user_id'";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $cartItems[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart - Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="fav.png" type="image/x-icon">
    <style>
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
        .card {
            background-color: #2c2f48;
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            color: white;
        }
        .table {
            color: white;
        }
        .table thead {
            color: #8f94fb;
            border-bottom: 1px solid #4e44ce;
        }
        .table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-checkout {
            background-color: #4e54c8;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .btn-checkout:hover {
            background-color: #8f94fb;
            transform: translateY(-2px);
        }
        .quantity-input {
            width: 70px;
            text-align: center;
            background-color: #1e1e2f;
            border: 1px solid #4e44ce;
            color: white;
        }
        .btn-remove {
            background-color: #dc3545;
            border: none;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .btn-remove:hover {
            background-color: #bb2d3b;
        }
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 300px;
        }
        .product-img {
            height: 100px;
            width: 100px;
            object-fit: contain;
            border-radius: 10px;
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
                    <a class="nav-link active" href="cart.php">Cart</a>
                </li>
                <li class="nav-item">
                        <a class="nav-link active" href="user_orders.php">My Orders</a>
                    </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php">About Us</a>
                </li>
                <?php if ($role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex">
                <a href="cart.php" class="btn btn-outline-light me-2 position-relative">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <?php if (count($cartItems) > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo count($cartItems); ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="logout.php" class="btn btn-outline-danger me-2">Logout</a>
                <a href="profile.php" class="btn btn-outline-light">
                    <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($name); ?>
                </a>
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

    <h2 class="mb-4">Your Shopping Cart</h2>

    <?php if (empty($cartItems)): ?>
        <div class="card text-center p-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h3>Your cart is empty</h3>
            <p  style="color: white;" >Looks like you haven't added any items to your cart yet</p>
            <a href="products.php" class="btn btn-primary">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Details</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td>
                                <img src="<?php echo $item['image_path']; ?>" class="product-img" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </td>
                            <td>
                                <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                            </td>
                            <td>Rs.<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="gadget_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" class="form-control quantity-input" name="quantity" 
                                           value="<?php echo $item['quantity']; ?>" min="1">
                                    <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary mt-2">
                                        Update
                                    </button>
                                </form>
                            </td>
                            <td>Rs.<?php echo number_format($item['subtotal'], 2); ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="gadget_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="remove_item" class="btn btn-remove btn-sm">
                                        <i class="fas fa-trash-alt"></i> Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="4" class="text-end fw-bold">Total</td>
                        <td colspan="2" class="fw-bold text-success">Rs.<?php echo number_format($total, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="text-end mt-4">
            <a href="products.php" class="btn btn-outline-light me-2">
                <i class="fas fa-arrow-left me-1"></i> Continue Shopping
            </a>
            <a href="checkout.php" class="btn btn-checkout">
                <i class="fas fa-credit-card me-1"></i> Proceed to Checkout
            </a>
        </div>
    <?php endif; ?>
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
<?php $conn->close(); 
?>