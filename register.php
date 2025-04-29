<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Composer autoload
require 'db.php';

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];

    if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $msg = "Name must only contain letters and spaces!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email format!";
    } elseif (strlen($password_raw) < 6) {
        $msg = "Password must be at least 6 characters!";
    } else {
        // Check if user already exists
        $check = $conn->query("SELECT * FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            $msg = "Email already registered!";
        } else {
            $code = rand(100000, 999999);
            $_SESSION['register'] = [
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password_raw, PASSWORD_DEFAULT),
                'code' => $code
            ];

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nkmoviestheater@gmail.com';
                $mail->Password = 'clao qnfn wyfl tlmp'; // Gmail App Password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('nkmoviestheater@gmail.com', 'Hamro ElectroStore');
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'Email Verification Code - Hamro ElectroStore';
                $mail->Body = "
                      <div style='max-width:600px; margin:auto; font-family:Arial, sans-serif; background-color:#121212; color:#f0f0f0; padding:30px; border-radius:10px;'>
                        <div style='text-align:center; padding-bottom:15px;'>
                            <h2 style='color:#4CAF50;'>🔌 Hamro ElectroStore</h2>
                            <p style='font-size:14px; color:#ccc;'>Your trusted tech & gadget partner</p>
                        </div>
                
                        <div style='background-color:#1e1e1e; padding:25px; border-radius:8px; box-shadow:0 0 15px rgba(0,0,0,0.3);'>
                            <p style='font-size:16px;'>👋 Hello <strong>$name</strong>,</p>
                            <p style='font-size:15px;'>Thanks for signing up at <strong>Hamro ElectroStore</strong>! 🖥️📱</p>
                
                            <p style='margin-top:15px; font-size:15px;'>Here's your one-time verification code:</p>
                            <div style='text-align:center; margin:25px 0;'>
                                <span style='font-size:34px; color:#00e676; font-weight:bold; letter-spacing:6px;'>$code</span>
                            </div>
                
                            <p style='font-size:14px;'>Please enter this code on the verification page to confirm your email and activate your account.</p>
                            <p style='font-size:13px; color:#bbb;'>⚠️ If you did not request this, please ignore this message.</p>
                        </div>
                
                        <div style='margin-top:30px; text-align:center; font-size:12px; color:#888;'>
                            &copy; " . date("Y") . " Hamro ElectroStore • Powered by Niranjan 🛠️
                        </div>
                    </div>
                ";

                $mail->send();
                header("Location: verify.php");
                exit();
            } catch (Exception $e) {
                $msg = "Mailer Error: {$mail->ErrorInfo}";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="fav.png" type="image/x-icon">
    <style>
        body {
            background: linear-gradient(to right, #1f1c2c, #928dab);
            font-family: 'Segoe UI', sans-serif;
            color: #fff;
        }
        .register-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .form-control {
            border-radius: 10px;
            border: none;
            background: rgba(255,255,255,0.2);
            color: #fff;
        }
        .form-control::placeholder {
            color: #e0e0e0;
        }
        .form-control:focus {
            background-color: rgba(255,255,255,0.3);
            box-shadow: none;
        }
        .btn-register {
            background-color: #28a745;
            border: none;
            width: 100%;
            padding: 10px;
            font-weight: bold;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background-color: #218838;
        }
        .msg {
            margin-top: 15px;
            text-align: center;
            color: #ffcccb;
        }
        .redirect-btn a {
            background-color: #007bff;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            display: inline-block;
            margin-top: 20px;
        }
        .redirect-btn a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="register-container col-12 col-sm-10 col-md-6 col-lg-5">
            <h3 class="text-center mb-4">Register at <span class="text-warning">Hamro ElectroStore</span></h3>
            <form method="POST" id="registerForm">
                <div class="mb-3">
                    <input type="text" class="form-control" name="name" placeholder="Full Name" required>
                </div>
                <div class="mb-3">
                    <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Password (min 6 chars)" required>
                </div>
                <button type="submit" class="btn btn-register">Register</button>
                <?php if (!empty($msg)): ?>
                    <div class="msg"> <?php echo $msg; ?> </div>
                <?php endif; ?>
            </form>
            <div class="text-center redirect-btn">
                <a href="login.php">Already have an account? Login</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("registerForm").addEventListener("submit", function(e) {
            const name = document.querySelector("[name='name']").value.trim();
            const password = document.querySelector("[name='password']").value;
            const nameRegex = /^[a-zA-Z\s]+$/;

            if (!nameRegex.test(name)) {
                alert("Name must only contain letters and spaces.");
                e.preventDefault();
            } else if (password.length < 6) {
                alert("Password must be at least 6 characters.");
                e.preventDefault();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
