<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "electronics_store");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle gadget operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new gadget
    if (isset($_POST['add_gadget'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $category = $conn->real_escape_string($_POST['category']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = $conn->real_escape_string($_POST['price']);
        $stock = $conn->real_escape_string($_POST['stock']);
        $image_path = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
            $image_path = $target_file;
        }
        
        $sql = "INSERT INTO gadgets (name, category, description, price, stock, image_path) 
                VALUES ('$name', '$category', '$description', '$price', '$stock', '$image_path')";
        
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Gadget added successfully!";
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Error adding gadget: " . $conn->error;
        }
    }

    // Update existing gadget
    if (isset($_POST['update_gadget'])) {
        $id = $conn->real_escape_string($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        $category = $conn->real_escape_string($_POST['category']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = $conn->real_escape_string($_POST['price']);
        $stock = $conn->real_escape_string($_POST['stock']);
        
        // Get current image path
        $result = $conn->query("SELECT image_path FROM gadgets WHERE id = '$id'");
        $gadget = $result->fetch_assoc();
        $image_path = $gadget['image_path'];
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            if ($image_path && file_exists($image_path)) {
                unlink($image_path);
            }
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
            $image_path = $target_file;
        }
        
        $sql = "UPDATE gadgets SET 
                name='$name', 
                category='$category', 
                description='$description', 
                price='$price', 
                stock='$stock', 
                image_path='$image_path' 
                WHERE id='$id'";
        
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Gadget updated successfully!";
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating gadget: " . $conn->error;
        }
    }
} // <-- FIXED: This closing brace was missing

// Delete gadget
if (isset($_GET['delete_gadget'])) {
    $id = $conn->real_escape_string($_GET['delete_gadget']);
    $result = $conn->query("SELECT image_path FROM gadgets WHERE id = '$id'");
    if ($result->num_rows > 0) {
        $gadget = $result->fetch_assoc();
        if ($gadget['image_path'] && file_exists($gadget['image_path'])) {
            unlink($gadget['image_path']);
        }
    }
    $sql = "DELETE FROM gadgets WHERE id='$id'";
    if ($conn->query($sql)) {
        $_SESSION['message'] = "Gadget deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting gadget: " . $conn->error;
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Delete user
if (isset($_GET['delete_user'])) {
    $id = $conn->real_escape_string($_GET['delete_user']);
    $conn->query("DELETE FROM purchases WHERE user_id='$id'");
    $sql = "DELETE FROM users WHERE id='$id'";
    if ($conn->query($sql)) {
        $_SESSION['message'] = "User deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting user: " . $conn->error;
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Get statistics
$total_gadgets = $conn->query("SELECT COUNT(*) as count FROM gadgets")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_sales = $conn->query("SELECT SUM(total_price) as total FROM purchases")->fetch_assoc()['total'];
$monthly_sales = $conn->query("SELECT SUM(total_price) as total FROM purchases 
                              WHERE MONTH(purchase_date) = MONTH(CURRENT_DATE()) 
                              AND YEAR(purchase_date) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'];

// Get all gadgets
$gadgets = $conn->query("SELECT * FROM gadgets ORDER BY created_at DESC");

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

// Get all purchases with user and gadget details
$purchases = $conn->query("
    SELECT p.*, u.name as user_name, u.email, g.name as gadget_name 
    FROM purchases p
    JOIN users u ON p.user_id = u.id
    JOIN gadgets g ON p.gadget_id = g.id
    ORDER BY p.purchase_date DESC
");

// Get gadget for editing
$edit_gadget = null;
if (isset($_GET['edit_gadget'])) {
    $id = $conn->real_escape_string($_GET['edit_gadget']);
    $result = $conn->query("SELECT * FROM gadgets WHERE id = '$id'");
    $edit_gadget = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Hamro ElectroStore</title>
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
        .sidebar {
            height: 100vh;
            background-color: #1e1e2f;
            padding-top: 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link {
            color: #ccc;
            padding: 15px;
            font-size: 1rem;
            display: block;
            transition: background-color 0.3s, color 0.3s;
        }
        .sidebar .nav-link:hover {
            background-color: #302b63;
            color: #ffffff;
        }
        .content {
            margin-left: 250px;
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
        }
        .card h5 {
            font-weight: 700;
        }
        .card i {
            font-size: 2rem;
        }
        .stats-card {
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .gadget-img {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 300px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <a class="navbar-brand px-3" href="#">Hamro ElectroStore</a>
    <div class="mt-4">
        <a class="nav-link" href="#dashboard"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
        <a class="nav-link" href="#addGadget"><i class="fas fa-plus-circle me-2"></i>Add Gadget</a>
        <a class="nav-link" href="#manageGadgets"><i class="fas fa-edit me-2"></i>Manage Gadgets</a>
        <a class="nav-link" href="#manageUsers"><i class="fas fa-users me-2"></i>Manage Users</a>
        <a class="nav-link" href="#viewPurchases"><i class="fas fa-receipt me-2"></i>View Purchases</a>
        <a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <span class="navbar-brand">Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
        <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
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

    <!-- Dashboard Section -->
    <div id="dashboard" class="mb-5">
        <div class="section-title">Dashboard Overview</div>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card p-3 text-center stats-card">
                    <i class="fas fa-mobile-alt mb-2"></i>
                    <h5><?php echo $total_gadgets; ?></h5>
                    <p>Total Gadgets</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-center stats-card">
                    <i class="fas fa-users mb-2"></i>
                    <h5><?php echo $total_users; ?></h5>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-center stats-card">
                    <i class="fas fa-shopping-cart mb-2"></i>
                    <h5>Rs. <?php echo number_format($total_sales, 2); ?></h5>
                    <p>Total Sales</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-center stats-card">
                    <i class="fas fa-calendar-alt mb-2"></i>
                    <h5>Rs. <?php echo number_format($monthly_sales, 2); ?></h5>
                    <p>Monthly Sales</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Gadget Section -->
    <div id="addGadget" class="mb-5">
        <div class="section-title"><?php echo $edit_gadget ? 'Edit Gadget' : 'Add New Gadget'; ?></div>
        <form method="POST" enctype="multipart/form-data" class="card p-4">
            <?php if ($edit_gadget): ?>
                <input type="hidden" name="id" value="<?php echo $edit_gadget['id']; ?>">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo $edit_gadget ? htmlspecialchars($edit_gadget['name']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Smartphones" <?php echo ($edit_gadget && $edit_gadget['category'] == 'Smartphones') ? 'selected' : ''; ?>>Smartphones</option>
                            <option value="Laptops" <?php echo ($edit_gadget && $edit_gadget['category'] == 'Laptops') ? 'selected' : ''; ?>>Laptops</option>
                            <option value="Tablets" <?php echo ($edit_gadget && $edit_gadget['category'] == 'Tablets') ? 'selected' : ''; ?>>Tablets</option>
                            <option value="Accessories" <?php echo ($edit_gadget && $edit_gadget['category'] == 'Accessories') ? 'selected' : ''; ?>>Accessories</option>
                            <option value="Others" <?php echo ($edit_gadget && $edit_gadget['category'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" 
                               value="<?php echo $edit_gadget ? $edit_gadget['price'] : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" 
                               value="<?php echo $edit_gadget ? $edit_gadget['stock'] : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="image" name="image" <?php echo $edit_gadget ? '' : ''; ?>>
                        <?php if ($edit_gadget && $edit_gadget['image_path']): ?>
                            <div class="mt-2">
                                <img src="<?php echo $edit_gadget['image_path']; ?>" class="gadget-img" alt="Current Image">
                                <small class="d-block">Current image</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $edit_gadget ? htmlspecialchars($edit_gadget['description']) : ''; ?></textarea>
            </div>
            <button type="submit" name="<?php echo $edit_gadget ? 'update_gadget' : 'add_gadget'; ?>" class="btn btn-primary">
                <?php echo $edit_gadget ? 'Update Gadget' : 'Add Gadget'; ?>
            </button>
            <?php if ($edit_gadget): ?>
                <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Manage Gadgets Section -->
<div id="manageGadgets" class="mb-5">
    <div class="section-title">Manage Gadgets</div>
    
    <!-- Category Filter Buttons -->
    <div class="mb-3">
        <button class="btn btn-outline-light btn-sm filter-btn" data-category="All">All</button>
        <button class="btn btn-outline-light btn-sm filter-btn" data-category="Laptops">Laptops</button>
        <button class="btn btn-outline-light btn-sm filter-btn" data-category="Smartphones">Smartphones</button>
        <button class="btn btn-outline-light btn-sm filter-btn" data-category="Accessories">Accessories</button>
        <button class="btn btn-outline-light btn-sm filter-btn" data-category="Tablets">Tablets</button>
    </div>

    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover" id="gadgetTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($gadget = $gadgets->fetch_assoc()): ?>
                    <tr data-category="<?php echo htmlspecialchars($gadget['category']); ?>">
                        <td>
                            <?php if ($gadget['image_path']): ?>
                                <img src="<?php echo $gadget['image_path']; ?>" class="gadget-img" alt="<?php echo htmlspecialchars($gadget['name']); ?>" width="50">
                            <?php else: ?>
                                <i class="fas fa-image fa-2x text-muted"></i>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($gadget['name']); ?></td>
                        <td><?php echo htmlspecialchars($gadget['category']); ?></td>
                        <td>Rs.<?php echo number_format($gadget['price'], 2); ?></td>
                        <td><?php echo $gadget['stock']; ?></td>
                        <td>
                            <a href="admin_dashboard.php?edit_gadget=<?php echo $gadget['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="admin_dashboard.php?delete_gadget=<?php echo $gadget['id']; ?>" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this gadget?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Pagination Controls -->
<div class="d-flex justify-content-center mt-3">
    <nav>
        <ul class="pagination pagination-sm" id="paginationControls"></ul>
    </nav>
</div>
<!-- JavaScript for Category Filtering -->
<script>
const rowsPerPage = 5;
let currentPage = 1;

function updateTable(category = 'All') {
    const rows = Array.from(document.querySelectorAll('#gadgetTable tbody tr'));
    let filteredRows = category === 'All' 
        ? rows 
        : rows.filter(row => row.getAttribute('data-category') === category);

    rows.forEach(row => row.style.display = 'none'); // Hide all
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    filteredRows.slice(start, end).forEach(row => row.style.display = '');

    renderPaginationControls(totalPages, category);
}

function renderPaginationControls(totalPages, category) {
    const pagination = document.getElementById('paginationControls');
    pagination.innerHTML = '';

    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.addEventListener('click', e => {
            e.preventDefault();
            currentPage = i;
            updateTable(category);
        });
        pagination.appendChild(li);
    }
}

// Setup initial filter and pagination
let activeCategory = 'All';
document.querySelectorAll('.filter-btn').forEach(button => {
    button.addEventListener('click', () => {
        activeCategory = button.getAttribute('data-category');
        currentPage = 1; // Reset to first page
        updateTable(activeCategory);
    });
});

// Initialize on load
window.addEventListener('DOMContentLoaded', () => {
    updateTable();
});
</script>



    <!-- Manage Users Section -->
    <div id="manageUsers" class="mb-5">
        <div class="section-title">Manage Users</div>
        <div class="card p-4">
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['is_verified'] ? 'success' : 'warning'; ?>">
                                    <?php echo $user['is_verified'] ? 'Verified' : 'Unverified'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="admin_dashboard.php?delete_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

 <!-- View Purchases Section -->
<div id="viewPurchases" class="mb-5">
    <div class="section-title">View Purchases</div>

    <!-- User Filter -->
    <div class="mb-3">
        <form action="" method="GET" class="d-flex">
            <select name="user_id" class="form-select form-select-sm" aria-label="Select User">
                <option value="">Select User</option>
                <?php
                    // Query to get all users for the dropdown
                    $users_result = $conn->query("SELECT id, name FROM users");
                    while ($user = $users_result->fetch_assoc()) {
                        echo "<option value='{$user['id']}'>{$user['name']}</option>";
                    }
                ?>
            </select>
            <button type="submit" class="btn btn-outline-light btn-sm ms-2">Filter</button>
        </form>
    </div>

    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>User</th>
                        <th>Gadget</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        // Modify the query based on selected user
                        $query = "SELECT purchases.id, users.name AS user_name, gadgets.name AS gadget_name, 
                                  purchases.quantity, purchases.total_price, purchases.purchase_date 
                                  FROM purchases
                                  JOIN users ON purchases.user_id = users.id
                                  JOIN gadgets ON purchases.gadget_id = gadgets.id";

                        // If a user is selected, filter purchases by user_id
                        if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
                            $user_id = $_GET['user_id'];
                            $query .= " WHERE purchases.user_id = $user_id";
                        }

                        $purchases_result = $conn->query($query);
                        
                        while ($purchase = $purchases_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $purchase['id']; ?></td>
                        <td><?php echo htmlspecialchars($purchase['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($purchase['gadget_name']); ?></td>
                        <td><?php echo $purchase['quantity']; ?></td>
                        <td>Rs.<?php echo number_format($purchase['total_price'], 2); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($purchase['purchase_date'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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