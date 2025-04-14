<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Q&A Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?php echo is_logged_in() ? 'logged-in' : ''; ?>">
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">Q&A Platform</a>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="create-post.php">Create Post</a></li>
                        <li class="notification-dropdown">
                            <a href="javascript:void(0)" class="notification-bell">
                                <i class="fas fa-bell"></i>
                                <?php 
                                $unread_count = get_unread_notification_count($_SESSION['user_id']);
                                if ($unread_count > 0): 
                                ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-content">
                                <div class="dropdown-header">
                                    <h3>Notifications</h3>
                                    <a href="notifications.php" class="view-all">View All</a>
                                </div>
                                <div class="notification-list">
                                    <?php
                                    $notifications = get_notifications($_SESSION['user_id'], 5);
                                    if (count($notifications) > 0):
                                        foreach ($notifications as $notification):
                                    ?>
                                        <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['notification_id']; ?>">
                                            <div class="notification-content"><?php echo $notification['content']; ?></div>
                                            <div class="notification-time"><?php echo time_elapsed_string($notification['created_at']); ?></div>
                                        </a>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <div class="no-notifications">No notifications yet</div>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown-footer">
                                    <a href="notification_settings.php">Notification Settings</a>
                                </div>
                            </div>
                        </li>
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="signup.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">