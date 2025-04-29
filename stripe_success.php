<?php
session_start();
require 'vendor/autoload.php';
include 'db.php';

\Stripe\Stripe::setApiKey('sk_test_51QQkSDF5bVnd89ccv14S4NbraT5vaVK10mg8047ywCWQ8cJp5lSZ6EK0maRGmUm3WE3lV6o6mxq29irzMfByzYP700aKzTzO9I');

if (!isset($_GET['order_id']) || !isset($_GET['payment_id']) || !isset($_GET['session_id'])) {
    header("Location: checkout.php");
    exit();
}

$order_id = $_GET['order_id'];
$payment_id = $_GET['payment_id'];
$session_id = $_GET['session_id'];

try {
    // Retrieve the Stripe session
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    
    if ($session->payment_status == 'paid') {
        // Start transaction
        $conn->autocommit(FALSE);
        
        try {
            // 1. Update payment status to 'paid' in payments table
            $update_payment = "UPDATE payments SET status = 'paid', transaction_id = ? WHERE id = ?";
            $stmt_payment = $conn->prepare($update_payment);
            $stmt_payment->bind_param("si", $session->payment_intent, $payment_id);
            
            if (!$stmt_payment->execute()) {
                throw new Exception("Payment update failed: " . $stmt_payment->error);
            }
            
            // 2. Update order status and payment_status to 'paid' in orders table
            $update_order = "UPDATE orders SET status = 'processing', payment_status = 'paid' WHERE id = ?";
            $stmt_order = $conn->prepare($update_order);
            $stmt_order->bind_param("i", $order_id);
            
            if (!$stmt_order->execute()) {
                throw new Exception("Order update failed: " . $stmt_order->error);
            }
            
            // 3. Get cart items to record in purchases table
            $cart_items_sql = "SELECT c.gadget_id, c.quantity, g.price 
                             FROM cart c 
                             JOIN gadgets g ON c.gadget_id = g.id 
                             WHERE c.user_id = ?";
            $stmt_cart = $conn->prepare($cart_items_sql);
            $stmt_cart->bind_param("i", $_SESSION['user_id']);
            $stmt_cart->execute();
            $cart_items = $stmt_cart->get_result();
            
            // 4. Insert each cart item into purchases table
            $insert_purchase = "INSERT INTO purchases (user_id, gadget_id, quantity, total_price) VALUES (?, ?, ?, ?)";
            $stmt_purchase = $conn->prepare($insert_purchase);
            
            while ($item = $cart_items->fetch_assoc()) {
                $total_price = $item['price'] * $item['quantity'];
                $stmt_purchase->bind_param("iiid", $_SESSION['user_id'], $item['gadget_id'], $item['quantity'], $total_price);
                if (!$stmt_purchase->execute()) {
                    throw new Exception("Purchase record failed: " . $stmt_purchase->error);
                }
            }
            
            // 5. Clear the cart
            $clear_cart = "DELETE FROM cart WHERE user_id = ?";
            $stmt_clear = $conn->prepare($clear_cart);
            $stmt_clear->bind_param("i", $_SESSION['user_id']);
            if (!$stmt_clear->execute()) {
                throw new Exception("Cart clearance failed: " . $stmt_clear->error);
            }
            
            // 6. Clear session if direct purchase
            if (isset($_SESSION['checkout_items'])) {
                unset($_SESSION['checkout_items']);
                unset($_SESSION['is_direct_purchase']);
            }
            
            // Commit transaction if all operations succeeded
            $conn->commit();
            
            // Redirect to success page
            header("Location: order_success.php?order_id=$order_id");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Transaction failed: " . $e->getMessage());
            header("Location: stripe_failure.php?order_id=$order_id&payment_id=$payment_id");
            exit();
        }
    } else {
        // Payment not completed
        header("Location: stripe_failure.php?order_id=$order_id&payment_id=$payment_id");
        exit();
    }
} catch (Exception $e) { 
    // Handle error
    error_log("Stripe error: " . $e->getMessage());
    header("Location: stripe_failure.php?order_id=$order_id&payment_id=$payment_id");
    exit();
}
?>