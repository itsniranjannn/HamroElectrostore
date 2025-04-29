<?php
session_start();
include 'db.php';
include 'esewa_config.php';
require 'vendor/autoload.php'; // For Stripe

\Stripe\Stripe::setApiKey('sk_test_51QQkSDF5bVnd89ccv14S4NbraT5vaVK10mg8047ywCWQ8cJp5lSZ6EK0maRGmUm3WE3lV6o6mxq29irzMfByzYP700aKzTzO9I');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process the order
    $shipping_address = $conn->real_escape_string($_POST['shipping_address']);
    $contact_number = $conn->real_escape_string($_POST['contact_number']);
    $payment_method = $conn->real_escape_string($_POST['payment_method']);

    // Calculate total from cart or direct purchase items
    $total = 0;
    $cart_items = [];

    if (isset($_SESSION['checkout_items']) && !empty($_SESSION['checkout_items'])) {
        // Direct purchase items
        $cart_items = $_SESSION['checkout_items'];
        foreach ($cart_items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    } else {
        // Regular cart items
        $cart_sql = "SELECT g.id, g.name, g.price, c.quantity 
                    FROM cart c 
                    JOIN gadgets g ON c.gadget_id = g.id 
                    WHERE c.user_id = '$user_id'";
        $cart_result = $conn->query($cart_sql);

        while ($item = $cart_result->fetch_assoc()) {
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
            $cart_items[] = $item;
        }
    }

    // Set order status based on payment method
    $status = 'pending';

    // Create order with status
    $order_sql = "INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, contact_number, status)
                 VALUES ('$user_id', '$total', '$payment_method', '$shipping_address', '$contact_number', '$status')";

    if ($conn->query($order_sql)) {
        $order_id = $conn->insert_id;

        // Add order items
        foreach ($cart_items as $item) {
            $gadget_id = $item['gadget_id'] ?? $item['id']; // Handle both direct purchase and cart items
            $quantity = $item['quantity'];
            $price = $item['price'];

            $insert_sql = "INSERT INTO order_items (order_id, gadget_id, quantity, unit_price)
                          VALUES ('$order_id', '$gadget_id', '$quantity', '$price')";
            $conn->query($insert_sql);

            // Update gadget stock
            $update_sql = "UPDATE gadgets SET stock = stock - '$quantity' WHERE id = '$gadget_id'";
            $conn->query($update_sql);
        }

        // Handle payment based on method
        if ($payment_method == 'esewa') {
            // Initialize Esewa payment
            $payment_sql = "INSERT INTO payments (order_id, payment_method, amount, status)
                            VALUES ('$order_id', 'esewa', '$total', 'pending')";
            $conn->query($payment_sql);
            $payment_id = $conn->insert_id;
            
            // Esewa payment parameters
           // In the esewa payment section, update the success_url to include the payment_id
$success_url = "http://localhost/ecommerce-site/payment_success.php?order_id=$order_id&payment_id=$payment_id";
$failure_url = "http://localhost/ecommerce-site/payment_failure.php?order_id=$order_id&payment_id=$payment_id";
            
            // Generate unique transaction ID
            $transaction_uuid = "ESW" . time() . rand(1000, 9999);
            
            // Update payment with transaction ID
            $update_payment = "UPDATE payments SET transaction_id = '$transaction_uuid' WHERE id = '$payment_id'";
            $conn->query($update_payment);
            
            // Prepare data for signature
            $data_to_sign = [
                'total_amount' => $total,
                'transaction_uuid' => $transaction_uuid,
                'product_code' => ESEWA_MERCHANT_CODE
            ];
            
            // Generate signature using the function from esewa_config.php
            $signature = generateEsewaSignature($data_to_sign);
            
            // Debug the generated values
            error_log("Esewa Payment Parameters:");
            error_log("Amount: $total");
            error_log("Transaction UUID: $transaction_uuid");
            error_log("Product Code: " . ESEWA_MERCHANT_CODE);
            error_log("Signature: $signature");
            error_log("Success URL: $success_url");
            error_log("Failure URL: $failure_url");
            
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Redirecting to Esewa</title>
            </head>
            <body>
                <form id="esewaForm" action="<?php echo ESEWA_TEST_FORM_URL; ?>" method="POST">
                    <input type="hidden" name="amount" value="<?php echo $total; ?>">
                    <input type="hidden" name="tax_amount" value="0">
                    <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
                    <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>">
                    <input type="hidden" name="product_code" value="<?php echo ESEWA_MERCHANT_CODE; ?>">
                    <input type="hidden" name="product_service_charge" value="0">
                    <input type="hidden" name="product_delivery_charge" value="0">
                    <input type="hidden" name="success_url" value="<?php echo $success_url; ?>">
                    <input type="hidden" name="failure_url" value="<?php echo $failure_url; ?>">
                    <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
                    <input type="hidden" name="signature" value="<?php echo $signature; ?>">
                </form>
                <script>
                    document.getElementById('esewaForm').submit();
                </script>
            </body>
            </html>
            <?php
            exit();
        
        } elseif ($payment_method == 'stripe') {
            // Initialize Stripe payment
            $payment_sql = "INSERT INTO payments (order_id, payment_method, amount, status)
                           VALUES ('$order_id', 'stripe', '$total', 'pending')";
            $conn->query($payment_sql);
            $payment_id = $conn->insert_id;

            // Create Stripe Checkout session
            try {
                $checkout_session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'npr',
                            'product_data' => [
                                'name' => 'Order #' . $order_id,
                            ],
                            'unit_amount' => $total * 100, // Stripe uses smallest currency unit
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => 'http://localhost/ecommerce-site/stripe_success.php?order_id=' . $order_id . '&payment_id=' . $payment_id . '&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => 'http://localhost/ecommerce-site/stripe_failure.php?order_id=' . $order_id . '&payment_id=' . $payment_id,
                    'metadata' => [
                        'order_id' => $order_id,
                        'payment_id' => $payment_id
                    ],
                ]);

                // Store Stripe session ID in payments table
                $update_payment = "UPDATE payments SET transaction_id = '" . $checkout_session->id . "' WHERE id = '$payment_id'";
                $conn->query($update_payment);

                // Redirect to Stripe Checkout
                header("HTTP/1.1 303 See Other");
                header("Location: " . $checkout_session->url);
                exit();
            } catch (Exception $e) {
                $_SESSION['error'] = "Error creating Stripe session: " . $e->getMessage();
                header("Location: checkout.php");
                exit();
            }
        } else {
            // For other payment methods (cash on delivery, etc.)
            // Clear cart or direct purchase session
            if (isset($_SESSION['checkout_items'])) {
                // Clear direct purchase session
                unset($_SESSION['checkout_items']);
                unset($_SESSION['is_direct_purchase']);
            } else {
                // Clear cart
                $clear_sql = "DELETE FROM cart WHERE user_id = '$user_id'";
                $conn->query($clear_sql);
            }

            $_SESSION['order_id'] = $order_id;
            header("Location: order_success.php?order_id=$order_id");
            exit();
        }
    } else {
        $_SESSION['error'] = "Error creating order: " . $conn->error;
        header("Location: checkout.php");
        exit();
    }
}

// Get cart items for display
$cart_items = [];
$total = 0;

// Check if we have direct purchase items in session
if (isset($_SESSION['checkout_items']) && !empty($_SESSION['checkout_items'])) {
    // Use the direct purchase items
    $cart_items = $_SESSION['checkout_items'];
    foreach ($cart_items as &$item) {
        $item['subtotal'] = $item['price'] * $item['quantity'];
        $total += $item['subtotal'];
        // Add image_path by querying the database
        $img_sql = "SELECT image_path FROM gadgets WHERE id = '{$item['gadget_id']}'";
        $img_result = $conn->query($img_sql);
        $item['image_path'] = $img_result->fetch_assoc()['image_path'];
        // Add id for compatibility with cart items
        $item['id'] = $item['gadget_id'];
    }
    unset($item); // break the reference
} else {
    // Otherwise get items from cart as normal
    $sql = "SELECT g.id, g.name, g.price, g.image_path, c.quantity 
            FROM cart c 
            JOIN gadgets g ON c.gadget_id = g.id 
            WHERE c.user_id = '$user_id'";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $total += $row['subtotal'];
        $cart_items[] = $row;
    }
}

// Get user details for checkout form
$user_sql = "SELECT * FROM users WHERE id = '$user_id'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Checkout - Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
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

        .content {
            padding: 40px;
        }

        .card {
            background-color: #2c2f48;
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            color: white;
        }

        .table {
            color: white;
        }

        .table thead {
            color: #8f94fb;
            border-bottom: 1px solid #4e44ce;
        }

        .btn-checkout {
            background-color: #4e54c8;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
        }

        .btn-checkout:hover {
            background-color: #8f94fb;
        }

        .product-img {
            height: 80px;
            width: 80px;
            object-fit: contain;
            border-radius: 10px;
        }

        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 300px;
        }

        .esewa-logo {
            height: 30px;
            margin-left: 10px;
        }
        
        .stripe-logo {
            height: 30px;
            margin-left: 10px;
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
                </ul>
                <div class="d-flex">
                    <a href="cart.php" class="btn btn-outline-light me-2 position-relative">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if (count($cart_items) > 0 && !isset($_SESSION['checkout_items'])): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo count($cart_items); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger me-2">Logout</a>
                    <a href="profile.php" class="btn btn-outline-light">
                        <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['name']); ?>
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

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Shipping Information</h4>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number"
                                    value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="esewa" value="esewa" checked>
                                    <label class="form-check-label" for="esewa">
                                        <img src="esewa.png" alt="Esewa" class="esewa-logo">
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="stripe" value="stripe">
                                    <label class="form-check-label" for="stripe">
                                        <img src="stripe.png" alt="Stripe" class="stripe-logo">
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod">
                                    <label class="form-check-label" for="cod">
                                        Cash on Delivery
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-checkout btn-lg w-100">
                                <i class="fas fa-credit-card me-2"></i> Complete Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Order Summary</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo $item['image_path']; ?>" class="product-img me-2">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>Rs.<?php echo number_format($item['subtotal'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="2" class="fw-bold">Total</td>
                                        <td class="fw-bold text-success">Rs.<?php echo number_format($total, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
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