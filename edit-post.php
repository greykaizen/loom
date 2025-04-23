<?php
$page_title = "Edit Post";
$page_js = "post.js";
include 'includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

// Check if post ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$post_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get post details and check ownership
$post_query = "SELECT * FROM posts WHERE post_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $post_query);
mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit;
}

$post = mysqli_fetch_assoc($result);

// Process post update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content']);
    $category = sanitize_input($_POST['category']);
    $tags = isset($_POST['tags']) ? sanitize_input($_POST['tags']) : '';

    // Validate inputs
    if (!empty($title) && !empty($content) && !empty($category)) {
        // Update post
        $update_query = "UPDATE posts SET title = ?, content = ?, category = ? WHERE post_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "sssii", $title, $content, $category, $post_id, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            // Delete existing tags
            $delete_tags = "DELETE FROM post_tags WHERE post_id = ?";
            $stmt = mysqli_prepare($conn, $delete_tags);
            mysqli_stmt_bind_param($stmt, "i", $post_id);
            mysqli_stmt_execute($stmt);

            // Process tags
            if (!empty($tags)) {
                $tag_list = explode(',', $tags);

                // Limit to 5 tags
                if (count($tag_list) > 5) {
                    $tag_list = array_slice($tag_list, 0, 5);
                }

                foreach ($tag_list as $key => $tag_name) {
                    $tag_name = trim($tag_name);
                    if (empty($tag_name)) {
                        unset($tag_list[$key]);
                        continue;
                    }

                    // Sanitize each tag
                    $tag_list[$key] = sanitize_input($tag_name);

                    // Get or create tag
                    $tag_query = "SELECT tag_id FROM tags WHERE name = ?";
                    $stmt = mysqli_prepare($conn, $tag_query);
                    mysqli_stmt_bind_param($stmt, "s", $tag_name);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if (mysqli_num_rows($result) > 0) {
                        $tag = mysqli_fetch_assoc($result);
                        $tag_id = $tag['tag_id'];
                    } else {
                        $insert_tag = "INSERT INTO tags (name) VALUES (?)";
                        $stmt = mysqli_prepare($conn, $insert_tag);
                        mysqli_stmt_bind_param($stmt, "s", $tag_name);
                        mysqli_stmt_execute($stmt);
                        $tag_id = mysqli_insert_id($conn);
                    }

                    // Link tag to post
                    $link_tag = "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $link_tag);
                    mysqli_stmt_bind_param($stmt, "ii", $post_id, $tag_id);
                    mysqli_stmt_execute($stmt);
                }
            }

            // Redirect to the post
            header("Location: post.php?id=$post_id");
            exit;
        } else {
            $error = "Error updating post: " . mysqli_error($conn);
        }
    } else {
        $error = "All fields are required";
    }
}

// Get post tags
$tag_query = "SELECT t.name FROM tags t JOIN post_tags pt ON t.tag_id = pt.tag_id WHERE pt.post_id = ?";
$stmt = mysqli_prepare($conn, $tag_query);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$tag_result = mysqli_stmt_get_result($stmt);

$tags = [];
while ($tag = mysqli_fetch_assoc($tag_result)) {
    $tags[] = $tag['name'];
}
$tags_string = implode(',', $tags);
?>

<div class="form-container" style="max-width: 800px;">
    <h2 class="form-title">Edit Post</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form id="post-form" method="post" action="">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="content">Content</label>
            <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select class="form-control" id="category" name="category" required>
                <option value="">Select a category</option>
                <option value="general" <?php if ($post['category'] === 'general') echo 'selected'; ?>>General</option>
                <option value="technology" <?php if ($post['category'] === 'technology') echo 'selected'; ?>>Technology</option>
                <option value="programming" <?php if ($post['category'] === 'programming') echo 'selected'; ?>>Programming</option>
                <option value="science" <?php if ($post['category'] === 'science') echo 'selected'; ?>>Science</option>
                <option value="gaming" <?php if ($post['category'] === 'gaming') echo 'selected'; ?>>Gaming</option>
                <option value="art" <?php if ($post['category'] === 'art') echo 'selected'; ?>>Art & Design</option>
                <option value="music" <?php if ($post['category'] === 'music') echo 'selected'; ?>>Music</option>
                <option value="movies" <?php if ($post['category'] === 'movies') echo 'selected'; ?>>Movies & TV</option>
                <option value="books" <?php if ($post['category'] === 'books') echo 'selected'; ?>>Books & Literature</option>
                <option value="sports" <?php if ($post['category'] === 'sports') echo 'selected'; ?>>Sports</option>
                <option value="other" <?php if ($post['category'] === 'other') echo 'selected'; ?>>Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tag-input">Tags (Max 5)</label>
            <input type="text" class="form-control" id="tag-input" placeholder="Type a tag and press Enter or comma">
            <div id="tag-list" class="tag-list">
                <?php foreach ($tags as $tag): ?>
                    <div class="tag-item" data-tag="<?php echo htmlspecialchars($tag); ?>">
                        <span class="tag-name"><?php echo htmlspecialchars($tag); ?></span>
                        <span class="tag-remove">&times;</span>
                    </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" id="tags" name="tags" value="<?php echo htmlspecialchars($tags_string); ?>">
            <small class="form-text" style="color: #666;">Tags help others find your post. Add up to 5 tags.</small>
        </div>

        <button type="submit" class="btn btn-block">Update Post</button>

        <div class="form-footer" style="margin-top: 15px; text-align: center;">
            <a href="post.php?id=<?php echo $post_id; ?>">Cancel and go back to post</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>