<?php
require 'config.php';
session_start();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    die('Invalid request method.');
}

// Receive and sanitize input
$invoice_id          = filter_input(INPUT_GET, 'invoice_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$is_paid             = filter_input(INPUT_GET, 'is_paid', FILTER_VALIDATE_INT);
$amount_received     = filter_input(INPUT_GET, 'amount', FILTER_VALIDATE_FLOAT);
$currency_received   = filter_input(INPUT_GET, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$hash                = filter_input(INPUT_GET, 'hash', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$crosspay_invoice_id = filter_input(INPUT_GET, 'crosspay_invoice_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Check mandatory parameters
if (!$invoice_id || !$hash) {
    die('Missing required parameters.');
}

// Retrieve invoice data from the database to validate against
// Assume you have a table "invoices" with columns: invoice_id, amount, currency, status
// Use PDO (as an example)
try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $stmt = $db->prepare("SELECT amount, currency, status FROM invoices WHERE invoice_id = :invoice_id LIMIT 1");
    $stmt->execute(['invoice_id' => $invoice_id]);
    $invoiceData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoiceData) {
        die('Invoice not found.');
    }
    
    // Verify the hash
    // Check CrossPay documentation for what needs to be included in the signature.
    // Below is an example assuming only invoice_id is used in the hash.
    $data_to_sign = $invoice_id;
    $expected_hash = hash_hmac('sha256', $data_to_sign, CROSSPAY_API_KEY);
    
    if ($hash !== $expected_hash) {
        die('Invalid callback: hash mismatch');
    }
    
    // Compare received amount and currency with database records to prevent tampering
    if (floatval($invoiceData['amount']) !== floatval($amount_received) || $invoiceData['currency'] !== $currency_received) {
        die('Data mismatch: invoice details do not match stored records.');
    }
    
    // If reached here, the request is valid and the payment status is determined by is_paid
    // Update the invoice status in the database if needed
    if ($is_paid == 1 && $invoiceData['status'] !== 'paid') {
        $updateStmt = $db->prepare("UPDATE invoices SET status = 'paid', paid_at = NOW(), crosspay_invoice_id = :cpi WHERE invoice_id = :id");
        $updateStmt->execute(['id' => $invoice_id, 'cpi' => $crosspay_invoice_id]);
    }
    
    // Define payment status messages
    $payment_status = ($is_paid == 1) ? 'Payment Successful!' : 'Payment Failed!';
    $status_class = ($is_paid == 1) ? 'success' : 'failed';
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #009efd, #2af598);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            direction: ltr;
        }
        .container {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 450px;
            width: 100%;
        }
        .container h1 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .failed {
            color: #dc3545;
            font-weight: bold;
        }
        .details {
            margin-top: 20px;
            font-size: 16px;
            color: #333;
        }
        .details p {
            margin: 5px 0;
        }
        .btn {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            color: #fff;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .icon {
            font-size: 50px;
            margin-bottom: 20px;
        }
        .success .icon {
            color: #28a745;
        }
        .failed .icon {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="<?php echo $status_class; ?>">
            <div class="icon">
                <?php if ($is_paid == 1): ?>
                    &#10004; <!-- Check mark -->
                <?php else: ?>
                    &#10006; <!-- Cross mark -->
                <?php endif; ?>
            </div>
            <h1><?php echo htmlspecialchars($payment_status, ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <div class="details">
            <p>Invoice ID: <?php echo htmlspecialchars($invoice_id, ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Amount Paid: <?php echo htmlspecialchars($amount_received, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($currency_received, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <a href="index.php" class="btn">Return to Homepage</a>
    </div>
</body>
</html>
