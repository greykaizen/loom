<?php
$page_title = "Notification Settings";
include 'includes/header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get current preferences
$query = "SELECT * FROM notification_preferences WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
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
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

$prefs = mysqli_fetch_assoc($result);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_notifications = isset($_POST['comment_notifications']) ? 1 : 0;
    $upvote_notifications = isset($_POST['upvote_notifications']) ? 1 : 0;
    $downvote_notifications = isset($_POST['downvote_notifications']) ? 1 : 0;
    
    $update = "UPDATE notification_preferences SET 
               comment_notifications = ?, 
               upvote_notifications = ?, 
               downvote_notifications = ? 
               WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt, "iiii", $comment_notifications, $upvote_notifications, $downvote_notifications, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Notification settings updated successfully";
        
        // Refresh preferences
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $prefs = mysqli_fetch_assoc($result);
    } else {
        $error = "Error updating settings";
    }
}
?>

<div class="form-container">
    <h2 class="form-title">Notification Settings</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="comment_notifications" <?php echo $prefs['comment_notifications'] ? 'checked' : ''; ?>>
                Notify me when someone comments on my posts
            </label>
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="upvote_notifications" <?php echo $prefs['upvote_notifications'] ? 'checked' : ''; ?>>
                Notify me when someone upvotes my posts or comments
            </label>
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="downvote_notifications" <?php echo $prefs['downvote_notifications'] ? 'checked' : ''; ?>>
                Notify me when someone downvotes my posts or comments
            </label>
        </div>
        
        <button type="submit" class="btn btn-block">Save Settings</button>
        
        <div class="form-footer">
            <a href="profile.php">Back to Profile</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>