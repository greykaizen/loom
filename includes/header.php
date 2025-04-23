<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Q&A Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/responsive.css">
    <!-- <link rel="stylesheet" href="css/login.css"> -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="<?php echo is_logged_in() ? 'logged-in' : ''; ?>">
    <header>
        <div class="container header-container">
            <div class="logo">
                <a href="index.php">
                    <i class="fas fa-project-diagram"></i>
                    <span>loom</span>
                </a>
            </div>

            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

            <nav id="mainNav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <?php if (is_logged_in()): ?>
                        <li style="background-color: #e6f7ff; color: #0074d9; border-radius: 12px;">
                            <a href="create-post.php" style="color: inherit; text-decoration: none;">
                                <i class="fas fa-plus-circle"></i> Create Post
                            </a>
                        </li>
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
                    <a href="<?php echo $notification['link']; ?>"
                        class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-content"><?php echo $notification['content']; ?></div>
                        <div class="notification-time">
                            <?php echo time_elapsed_string($notification['created_at']); ?></div>
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
            <!-- Add Mark as Read Button -->
            <?php if ($unread_count > 0): ?>
    <!-- <form method="post" action="functions.php?action=mark_all_read=1" style="display: inline;"> -->
        <button type="submit" class="btn btn-sm btn-secondary" style="margin-left: 10px;">
        <a href="notifications.php?mark_all_read=1" class="btn">Mark All as Read</a>

            Mark All as Read
        </button>
    </form>
<?php endif; ?>
        </div>
    </div>
</li>
                        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">