<?php
// session_start();

session_start([
    'cookie_httponly' => true,     // Prevent JavaScript access to session cookie
    'cookie_secure' => true,       // Send cookie only over HTTPS (remove in development)
    'cookie_samesite' => 'Lax',    // Restrict cross-site requests
    'use_strict_mode' => true      // Reject uninitialized session IDs
]);
// Register new user
function register_user($username, $email, $password) {
    global $conn;
    
    // Check if username/email already exists
    $check_user = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $check_user);
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return false; // User already exists
    }
    
    // Hash password and insert user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password_hash);
    
    return mysqli_stmt_execute($stmt);
}

// Login user
function login_user($username, $password) {
    global $conn;
    
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // if (password_verify($password, $user['password_hash'])) {
        //     // Create session
        //     $_SESSION['user_id'] = $user['user_id'];
        //     $_SESSION['username'] = $user['username'];
        //     $_SESSION['logged_in'] = true;
        //     return true;
        // }
        if (password_verify($password, $user['password_hash'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Create session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            return true;
        }
    }
    
    return false;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Logout user
function logout_user() {
    session_unset();
    session_destroy();
}
?>