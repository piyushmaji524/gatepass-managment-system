<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'db_user');
define('DB_PASS', 'db_password');
define('DB_NAME', 'gatepass_db');

// Application Constants
define('APP_NAME', 'Gatepass Management System');
define('APP_URL', 'https://example.com');

// Session Configuration
session_start();

// Error Reporting (turn off in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Date/Time Configuration
date_default_timezone_set('Asia/Kolkata');

// Connect to Database
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Helper Functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Log System Activity
function logActivity($userId, $action, $details = '') {
    $conn = connectDB();
    $userId = (int)$userId;
    $action = $conn->real_escape_string($action);
    $details = $conn->real_escape_string($details);
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $conn->real_escape_string($_SERVER['HTTP_USER_AGENT']);
    
    $sql = "INSERT INTO logs (user_id, action, details, ip_address, user_agent) 
            VALUES ($userId, '$action', '$details', '$ipAddress', '$userAgent')";
    $conn->query($sql);
    $conn->close();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user has specific role
function hasRole($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return $_SESSION['role'] == $requiredRole;
}

// Redirect with message
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

// Generate random string for IDs, etc.
function generateRandomString($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Format date and time
function formatDateTime($datetime, $format = 'd M Y, h:i A') {
    $dt = new DateTime($datetime);
    return $dt->format($format);
}

// Send email notification
function sendEmailNotification($recipient_email, $recipient_name, $subject, $message, $gatepass_data = [], $action_url = '', $action_text = '') {
    // Email configuration
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . APP_NAME . " <noreply@example.com>" . "\r\n";
    
    // Extract variables for the template
    $email_subject = $subject;
    $email_message = $message;
    
    // Set gatepass details
    if (!empty($gatepass_data)) {
        extract($gatepass_data);
    }
    
    // Start output buffering
    ob_start();
    
    // Include the email template file
    include_once '../templates/email_template.php';
    
    // Get the HTML content from the buffer
    $html_message = ob_get_clean();
    
    // Send the email
    $mail_sent = mail($recipient_email, $subject, $html_message, $headers);
    
    // Log the email activity
    if ($mail_sent) {
        logActivity(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null, 'EMAIL_SENT', "Email sent to $recipient_email: $subject");
    } else {
        logActivity(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null, 'EMAIL_FAILED', "Failed to send email to $recipient_email: $subject");
    }
    
    return $mail_sent;
}
?>
