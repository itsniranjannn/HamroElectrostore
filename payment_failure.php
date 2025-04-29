<?php
session_start();
include 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the failure
error_log("eSewa payment failure handler triggered. GET params: " . print_r($_GET, true));

// Redirect if necessary parameters are missing
if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: checkout.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $conn->real_escape_string($_GET['order_id']);

// Fetch the order
$order_sql = "SELECT o.*, u.email, u.name 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = '$order_id' AND o.user_id = '$user_id'";
$order_result = $conn->query($order_sql);

if ($order_result && $order_result->num_rows > 0) {
    $order = $order_result->fetch_assoc();

    // Begin database transaction
    $conn->begin_transaction();
    try {
        // Update payment status if payment_id exists
        if (isset($_GET['payment_id'])) {
            $payment_id = $conn->real_escape_string($_GET['payment_id']);
            $update_payment = "UPDATE payments SET status = 'failed' WHERE id = '$payment_id'";
            if (!$conn->query($update_payment)) {
                throw new Exception("Payment status update failed: " . $conn->error);
            }
        }

        // Update order status
        $update_order = "UPDATE orders SET status = 'failed', payment_status = 'failed' 
                         WHERE id = '$order_id' AND user_id = '$user_id'";
        if (!$conn->query($update_order)) {
            throw new Exception("Order status update failed: " . $conn->error);
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
    }

    // Send payment failure email
    try {
        $mail = new PHPMailer(true);

        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nkmoviestheater@gmail.com'; // Ideally load this from secure config
        $mail->Password = 'clao qnfn wyfl tlmp';         // Ideally load this from secure config
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email settings
        $mail->setFrom('nkmoviestheater@gmail.com', 'Hamro ElectroStore');
        $mail->addAddress($order['email'], $order['name']);
        $mail->isHTML(true);
        $mail->Subject = 'Payment Failed for Order #' . htmlspecialchars($order_id);

        $order_date = date('M j, Y', strtotime($order['order_date']));
        $payment_method = htmlspecialchars(ucwords($order['payment_method']));
        $amount = number_format($order['total_amount'], 2);

        $mail->Body = '
        <div style="max-width:600px;margin:auto;font-family:Arial,sans-serif;background-color:#121212;color:#f0f0f0;padding:30px;border-radius:10px;">
            <div style="text-align:center;padding-bottom:15px;border-bottom:1px solid #333;">
                <h2 style="color:#8f94fb;margin-bottom:5px;">🔌 Hamro ElectroStore</h2>
                <p style="font-size:14px;color:#ccc;">Your trusted tech & gadget partner</p>
            </div>
            <div style="background-color:#1e1e1e;padding:25px;border-radius:8px;margin-top:20px;box-shadow:0 0 15px rgba(0,0,0,0.3);">
                <h3 style="color:#ff4444;">❌ Payment Failed</h3>
                <p>Hello <strong>' . htmlspecialchars($order['name']) . '</strong>,</p>
                <p>We couldn\'t process your payment for Order #' . htmlspecialchars($order_id) . '.</p>
                <div style="background-color:#2a2a3c;padding:15px;border-radius:6px;margin:20px 0;">
                    <h4 style="margin-top:0;color:#8f94fb;">Order Details</h4>
                    <p><strong>Order #:</strong> ' . htmlspecialchars($order_id) . '</p>
                    <p><strong>Amount:</strong> Rs.' . $amount . '</p>
                    <p><strong>Date:</strong> ' . $order_date . '</p>
                    <p><strong>Payment Method:</strong> <span style="background-color:#020202;padding:3px 8px;border-radius:4px;display:inline-block;">' . $payment_method . '</span></p>
                </div>
                <p style="font-size:14px;color:#bbb;border-top:1px solid #333;padding-top:15px;margin-bottom:5px;">
                    Need help? Contact us at <a href="mailto:support@hamroelectro.com" style="color:#8f94fb;">support@hamroelectro.com</a> or call +977 9816767996
                </p>
            </div>
            <div style="margin-top:30px;text-align:center;font-size:12px;color:#888;">
                &copy; ' . date("Y") . ' Hamro ElectroStore • Kapan, Kathmandu
            </div>
        </div>';

        $mail->AltBody = "Payment Failed\n\n" .
            "Hello " . $order['name'] . ",\n\n" .
            "We couldn't process your payment for Order #" . $order_id . ".\n\n" .
            "Order #: " . $order_id . "\n" .
            "Amount: Rs." . $amount . "\n" .
            "Date: " . $order_date . "\n" .
            "Payment Method: " . $order['payment_method'] . "\n\n" .
            "Please try again at: http://localhost/ecommerce-site/checkout.php\n\n" .
            "Support: support@hamroelectro.com | +977 9816767996";

        $mail->send();
        error_log("Failure email sent successfully to " . $order['email']);
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }
}

// Set error message and redirect
$_SESSION['error'] = "Payment failed. Please try again.";
header("Location: checkout.php");
exit();
?>
