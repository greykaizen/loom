<?php
$page_title = "Login";
$page_js = "auth.js";
include 'includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password
    
    if (login_user($username, $password)) {
        // Redirect to home page after successful login
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>

<div class="form-container">
    <h2 class="form-title">Login</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form id="login-form" method="post" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-block">Login</button>
        
        <div class="form-footer">
            <p>Don't have an account? <a href="signup.php">Sign up</a></p>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>