<?php
$page_title = "Edit Profile";
include 'includes/header.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

// Get user details
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Process profile updates
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate email
    if (empty($email)) {
        $error = "Email is required";
    } else {
        // Check if email exists but belongs to another user
        $check_email = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Email already in use by another account";
        } else {
            // Prepare for updates
            $profile_picture_path = $user['profile_picture']; // Default to current picture
            $update_success = true;
            
            // Handle profile picture upload if provided
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/profile_pictures/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Process the uploaded file
                $file_name = $_FILES['profile_picture']['name'];
                $file_tmp = $_FILES['profile_picture']['tmp_name'];
                $file_size = $_FILES['profile_picture']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
                
                if (in_array($file_ext, $allowed_extensions)) {
                    if ($file_size <= 2097152) { // 2MB limit
                        $new_file_name = $user_id . '_' . time() . '.' . $file_ext;
                        $destination = $upload_dir . $new_file_name;
                        
                        if (move_uploaded_file($file_tmp, $destination)) {
                            $profile_picture_path = $destination;
                        } else {
                            $error = "Error uploading file";
                            $update_success = false;
                        }
                    } else {
                        $error = "File size too large (max 2MB)";
                        $update_success = false;
                    }
                } else {
                    $error = "Invalid file type. Only JPG, PNG and GIF are allowed";
                    $update_success = false;
                }
            }
            
            // Update profile if no errors so far
            if ($update_success) {
                // Update email and profile picture
                $update_query = "UPDATE users SET email = ?, profile_picture = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "ssi", $email, $profile_picture_path, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Profile updated successfully";
                    
                    // Update password if provided
                    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
                        // Verify current password
                        if (password_verify($current_password, $user['password_hash'])) {
                            if ($new_password === $confirm_password) {
                                // Update password
                                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                                $update_password = "UPDATE users SET password_hash = ? WHERE user_id = ?";
                                $stmt = mysqli_prepare($conn, $update_password);
                                mysqli_stmt_bind_param($stmt, "si", $password_hash, $user_id);
                                mysqli_stmt_execute($stmt);
                                
                                $success = "Profile and password updated successfully";
                            } else {
                                $error = "New passwords do not match";
                            }
                        } else {
                            $error = "Current password is incorrect";
                        }
                    }
                    
                    // Refresh user data
                    $stmt = mysqli_prepare($conn, $user_query);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $user = mysqli_fetch_assoc($result);
                } else {
                    $error = "Error updating profile: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Check if profile image exists and set default if not
$profile_image = $user['profile_picture'];
if (empty($profile_image) || !file_exists($profile_image)) {
    $profile_image = 'uploads/profile_pictures/default.jpg';
}
?>

<div class="form-container">
    <h2 class="form-title">Edit Profile</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label>Current Profile Picture</label>
            <div style="margin-bottom: 10px; text-align: center;">
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile picture" 
                     style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; background-color: #f0f0f0;">
            </div>
            <label for="profile_picture">Update Profile Picture</label>
            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
            <small class="form-text" style="color: #666;">Max size: 2MB. Supported formats: JPG, PNG, GIF</small>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
            <small class="form-text" style="color: #666;">Username cannot be changed</small>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <h3 style="margin-top: 20px; margin-bottom: 15px;">Change Password</h3>

        <div class="form-group">
            <label for="current-password">Current Password</label>
            <input type="password" class="form-control" id="current-password" name="current_password">
        </div>

        <div class="form-group">
            <label for="new-password">New Password</label>
            <input type="password" class="form-control" id="new-password" name="new_password">
        </div>

        <div class="form-group">
            <label for="confirm-password">Confirm New Password</label>
            <input type="password" class="form-control" id="confirm-password" name="confirm_password">
        </div>

        <button type="submit" class="btn btn-block">Save Changes</button>

        <div class="form-footer">
            <a href="profile.php">Back to Profile</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>