<?php
require_once 'connection.php';

function validatePassword($password) {
    // Check for minimum length of 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // Check for at least one special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    
    return true;
}
function generateTwoFactorSecret() {
    return bin2hex(random_bytes(16));
}

function verifyTwoFactorCode($secret, $code) {
    $ga = new PHPGangsta_GoogleAuthenticator();
    return $ga->verifyCode($secret, $code, 2);
}

function generatePasswordResetToken($email) {
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $db = (new Connection())->getConnection();
    $query = "INSERT INTO password_resets (email, token, expiry) VALUES (:email, :token, :expiry)";
    $stmt = $db->prepare($query);
    $stmt->execute([':email' => $email, ':token' => $token, ':expiry' => $expiry]);
    
    return $token;
}

function sendPasswordResetEmail($email, $token) {
    $resetLink = "https://yourdomain.com/reset_password.php?token=$token";
    $subject = "Password Reset Request";
    $message = "Click the following link to reset your password: $resetLink";
    mail($email, $subject, $message);
}
?>