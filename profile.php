<?php
include 'includes/header.php';

// Check if viewing own profile or someone else's
if (isset($_GET['user'])) {
    $username = sanitize_input($_GET['user']);

    // Get user details
    $user_query = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        header("Location: index.php");
        exit;
    }

    $user = mysqli_fetch_assoc($result);
    $page_title = $user['username'] . "'s Profile";
    $viewing_own = is_logged_in() && $_SESSION['username'] === $username;
} else {
    // Must be logged in to view own profile
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }

    $username = $_SESSION['username'];

    // Get user details
    $user_query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    $page_title = "My Profile";
    $viewing_own = true;
}

// Get user's posts
$posts_query = "SELECT p.*, COUNT(c.comment_id) as comment_count 
                FROM posts p 
                LEFT JOIN comments c ON p.post_id = c.post_id 
                WHERE p.user_id = ? 
                GROUP BY p.post_id 
                ORDER BY p.created_at DESC 
                LIMIT 10";
$stmt = mysqli_prepare($conn, $posts_query);
mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
mysqli_stmt_execute($stmt);
$posts_result = mysqli_stmt_get_result($stmt);
?>

<div class="content-area">
    <div class="profile-header" style="display: flex; align-items: center; margin-bottom: 20px;">
        <div class="profile-avatar" style="margin-right: 20px;">
            <?php
            $profile_image = htmlspecialchars($user['profile_picture']);
            // Check if image exists and is not default
            if (empty($profile_image) || $profile_image == 'default.jpg' || !file_exists($profile_image)) {
                $profile_image = 'uploads/profile_pictures/default.jpg'; // Default image path
            }
            ?>
            <img src="<?php echo $profile_image; ?>" alt="Profile picture" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; background-color: #f0f0f0;">
        </div>
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($user['username']); ?></h1>
            <p>Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            <p>Karma: <?php echo $user['karma']; ?></p>

            <?php if ($viewing_own): ?>
                <a href="edit-profile.php" class="btn" style="margin-top: 10px;">Edit Profile</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-content">
        <h2>Recent Posts</h2>

        <?php if (mysqli_num_rows($posts_result) > 0): ?>
            <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                <div class="card post-card" data-post-id="<?php echo $post['post_id']; ?>">
                    <div class="vote-column">
                        <div class="vote-count"><?php echo $post['upvotes'] - $post['downvotes']; ?></div>
                    </div>
                    <div class="post-content">
                        <div class="post-header">
                            <div class="post-category">
                                <a href="index.php?category=<?php echo urlencode($post['category']); ?>"><?php echo htmlspecialchars($post['category']); ?></a>
                            </div>
                            <div class="post-meta">
                                <?php echo time_elapsed_string($post['created_at']); ?>
                            </div>
                        </div>
                        <h2 class="post-title">
                            <a href="post.php?id=<?php echo $post['post_id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                        </h2>
                        <div class="post-body">
                            <?php
                            // $content = nl2br(htmlspecialchars($post['content']));
                            $content = nl2br($post['content']);
                            echo (strlen($content) > 150) ? substr($content, 0, 150) . '...' : $content;
                            ?>
                        </div>
                        <div class="post-footer">
                            <a href="post.php?id=<?php echo $post['post_id']; ?>" class="post-action">
                                <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?> Comments
                            </a>

                            <?php if ($viewing_own): ?>
                                <a href="edit-post.php?id=<?php echo $post['post_id']; ?>" class="post-action">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-posts-message" style="text-align: center; padding: 30px;">
                <p>No posts yet.</p>
                <?php if ($viewing_own): ?>
                    <a href="create-post.php" class="btn" style="margin-top: 15px;">Create your first post</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>