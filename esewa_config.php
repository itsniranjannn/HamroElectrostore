<?php
// eSewa Test Credentials
define('ESEWA_TEST_FORM_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
define('ESEWA_MERCHANT_CODE', 'EPAYTEST');
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');

/**
 * Generate eSewa signature for request data.
 *
 * @param array $data Must contain 'total_amount', 'transaction_uuid', 'product_code'
 * @return string Base64-encoded HMAC-SHA256 signature
 */
function generateEsewaSignature($data) {
    if (!isset($data['total_amount'], $data['transaction_uuid'], $data['product_code'])) {
        return '';
    }
    $message = "total_amount={$data['total_amount']},transaction_uuid={$data['transaction_uuid']},product_code={$data['product_code']}";
    return base64_encode(hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true));
}

/**
 * Verify eSewa response signature.
 *
 * @param array $data Must contain 'total_amount', 'transaction_uuid', 'product_code', 'signature'
 * @return bool True if signature matches, false otherwise
 */
function verifyEsewaResponse($data) {
    foreach (['total_amount', 'transaction_uuid', 'product_code', 'signature'] as $key) {
        if (!isset($data[$key])) {
            return false;
        }
    }
    $message = "total_amount={$data['total_amount']},transaction_uuid={$data['transaction_uuid']},product_code={$data['product_code']}";
    $computed_signature = base64_encode(hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true));
    // Use hash_equals for timing-safe comparison
    return hash_equals(trim($computed_signature), trim($data['signature']));
}
?>
