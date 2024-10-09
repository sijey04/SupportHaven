<?php
function checkRole($requiredRole) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $requiredRole) {
        header("Location: unauthorized.php");
        exit();
    }
}

function checkPermission($requiredPermission) {
    $db = (new Connection())->getConnection();
    $query = "SELECT permissions.name FROM user_permissions 
              JOIN permissions ON user_permissions.permission_id = permissions.id 
              WHERE user_permissions.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array($requiredPermission, $permissions)) {
        header("Location: unauthorized.php");
        exit();
    }
}
?>