<?php
// eSewa Payment Configuration
define('ESEWA_MERCHANT_CODE', 'EPAYTEST'); // Use your merchant code (EPAYTEST for testing)
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q'); // Your eSewa secret key
define('ESEWA_TEST_FORM_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
define('ESEWA_VERIFY_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/transrec'); // Payment Verification URL (test)

function generateEsewaSignature($data) {
    $signature_string = "total_amount={$data['total_amount']},transaction_uuid={$data['transaction_uuid']},product_code={$data['product_code']}";
    $signature = base64_encode(hash_hmac('sha256', $signature_string, ESEWA_SECRET_KEY, true));
    return $signature;
}
?>