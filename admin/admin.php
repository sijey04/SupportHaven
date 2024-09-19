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

// Function to fetch all bookings
function getAllBookings($db) {
    $query = "SELECT b.*, u.firstName, u.lastName FROM bookings b LEFT JOIN users u ON b.user_id = u.id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get total earnings
function getTotalEarnings($db) {
    $query = "SELECT SUM(total_cost) as total_earnings FROM bookings";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_earnings'] ?? 0;
}

// Function to get total number of users
function getTotalUsers($db) {
    $query = "SELECT COUNT(*) as total_users FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total_users'];
}

$users = getAllUsers($db);
$bookings = getAllBookings($db);
$totalEarnings = getTotalEarnings($db);
$totalUsers = getTotalUsers($db);

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
    <title>SupportHaven Admin Panel</title>
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
                            <a class="nav-link active" href="admin.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i>User Management</a>
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
                    <p class="lead">Dashboard</p>
                </div>
                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-dollar-sign me-2"></i>Total Earnings</h5>
                                <p class="card-text display-4">$<?php echo number_format($totalEarnings, 2); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-users me-2"></i>Total Users</h5>
                                <p class="card-text display-4"><?php echo $totalUsers; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h2 class="mb-0"><i class="fas fa-user me-2"></i>Users</h2>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['firstName'] ?? '') . ' ' . htmlspecialchars($user['lastName'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light">
                        <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Bookings</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Service</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['id']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['firstName'] ?? '') . ' ' . htmlspecialchars($booking['lastName'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($booking['service_id']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['booking_time']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['location']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
