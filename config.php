
<?php
// Enable error displaying (for debugging purposes only)
// Remove or comment out these lines in a production environment
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CrossPay API credentials (Replace with your actual credentials)
define('CROSSPAY_API_DATA', 'XXXXXXXXXXXXXXXXXXXXXXX'); // Your actual api_data
define('CROSSPAY_API_KEY', 'XXXXXXXXXXXXXXXXXXXXXXX'); // Your actual apiKey
define('CROSSPAY_API_BASE_URL', 'https://crosspayonline.com'); // Base URL for CrossPay API
define('RETURN_URL', 'https://payXXXXXXXXXXXX.COM/success.php'); // Return URL for CrossPay

// Strong secret key for generating verification tokens
define('SECRET_KEY', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');

// Function to generate a random and secure invoice number
function generateInvoiceNumber() {
    $prefix = 'PAY-'; // Optional prefix
    $randomNumber = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8); // Random alphanumeric characters
    return $prefix . $randomNumber;
}
?>

