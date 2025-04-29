<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle order cancellation
if (isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];

    // Check if order belongs to user and is pending or processing
    $check_sql = "SELECT o.*, p.status as payment_status 
                 FROM orders o
                 LEFT JOIN payments p ON o.id = p.order_id
                 WHERE o.id = '$order_id' AND o.user_id = '$user_id' 
                 AND (o.status = 'pending' OR o.status = 'processing')";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        $order = $check_result->fetch_assoc();

        // Check payment status if not COD
        if ($order['payment_method'] != 'cod' && isset($order['payment_status']) && $order['payment_status'] == 'paid') {
            $_SESSION['message'] = "Cannot cancel order #$order_id - payment already processed.";
            header("Location: user_orders.php");
            exit();
        }

        // Update order status to cancelled
        $update_sql = "UPDATE orders SET status = 'cancelled' WHERE id = '$order_id'";

        // If COD order, also update payment status if payment record exists
        if ($order['payment_method'] == 'cod') {
            $payment_sql = "UPDATE payments SET status = 'cancelled' WHERE order_id = '$order_id'";
            $conn->query($payment_sql);
        }

        if ($conn->query($update_sql)) {
            $_SESSION['message'] = "Order #$order_id has been cancelled successfully.";
        } else {
            $_SESSION['message'] = "Error cancelling order: " . $conn->error;
        }
    } else {
        $_SESSION['message'] = "Order cannot be cancelled or doesn't exist.";
    }

    header("Location: user_orders.php");
    exit();
}

// Handle order received confirmation
if (isset($_POST['mark_received'])) {
    $order_id = $_POST['order_id'];

    // Check if order belongs to user and is processing
    $check_sql = "SELECT o.*, p.id as payment_id 
                 FROM orders o
                 LEFT JOIN payments p ON o.id = p.order_id
                 WHERE o.id = '$order_id' AND o.user_id = '$user_id' 
                 AND o.status = 'processing'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        $order = $check_result->fetch_assoc();

        // Update order status to delivered
        $update_sql = "UPDATE orders SET status = 'delivered' WHERE id = '$order_id'";

        // Update payment status to paid if payment record exists
        if ($order['payment_id']) {
            $payment_sql = "UPDATE payments SET status = 'paid' WHERE order_id = '$order_id'";
            $conn->query($payment_sql);
        }

        if ($conn->query($update_sql)) {
            $_SESSION['message'] = "Order #$order_id has been marked as delivered successfully.";
        } else {
            $_SESSION['message'] = "Error updating order: " . $conn->error;
        }
    } else {
        $_SESSION['message'] = "Order cannot be marked as delivered or doesn't exist.";
    }

    header("Location: user_orders.php");
    exit();
}

// Get all orders for the current user with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

// Filter by status if provided
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_condition = "";
if ($status_filter != 'all') {
    $filter_condition = "AND o.status = '$status_filter'";
}

$orders_sql = "SELECT o.*, p.status as payment_status 
               FROM orders o
               LEFT JOIN payments p ON o.id = p.order_id
               WHERE o.user_id = '$user_id' 
               $filter_condition
               ORDER BY o.order_date DESC 
               LIMIT $per_page OFFSET $offset";
$orders_result = $conn->query($orders_sql);

// Get total count of orders for pagination
$count_sql = "SELECT COUNT(*) as total FROM orders o
              WHERE o.user_id = '$user_id' 
              $filter_condition";
$count_result = $conn->query($count_sql);
$total_orders = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #8f94fb;
            --secondary-color: #4e54c8;
            --dark-bg: #1e1e2f;
            --card-bg: #2c2f48;
            --text-light: #f0f0f0;
            --text-muted: #b3b3b3;
        }

        body {
            background: linear-gradient(to right, #0f0c29, #302b63, #24243e);
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }

        .navbar {
            background-color: var(--dark-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--text-light);
        }

        .nav-link {
            color: var(--text-muted) !important;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color) !important;
        }

        .order-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 25px;
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .order-header {
            background: linear-gradient(135deg, rgba(79, 84, 200, 0.2), rgba(143, 148, 251, 0.15));
            padding: 18px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .order-body {
            padding: 20px 25px;
        }

        .product-img {
            height: 70px;
            width: 70px;
            object-fit: contain;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.05);
            padding: 5px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: rgba(255, 193, 7, 0.15);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-processing {
            background-color: rgba(23, 162, 184, 0.15);
            color: #17a2b8;
            border: 1px solid rgba(23, 162, 184, 0.3);
        }

        .status-delivered {
            background-color: rgba(13, 110, 253, 0.15);
            color: #0d6efd;
            border: 1px solid rgba(13, 110, 253, 0.3);
        }

        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Payment status styles */
        .payment-status {
            font-size: 0.8rem;
            margin-left: 5px;
        }

        .payment-paid {
            color: #28a745;
        }

        .payment-failed {
            color: #dc3545;
        }

        .payment-pending {
            color: #ffc107;
        }

        .payment-cancelled {
            color: #6c757d;
        }

        .payment-completed {
            color: #17a2b8;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background-color: rgba(44, 47, 72, 0.5);
            border-radius: 12px;
            margin-top: 30px;
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .order-summary-card {
            background-color: rgba(44, 47, 72, 0.7);
            border-radius: 10px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .timeline {
            position: relative;
            padding-left: 30px;
            margin: 20px 0;
        }

        .timeline:before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
        }

        .timeline-step {
            position: relative;
            margin-bottom: 25px;
        }

        .timeline-icon {
            position: absolute;
            left: -28px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
        }

        .timeline-content {
            padding-left: 15px;
        }

        .timeline-date {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .pagination .page-link {
            background-color: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-link:hover {
            background-color: rgba(143, 148, 251, 0.3);
        }

        .btn-view-details {
            background-color: var(--primary-color);
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-view-details:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-mark-received {
            background-color: #28a745;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-mark-received:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .order-id {
            color: var(--primary-color);
            font-weight: 600;
        }

        .order-date {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .product-name {
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .product-name:hover {
            color: var(--primary-color);
        }

        .section-title {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 10px;
        }

        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 3px;
        }

        @media (max-width: 768px) {
            .order-card {
                margin-bottom: 30px;
            }

            .order-summary-card {
                margin-top: 20px;
            }
        }

        .modal-content {
            background-color: var(--card-bg) !important;
            color: var(--text-light);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-body table {
            width: 100%;
            border-collapse: collapse;
        }

        .modal-body th,
        .modal-body td {
            padding: 8px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-body th {
            text-align: left;
            font-weight: 500;
            color: var(--primary-color);
        }

        .cancel-confirm-modal .modal-content {
            background-color: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .cancel-confirm-modal .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .cancel-confirm-modal .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
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
                        <a class="nav-link active" href="user_orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="cart.php" class="btn btn-outline-light me-3 position-relative">
                        <i class="fas fa-shopping-cart"></i>
                        <?php
                        $cart_count = 0;
                        if (isset($_SESSION['user_id'])) {
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
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name']); ?>&background=random" alt="Profile" width="32" height="32" class="rounded-circle me-2">
                            <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="dropdownUser">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="user_orders.php"><i class="fas fa-clipboard-list me-2"></i> My Orders</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="section-title"><i class="fas fa-clipboard-list me-2"></i> My Orders</h2>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if ($total_orders > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="mb-0">Showing <?php echo min($per_page, $total_orders - $offset); ?> of <?php echo $total_orders; ?> order<?php echo $total_orders > 1 ? 's' : ''; ?></p>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                                <li><a class="dropdown-item" href="?status=all">All Orders</a></li>
                                <li><a class="dropdown-item" href="?status=pending">Pending</a></li>
                                <li><a class="dropdown-item" href="?status=processing">Processing</a></li>
                                <li><a class="dropdown-item" href="?status=delivered">Delivered</a></li>
                                <li><a class="dropdown-item" href="?status=cancelled">Cancelled</a></li>
                            </ul>
                        </div>
                    </div>

                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <?php
                        // Get order items for this order
                        $order_id = $order['id'];
                        $items_sql = "SELECT oi.*, g.name, g.image_path, g.category 
                                     FROM order_items oi 
                                     JOIN gadgets g ON oi.gadget_id = g.id 
                                     WHERE oi.order_id = '$order_id'";
                        $items_result = $conn->query($items_sql);
                        $total_items = $items_result->num_rows;

                        // Get order status timeline
                        $timeline = [
                            ['status' => 'confirmed', 'date' => $order['order_date'], 'icon' => 'check', 'text' => 'Order Confirmed'],
                            ['status' => 'processing', 'date' => date('Y-m-d H:i:s', strtotime($order['order_date'] . ' +1 day')), 'icon' => 'cog', 'text' => 'Processing'],
                            ['status' => 'shipped', 'date' => date('Y-m-d H:i:s', strtotime($order['order_date'] . ' +3 days')), 'icon' => 'truck', 'text' => 'Shipped'],
                            ['status' => 'delivered', 'date' => $order['status'] == 'delivered' ? date('Y-m-d H:i:s', strtotime($order['order_date'] . ' +5 days')) : null, 'icon' => 'home', 'text' => 'Delivered']
                        ];

                        // Determine payment status class
                        $payment_status_class = '';
                        $payment_status_text = 'Pending';
                        if (isset($order['payment_status'])) {
                            $payment_status_class = 'payment-' . strtolower($order['payment_status']);
                            $payment_status_text = ucfirst($order['payment_status']);
                        }
                        ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mb-2 mb-md-0">
                                        <h5 class="mb-1">Order <span class="order-id">#<?php echo $order['id']; ?></span></h5>
                                        <div class="order-date">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            <?php echo date('F j, Y \a\t h:i A', strtotime($order['order_date'])); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                            <i class="fas fa-<?php echo $order['status'] == 'completed' ? 'check-double' : ($order['status'] == 'delivered' ? 'check-circle' : ($order['status'] == 'cancelled' ? 'times-circle' : 'sync-alt')); ?> me-1"></i>
                                            <?php echo ucfirst($order['status']); ?>
                                            <span class="payment-status <?php echo $payment_status_class; ?>">
                                                (<?php echo $payment_status_text; ?>)
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="order-body">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <h6 class="mb-3"><i class="fas fa-box-open me-2"></i> Items (<?php echo $total_items; ?>)</h6>
                                        <div class="table-responsive">
                                            <table class="table table-borderless table-hover align-middle">
                                                <tbody>
                                                    <?php while ($item = $items_result->fetch_assoc()): ?>
                                                        <tr>
                                                            <td width="80">
                                                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="product-img" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                            </td>
                                                            <td>
                                                                <a href="product_detail.php?id=<?php echo $item['gadget_id']; ?>" class="text-decoration-none">
                                                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($item['category']); ?></small>
                                                                </a>
                                                            </td>
                                                            <td class="text-end">
                                                                <div class="fw-bold">Rs.<?php echo number_format($item['unit_price'], 2); ?></div>
                                                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                                            </td>
                                                            <td class="text-end fw-bold">
                                                                Rs.<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Order Timeline -->
                                        <div class="mt-4">
                                            <h6 class="mb-3"><i class="fas fa-history me-2"></i> Order Status</h6>
                                            <div class="timeline">
                                                <?php foreach ($timeline as $step): ?>
                                                    <?php
                                                    $is_active = ($order['status'] == 'delivered' || $order['status'] == 'completed') ||
                                                        ($order['status'] == 'processing' && $step['status'] != 'delivered') ||
                                                        ($order['status'] == 'pending' && in_array($step['status'], ['confirmed', 'processing']));
                                                    ?>
                                                    <div class="timeline-step">
                                                        <div class="timeline-icon" style="background-color: <?php echo $is_active ? 'var(--primary-color)' : '#6c757d'; ?>">
                                                            <i class="fas fa-<?php echo $step['icon']; ?>"></i>
                                                        </div>
                                                        <div class="timeline-content">
                                                            <h6 class="mb-1" style="color: <?php echo $is_active ? 'var(--text-light)' : 'var(--text-muted)'; ?>">
                                                                <?php echo $step['text']; ?>
                                                            </h6>
                                                            <?php if ($step['date']): ?>
                                                                <div class="timeline-date">
                                                                    <?php echo date('M j, Y', strtotime($step['date'])); ?>
                                                                    <?php if ($step['status'] == 'confirmed'): ?>
                                                                        <small class="ms-2"><?php echo date('h:i A', strtotime($step['date'])); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if ($order['status'] == 'completed'): ?>
                                                    <div class="timeline-step">
                                                        <div class="timeline-icon" style="background-color: var(--primary-color)">
                                                            <i class="fas fa-check-double"></i>
                                                        </div>
                                                        <div class="timeline-content">
                                                            <h6 class="mb-1" style="color: var(--text-light)">
                                                                Order Completed
                                                            </h6>
                                                            <div class="timeline-date">
                                                                <?php
                                                                $completed_date = $conn->query("SELECT updated_at FROM orders WHERE id = '$order_id'")->fetch_assoc()['updated_at'];
                                                                echo date('M j, Y', strtotime($completed_date));
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="order-summary-card">
                                            <h6 class="mb-3"><i class="fas fa-receipt me-2"></i> Order Summary</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Subtotal (<?php echo $total_items; ?> item<?php echo $total_items > 1 ? 's' : ''; ?>):</span>
                                                <span>Rs.<?php echo number_format($order['total_amount'], 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Shipping:</span>
                                                <span class="text-success">Free</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>Payment Method:</span>
                                                <span>
                                                    <?php if ($order['payment_method'] == 'esewa'): ?>
                                                        <img src="esewa.png" alt="Esewa" style="height: 20px;">
                                                    <?php else: ?>
                                                        <i class="fas fa-money-bill-wave me-1"></i> <?php echo strtoupper($order['payment_method']); ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span>Payment Status:</span>
                                                <span class="<?php echo $payment_status_class; ?>">
                                                    <?php echo $payment_status_text; ?>
                                                </span>
                                            </div>
                                            <hr class="my-3" style="border-color: rgba(255,255,255,0.1);">
                                            <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                                                <span>Total:</span>
                                                <span class="text-primary">Rs.<?php echo number_format($order['total_amount'], 2); ?></span>
                                            </div>

                                            <div class="d-grid gap-2">
                                                <button class="btn btn-view-details" data-bs-toggle="modal" data-bs-target="#orderDetailsModal<?php echo $order['id']; ?>">
                                                    <i class="fas fa-eye me-1"></i> View Order Details
                                                </button>
                                                <?php if ($order['status'] == 'processing'): ?>
                                                    <form method="post" action="user_orders.php">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <button type="submit" name="mark_received" class="btn btn-mark-received">
                                                            <i class="fas fa-check-circle me-1"></i> Mark as Received
                                                        </button>
                                                    </form>
                                                <?php elseif (
                                                    $order['status'] == 'pending' ||
                                                    (isset($order['payment_status']) && $order['payment_status'] == 'failed')
                                                ): ?>
                                                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $order['id']; ?>">
                                                        <i class="fas fa-times me-1"></i> Cancel Order
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Shipping Information -->
                                        <div class="order-summary-card mt-3">
                                            <h6 class="mb-3"><i class="fas fa-truck me-2"></i> Shipping Information</h6>
                                            <div class="mb-2">
                                                <div class="">Deliver to</div>
                                                <div><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                                            </div>
                                            <div class="mb-2">
                                                <div class="">Contact</div>
                                                <div><?php echo htmlspecialchars($order['contact_number']); ?></div>
                                            </div>
                                            <?php if ($order['status'] == 'delivered' || $order['status'] == 'completed'): ?>
                                                <div class="mt-3">
                                                    <button class="btn btn-sm btn-outline-light w-100">
                                                        <i class="fas fa-headset me-1"></i> Need Help?
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cancel Order Modal -->
                        <div class="modal fade cancel-confirm-modal" id="cancelModal<?php echo $order['id']; ?>" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="cancelModalLabel" style="color: #f0f0f0;">Cancel Order #<?php echo $order['id']; ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p style="color: #f0f0f0;">Are you sure you want to cancel this order? This action cannot be undone.</p>
                                        <p class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Order total: Rs.<?php echo number_format($order['total_amount'], 2); ?></p>
                                        <?php if (isset($order['payment_status'])): ?>
                                            <p class="<?php echo $payment_status_class; ?>">
                                                <i class="fas fa-info-circle me-2"></i>Payment Status: <?php echo $payment_status_text; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <form method="post" action="user_orders.php">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" name="cancel_order" class="btn btn-danger">
                                                <i class="fas fa-times me-1"></i> Confirm Cancel
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Details Modal -->
                        <div class="modal fade" id="orderDetailsModal<?php echo $order['id']; ?>" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content" style="background-color: var(--card-bg);">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="orderDetailsModalLabel">Order Details #<?php echo $order['id']; ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-box-open me-2"></i> Order Items</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-borderless table-hover align-middle">
                                                        <thead>
                                                            <tr>
                                                                <th>Item</th>
                                                                <th>Qty</th>
                                                                <th>Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            // Re-fetch items for this order
                                                            $items_result = $conn->query($items_sql);
                                                            while ($item = $items_result->fetch_assoc()): ?>
                                                                <tr>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="product-img me-2" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                                            <div>
                                                                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                                                <small class="text-muted"><?php echo htmlspecialchars($item['category']); ?></small>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td><?php echo $item['quantity']; ?></td>
                                                                    <td>Rs.<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                                                                </tr>
                                                            <?php endwhile; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-info-circle me-2"></i> Order Information</h6>
                                                <div class="order-summary-card mb-3">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Order Status:</span>
                                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Order Date:</span>
                                                        <span><?php echo date('F j, Y \a\t h:i A', strtotime($order['order_date'])); ?></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Payment Method:</span>
                                                        <span>
                                                            <?php if ($order['payment_method'] == 'esewa'): ?>
                                                                <img src="esewa.png" alt="Esewa" style="height: 20px;">
                                                            <?php elseif ($order['payment_method'] == 'stripe'): ?>
                                                                <img src="stripe.png" alt="Stripe" style="height: 20px;">
                                                            <?php else: ?>
                                                                <i class="fas fa-money-bill-wave me-1"></i> <?php echo strtoupper($order['payment_method']); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Payment Status:</span>
                                                        <span class="<?php echo $payment_status_class; ?>">
                                                            <?php echo $payment_status_text; ?>
                                                        </span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Subtotal:</span>
                                                        <span>Rs.<?php echo number_format($order['total_amount'], 2); ?></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Shipping:</span>
                                                        <span class="text-success">Free</span>
                                                    </div>
                                                    <hr class="my-2">
                                                    <div class="d-flex justify-content-between fw-bold">
                                                        <span>Total:</span>
                                                        <span>Rs.<?php echo number_format($order['total_amount'], 2); ?></span>
                                                    </div>
                                                </div>

                                                <h6><i class="fas fa-truck me-2"></i> Shipping Information</h6>
                                                <div class="order-summary-card">
                                                    <div class="mb-2">
                                                        <div>Shipping Address</div>
                                                        <div><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <div>Contact Number</div>
                                                        <div><?php echo htmlspecialchars($order['contact_number']); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <?php if ($order['status'] == 'processing'): ?>
                                            <form method="post" action="user_orders.php" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="mark_received" class="btn btn-success">
                                                    <i class="fas fa-check-circle me-1"></i> Mark as Received
                                                </button>
                                            </form>
                                        <?php elseif (
                                            $order['status'] == 'pending' ||
                                            (isset($order['payment_status']) && $order['payment_status'] == 'failed')
                                        ): ?>
                                            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-times me-1"></i> Cancel Order
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status_filter != 'all' ? '&status=' . $status_filter : ''; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No orders yet</h3>
                        <p>You haven't placed any orders with us yet.</p>
                        <a href="products.php" class="btn btn-primary mt-3 px-4 py-2">
                            <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                        </a>
                    </div>
                <?php endif; ?>
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