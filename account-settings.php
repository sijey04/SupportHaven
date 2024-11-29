<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Connection();
$db = $database->getConnection();

// Fetch user details
$user_id = $_SESSION['user_id'];
$query = "SELECT u.*, ur.role_name 
          FROM users u 
          JOIN user_roles ur ON u.user_role_id = ur.id 
          WHERE u.id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
$message = '';
$error = '';

// Add this near the top of your file after session_start()
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST request received');
    error_log('FILES array: ' . print_r($_FILES, true));
}

// Handle profile image upload
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    error_log('Processing profile image upload');
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    error_log('File type: ' . $_FILES['profile_image']['type']);
    error_log('File size: ' . $_FILES['profile_image']['size']);
    
    if (in_array($_FILES['profile_image']['type'], $allowed_types) && $_FILES['profile_image']['size'] <= $max_size) {
        $upload_dir = 'uploads/profile_images/';
        
        // Check directory permissions
        error_log('Upload directory exists: ' . (file_exists($upload_dir) ? 'yes' : 'no'));
        error_log('Upload directory writable: ' . (is_writable($upload_dir) ? 'yes' : 'no'));
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $error = "Failed to create upload directory.";
                error_log("Failed to create directory: " . $upload_dir);
            }
        }
        
        // Ensure directory is writable
        if (!is_writable($upload_dir)) {
            chmod($upload_dir, 0777);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $filename;
        
        error_log("Attempting to move file to: " . $target_path);
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
            // Update database with new image path
            $update_query = "UPDATE users SET avatar = :avatar WHERE id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":avatar", $filename);
            $update_stmt->bindParam(":user_id", $user_id);
            
            if ($update_stmt->execute()) {
                $message = "Profile image updated successfully!";
                // Update the user array with new image
                $user['avatar'] = $filename;
                error_log("Database updated successfully with new avatar: " . $filename);
            } else {
                $error = "Failed to update profile image in database.";
                error_log("Database update failed: " . print_r($update_stmt->errorInfo(), true));
            }
        } else {
            $error = "Failed to upload image.";
            error_log("Failed to move uploaded file. Upload error code: " . $_FILES['profile_image']['error']);
        }
    } else {
        $error = "Invalid file type or size. Please upload a JPG, PNG, or GIF file under 5MB.";
        error_log("Invalid file type or size: " . $_FILES['profile_image']['type'] . ", " . $_FILES['profile_image']['size']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $email = $_POST['email'];
        
        // Update user profile
        $update_query = "UPDATE users SET firstname = :firstname, lastname = :lastname, email = :email 
                        WHERE id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":firstname", $firstname);
        $update_stmt->bindParam(":lastname", $lastname);
        $update_stmt->bindParam(":email", $email);
        $update_stmt->bindParam(":user_id", $user_id);
        
        if ($update_stmt->execute()) {
            $message = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile.";
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_query = "UPDATE users SET password = :password WHERE id = :user_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(":password", $hashed_password);
                $update_stmt->bindParam(":user_id", $user_id);
                
                if ($update_stmt->execute()) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Failed to change password.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Sidebar (same as other pages) -->
    <aside class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-6">
            <a href="index.html">
                <img src="images/logo.png" alt="SupportHaven" class="h-12 mb-8 pl-4">
            </a>
            
            <nav class="space-y-2">
                <a href="user-landing.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-home w-5 mr-3"></i> Home
                </a>
                <a href="my-services.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-palette w-5 mr-3"></i> My Services
                </a>
                <a href="favorites.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-heart w-5 mr-3"></i> Favorites
                </a>
                <a href="account-settings.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg bg-gray-100">
                    <i class="fas fa-user-cog w-5 mr-3"></i> Account settings
                </a>
                
            </nav>
        </div>
        
        <div class="mt-auto p-6">   
            <a href="logout.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">Account Settings</h1>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Settings -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="p-6 border-b">
                    <form method="POST" enctype="multipart/form-data" id="profile-image-form">
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <img src="<?php 
                                    if (!empty($user['avatar']) && file_exists('uploads/profile_images/' . $user['avatar'])) {
                                        echo 'uploads/profile_images/' . htmlspecialchars($user['avatar']);
                                    } else {
                                        echo 'images/default-avatar.svg';
                                    }
                                ?>" 
                                    alt="Profile Picture" 
                                    class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg bg-gray-50">
                                <label for="profile_image" class="absolute bottom-0 right-0 bg-purple-600 text-white rounded-full p-2 cursor-pointer hover:bg-purple-700 transition">
                                    <i class="fas fa-camera"></i>
                                    <input type="file" 
                                           id="profile_image" 
                                           name="profile_image" 
                                           class="hidden" 
                                           accept="image/jpeg,image/png,image/gif">
                                </label>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
                                <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                    </form>
                </div>
                <form class="p-6" method="POST" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="col-span-2">
                            <button type="submit" name="update_profile" 
                                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                                Update Profile
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold">Change Password</h2>
                </div>
                <form class="p-6" method="POST">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password" name="current_password" required
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" name="new_password" required
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" required
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <button type="submit" name="change_password"
                                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                                Change Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Account Status -->
            <div class="mt-6 bg-gray-50 rounded-xl p-6 border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium">Account Status</h3>
                        <p class="text-sm text-gray-600">Your account type is: <?php echo htmlspecialchars($user['role_name']); ?></p>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Active</span>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

<script>
document.getElementById('profile_image').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        // Get the form reference
        const form = document.getElementById('profile-image-form');
        
        // Submit the form
        form.submit();
    }
});
</script>

<style>
.profile-image-container {
    position: relative;
    width: 96px;
    height: 96px;
}

.profile-image-container:hover .upload-overlay {
    opacity: 1;
}

.upload-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(79, 70, 229, 0.9);
    color: white;
    padding: 4px;
    text-align: center;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.3s ease;
    cursor: pointer;
}

.profile-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
</style>
