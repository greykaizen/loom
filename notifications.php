<?php
$page_title = "Notifications";
include 'includes/header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    mark_all_notifications_read($user_id);
    header("Location: notifications.php");
    exit;
}

// Get all notifications
$notifications = get_notifications($user_id, 50);
?>

<div class="content-area">
    <div class="notifications-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>Your Notifications</h1>
        <a href="notifications.php?mark_all_read=1" class="btn">Mark All as Read</a>
    </div>
    
    <div class="card">
        <?php if (count($notifications) > 0): ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                    <a href="<?php echo $notification['link']; ?>" class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['notification_id']; ?>">
                        <div class="notification-content"><?php echo $notification['content']; ?></div>
                        <div class="notification-details">
                            <?php if ($notification['actor_name']): ?>
                                <span class="notification-actor">From: <?php echo htmlspecialchars($notification['actor_name']); ?></span>
                            <?php endif; ?>
                            <span class="notification-time"><?php echo time_elapsed_string($notification['created_at']); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-notifications" style="padding: 30px; text-align: center;">
                <p>You don't have any notifications yet.</p>
                <a href="index.php" class="btn" style="margin-top: 15px;">Browse Posts</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notifications-list {
    border-top: 1px solid var(--border-color);
}
.notification-item {
    display: block;
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
    transition: background-color 0.3s;
}
.notification-item:hover {
    background-color: #f5f5f5;
    text-decoration: none;
}
.notification-item.unread {
    background-color: #e8f4fd;
}
.notification-content {
    margin-bottom: 8px;
    color: var(--text-color);
}
.notification-details {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: var(--text-secondary);
}
</style>

<?php include 'includes/footer.php'; ?>