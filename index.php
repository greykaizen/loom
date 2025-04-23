<?php
$page_title = "Home";
include 'includes/header.php';

// Get posts with pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$limit = 10;
$offset = ($page - 1) * $limit;

$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : null;
$tag = isset($_GET['tag']) ? sanitize_input($_GET['tag']) : null;

// Build query based on filters
$query = "SELECT p.*, u.username, COUNT(c.comment_id) as comment_count 
          FROM posts p 
          LEFT JOIN users u ON p.user_id = u.user_id 
          LEFT JOIN comments c ON p.post_id = c.post_id";

$params = [];
$types = "";

if ($category) {
    $query .= " WHERE p.category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($tag) {
    if ($category) {
        $query .= " AND p.post_id IN (
                    SELECT pt.post_id FROM post_tags pt 
                    JOIN tags t ON pt.tag_id = t.tag_id 
                    WHERE t.name = ?)";
    } else {
        $query .= " WHERE p.post_id IN (
                    SELECT pt.post_id FROM post_tags pt 
                    JOIN tags t ON pt.tag_id = t.tag_id 
                    WHERE t.name = ?)";
    }
    $params[] = $tag;
    $types .= "s";
}

$query .= " GROUP BY p.post_id ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);

if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// OPTIMIZATION: This is where we could fetch all tags at once if we wanted to
// Currently keeping individual tag queries for simplicity
?>

<div class="content-area">
    <!-- <?php if (is_logged_in()): ?>
        <div class="create-post-btn" style="margin-bottom: 20px;">
            <a href="create-post.php" class="btn">Create Post</a>
        </div>
    <?php endif; ?> -->

    <?php if ($category || $tag): ?>
        <div class="filter-info" style="margin-bottom: 20px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
            <?php if ($category): ?>
                <p>Category: <strong><?php echo htmlspecialchars($category); ?></strong></p>
            <?php endif; ?>

            <?php if ($tag): ?>
                <p>Tag: <strong><?php echo htmlspecialchars($tag); ?></strong></p>
            <?php endif; ?>

            <a href="index.php" style="margin-top: 5px; display: inline-block;">Clear Filters</a>
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($post = mysqli_fetch_assoc($result)): ?>
            <div class="card post-card" data-post-id="<?php echo $post['post_id']; ?>">
                <div class="vote-column">
                    <button
                        class="vote-btn vote-up <?php echo (is_logged_in() && get_user_vote($_SESSION['user_id'], $post['post_id']) == 1) ? 'upvoted' : ''; ?>"
                        data-vote="1">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <div class="vote-count"><?php echo $post['upvotes'] - $post['downvotes']; ?></div>
                    <button
                        class="vote-btn vote-down <?php echo (is_logged_in() && get_user_vote($_SESSION['user_id'], $post['post_id']) == -1) ? 'downvoted' : ''; ?>"
                        data-vote="-1">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                </div>
                <div class="post-content">
                    <div class="post-header">
                        <div class="post-category">
                            <a
                                href="index.php?category=<?php echo urlencode($post['category']); ?>"><?php echo htmlspecialchars($post['category']); ?></a>
                        </div>
                        <div class="post-meta">
                            Posted by <a
                                href="profile.php?user=<?php echo urlencode($post['username']); ?>"><?php echo htmlspecialchars($post['username']); ?></a>
                            <?php echo time_elapsed_string($post['created_at']); ?>
                        </div>
                    </div>
                    <h2 class="post-title">
                        <a
                            href="post.php?id=<?php echo $post['post_id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                    </h2>
                    <div class="post-body">
                        <!-- <?php
                                $content = htmlspecialchars($post['content']);
                                echo (strlen($content) > 200) ? substr($content, 0, 200) . '...' : $content;
                                ?> -->
                        <?php
                        // Content should already be sanitized in the database
                        $content = $post['content'];
                        echo (strlen($content) > 200) ? substr($content, 0, 200) . '...' : $content;
                        ?>
                    </div>
                    <div class="post-footer">
                        <a href="post.php?id=<?php echo $post['post_id']; ?>" class="post-action">
                            <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?> Comments
                        </a>
                        <div class="post-action report-btn" data-type="post" data-id="<?php echo $post['post_id']; ?>">
                            <i class="fas fa-flag"></i> Report
                        </div>

                        <?php
                        // Get tags for this post - using individual queries
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

                        // REMOVED: This block caused the infinite loop issue
                        // It was trying to get all post IDs again inside the main loop
                        // which reset the result pointer and created an endless cycle
                        // -------------------------------
                        // Get post IDs
                        //         $post_ids = [];
                        //         mysqli_data_seek($result, 0); // Reset result pointer
                        //         while ($post = mysqli_fetch_assoc($result)) {
                        //             $post_ids[] = $post['post_id'];
                        //         }

                        //         // Fetch all tags for all posts in one query if we have posts
                        //         $post_tags = [];
                        //         if (!empty($post_ids)) {
                        //             $ids_string = implode(',', $post_ids);
                        //             $tag_query = "SELECT pt.post_id, t.name FROM tags t 
                        //  JOIN post_tags pt ON t.tag_id = pt.tag_id 
                        //  WHERE pt.post_id IN ($ids_string)";
                        //             $tag_result = mysqli_query($conn, $tag_query);

                        //             while ($tag = mysqli_fetch_assoc($tag_result)) {
                        //                 $post_tags[$tag['post_id']][] = $tag['name'];
                        //             }
                        //         }

                        //         // Reset result pointer again for the display loop
                        //         mysqli_data_seek($result, 0);
                        ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

        <!-- Pagination -->
        <?php
        // Count total posts for pagination
        $count_query = "SELECT COUNT(*) as total FROM posts";
        $count_params = [];
        $count_types = "";

        if ($category) {
            $count_query .= " WHERE category = ?";
            $count_params[] = $category;
            $count_types .= "s";
        }

        if ($tag) {
            if ($category) {
                $count_query .= " AND post_id IN (
                                SELECT pt.post_id FROM post_tags pt 
                                JOIN tags t ON pt.tag_id = t.tag_id 
                                WHERE t.name = ?)";
            } else {
                $count_query .= " WHERE post_id IN (
                                SELECT pt.post_id FROM post_tags pt 
                                JOIN tags t ON pt.tag_id = t.tag_id 
                                WHERE t.name = ?)";
            }
            $count_params[] = $tag;
            $count_types .= "s";
        }

        $count_stmt = mysqli_prepare($conn, $count_query);

        if ($count_params) {
            mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
        }

        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_row = mysqli_fetch_assoc($count_result);
        $total_posts = $count_row['total'];

        $total_pages = ceil($total_posts / $limit);

        if ($total_pages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; margin-top: 20px;">
                <?php
                $url_params = '';
                if ($category)
                    $url_params .= '&category=' . urlencode($category);
                if ($tag)
                    $url_params .= '&tag=' . urlencode($tag);

                // Previous button
                if ($page > 1): ?>
                    <a href="index.php?page=<?php echo $page - 1; ?><?php echo $url_params; ?>" class="btn"
                        style="margin-right: 10px;">Previous</a>
                <?php endif; ?>

                <!-- Page numbers -->
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="index.php?page=<?php echo $i; ?><?php echo $url_params; ?>" class="btn"
                        style="margin-right: 5px; <?php echo ($i == $page) ? 'background-color: #0066b2;' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- Next button -->
                <?php if ($page < $total_pages): ?>
                    <a href="index.php?page=<?php echo $page + 1; ?><?php echo $url_params; ?>" class="btn">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-posts-message" style="text-align: center; padding: 30px;">
            <p>No posts found. Be the first to create a post!</p>
            <?php if (is_logged_in()): ?>
                <a href="create-post.php" class="btn" style="margin-top: 15px;">Create Post</a>
            <?php else: ?>
                <a href="login.php" class="btn" style="margin-top: 15px;">Login to create a post</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
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