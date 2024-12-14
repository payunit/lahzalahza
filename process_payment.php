<?php
require 'config.php';

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

// Function to get user's IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP']; // IP from shared internet
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR']; // IP passed from proxy
    } else {
        return $_SERVER['REMOTE_ADDR']; // Direct IP
    }
}

// Receive and sanitize input data
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

// Validate inputs
if (!$name || !$email || !$phone || !$currency || !$amount) {
    // Redirect back with an error message
    $error = urlencode('Invalid input data. Please go back and try again.');
    header("Location: index.php?error=$error");
    exit;
}

// Generate a secure, random invoice number
$invoice_id = generateInvoiceNumber();

// Create a verification token
$verification_token = hash_hmac('sha256', $invoice_id . $amount . $currency, SECRET_KEY);

// Prepare inv_details JSON as per CrossPayOnline documentation
$inv_details = [
    "inv_items" => [
        [
            "name" => $invoice_id, // Sending only the invoice number
            "quantity" => "1.00",
            "unitPrice" => number_format($amount, 2, '.', ''),
            "totalPrice" => number_format($amount, 2, '.', ''),
            "currency" => $currency
        ]
    ],
    "inv_info" => [
        ["row_title" => "Vat", "row_value" => "0"],
        ["row_title" => "Delivery", "row_value" => "0"],
        ["row_title" => "Promo Code", "row_value" => 0],
        ["row_title" => "Discounts", "row_value" => 0]
    ],
    "user" => [
        "userName" => $name
    ]
];

// Encode inv_details to JSON
$inv_details_json = json_encode($inv_details);

// Construct the payment URL
$payment_url = CROSSPAY_API_BASE_URL . "/api/createInvoiceByAccountLahza?" . http_build_query([
    'api_data'   => CROSSPAY_API_DATA,
    'invoice_id' => $invoice_id,
    'apiKey'     => CROSSPAY_API_KEY,
    'total'      => number_format($amount, 2, '.', ''),
    'currency'   => $currency,
    'inv_details'=> $inv_details_json,
    'return_url' => RETURN_URL,
    'email'      => $email,
    'mobile'     => $phone,
    'name'       => $name,
    'verification_token' => $verification_token
]);

// Get user's IP address
$userIP = getUserIP();

// Log the transaction details before redirecting
$logData = [
    'date'       => date('Y-m-d H:i:s'),
    'invoice_id' => $invoice_id,
    'name'       => $name,
    'email'      => $email,
    'phone'      => $phone,
    'currency'   => $currency,
    'amount'     => $amount,
    'ip_address' => $userIP // Add the IP address here
];
file_put_contents('transactions.log', json_encode($logData) . PHP_EOL, FILE_APPEND);

// Redirect the user to the CrossPay payment page
header('Location: ' . $payment_url);
exit;
?>
