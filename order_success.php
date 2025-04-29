<?php
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    $_SESSION['error'] = "Order ID not provided";
    header("Location: cart.php");
    exit();
}

$order_id = $conn->real_escape_string($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Get order details with error handling
$order_sql = "SELECT o.*, u.email 
             FROM orders o 
             JOIN users u ON o.user_id = u.id 
             WHERE o.id = '$order_id' AND o.user_id = '$user_id'";
$order_result = $conn->query($order_sql);

if (!$order_result) {
    die("Database error: " . $conn->error);
}

if ($order_result->num_rows == 0) {
    $_SESSION['error'] = "Order not found or doesn't belong to you";
    header("Location: cart.php");
    exit();
}

$order = $order_result->fetch_assoc();

// Get order items with error handling
$items_sql = "SELECT oi.*, g.name, g.image_path, g.category 
             FROM order_items oi 
             JOIN gadgets g ON oi.gadget_id = g.id 
             WHERE oi.order_id = '$order_id'";
$items_result = $conn->query($items_sql);

if (!$items_result) {
    die("Database error: " . $conn->error);
}

$order_items = [];
$total_items = 0;
while ($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
    $total_items += $item['quantity'];
}

// Get similar products based on categories in this order
$categories_in_order = array_unique(array_column($order_items, 'category'));
$similar_products_sql = "SELECT * FROM gadgets WHERE category IN ('" . implode("','", $categories_in_order) . "') 
                        AND id NOT IN (SELECT gadget_id FROM order_items WHERE order_id = '$order_id')
                        AND stock > 0 ORDER BY RAND() LIMIT 4";
$similar_products = $conn->query($similar_products_sql);

// Send confirmation email
require 'vendor/autoload.php'; // Make sure to include PHPMailer

$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'nkmoviestheater@gmail.com';
    $mail->Password   = 'clao qnfn wyfl tlmp';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('nkmoviestheater@gmail.com', 'Hamro ElectroStore');
    $mail->addAddress($order['email'], $_SESSION['name']);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your Order #' . $order_id . ' is Confirmed!';
    
    $mail->Body = '
    <div style="max-width:600px; margin:auto; font-family:Arial, sans-serif; background-color:#121212; color:#f0f0f0; padding:30px; border-radius:10px;">
        <div style="text-align:center; padding-bottom:15px; border-bottom:1px solid #333;">
            <h2 style="color:#8f94fb; margin-bottom:5px;">🔌 Hamro ElectroStore</h2>
            <p style="font-size:14px; color:#ccc;">Your trusted tech & gadget partner</p>
        </div>

        <div style="background-color:#1e1e1e; padding:25px; border-radius:8px; margin-top:20px; box-shadow:0 0 15px rgba(0,0,0,0.3);">
            <h3 style="color:#8f94fb; margin-top:0;">✅ Order Confirmed!</h3>
            <p style="font-size:15px;">Hello <strong>'.htmlspecialchars($_SESSION['name']).'</strong>,</p>
            <p style="font-size:15px;">Thank you for your order! We\'re preparing your items.</p>

            <div style="background-color:#24243e; padding:15px; border-radius:6px; margin:20px 0; border-left:4px solid #4e44ce;">
                <h4 style="margin-top:0; color:#8f94fb;">📦 Order #'.htmlspecialchars($order_id).'</h4>
                <p style="margin-bottom:5px;"><strong>Date:</strong> '.date('M j, Y', strtotime($order['order_date'])).'</p>
                <p style="margin-bottom:5px;"><strong>Total:</strong> Rs.'.number_format($order['total_amount'], 2).'</p>
                <p style="margin-bottom:0;"><strong>Items:</strong> '.$total_items.'</p>
                 <p style="margin-bottom:5px;"><strong>Payment Method:</strong> 
    <span style="background-color:rgb(0, 0, 0); padding: 3px 8px; border-radius: 4px; display: inline-block;">
        '.htmlspecialchars(ucwords($order['payment_method'])).'
    </span>
</p>
            </div>

            <div style="background-color:#2a2a3c; padding:15px; border-radius:6px; margin:20px 0;">
                <h4 style="margin-top:0; color:#8f94fb;">⚠️ Important Notice</h4>
                <p style="margin-bottom:0;">Please collect your order by paying the charge amount when it arrives at our delivery location.</p>
            </div>

            <div style="text-align:center; margin:25px 0;">
                <a href="http://yourwebsite.com/user_orders.php" style="background-color:#4e44ce; color:white; padding:12px 25px; text-decoration:none; border-radius:5px; font-weight:bold; display:inline-block;">View Your Order</a>
            </div>

            <p style="font-size:14px; color:#bbb; border-top:1px solid #333; padding-top:15px; margin-bottom:5px;">
                Need help? Contact us at <a href="mailto:support@hamroelectro.com" style="color:#8f94fb;">support@hamroelectro.com</a> or call +977 9816767996
            </p>
        </div>

        <div style="margin-top:30px; text-align:center; font-size:12px; color:#888;">
            &copy; '.date("Y").' Hamro ElectroStore • Kapan, Kathmandu
        </div>
    </div>';
    $mail->AltBody = 
    "🔌 Hamro ElectroStore\n" .
    "Your trusted tech & gadget partner\n\n" .
    
    "✅ Order Confirmed!\n\n" .
    "Hello ".$_SESSION['name'].",\n" .
    "Thank you for your order! We're preparing your items.\n\n" .
    
    "📦 Order Details\n" .
    "Order #: ".$order_id."\n" .
    "Date: ".date('M j, Y', strtotime($order['order_date']))."\n" .
    "Total: Rs.".number_format($order['total_amount'], 2)."\n" .
    "Items: ".$total_items."\n\n" .
    "Payment Method: ".$order['payment_method']."\n" .
    
    "⚠️ Important Notice:\n" .
    "Please collect your order by paying the charge amount when it arrives at our delivery location.\n\n" .
    
    "👉 View Your Order:\n" .
    "http://localhost/ecommerce-site/user_orders.php\n\n" .
    
    "Need help? Contact us at support@hamroelectro.com or call +977 9816767996\n\n" .
    
    "© ".date("Y")." Hamro ElectroStore • Kapan, Kathmandu";
    
    $mail->send();
} catch (Exception $e) {
    // Log error but don't show to user
    error_log("Mailer Error: " . $mail->ErrorInfo);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Confirmation - Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

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

        .order-card {
            background-color: #2c2f48;
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .product-img {
            height: 80px;
            width: 80px;
            object-fit: contain;
        }

        .table {
            color: white;
        }

        .table th {
            color: #8f94fb;
            border-bottom: 1px solid #4e44ce;
        }

        .table td {
            border-bottom: 1px solid #3a3a5a;
            vertical-align: middle;
        }

        .badge-category {
            background-color: #4e44ce;
        }

        .similar-products .card {
            background-color: #2c2f48;
            border: none;
            border-radius: 15px;
            transition: transform 0.3s;
            color: white;
        }

        .similar-products .card:hover {
            transform: translateY(-5px);
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

        .timeline-content {
            padding: 15px;
            border-radius: 10px;
            color: white;
        }

        .contact-info {
            background-color: #2c2f48;
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            color: white;
        }

        .order-summary {
            background-color: #2c2f48;
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            color: white;
        }

        .bg-dark-custom {
            background-color: #1e1e2f;
            color: white;
        }

        .text-muted {
            color: #b3b3b3 !important;
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
                        <a class="nav-link" href="about.php">About Us</a>
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
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="order-card">
                    <div class="text-center mb-5">
                        <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                        <h1 class="mb-3">Order Confirmed!</h1>
                        <p class="lead">Thank you for your purchase, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
                        <p class="mb-4">Your order #<?php echo htmlspecialchars($order_id); ?> has been placed successfully.</p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="products.php" class="btn btn-primary px-4">
                                <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
                            </a>
                            <a href="user_orders.php" class="btn btn-outline-light px-4">
                                <i class="fas fa-clipboard-list me-2"></i> View All Orders
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Order Summary -->
                        <div class="col-md-6">
                            <div class="order-summary">
                                <h4 class="mb-4"><i class="fas fa-receipt me-2"></i>Order Summary</h4>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Order Number:</span>
                                    <strong>#<?php echo htmlspecialchars($order_id); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Order Date:</span>
                                    <strong><?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Items:</span>
                                    <strong><?php echo $total_items; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Payment Method:</span>
                                    <strong><?php echo ucfirst(htmlspecialchars($order['payment_method'])); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Order Status:</span>
                                    <strong class="text-success">Confirmed</strong>
                                </div>
                                <hr class="my-3">
                                <div class="d-flex justify-content-between fw-bold fs-5">
                                    <span>Total Amount:</span>
                                    <span>Rs.<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                                <hr>
                                <h6>Make sure to pick your gadget from our delivery by paying the charge amount.</h6>
                            </div>

                            <!-- Order Timeline -->
                            <div class="mt-4">
                                <h5 class="mb-4"><i class="fas fa-history me-2"></i>Order Status</h5>
                                <div class="timeline">
                                    <div class="timeline-step">
                                        <div class="timeline-icon">
                                            <i class="fas fa-check text-white"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>Order Confirmed</h6>
                                            <p class="mb-0 text-muted">We've received your order</p>
                                            <small class="text-muted"><?php echo date('M j, g:i a', strtotime($order['order_date'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="timeline-icon">
                                            <i class="fas fa-box text-white"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>Processing</h6>
                                            <p class="mb-0 text-muted">We're preparing your order</p>
                                            <small class="text-muted">Expected: <?php echo date('M j', strtotime('+1 day')); ?></small>
                                        </div>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="timeline-icon bg-secondary">
                                            <i class="fas fa-truck text-white"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>Shipped</h6>
                                            <p class="mb-0 text-muted">Your order is on the way</p>
                                            <small class="text-muted">Expected: <?php echo date('M j', strtotime('+3 days')); ?></small>
                                        </div>
                                    </div>
                                    <div class="timeline-step">
                                        <div class="timeline-icon bg-secondary">
                                            <i class="fas fa-home text-white"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>Delivered</h6>
                                            <p class="mb-0 text-muted">Your order has arrived</p>
                                            <small class="text-muted">Expected: <?php echo date('M j', strtotime('+5 days')); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Information -->
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h4 class="mb-4"><i class="fas fa-truck me-2"></i>Shipping Information</h4>
                                <div class="p-3 bg-dark-custom rounded">
                                    <h5>UserName:<?php echo htmlspecialchars($_SESSION['name']); ?></h5>
                                    <p class="mb-1">Shipping Address: </address><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                    <p class="mb-1">Phone: <?php echo htmlspecialchars($order['contact_number']); ?></p>
                                    <p class="mb-0">Email: <?php echo htmlspecialchars($order['email']); ?></p>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div>
                                <h4 class="mb-4"><i class="fas fa-box-open me-2"></i>Order Items (<?php echo count($order_items); ?>)</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Qty</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($order_items as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="product-img me-3">
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                                <small class="text-muted"><?php echo htmlspecialchars($item['category']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>Rs.<?php echo number_format($item['unit_price'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                    <td>Rs.<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                                <td class="fw-bold">Rs.<?php echo number_format($order['total_amount'], 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Shipping:</td>
                                                <td class="fw-bold text-success">Free (All over Nepal)</td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Total:</td>
                                                <td class="fw-bold">Rs.<?php echo number_format($order['total_amount'], 2); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Similar Products -->
                    <?php if ($similar_products && $similar_products->num_rows > 0): ?>
                        <div class="similar-products mt-5">
                            <h4 class="mb-4">You Might Also Like</h4>
                            <div class="row row-cols-2 row-cols-md-4 g-3">
                                <?php while ($product = $similar_products->fetch_assoc()): ?>
                                    <div class="col">
                                        <div class="card h-100 p-2">
                                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            <div class="card-body p-2">
                                                <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold">Rs.<?php echo number_format($product['price'], 2); ?></span>
                                                    <span class="badge badge-category"><?php echo htmlspecialchars($product['category']); ?></span>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent border-0 p-2">
                                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary w-100">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Contact Information -->
                    <div class="contact-info mt-5">
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
                                    <i class="fas fa-map-marker-alt me-2"></i> <a href="#" class="text-white">View on Map</a>
                                </address>
                                <div class="social-links mt-3">
                                    <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                                    <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php $conn->close(); ?>