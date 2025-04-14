<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'loom';

try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    
    if (!$conn) {
        throw new Exception(mysqli_connect_error());
    }
} catch (Exception $e) {
    // Log the error
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display user-friendly message
    // include_once 'error.php';
    echo "Error has occued on db end";
    exit;
}
?>