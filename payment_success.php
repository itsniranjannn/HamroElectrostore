<?php
session_start();
include 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Composer autoloader

if (!isset($_GET['order_id']) || !isset($_GET['payment_id'])) {
    $_SESSION['error'] = "Order ID or Payment ID not provided";
    header("Location: cart.php");
    exit();
}

$order_id = $conn->real_escape_string($_GET['order_id']);
$payment_id = $conn->real_escape_string($_GET['payment_id']);
$user_id = $_SESSION['user_id'] ?? 0;

// Collect eSewa response safely
$data = [
    'total_amount' => $_GET['total_amount'] ?? '',
    'transaction_uuid' => $_GET['transaction_uuid'] ?? '',
    'product_code' => $_GET['product_code'] ?? '',
    'signature' => $_GET['signature'] ?? ''
];

// Replace with your actual eSewa verification function
function verifyEsewaResponse($data) {
    // Implement your verification logic
    return true; // assume success for now
}

// Verify payment
if (!verifyEsewaResponse($data)) {
    $_SESSION['error'] = "Payment verification failed";

    $conn->query("UPDATE payments SET status = 'failed' WHERE id = '$payment_id'");
    $conn->query("UPDATE orders SET status = 'failed', payment_status = 'failed' WHERE id = '$order_id'");

    header("Location: payment_failure.php?order_id=$order_id&payment_id=$payment_id");
    exit();
}

// Payment is valid: start transaction
$conn->autocommit(FALSE);
try {
    // 1. Update payments table
    $stmt = $conn->prepare("UPDATE payments SET status='paid', transaction_id=? WHERE id=?");
    $stmt->bind_param("si", $data['transaction_uuid'], $payment_id);
    $stmt->execute();

    // 2. Update orders table
    $stmt = $conn->prepare("UPDATE orders SET status='processing', payment_status='paid' WHERE id=?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // 3. Record purchases from cart
    $stmt = $conn->prepare("SELECT c.gadget_id, c.quantity, g.price FROM cart c JOIN gadgets g ON c.gadget_id=g.id WHERE c.user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result();

    $stmt_insert = $conn->prepare("INSERT INTO purchases (user_id, gadget_id, quantity, total_price) VALUES (?, ?, ?, ?)");
    while ($item = $cart_items->fetch_assoc()) {
        $total_price = $item['price'] * $item['quantity'];
        $stmt_insert->bind_param("iiid", $user_id, $item['gadget_id'], $item['quantity'], $total_price);
        $stmt_insert->execute();
    }

    // 4. Clear cart or direct purchase session
    if (isset($_SESSION['checkout_items'])) {
        unset($_SESSION['checkout_items'], $_SESSION['is_direct_purchase']);
    } else {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Transaction failed: " . $e->getMessage();
    header("Location: payment_failure.php?order_id=$order_id&payment_id=$payment_id");
    exit();
}

// Send success email
$user_email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['name'] ?? '';
$amount = number_format((float)$data['total_amount'], 2);

if ($user_email) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nkmoviestheater@gmail.com';
        $mail->Password   = 'clao qnfn wyfl tlmp'; // secure in production
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('nkmoviestheater@gmail.com', 'Hamro ElectroStore');
        $mail->addAddress($user_email, $user_name);

        $mail->isHTML(true);
        $mail->Subject = 'Payment Successful for Order #' . htmlspecialchars($order_id);
        $mail->Body = '
        <div style="max-width:600px;margin:auto;font-family:Arial,sans-serif;background-color:#121212;color:#f0f0f0;padding:30px;border-radius:10px;">
            <div style="text-align:center;padding-bottom:15px;border-bottom:1px solid #333;">
                <h2 style="color:#8f94fb;margin-bottom:5px;">🔌 Hamro ElectroStore</h2>
                <p style="font-size:14px;color:#ccc;">Your trusted tech & gadget partner</p>
            </div>
            <div style="background-color:#1e1e1e;padding:25px;border-radius:8px;margin-top:20px;box-shadow:0 0 15px rgba(0,0,0,0.3);">
                <h3 style="color:#44ff44;">✅ Payment Successful</h3>
                <p>Hello <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
                <p>Your payment for Order #' . htmlspecialchars($order_id) . ' has been successfully received.</p>
                <div style="background-color:#2a2a3c;padding:15px;border-radius:6px;margin:20px 0;">
                    <h4 style="margin-top:0;color:#8f94fb;">Order Details</h4>
                    <p><strong>Order #:</strong> ' . htmlspecialchars($order_id) . '</p>
                    <p><strong>Amount:</strong> Rs.' . $amount . '</p>
                    <p><strong>Date:</strong> ' . date('Y-m-d H:i') . '</p>
                    <p><strong>Payment Method:</strong> <span style="background-color:#020202;padding:3px 8px;border-radius:4px;display:inline-block;">eSewa</span></p>
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
        // email failed, continue
    }
}

// Redirect to success page
header("Location: order_success.php?order_id=$order_id");
exit();
?>
