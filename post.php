<?php
$page_js = "post.js";
include 'includes/header.php';

// Get post ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$post_id = (int)$_GET['id'];

// Get post details
$post_query = "SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.post_id = ?";
$stmt = mysqli_prepare($conn, $post_query);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit;
}

$post = mysqli_fetch_assoc($result);
$page_title = $post['title'];

// Process comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $comment_content = sanitize_input($_POST['comment']);
    
    if (!empty($comment_content)) {
        $user_id = $_SESSION['user_id'];
        
        $insert_query = "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "iis", $post_id, $user_id, $comment_content);
        
        if (mysqli_stmt_execute($stmt)) {
            $comment_id = mysqli_insert_id($conn);
            
            // Create notification for post owner
            if ($post['user_id'] != $user_id) {
                $post_title = htmlspecialchars(substr($post['title'], 0, 50));
                if (strlen($post['title']) > 50) {
                    $post_title .= '...';
                }
                $notification_content = $_SESSION['username'] . " commented on your post: \"$post_title\"";
                $notification_link = "post.php?id=$post_id#comment-$comment_id";
                create_notification($post['user_id'], $notification_content, $notification_link, 'comment', $post_id, $user_id);
            }
            
            // Redirect to avoid resubmit on refresh
            header("Location: post.php?id=$post_id#comment-$comment_id");
            exit;
        }
    }
}

// Get comments
$comment_query = "SELECT c.*, u.username FROM comments c 
                 JOIN users u ON c.user_id = u.user_id 
                 WHERE c.post_id = ? 
                 ORDER BY c.created_at DESC";
$stmt = mysqli_prepare($conn, $comment_query);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$comments_result = mysqli_stmt_get_result($stmt);
?>

<div class="content-area">
    <!-- Post -->
    <div class="card post-card" data-post-id="<?php echo $post['post_id']; ?>">
        <div class="vote-column">
            <button class="vote-btn vote-up <?php echo (is_logged_in() && get_user_vote($_SESSION['user_id'], $post['post_id']) == 1) ? 'upvoted' : ''; ?>" data-vote="1">
                <i class="fas fa-arrow-up"></i>
            </button>
            <div class="vote-count"><?php echo $post['upvotes'] - $post['downvotes']; ?></div>
            <button class="vote-btn vote-down <?php echo (is_logged_in() && get_user_vote($_SESSION['user_id'], $post['post_id']) == -1) ? 'downvoted' : ''; ?>" data-vote="-1">
                <i class="fas fa-arrow-down"></i>
            </button>
        </div>
        <div class="post-content">
            <div class="post-header">
                <div class="post-category">
                    <a href="index.php?category=<?php echo urlencode($post['category']); ?>"><?php echo htmlspecialchars($post['category']); ?></a>
                </div>
                <div class="post-meta">
                    Posted by <a href="profile.php?user=<?php echo urlencode($post['username']); ?>"><?php echo htmlspecialchars($post['username']); ?></a>
                    <?php echo time_elapsed_string($post['created_at']); ?>
                </div>
            </div>
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-body">
                <?php echo nl2br($post['content']); ?>
            </div>
            <div class="post-footer">
                <div class="post-action report-btn" data-type="post" data-id="<?php echo $post['post_id']; ?>">
                    <i class="fas fa-flag"></i> Report
                </div>
                
                <?php if (is_logged_in() && $_SESSION['user_id'] == $post['user_id']): ?>
                <a href="edit-post.php?id=<?php echo $post['post_id']; ?>" class="post-action">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <?php endif; ?>
                
                <?php
                // Get tags for this post
                $tag_query = "SELECT t.name FROM tags t 
                             JOIN post_tags pt ON t.tag_id = pt.tag_id 
                             WHERE pt.post_id = ?";
                $tag_stmt = mysqli_prepare($conn, $tag_query);
                mysqli_stmt_bind_param($tag_stmt, "i", $post['post_id']);
                mysqli_stmt_execute($tag_stmt);
                $tag_result = mysqli_stmt_get_result($tag_stmt);
                
                if (mysqli_num_rows($tag_result) > 0) {
                    echo '<div class="post-tags">';
                    while ($tag = mysqli_fetch_assoc($tag_result)) {
                        echo '<a href="index.php?tag=' . urlencode($tag['name']) . '" class="post-tag">' . htmlspecialchars($tag['name']) . '</a>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Comments section -->
    <div class="comments-section" style="margin-top: 20px;">
        <h3><?php echo mysqli_num_rows($comments_result); ?> Comments</h3>
        
        <?php if (is_logged_in()): ?>
        <div class="comment-form-container" style="margin-bottom: 20px;">
            <form id="comment-form" method="post">
                <div class="form-group">
                    <textarea class="form-control" id="comment-content" name="comment" placeholder="Add a comment..." required></textarea>
                </div>
                <button type="submit" class="btn">Submit</button>
            </form>
        </div>
        <?php else: ?>
        <div class="login-to-comment" style="text-align: center; margin: 20px 0; padding: 15px; background: #f8f8f8; border-radius: 4px;">
            <p><a href="login.php">Login</a> to join the conversation</p>
        </div>
        <?php endif; ?>
        
        <!-- Comments list -->
        <div class="comments-list">
            <?php if (mysqli_num_rows($comments_result) > 0): ?>
                <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                    <div class="comment" id="comment-<?php echo $comment['comment_id']; ?>" data-comment-id="<?php echo $comment['comment_id']; ?>">
                        <div class="comment-header">
                            <div class="comment-user">
                                <a href="profile.php?user=<?php echo urlencode($comment['username']); ?>"><?php echo htmlspecialchars($comment['username']); ?></a>
                            </div>
                            <div class="comment-meta">
                                <?php echo time_elapsed_string($comment['created_at']); ?>
                            </div>
                        </div>
                        <div class="comment-body">
                            <?php echo nl2br($comment['content']); ?>
                        </div>
                        <div class="comment-footer">
                            <div class="vote-controls" style="display: flex; align-items: center;">
                                <button class="vote-btn vote-up <?php echo (is_logged_in() && get_user_vote($_SESSION['user_id'], null, $comment['comment_id']) == 1) ? 'upvoted' : ''; ?>" data-vote="1">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                                <div class="vote-count" style="margin: 0 5px;"><?php echo $comment['upvotes'] - $comment['downvotes']; ?></div>
                                <button class="vote-btn vote-down <?php echo (is_logged_in() && get_user_vote($_SESSION['user_id'], null, $comment['comment_id']) == -1) ? 'downvoted' : ''; ?>" data-vote="-1">
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                            </div>
                            
                            <div class="report-btn" data-type="comment" data-id="<?php echo $comment['comment_id']; ?>" style="margin-left: 15px; cursor: pointer;">
                                <i class="fas fa-flag"></i> Report
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-comments" style="text-align: center; padding: 20px;">
                    <p>No comments yet. Be the first to add one!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div id="report-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Report Content</h2>
        <form action="report.php" method="post">
            <input type="hidden" id="report-type" name="type" value="">
            <input type="hidden" id="report-id" name="id" value="">
            
            <div class="form-group">
                <label for="report-reason">Reason</label>
                <select class="form-control" id="report-reason" name="reason" required>
                    <option value="">Select a reason</option>
                    <option value="spam">Spam</option>
                    <option value="harassment">Harassment</option>
                    <option value="violence">Violence</option>
                    <option value="misinformation">Misinformation</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="report-details">Details (optional)</label>
                <textarea class="form-control" id="report-details" name="details"></textarea>
            </div>
            
            <button type="submit" class="btn btn-block">Submit Report</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>