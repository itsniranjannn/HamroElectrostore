<?php
session_start();
include 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Composer autoloader

$order_id = $_GET['order_id'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['name'] ?? '';
$amount = $_SESSION['amount'] ?? '';
$order_date = date('Y-m-d'); // Replace with actual order date if available
$payment_method = $_SESSION['payment_method'] ?? 'eSewa'; // Replace with actual method if stored

// Update database if order and payment IDs exist
if ($order_id && $payment_id) {
    $conn->query("UPDATE payments SET status = 'failed' WHERE id = '$payment_id'");
    $conn->query("UPDATE orders SET status = 'failed', payment_status = 'failed' WHERE id = '$order_id'");
}

// Send failure email with stylish template
if ($user_email) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nkmoviestheater@gmail.com'; // your SMTP email
        $mail->Password   = 'clao qnfn wyfl tlmp';       // your app password or secure method
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('nkmoviestheater@gmail.com', 'Hamro ElectroStore');
        $mail->addAddress($user_email, $user_name);

        $mail->isHTML(true);
        $mail->Subject = 'Payment Failed for Order #' . htmlspecialchars($order_id);

        $mail->Body = '
        <div style="max-width:600px;margin:auto;font-family:Arial,sans-serif;background-color:#121212;color:#f0f0f0;padding:30px;border-radius:10px;">
            <div style="text-align:center;padding-bottom:15px;border-bottom:1px solid #333;">
                <h2 style="color:#8f94fb;margin-bottom:5px;">🔌 Hamro ElectroStore</h2>
                <p style="font-size:14px;color:#ccc;">Your trusted tech & gadget partner</p>
            </div>
            <div style="background-color:#1e1e1e;padding:25px;border-radius:8px;margin-top:20px;box-shadow:0 0 15px rgba(0,0,0,0.3);">
                <h3 style="color:#ff4444;">❌ Payment Failed</h3>
                <p>Hello <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
                <p>We couldn\'t process your payment for Order #' . htmlspecialchars($order_id) . '.</p>
                <div style="background-color:#2a2a3c;padding:15px;border-radius:6px;margin:20px 0;">
                    <h4 style="margin-top:0;color:#8f94fb;">Order Details</h4>
                    <p><strong>Order #:</strong> ' . htmlspecialchars($order_id) . '</p>
                    <p><strong>Amount:</strong> Rs.' . htmlspecialchars($amount) . '</p>
                    <p><strong>Date:</strong> ' . htmlspecialchars($order_date) . '</p>
                    <p><strong>Payment Method:</strong> <span style="background-color:#020202;padding:3px 8px;border-radius:4px;display:inline-block;">' . htmlspecialchars($payment_method) . '</span></p>
                </div>
                <p style="font-size:14px;color:#bbb;border-top:1px solid #333;padding-top:15px;margin-bottom:5px;">
                    Need help? Contact us at <a href="mailto:support@hamroelectro.com" style="color:#8f94fb;">support@hamroelectro.com</a> or call +977 9816767996
                </p>
            </div>
            <div style="margin-top:30px;text-align:center;font-size:12px;color:#888;">
                &copy; ' . date("Y") . ' Hamro ElectroStore • Kapan, Kathmandu
            </div>
        </div>';

        $mail->send();
    } catch (Exception $e) {
        // Email failed but do not stop the flow
    }
}

// Redirect user back to cart or failure page
header("Location: cart.php");
exit();
