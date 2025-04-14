<?php
$page_title = "Create Post";
$page_js = "post.js";
include 'includes/header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

// Process post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content']);
    $category = sanitize_input($_POST['category']);
    $tags = isset($_POST['tags']) ? sanitize_input($_POST['tags']) : '';

    // Validate inputs
    if (!empty($title) && !empty($content) && !empty($category)) {
        $user_id = $_SESSION['user_id'];

        // Insert post
        $post_query = "INSERT INTO posts (user_id, title, content, category) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $post_query);
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $title, $content, $category);

        if (mysqli_stmt_execute($stmt)) {
            $post_id = mysqli_insert_id($conn);

            // Process tags
            if (!empty($tags)) {
                $tag_list = explode(',', $tags);

                foreach ($tag_list as $key => $tag_name) {
                    $tag_name = trim($tag_name);
                    if (empty($tag_name)) {
                        unset($tag_list[$key]);
                        continue;
                    }

                    // Sanitize each tag
                    $tag_list[$key] = sanitize_input($tag_name);

                    // Limit to 5 tags
                    if (count($tag_list) > 5) {
                        $tag_list = array_slice($tag_list, 0, 5);
                    }

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

            // Redirect to the new post
            header("Location: post.php?id=$post_id");
            exit;
        } else {
            $error = "Error creating post: " . mysqli_error($conn);
        }
    } else {
        $error = "All fields are required";
    }
}
?>

<div class="form-container" style="max-width: 800px;">
    <h2 class="form-title">Create Post</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form id="post-form" method="post" action="">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="content">Content</label>
            <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select class="form-control" id="category" name="category" required>
                <option value="">Select a category</option>
                <option value="general">General</option>
                <option value="technology">Technology</option>
                <option value="programming">Programming</option>
                <option value="science">Science</option>
                <option value="gaming">Gaming</option>
                <option value="art">Art & Design</option>
                <option value="music">Music</option>
                <option value="movies">Movies & TV</option>
                <option value="books">Books & Literature</option>
                <option value="sports">Sports</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tag-input">Tags (Max 5)</label>
            <input type="text" class="form-control" id="tag-input" placeholder="Type a tag and press Enter or comma">
            <div id="tag-list" class="tag-list"></div>
            <input type="hidden" id="tags" name="tags" value="">
            <small class="form-text" style="color: #666;">Tags help others find your post. Add up to 5 tags.</small>
        </div>

        <button type="submit" class="btn btn-block">Create Post</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>