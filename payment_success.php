<?php
session_start();
include 'db.php';
include 'esewa_config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

error_log("=== eSewa callback start ===");
error_log("GET parameters: " . print_r($_GET, true));

// Step 1: Validate required GET parameters
$required_params = ['refId', 'transaction_uuid', 'signature', 'total_amount', 'product_code'];
foreach ($required_params as $param) {
    if (empty($_GET[$param])) {
        error_log("Missing required GET parameter: $param");
        header("Location: payment_failure.php?reason=missing_$param");
        exit();
    }
}

// Escape inputs
$refId = $conn->real_escape_string($_GET['refId']);
$transaction_uuid = $conn->real_escape_string($_GET['transaction_uuid']);
$amount = $conn->real_escape_string($_GET['total_amount']);
$received_signature = $conn->real_escape_string($_GET['signature']);

// Step 2: Signature verification
$data_for_signature = [
    'total_amount' => $amount,
    'transaction_uuid' => $transaction_uuid,
    'product_code' => ESEWA_MERCHANT_CODE
];

$expected_signature = generateEsewaSignature($data_for_signature);
if (!hash_equals($expected_signature, $received_signature)) {
    error_log("Signature mismatch");
    header("Location: payment_failure.php?reason=invalid_signature");
    exit();
}

// Step 3: Fetch payment
$order_query = "SELECT * FROM payments WHERE transaction_id = '$transaction_uuid' AND amount = '$amount' LIMIT 1";
$order_result = $conn->query($order_query);

if ($order_result && $order_result->num_rows > 0) {
    $payment = $order_result->fetch_assoc();
    $order_id = $payment['order_id'];
    $payment_id = $payment['id'];
} else {
    error_log("Payment not found for transaction: $transaction_uuid");
    header("Location: payment_failure.php?reason=order_not_found");
    exit();
}

// Step 4: Fetch order and user
$order_sql = "SELECT o.*, u.id as user_id, u.email 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = '$order_id'";
$order_result = $conn->query($order_sql);

if (!$order_result || $order_result->num_rows === 0) {
    error_log("Order not found for ID: $order_id");
    header("Location: payment_failure.php?reason=order_not_found");
    exit();
}

$order = $order_result->fetch_assoc();
$user_id = $order['user_id'];

// Step 5: Verify payment with eSewa
$verify_payload = [
    'merchant_code' => ESEWA_MERCHANT_CODE,
    'transaction_uuid' => $transaction_uuid,
    'total_amount' => $amount,
    'reference_code' => $refId
];

$ch = curl_init(ESEWA_VERIFY_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verify_payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    error_log("eSewa verification failed: $curl_error");
    header("Location: payment_failure.php?reason=server_error");
    exit();
}

$response_data = json_decode($response, true);
error_log("eSewa verification response: " . print_r($response_data, true));

// Step 6: If payment is confirmed
if ($http_code === 200 && isset($response_data['status']) && $response_data['status'] === 'COMPLETE') {
    error_log("Verified: Payment COMPLETE");

    $conn->begin_transaction();

    try {
        // Update order
        $conn->query("UPDATE orders 
                      SET status = 'processing', 
                          payment_status = 'paid', 
                          payment_verified_at = NOW() 
                      WHERE id = '$order_id'");

        // Update payment (using ID, important)
        $conn->query("UPDATE payments 
                      SET transaction_id = '$refId', 
                          payment_method = 'esewa', 
                          amount = '$amount', 
                          status = 'paid', 
                          created_at = NOW() 
                      WHERE id = '$payment_id'");

        // Insert into purchases
        $conn->query("INSERT INTO purchases (user_id, gadget_id, purchase_date, total_price, quantity)
                      SELECT '$user_id', gadget_id, NOW(), unit_price * quantity, quantity
                      FROM order_items
                      WHERE order_id = '$order_id'");

        // Clear cart
        $conn->query("DELETE FROM cart WHERE user_id = '$user_id'");

        $conn->commit();
        error_log("Transaction committed for Order #$order_id");

        // Clear session
        unset($_SESSION['checkout_items']);
        unset($_SESSION['is_direct_purchase']);

        $_SESSION['order_id'] = $order_id;
        header("Location: order_success.php?order_id=$order_id");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction error: " . $e->getMessage());
        header("Location: payment_failure.php?reason=transaction_failed");
        exit();
    }

} else {
    error_log("Verification failed or status != COMPLETE");
    header("Location: payment_failure.php?reason=verification_failed");
    exit();
}
?>
