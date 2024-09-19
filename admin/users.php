<?php
session_start();
require_once __DIR__ . '/../connection.php';
 
// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Connection();
$db = $database->getConnection();

// Function to fetch all users
function getAllUsers($db) {
    $query = "SELECT u.id, u.firstName, u.lastName, u.email, u.user_role_id, ur.role_name 
              FROM users u 
              LEFT JOIN user_roles ur ON u.user_role_id = ur.id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get user by ID
function getUserById($db, $id) {
    $query = "SELECT u.id, u.firstName, u.lastName, u.email, u.user_role_id, ur.role_name 
              FROM users u 
              LEFT JOIN user_roles ur ON u.user_role_id = ur.id 
              WHERE u.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to add new user
function addUser($db, $userData) {
    $query = "INSERT INTO users (firstName, lastName, email, password, user_role_id) VALUES (:firstName, :lastName, :email, :password, :user_role_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':firstName', $userData['firstName']);
    $stmt->bindParam(':lastName', $userData['lastName']);
    $stmt->bindParam(':email', $userData['email']);
    $stmt->bindParam(':password', password_hash($userData['password'], PASSWORD_DEFAULT));
    $stmt->bindParam(':user_role_id', $userData['user_role_id']);
    $result = $stmt->execute();

    if ($result && $userData['user_role_id'] == 2) {
        $userId = $db->lastInsertId();
        $techQuery = "INSERT INTO technicians (user_id) VALUES (:user_id)";
        $techStmt = $db->prepare($techQuery);
        $techStmt->bindParam(':user_id', $userId);
        $techStmt->execute();
    }

    return $result;
}

// Function to update user
function updateUser($db, $userData) {
    $query = "UPDATE users SET firstName = :firstName, lastName = :lastName, email = :email, user_role_id = :user_role_id WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':firstName', $userData['firstName']);
    $stmt->bindParam(':lastName', $userData['lastName']);
    $stmt->bindParam(':email', $userData['email']);
    $stmt->bindParam(':user_role_id', $userData['user_role_id']);
    $stmt->bindParam(':id', $userData['id']);
    $result = $stmt->execute();

    if ($result) {
        // Check if the user is being changed to a technician
        if ($userData['user_role_id'] == 2) {
            $checkQuery = "SELECT * FROM technicians WHERE user_id = :user_id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userData['id']);
            $checkStmt->execute();

            if ($checkStmt->rowCount() == 0) {
                $techQuery = "INSERT INTO technicians (user_id) VALUES (:user_id)";
                $techStmt = $db->prepare($techQuery);
                $techStmt->bindParam(':user_id', $userData['id']);
                $techStmt->execute();
            }
        } else {
            // If the user is no longer a technician, remove them from the technicians table
            $deleteQuery = "DELETE FROM technicians WHERE user_id = :user_id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':user_id', $userData['id']);
            $deleteStmt->execute();
        }
    }

    return $result;
}

// Function to delete user
function deleteUser($db, $id) {
    // First, delete from technicians table if exists
    $deleteTechQuery = "DELETE FROM technicians WHERE user_id = :id";
    $deleteTechStmt = $db->prepare($deleteTechQuery);
    $deleteTechStmt->bindParam(':id', $id);
    $deleteTechStmt->execute();

    // Then delete from users table
    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

$action = $_GET['action'] ?? 'list';
$userId = $_GET['id'] ?? null;

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'firstName' => $_POST['firstName'],
                'lastName' => $_POST['lastName'],
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                'user_role_id' => $_POST['user_role_id']
            ];
            if (addUser($db, $userData)) {
                header("Location: users.php");
                exit();
            }
        }
        break;
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'id' => $userId,
                'firstName' => $_POST['firstName'],
                'lastName' => $_POST['lastName'],
                'email' => $_POST['email'],
                'user_role_id' => $_POST['user_role_id']
            ];
            if (updateUser($db, $userData)) {
                header("Location: users.php");
                exit();
            }
        } else {
            $user = getUserById($db, $userId);
        }
        break;
    case 'delete':
        if (deleteUser($db, $userId)) {
            header("Location: users.php");
            exit();
        }
        break;
    case 'view':
        $user = getUserById($db, $userId);
        break;
    default:
        $users = getAllUsers($db);
        break;
}

// Fetch the admin's name
$adminName = '';
if (isset($_SESSION['user_id'])) {
    $query = "SELECT firstName, lastName FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $adminName = htmlspecialchars($result['firstName'] . ' ' . $result['lastName']);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SupportHaven User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
            color: #fff;
        }
        .sidebar .nav-link {
            color: #fff;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
        }
        .main-content {
            padding: 2rem;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .table {
            background-color: #fff;
            border-radius: 10px;
        }
        .admin-header {
            background-color: #ffffff;
            color: #333;
            padding: 1rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-logo {
            max-width: 150px;
            height: auto;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 80%;
                height: 100%;
                z-index: 1000;
                transition: 0.3s;
            }
            .sidebar.active {
                left: 0;
            }
            .main-content {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky">
                    <div class="text-center mb-4">
                        <img src="../images/logo.png" alt="SupportHaven Logo" class="admin-logo mb-3">
                        <h4>Admin Panel</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="users.php"><i class="fas fa-users me-2"></i>User Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="technician-management.php"><i class="fas fa-user-tie me-2"></i>Technician Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-calendar-check me-2"></i>Booking Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-cogs me-2"></i>Service Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-chart-bar me-2"></i>Reports and Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-sliders-h me-2"></i>Settings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 main-content">
                <button class="btn btn-primary d-md-none mb-3" type="button" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="admin-header p-4">
                    <h2 class="display-9 fw-bold">Welcome, <?php echo $adminName ? $adminName : 'Admin'; ?></h2>
                    <p class="lead">User Management</p>
                </div>

                <?php if ($action === 'list'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h2 class="mb-0"><i class="fas fa-users me-2"></i>Users</h2>
                            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add New User</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['firstName']) . ' ' . htmlspecialchars($user['lastName']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                            <td>
                                                <a href="?action=view&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                                <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i><?php echo $action === 'add' ? 'Add New User' : 'Edit User'; ?></h2>
                        </div>
                        <div class="card-body">
                            <form action="?action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $userId : ''; ?>" method="post">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo $action === 'edit' && isset($user['firstName']) ? htmlspecialchars($user['firstName']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo $action === 'edit' && isset($user['lastName']) ? htmlspecialchars($user['lastName']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $action === 'edit' && isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" required>
                                </div>
                                <?php if ($action === 'add'): ?>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label for="user_role_id" class="form-label">Role</label>
                                    <select class="form-select" id="user_role_id" name="user_role_id" required>
                                        <option value="3" <?php echo ($action === 'edit' && isset($user['user_role_id']) && $user['user_role_id'] == 3) ? 'selected' : ''; ?>>Admin</option>
                                        <option value="1" <?php echo ($action === 'edit' && isset($user['user_role_id']) && $user['user_role_id'] == 1) ? 'selected' : ''; ?>>Customer</option>
                                        <option value="2" <?php echo ($action === 'edit' && isset($user['user_role_id']) && $user['user_role_id'] == 2) ? 'selected' : ''; ?>>Technician</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add User' : 'Update User'; ?></button>
                                <a href="?action=list" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                <?php elseif ($action === 'view'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h2 class="mb-0"><i class="fas fa-user me-2"></i>User Details</h2>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">ID</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($user['id']); ?></dd>

                                <dt class="col-sm-3">First Name</dt>
                                <dd class="col-sm-9"><?php echo isset($user['firstName']) ? htmlspecialchars($user['firstName']) : 'N/A'; ?></dd>

                                <dt class="col-sm-3">Last Name</dt>
                                <dd class="col-sm-9"><?php echo isset($user['lastName']) ? htmlspecialchars($user['lastName']) : 'N/A'; ?></dd>

                                <dt class="col-sm-3">Email</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($user['email']); ?></dd>

                                <dt class="col-sm-3">Role</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($user['role_name']); ?></dd>
                            </dl>
                            <a href="?action=list" class="btn btn-primary">Back to List</a>
                            <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-warning">Edit User</a>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var sidebarToggle = document.getElementById('sidebarToggle');
            var sidebar = document.querySelector('.sidebar');

            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });

            // Close sidebar when clicking outside of it
            document.addEventListener('click', function(event) {
                var isClickInside = sidebar.contains(event.target) || sidebarToggle.contains(event.target);
                if (!isClickInside && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
