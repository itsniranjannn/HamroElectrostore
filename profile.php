<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user details
$user_sql = "SELECT * FROM users WHERE id = '$user_id'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// Get latest order for contact number (if exists)
$order_sql = "SELECT contact_number FROM orders WHERE user_id = '$user_id' ORDER BY order_date DESC LIMIT 1";
$order_result = $conn->query($order_sql);
$latest_order = $order_result->fetch_assoc();
$contact_number = $latest_order['contact_number'] ?? 'Not provided yet';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);

    $update_sql = "UPDATE users SET name = '$name', email = '$email' WHERE id = '$user_id'";
    if ($conn->query($update_sql)) {
        $_SESSION['name'] = $name;
        $_SESSION['message'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating profile: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Profile - Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-bg: linear-gradient(to right, #0f0c29, #302b63, #24243e);
            --card-bg: #2c2f48;
            --navbar-bg: #1e1e2f;
            --accent-color: #4e54c8;
            --accent-hover: #8f94fb;
            --text-light: #ffffff;
            --text-muted: #cccccc;
        }

        body {
            background: var(--primary-bg);
            color: var(--text-light);
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
        }

        .navbar {
            background-color: var(--navbar-bg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--text-light);
        }

        .nav-link {
            color: var(--text-muted) !important;
            transition: color 0.3s;
            font-weight: 500;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--text-light) !important;
        }

        .profile-container {
            padding: 40px 0;
        }

        .profile-card {
            background-color: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .profile-header {
            background: var(--accent-color);
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid var(--card-bg);
            object-fit: cover;
            margin-bottom: 15px;
            background-color: #3a3e6b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 15px;
        }

        .profile-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .profile-role {
            display: inline-block;
            background-color: rgba(0, 0, 0, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .profile-body {
            padding: 30px;
        }

        .info-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .btn-edit {
            background-color: var(--accent-color);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-edit:hover {
            background-color: var(--accent-hover);
            transform: translateY(-2px);
        }

        .stats-card {
            background-color: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            font-size: 2rem;
            color: var(--accent-color);
            margin-bottom: 15px;
        }

        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stats-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 300px;
        }

        .form-control,
        .form-select {
            background-color: #1e1e2f;
            border: 1px solid #4e44ce;
            color: white;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #1e1e2f;
            color: white;
            border-color: var(--accent-hover);
            box-shadow: 0 0 0 0.25rem rgba(78, 84, 200, 0.25);
        }

        .table {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: rgba(78, 84, 200, 0.05);
            --bs-table-hover-bg: rgba(78, 84, 200, 0.1);
            color: white;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            background-color: #1e1e2f;
            position: sticky;
            top: 0;
        }

        .table td,
        .table th {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid #3a3a5a;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(78, 84, 200, 0.15);
        }

        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .badge i {
            font-size: 0.7em;
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
                        <a class="nav-link" href="home.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="fas fa-box-open me-1"></i> Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart me-1"></i> Cart</a>
                    </li>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_products.php"><i class="fas fa-cog me-1"></i> Manage</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex">
                    <a href="profile.php" class="btn btn-outline-light me-2 position-relative">
                        <i class="fas fa-user-circle me-1"></i> Profile
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Alerts -->
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

    <!-- Profile Content -->
    <div class="profile-container">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <h2 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                            <span class="profile-role"><?php echo ucfirst($role); ?></span>
                        </div>
                        <div class="profile-body">
                            <div class="info-item">
                                <div class="info-label">Email Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Contact Number</div>
                                <div class="info-value"><?php echo $contact_number ? htmlspecialchars($contact_number) : 'Not provided'; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account Created</div>
                                <div class="info-value">
                                    <?php
                                    $created_at = new DateTime($user['created_at']);
                                    echo $created_at->format('F j, Y');
                                    ?>
                                </div>
                            </div>
                            <?php if ($role === 'admin'): ?>
                                <div class="info-item">
                                    <div class="info-label">Admin Privileges</div>
                                    <div class="info-value">Full Access</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <?php if ($role === 'admin'): ?>
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stats-number">
                                <?php
                                $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                                echo $user_count;
                                ?>
                            </div>
                            <div class="stats-label">Total Users</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-8">
                    <div class="profile-card">
                        <div class="profile-body">
                            <h4 class="mb-4"><i class="fas fa-user-edit me-2"></i> Edit Profile</h4>

                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone"
                                        value="<?php echo $contact_number ? htmlspecialchars($contact_number) : ''; ?>">
                                </div>

                                <div class="text-end">
                                    <button type="submit" name="update_profile" class="btn btn-edit">
                                        <i class="fas fa-save me-1"></i> Update Profile
                                    </button>
                                </div>
                            </form>

                            <hr class="my-4">

                            <h4 class="mb-4"><i class="fas fa-history me-2"></i> Recent Activity</h4>

                            <?php
                            $activity_sql = "SELECT * FROM orders WHERE user_id = '$user_id' ORDER BY order_date DESC LIMIT 5";
                            $activity_result = $conn->query($activity_sql);

                            if ($activity_result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="ps-4">Order ID</th>
                                                <th>Date</th>
                                                <th class="text-end">Amount</th>
                                                <th>Status</th>
                                                <th class="text-end pe-4">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($order = $activity_result->fetch_assoc()): ?>
                                                <tr class="position-relative">
                                                    <td class="ps-4 fw-bold">
                                                        <a href="user_orders.php?id=<?php echo $order['id']; ?>" class="stretched-link text-decoration-none text-white">
                                                            #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span><?php echo date('M j, Y', strtotime($order['order_date'])); ?></span>
                                                            <small class="text-muted"><?php echo date('g:i a', strtotime($order['order_date'])); ?></small>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-flex flex-column">
                                                            <span>Rs. <?php echo number_format($order['total_amount'], 2); ?></span>
                                                            <small class="text-muted">
                                                                <?php
                                                                $item_count = $conn->query("SELECT COUNT(*) as count FROM order_items WHERE order_id = '{$order['id']}'")->fetch_assoc()['count'];
                                                                echo $item_count . ' item' . ($item_count != 1 ? 's' : '');
                                                                ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge rounded-pill bg-<?php
                                                                                            switch ($order['status']) {
                                                                                                case 'completed':
                                                                                                    echo 'success';
                                                                                                    break;
                                                                                                case 'failed':
                                                                                                    echo 'danger';
                                                                                                    break;
                                                                                                case 'processing':
                                                                                                    echo 'primary';
                                                                                                    break;
                                                                                                case 'shipped':
                                                                                                    echo 'info';
                                                                                                    break;
                                                                                                default:
                                                                                                    echo 'secondary';
                                                                                            }
                                                                                            ?>">
                                                            <i class="fas fa-<?php
                                                                                switch ($order['status']) {
                                                                                    case 'completed':
                                                                                        echo 'check-circle';
                                                                                        break;
                                                                                    case 'failed':
                                                                                        echo 'times-circle';
                                                                                        break;
                                                                                    case 'processing':
                                                                                        echo 'sync-alt';
                                                                                        break;
                                                                                    case 'shipped':
                                                                                        echo 'truck';
                                                                                        break;
                                                                                    default:
                                                                                        echo 'ellipsis-h';
                                                                                }
                                                                                ?> me-1"></i>
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="user_orders.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-light" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($order['status'] == 'processing'): ?>
                                                                <button class="btn btn-outline-danger" title="Cancel Order" data-bs-toggle="modal" data-bs-target="#cancelOrderModal<?php echo $order['id']; ?>">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>

                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <a href="user_orders.php" class="btn btn-sm btn-outline-light">
                                            <i class="fas fa-history me-1"></i> View Full Order History
                                        </a>
                                        <small class="text-muted">Showing 5 most recent orders</small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div>
                                        <h5 class="mb-1">No recent orders found</h5>
                                        <p class="mb-0">You haven't placed any orders yet. <a href="products.php" class="alert-link">Browse our products</a></p>
                                    </div>
                                </div>
                            <?php endif; ?>

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