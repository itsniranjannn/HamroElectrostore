<?php
session_start();
include 'db.php';

if (!isset($_GET['order_id']) || !isset($_GET['payment_id'])) {
    header("Location: checkout.php");
    exit();
}

$order_id = $_GET['order_id'];
$payment_id = $_GET['payment_id'];

// Update payment status to failed
$update_payment = "UPDATE payments SET status = 'failed' WHERE id = '$payment_id'";
$conn->query($update_payment);

// Update order status to failed
$update_order = "UPDATE orders SET status = 'failed' WHERE id = '$order_id'";
$conn->query($update_order);

// Get order details
$order_sql = "SELECT * FROM orders WHERE id = '$order_id'";
$order_result = $conn->query($order_sql);
$order = $order_result->fetch_assoc();

// Get user details
$user_sql = "SELECT * FROM users WHERE id = '{$_SESSION['user_id']}'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// Send failure email
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'nkmoviestheater@gmail.com';
    $mail->Password   = 'clao qnfn wyfl tlmp';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('nkmoviestheater@gmail.com', 'Hamro ElectroStore');
    $mail->addAddress($user['email'], $_SESSION['name']);
    
    $mail->isHTML(true);
    $mail->Subject = 'Payment Failed for Order #' . $order_id;
    
    $mail->Body = '
    <div style="max-width:600px; margin:auto; font-family:Arial, sans-serif; background-color:#121212; color:#f0f0f0; padding:30px; border-radius:10px;">
        <div style="text-align:center; padding-bottom:15px; border-bottom:1px solid #333;">
            <h2 style="color:#8f94fb; margin-bottom:5px;">🔌 Hamro ElectroStore</h2>
            <p style="font-size:14px; color:#ccc;">Your trusted tech & gadget partner</p>
        </div>

        <div style="background-color:#1e1e1e; padding:25px; border-radius:8px; margin-top:20px; box-shadow:0 0 15px rgba(0,0,0,0.3);">
            <h3 style="color:#ff6b6b; margin-top:0;">❌ Payment Failed</h3>
            <p style="font-size:15px;">Hello <strong>'.htmlspecialchars($_SESSION['name']).'</strong>,</p>
            <p style="font-size:15px;">We couldn\'t process your payment for Order #'.htmlspecialchars($order_id).'.</p>

            <div style="background-color:#24243e; padding:15px; border-radius:6px; margin:20px 0; border-left:4px solid #ff6b6b;">
                <h4 style="margin-top:0; color:#ff6b6b;">📦 Order #'.htmlspecialchars($order_id).'</h4>
                <p style="margin-bottom:5px;"><strong>Date:</strong> '.date('M j, Y', strtotime($order['order_date'])).'</p>
                <p style="margin-bottom:5px;"><strong>Amount:</strong> Rs.'.number_format($order['total_amount'], 2).'</p>
                <p style="margin-bottom:0;"><strong>Payment Method:</strong> Stripe</p>
            </div>

            <div style="text-align:center; margin:25px 0;">
                <a href="http://localhost/ecommerce-site/checkout.php" style="background-color:#4e44ce; color:white; padding:12px 25px; text-decoration:none; border-radius:5px; font-weight:bold; display:inline-block;">Try Again</a>
            </div>

            <p style="font-size:14px; color:#bbb; border-top:1px solid #333; padding-top:15px; margin-bottom:5px;">
                Need help? Contact us at <a href="mailto:support@hamroelectro.com" style="color:#8f94fb;">support@hamroelectro.com</a> or call +977 9816767996
            </p>
        </div>

        <div style="margin-top:30px; text-align:center; font-size:12px; color:#888;">
            &copy; '.date("Y").' Hamro ElectroStore • Kapan, Kathmandu
        </div>
    </div>';
    
    $mail->AltBody = "Payment Failed for Order #$order_id\n\nHello {$_SESSION['name']},\n\nWe couldn't process your payment for Order #$order_id.\n\nOrder Details:\nDate: ".date('M j, Y', strtotime($order['order_date']))."\nAmount: Rs.".number_format($order['total_amount'], 2)."\nPayment Method: Stripe\n\nPlease try again or contact support.";
    
    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
}

// Display failure message to user
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Failed - Hamro ElectroStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #0f0c29, #302b63, #24243e);
            color: white;
            font-family: 'Roboto', sans-serif;
        }
        .card {
            background-color: #2c2f48;
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card text-center p-5">
                    <i class="fas fa-times-circle fa-5x text-danger mb-4"></i>
                    <h1 class="mb-4">Payment Failed</h1>
                    <p class="mb-4">We couldn't process your payment for Order #<?php echo $order_id; ?>.</p>
                    <p class="mb-4">Please try again or contact support.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="checkout.php" class="btn btn-primary px-4">
                            <i class="fas fa-credit-card me-2"></i> Try Again
                        </a>
                        <a href="user_orders.php" class="btn btn-outline-light px-4">
                            <i class="fas fa-clipboard-list me-2"></i> My Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>