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

// Function to fetch all services
function getAllServices($db, $filters = []) {
    $query = "SELECT s.id, s.name, s.price, s.description, c.name as category_name
              FROM services s 
              LEFT JOIN categories c ON s.category_id = c.id";

    $conditions = [];
    $params = [];

    if (!empty($filters['search'])) {
        $conditions[] = "(s.name LIKE :search OR s.description LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    if (!empty($filters['category'])) {
        $conditions[] = "s.category_id = :category_id";
        $params[':category_id'] = $filters['category'];
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    $query .= " ORDER BY s.name ASC";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get service by ID
function getServiceById($db, $id) {
    $query = "SELECT s.*, c.name as category_name
              FROM services s 
              LEFT JOIN categories c ON s.category_id = c.id
              WHERE s.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to add new service
function addService($db, $serviceData) {
    $query = "INSERT INTO services (category_id, name, price, description) 
              VALUES (:category_id, :name, :price, :description)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':category_id', $serviceData['category_id']);
    $stmt->bindParam(':name', $serviceData['name']);
    $stmt->bindParam(':price', $serviceData['price']);
    $stmt->bindParam(':description', $serviceData['description']);
    return $stmt->execute();
}

// Function to update service
function updateService($db, $serviceData) {
    $query = "UPDATE services SET 
              category_id = :category_id,
              name = :name,
              price = :price,
              description = :description
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':category_id', $serviceData['category_id']);
    $stmt->bindParam(':name', $serviceData['name']);
    $stmt->bindParam(':price', $serviceData['price']);
    $stmt->bindParam(':description', $serviceData['description']);
    $stmt->bindParam(':id', $serviceData['id']);
    return $stmt->execute();
}

// Function to delete service
function deleteService($db, $id) {
    $query = "DELETE FROM services WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

// Function to get all categories
function getAllCategories($db) {
    $query = "SELECT id, name FROM categories";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$serviceId = isset($_GET['id']) ? $_GET['id'] : null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $serviceData = [
            'category_id' => $_POST['category_id'],
            'name' => $_POST['name'],
            'price' => $_POST['price'],
            'description' => $_POST['description']
        ];
        if (addService($db, $serviceData)) {
            header("Location: service-management.php");
            exit();
        }
    } elseif ($action === 'edit') {
        $serviceData = [
            'id' => $serviceId,
            'category_id' => $_POST['category_id'],
            'name' => $_POST['name'],
            'price' => $_POST['price'],
            'description' => $_POST['description']
        ];
        if (updateService($db, $serviceData)) {
            header("Location: service-management.php");
            exit();
        }
    }
}

// Handle actions
switch ($action) {
    case 'add':
        $categories = getAllCategories($db);
        break;
    case 'edit':
        $service = getServiceById($db, $serviceId);
        $categories = getAllCategories($db);
        break;
    case 'delete':
        if (deleteService($db, $serviceId)) {
            header("Location: service-management.php");
            exit();
        }
        break;
    case 'view':
        $service = getServiceById($db, $serviceId);
        break;
    default:
        $filters = [
            'search' => isset($_GET['search']) ? $_GET['search'] : '',
            'category' => isset($_GET['category']) ? $_GET['category'] : ''
        ];
        $services = getAllServices($db, $filters);
        $categories = getAllCategories($db);
        break;
}

// Fetch the admin's name
$adminName = '';
if (isset($_SESSION['user_id'])) {
    $query = "SELECT firstname, lastname FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $adminName = htmlspecialchars($result['firstname'] . ' ' . $result['lastname']);
    }
}   

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management - SupportHaven Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .modal-backdrop {
            opacity: 0.5;
        }
        .modal-dialog {
            margin: 1.75rem auto;
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
                        <h4 class="text-white">Admin Panel</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i>User Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="technician-management.php"><i class="fas fa-user-tie me-2"></i>Technician Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="booking-management.php"><i class="fas fa-calendar-check me-2"></i>Booking Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="service-management.php"><i class="fas fa-cogs me-2"></i>Service Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php"><i class="fas fa-chart-bar me-2"></i>Reports and Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php"><i class="fas fa-sliders-h me-2"></i>Settings</a>
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
                    <p class="lead">Service Management</p>
                </div>

                <?php if ($action === 'list'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h2 class="mb-0"><i class="fas fa-cogs me-2"></i>Services</h2>
                            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add New Service</a>
                        </div>
                        <div class="card-body">
                            <form action="" method="get" class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-control" name="category">
                                            <option value="">All Categories</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                                    </div>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($service['id']); ?></td>
                                            <td><?php echo htmlspecialchars($service['name']); ?></td>
                                            <td><?php echo htmlspecialchars($service['category_name']); ?></td>
                                            <td>₱<?php echo htmlspecialchars($service['price']); ?></td>
                                            <td>
                                                <a href="?action=view&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                                <a href="?action=edit&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                <a href="?action=delete&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this service?');"><i class="fas fa-trash"></i></a>
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
                            <h2 class="mb-0"><i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?> me-2"></i><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Service</h2>
                        </div>
                        <div class="card-body">
                            <form action="?action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $serviceId : ''; ?>" method="post">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-control" id="category_id" name="category_id" required>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo isset($service['category_id']) && $service['category_id'] == $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Service Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($service['name']) ? htmlspecialchars($service['name']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (₱)</label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo isset($service['price']) ? htmlspecialchars($service['price']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required><?php echo isset($service['description']) ? htmlspecialchars($service['description']) : ''; ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add Service' : 'Update Service'; ?></button>
                                <a href="?action=list" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                <?php elseif ($action === 'view'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h2 class="mb-0"><i class="fas fa-cog me-2"></i>Service Details</h2>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">Service ID</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($service['id']); ?></dd>

                                <dt class="col-sm-3">Name</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($service['name']); ?></dd>

                                <dt class="col-sm-3">Category</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($service['category_name']); ?></dd>

                                <dt class="col-sm-3">Price</dt>
                                <dd class="col-sm-9">₱<?php echo htmlspecialchars($service['price']); ?></dd>

                                <dt class="col-sm-3">Description</dt>
                                <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($service['description'])); ?></dd>
                            </dl>
                            <a href="?action=list" class="btn btn-primary mt-3">Back to List</a>
                            <a href="?action=edit&id=<?php echo $service['id']; ?>" class="btn btn-warning mt-3">Edit Service</a>
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