<?php
require_once 'auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email'])) {
        $email = $_POST['email'];
        $token = generatePasswordResetToken($email);
        sendPasswordResetEmail($email, $token);
        $message = "Password reset link sent to your email.";
    } elseif (isset($_POST['token']) && isset($_POST['new_password'])) {
        $token = $_POST['token'];
        $newPassword = $_POST['new_password'];
        
        if (!validatePassword($newPassword)) {
            $error = "Password does not meet complexity requirements.";
        } else {
            $db = (new Connection())->getConnection();
            $query = "SELECT email FROM password_resets WHERE token = :token AND expiry > NOW()";
            $stmt = $db->prepare($query);
            $stmt->execute([':token' => $token]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $email = $result['email'];
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users SET password = :password WHERE email = :email";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([':password' => $hashedPassword, ':email' => $email]);
                
                $deleteQuery = "DELETE FROM password_resets WHERE email = :email";
                $deleteStmt = $db->prepare($deleteQuery);
                $deleteStmt->execute([':email' => $email]);
                
                $message = "Password reset successful. You can now login with your new password.";
            } else {
                $error = "Invalid or expired token.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <!-- Add your CSS here -->
</head>
<body>
    <h2>Reset Password</h2>
    <?php 
    if (isset($message)) echo "<p>$message</p>";
    if (isset($error)) echo "<p>$error</p>";
    ?>
    <?php if (!isset($_GET['token'])): ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
            <input type="password" name="new_password" placeholder="Enter new password" required>
            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
</body>
</html>