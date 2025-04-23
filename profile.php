<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/header.php';

// Function to check for banned words
// function containsBannedWords($conn, $content) {
//     $content = strtolower($content);
//     $query = "SELECT LOWER(word) as word FROM banned_words";
//     $result = mysqli_query($conn, $query);
    
//     if (!$result) {
//         error_log("Database error in containsBannedWords: " . mysqli_error($conn));
//         return false;
//     }
    
//     while ($row = mysqli_fetch_assoc($result)) {
//         if (strpos($content, $row['word']) !== false) {
//             return true;
//         }
//     }
//     return false;
// }

// Handle post deletion
if (isset($_GET['delete']) && is_logged_in()) {
    $post_id = (int)$_GET['delete'];

    // Verify post belongs to logged-in user
    $check_query = "SELECT * FROM posts WHERE post_id = ? AND user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    
    if (!$check_stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, "ii", $post_id, $_SESSION['user_id']);
    
    if (!mysqli_stmt_execute($check_stmt)) {
        die("Execute failed: " . mysqli_stmt_error($check_stmt));
    }
    
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        // Delete the post
        $delete_query = "DELETE FROM posts WHERE post_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        
        if (!$delete_stmt) {
            die("Prepare failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($delete_stmt, "i", $post_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $_SESSION['message'] = "Post deleted successfully.";
        } else {
            $_SESSION['message'] = "Error deleting post: " . mysqli_stmt_error($delete_stmt);
        }
        
        mysqli_stmt_close($delete_stmt);
    } else {
        $_SESSION['message'] = "Post not found or you don't have permission to delete it.";
    }

    mysqli_stmt_close($check_stmt);
    
    // Redirect to current profile page
    $redirect_url = "profile.php";
    if (isset($_GET['user'])) {
        $redirect_url .= "?user=" . urlencode($_GET['user']);
    }
    header("Location: " . $redirect_url);
    exit;
}

// Check if viewing own profile or someone else's
if (isset($_GET['user'])) {
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $_GET['user'])) {
        header("Location: index.php");
        exit;
    }
    
    $username = sanitize_input($_GET['user']);

    $user_query = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    if (!$stmt) {
        die("Database error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        header("Location: index.php");
        exit;
    }

    $user = mysqli_fetch_assoc($result);
    $page_title = htmlspecialchars($user['username']) . "'s Profile";
    $viewing_own = is_logged_in() && $_SESSION['username'] === $username;
    mysqli_stmt_close($stmt);
} else {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }

    $username = $_SESSION['username'];

    $user_query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    if (!$stmt) {
        die("Database error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    $page_title = "My Profile";
    $viewing_own = true;
    mysqli_stmt_close($stmt);
}

// Handle moderation toggle
// if ($viewing_own && isset($_GET['enable_moderation'])) {
//     // Get the new moderation state (0 or 1)
//     $enable = (int)$_GET['enable_moderation'];

//     // Update user's moderation setting in database
//     $update_query = "UPDATE users SET moderation_enabled = ? WHERE user_id = ?";
//     $stmt = mysqli_prepare($conn, $update_query);
//     mysqli_stmt_bind_param($stmt, "ii", $enable, $_SESSION['user_id']);
//     mysqli_stmt_execute($stmt);
//     mysqli_stmt_close($stmt);

//     // If enabling moderation, scan and clean existing posts
//     if ($enable) {
//         // Get all user's posts
//         $posts_query = "SELECT post_id, content FROM posts WHERE user_id = ?";
//         $stmt = mysqli_prepare($conn, $posts_query);
//         mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
//         mysqli_stmt_execute($stmt);
//         $result = mysqli_stmt_get_result($stmt);
        
//         $deleted_count = 0;
//         while ($post = mysqli_fetch_assoc($result)) {
//             // Check for banned words
//             if (containsBannedWords($conn, $post['content'])) {
//                 // Delete post if contains banned words
//                 $delete_query = "DELETE FROM posts WHERE post_id = ?";
//                 $delete_stmt = mysqli_prepare($conn, $delete_query);
//                 mysqli_stmt_bind_param($delete_stmt, "i", $post['post_id']);
//                 mysqli_stmt_execute($delete_stmt);
//                 mysqli_stmt_close($delete_stmt);
//                 $deleted_count++;
//             }
//         }
//         mysqli_stmt_close($stmt);

//         // Set appropriate feedback message
//         if ($deleted_count > 0) {
//             $_SESSION['message'] = "Moderation enabled. $deleted_count posts with banned words were removed.";
//         } else {
//             $_SESSION['message'] = "Moderation enabled. No posts with banned words found.";
//         }
//     } else {
//         $_SESSION['message'] = "Moderation disabled.";
//     }

    // Preserve URL parameters during redirect
//     $redirect_url = "profile.php";
//     if (isset($_GET['user'])) {
//         $redirect_url .= "?user=" . urlencode($_GET['user']);
//     }
    
//     // Redirect back to profile page
//     header("Location: " . $redirect_url);
//     exit;
// }

// Check for banned words in existing posts if moderation is enabled
// if ($viewing_own && !empty($user['moderation_enabled'])) {
//     $posts_query = "SELECT post_id, content FROM posts WHERE user_id = ?";
//     $stmt = mysqli_prepare($conn, $posts_query);
//     mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
//     mysqli_stmt_execute($stmt);
//     $result = mysqli_stmt_get_result($stmt);

//     while ($post = mysqli_fetch_assoc($result)) {
//         if (containsBannedWords($conn, $post['content'])) {
//             $delete_query = "DELETE FROM posts WHERE post_id = ?";
//             $delete_stmt = mysqli_prepare($conn, $delete_query);
//             mysqli_stmt_bind_param($delete_stmt, "i", $post['post_id']);
//             mysqli_stmt_execute($delete_stmt);
//             mysqli_stmt_close($delete_stmt);
//         }
//     }
//     mysqli_stmt_close($stmt);
// }

$profile_image = 'uploads/profile_pictures/default.jpg';
if (!empty($user['profile_picture'])) {
    $allowed_path = 'uploads/profile_pictures/';
    $user_image = basename($user['profile_picture']);
    $full_path = $allowed_path . $user_image;
    if (file_exists($full_path) && is_file($full_path)) {
        $profile_image = $full_path;
    }
}

$posts_query = "SELECT p.*, COUNT(c.comment_id) as comment_count 
                FROM posts p 
                LEFT JOIN comments c ON p.post_id = c.post_id 
                WHERE p.user_id = ? 
                GROUP BY p.post_id, p.title, p.content, p.user_id, p.category, p.created_at, p.upvotes, p.downvotes
                ORDER BY p.created_at DESC 
                LIMIT 10";
$stmt = mysqli_prepare($conn, $posts_query);
if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
mysqli_stmt_execute($stmt);
$posts_result = mysqli_stmt_get_result($stmt);
?>

<main class="container">
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile picture">
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo htmlspecialchars($user['karma']); ?></div>
                        <div class="stat-label">Karma</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo mysqli_num_rows($posts_result); ?></div>
                        <div class="stat-label">Posts</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php echo time_elapsed_string($user['created_at']); ?>
                        </div>
                        <div class="stat-label">Member</div>
                    </div>
                </div>
                <?php if ($viewing_own): ?>
               
                    <div class="profile-actions">
                        <a href="edit-profile.php" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-content">
            <h2 class="section-title">Recent Posts</h2>
            <?php if (mysqli_num_rows($posts_result) > 0): ?>
                <div class="posts-grid">
                    <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                        <div class="post-card" data-post-id="<?php echo htmlspecialchars($post['post_id']); ?>">
                            <div class="vote-column">
                                <button class="vote-btn upvote"><i class="fas fa-arrow-up"></i></button>
                                <div class="vote-count"><?php echo (int)($post['upvotes'] - $post['downvotes']); ?></div>
                                <button class="vote-btn downvote"><i class="fas fa-arrow-down"></i></button>
                            </div>
                            <div class="post-content">
                                <div class="post-header">
                                    <div class="post-category">
                                        <a href="index.php?category=<?php echo urlencode($post['category']); ?>">
                                            <?php echo htmlspecialchars($post['category']); ?>
                                        </a>
                                    </div>
                                    <div class="post-meta">
                                        <?php echo time_elapsed_string($post['created_at']); ?>
                                    </div>
                                </div>
                                <h3 class="post-title">
                                    <a href="post.php?id=<?php echo htmlspecialchars($post['post_id']); ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h3>
                                <div class="post-body">
                                    <?php
                                    $content = nl2br(htmlspecialchars($post['content']));
                                    echo (strlen($content) > 150) ? substr($content, 0, 150) . '...' : $content;
                                    ?>
                                </div>
                                <div class="post-footer">
                                    <a href="post.php?id=<?php echo htmlspecialchars($post['post_id']); ?>" class="post-action">
                                        <i class="fas fa-comment"></i> <?php echo (int)$post['comment_count']; ?> Comments
                                    </a>
                                    <?php if ($viewing_own): ?>
                                        <a href="edit-post.php?id=<?php echo htmlspecialchars($post['post_id']); ?>" class="post-action">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="profile.php?<?php echo isset($_GET['user']) ? 'user='.urlencode($_GET['user']).'&' : ''; ?>delete=<?php echo htmlspecialchars($post['post_id']); ?>" 
                                           class="post-action delete" 
                                           onclick="return confirm('Are you sure you want to delete this post?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-posts-message">
                    <div class="empty-state">
                        <i class="fas fa-pen-fancy empty-icon"></i>
                        <h3>No posts yet</h3>
                        <p>When you create posts, they'll appear here.</p>
                        <?php if ($viewing_own): ?>
                            <a href="create-post.php" class="btn btn-primary">Create your first post</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/profile.js"></script>
</main>

<?php include 'includes/footer.php'; ?>