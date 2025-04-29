<?php
session_start();
require 'db.php';
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $userQuery = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($userQuery->num_rows > 0) {
        $user = $userQuery->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($_SESSION['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: home.php");
            }
            exit();
        } else {
            $msg = "Invalid password.";
        }
    } else {
        $msg = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="fav.png" type="image/x-icon">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap');

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #09002b, #000000);
            color: #f0f0f0;
            height: 100vh;
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.05);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .form-label, .form-control, .btn {
            font-size: 1rem;
        }

        .form-control {
            background-color: #1a1a2e;
            color: #fff;
            border: 1px solid #333;
        }

        .form-control::placeholder {
            color: #aaa;
        }

        .btn-primary {
            background-color: #5c2eff;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #7c4dff;
        }

        .btn-outline-primary {
            border-color: #5c2eff;
            color: #5c2eff;
        }

        .btn-outline-primary:hover {
            background-color: #5c2eff;
            color: #fff;
        }

        .text-primary {
            color: #cba3ff !important;
        }

        .msg {
            color: #ff4b5c;
            margin-top: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center align-items-center vh-100">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5">
            <div class="login-container">
                <h3 class="text-center text-primary mb-4">Login to Hamro ElectroStore</h3>

                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" name="email" id="email" placeholder="Enter email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
                            <span class="input-group-text" id="togglePassword">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>

                    <div class="forgot text-end mt-2">
                        <a href="forgot_password.php" class="text-decoration-none text-light">Forgot Password?</a>
                    </div>

                    <div class="mt-4 text-center">
                        <p>Don't have an account?</p>
                        <a href="register.php" class="btn btn-outline-primary w-100">Sign Up</a>
                    </div>

                    <?php if (!empty($msg)) : ?>
                        <div class="msg text-center"><?php echo htmlspecialchars($msg); ?></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById("togglePassword").addEventListener("click", function () {
        const passwordField = document.getElementById("password");
        const icon = document.getElementById("eyeIcon");
        const isPassword = passwordField.type === "password";
        passwordField.type = isPassword ? "text" : "password";
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
    });
</script>
</body>
</html>