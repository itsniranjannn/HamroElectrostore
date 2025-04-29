<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 
include 'db.php'; 
$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Generate 6-digit code
        $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt_update = $conn->prepare("UPDATE users SET reset_code = ?, reset_code_expiry = ? WHERE email = ?");
        $stmt_update->bind_param("sss", $code, $expiry, $email);
        $stmt_update->execute();

        // Send email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Your SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = 'nkmoviestheater@gmail.com'; // Update with your email
            $mail->Password   = 'clao qnfn wyfl tlmp'; // Gmail App password or your SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('nkmoviestheater@gmail.com', 'Hamro ElectroStore');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code - Hamro ElectroStore';
            
            // Styled email body
            $mail->Body = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                    }
                    .header {
                        text-align: center;
                        padding: 20px 0;
                        background: #4e54c8;
                        color: white;
                        border-radius: 8px 8px 0 0;
                    }
                    .content {
                        padding: 20px;
                        background: #f9f9f9;
                        border-radius: 0 0 8px 8px;
                    }
                    .code {
                        font-size: 24px;
                        font-weight: bold;
                        text-align: center;
                        margin: 20px 0;
                        padding: 15px;
                        background: #e9e9e9;
                        border-radius: 5px;
                        letter-spacing: 3px;
                        color: #4e54c8;
                    }
                    .footer {
                        margin-top: 20px;
                        font-size: 12px;
                        text-align: center;
                        color: #777;
                    }
                    .button {
                        display: inline-block;
                        padding: 10px 20px;
                        background: #4e54c8;
                        color: white;
                        text-decoration: none;
                        border-radius: 5px;
                        margin-top: 15px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>Hamro ElectroStore</h2>
                    <h3>Password Reset Code</h3>
                </div>
                <div class="content">
                    <p>Hello,</p>
                    <p>We received a request to reset your password. Please use the following verification code:</p>
                    
                    <div class="code">' . $code . '</div>
                    
                    <p>This code will expire in 15 minutes. If you didn\'t request this, please ignore this email.</p>
                    
                    <p>Thank you,<br>Hamro ElectroStore Team</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' Hamro ElectroStore. All rights reserved.</p>
                </div>
            </body>
            </html>
            ';

            // Plain text version for non-HTML email clients
            $mail->AltBody = "Password Reset Code\n\n" .
                            "Your verification code is: $code\n" .
                            "This code will expire in 15 minutes.\n\n" .
                            "If you didn't request this, please ignore this email.\n\n" .
                            "Thank you,\nHamro ElectroStore Team";

            $mail->send();
            $success = "A 6-digit verification code has been sent to your email.";
        } catch (Exception $e) {
            $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "No account found with that email.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Hamro ElectroStore</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e54c8;
            --secondary-color: #8f94fb;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --error-color: #dc3545;
            --success-color: #28a745;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-color);
        }
        
        .container {
            width: 100%;
            max-width: 420px;
            margin: 20px;
            padding: 30px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        h2 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .logo {
            margin-bottom: 20px;
        }
        
        .logo img {
            height: 50px;
        }
        
        form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        input[type="email"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(78, 84, 200, 0.2);
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            background: linear-gradient(to right, #4348a8, #7a80e6);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
        }
        
        .message {
            margin-top: 20px;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .error {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .back-to-login {
            margin-top: 20px;
            font-size: 14px;
        }
        
        .back-to-login a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="https://via.placeholder.com/150x50?text=Hamro+ElectroStore" alt="Hamro ElectroStore Logo">
        </div>
        <h2>Forgot Password</h2>
        <p>Enter your email address to receive a 6-digit verification code</p>
        
        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="your@email.com" required>
            </div>
            <button type="submit">Send Verification Code</button>
        </form>
        
        <?php if ($success): ?>
            <div class="message success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="back-to-login">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>