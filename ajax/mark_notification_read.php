<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth_functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

if ($notification_id > 0) {
    // Verify notification belongs to user
    $query = "SELECT user_id FROM notifications WHERE notification_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $notification_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $notification = mysqli_fetch_assoc($result);
        if ($notification['user_id'] == $user_id) {
            mark_notification_read($notification_id);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
}
?>