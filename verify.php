<?php
session_start();
require 'db.php';

$msg = "";
$showLoginBtn = false;

if (!isset($_SESSION['register'])) {
    header("Location: register.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputCode = $_POST['code'];
    $stored = $_SESSION['register'];

    if ($inputCode == $stored['code']) {
        $is_verified = 1;
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, verification_code, is_verified) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $stored['name'], $stored['email'], $stored['password'], $stored['code'], $is_verified);
        if ($stmt->execute()) {
            unset($_SESSION['register']);
            $msg = "✅ Email verified and account created!";
            $showLoginBtn = true;
        } else {
            $msg = "❌ Database error.";
        }
    } else {
        $msg = "❌ Incorrect verification code!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification | Hamro ElectroStore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="fav.png" type="image/x-icon">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #1f1c2c, #928dab);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            color: #333;
        }

        .container {
            background: #fff;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
        }

        h2{
            text-align: center;
            color: #007bff;
        }
        p {
            text-align: center;
            color: #555;
            font-size: 15px;
            margin-top: -10px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        input[type="text"], input[type="submit"], .login-btn {
            width: 100%;
            padding: 12px 15px;
            margin: 10px 0;
            border-radius: 10px;
            font-size: 16px;
        }

        input[type="text"] {
            border: 1px solid #ccc;
        }

        input[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #218838;
        }

        .msg {
            margin-top: 15px;
            text-align: center;
            color: #dc3545;
            font-weight: 500;
        }

        .login-btn {
            background-color: #007bff;
            color: white;
            border: none;
            margin-top: 15px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .login-btn:hover {
            background-color: #0056b3;
        }

        @media (max-width: 500px) {
            .container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
    <h2>Verify Your Email</h2>
<p>We’ve sent a 6-digit verification code to your email. Enter it below to complete your registration and activate your account at <strong>Hamro ElectroStore</strong>.</p>
<form method="POST">
    <input type="text" name="code" placeholder="6-digit Verification Code" maxlength="6" required />
    <input type="submit" value="Verify">
</form>

<?php if (!empty($msg)): ?>
    <div class="msg"><?php echo $msg; ?></div>
<?php endif; ?>

<?php if ($showLoginBtn): ?>
    <a href="login.php" class="login-btn">Go to Login</a>
<?php endif; ?>

    </div>
</body>
</html>
