<?php
session_start();
require_once 'auth_functions.php';

if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = (new Connection())->getConnection();
    $query = "SELECT two_factor_secret FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $_SESSION['temp_user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (verifyTwoFactorCode($user['two_factor_secret'], $_POST['code'])) {
        $_SESSION['user_id'] = $_SESSION['temp_user_id'];
        unset($_SESSION['temp_user_id']);
        header("Location: user-landing.php");
        exit();
    } else {
        $error = "Invalid code";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication</title>
    <!-- Add your CSS here -->
</head>
<body>
    <h2>Enter Two-Factor Authentication Code</h2>
    <?php if (isset($error)) echo "<p>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="code" required>
        <button type="submit">Verify</button>
    </form>
</body>
</html>