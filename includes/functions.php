<?php
// Time formatter (e.g. "2 hours ago")
function time_elapsed_string($datetime) {
    $timezone = new DateTimeZone('Asia/Karachi'); // Pakistan timezone

    $now = new DateTime('now', $timezone);
    $ago = new DateTime($datetime, $timezone);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

// Sanitize user input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Get user vote on post/comment
function get_user_vote($user_id, $post_id = null, $comment_id = null) {
    global $conn;
    
    if ($post_id) {
        $sql = "SELECT vote_type FROM votes WHERE user_id = ? AND post_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    } else {
        $sql = "SELECT vote_type FROM votes WHERE user_id = ? AND comment_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $comment_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['vote_type'];
    }
    
    return 0; // No vote
}
// Add to functions.php
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


// Create a notification
function create_notification($user_id, $content, $link, $notification_type, $reference_id, $actor_id) {
    global $conn;
    
    // Check user preferences first
    $pref_query = "SELECT * FROM notification_preferences WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $pref_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // If no preferences set, create default
    if (mysqli_num_rows($result) == 0) {
        $insert = "INSERT INTO notification_preferences (user_id) VALUES (?)";
        $stmt = mysqli_prepare($conn, $insert);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Get default preferences
        $stmt = mysqli_prepare($conn, $pref_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    }
    
    $prefs = mysqli_fetch_assoc($result);
    
    // Check if user wants this notification type
    $create_notification = false;
    
    switch ($notification_type) {
        case 'comment':
            $create_notification = $prefs['comment_notifications'];
            break;
        case 'upvote':
            $create_notification = $prefs['upvote_notifications'];
            break;
        case 'downvote':
            $create_notification = $prefs['downvote_notifications'];
            break;
        default:
            $create_notification = true;
    }
    
    // Don't notify user about their own actions
    if ($user_id == $actor_id) {
        $create_notification = false;
    }
    
    if ($create_notification) {
        $insert = "INSERT INTO notifications (user_id, content, link, notification_type, reference_id, actor_id) 
                   VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert);
        mysqli_stmt_bind_param($stmt, "isssii", $user_id, $content, $link, $notification_type, $reference_id, $actor_id);
        mysqli_stmt_execute($stmt);
    }
}

// Get unread notification count
function get_unread_notification_count($user_id) {
    global $conn;
    
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    return $data['count'];
}

// Get notifications
function get_notifications($user_id, $limit = 10) {
    global $conn;
    
    $query = "SELECT n.*, u.username as actor_name 
              FROM notifications n 
              LEFT JOIN users u ON n.actor_id = u.user_id 
              WHERE n.user_id = ? 
              ORDER BY n.created_at DESC 
              LIMIT ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

// Mark notification as read
function mark_notification_read($notification_id) {
    global $conn;
    
    $query = "UPDATE notifications SET is_read = TRUE WHERE notification_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $notification_id);
    mysqli_stmt_execute($stmt);
}

// Mark all notifications as read
function mark_all_notifications_read($user_id) {
    global $conn;
    
    $query = "UPDATE notifications SET is_read = TRUE WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
}

function delete_post($post_id, $user_id) {
    global $conn;

    $query = "SELECT * FROM posts WHERE post_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        return false; // Not allowed to delete
    }

    $delete = "DELETE FROM posts WHERE post_id = ?";
    $stmt = mysqli_prepare($conn, $delete);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    return mysqli_stmt_execute($stmt);
}
?>
