<?php
require_once 'connection.php';

function validatePassword($password) {
    
    $uppercase = preg_match('/[A-Z]/', $password);
    $lowercase = preg_match('/[a-z]/', $password);
    $number    = preg_match('/[0-9]/', $password);
    $specialChars = preg_match('/[^A-Za-z0-9]/', $password);

    
    $minLength = 8;

    return $uppercase && $lowercase && $number && $specialChars && strlen($password) >= $minLength;
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