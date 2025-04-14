<?php
$page_title = "Sign Up";
$page_js = "auth.js";
include 'includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

// Process signup form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // Don't sanitize password
    
    // Additional validation could be done here
    
    if (register_user($username, $email, $password)) {
        // Log the user in
        login_user($username, $password);
        
        // Redirect to home page
        header("Location: index.php");
        exit;
    } else {
        $error = "Username or email already exists";
    }
}
?>

<div class="form-container">
    <h2 class="form-title">Create an Account</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form id="signup-form" method="post" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm-password">Confirm Password</label>
            <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn btn-block">Sign Up</button>
        
        <div class="form-footer">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>