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

// Function to fetch all technicians
function getAllTechnicians($db) {
    $query = "SELECT t.id, u.firstName, u.lastName, u.email, t.expertise, t.experience, t.photo, t.status, t.phone
              FROM technicians t 
              JOIN users u ON t.user_id = u.id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get technician by ID
function getTechnicianById($db, $id) {
    $query = "SELECT t.id, u.firstName, u.lastName, u.email, t.expertise, t.experience, t.photo, t.status, t.phone
              FROM technicians t 
              JOIN users u ON t.user_id = u.id 
              WHERE t.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to add new technician
function addTechnician($db, $technicianData) {
    $db->beginTransaction();
    try {
        // Insert into users table
        $userQuery = "INSERT INTO users (firstName, lastName, email, password, user_role_id) 
                      VALUES (:firstName, :lastName, :email, :password, 
                             (SELECT id FROM user_roles WHERE role_name = 'technician'))";
        $userStmt = $db->prepare($userQuery);
        $userStmt->bindParam(":firstName", $technicianData['firstName']);
        $userStmt->bindParam(":lastName", $technicianData['lastName']);
        $userStmt->bindParam(":email", $technicianData['email']);
        $userStmt->bindParam(":password", password_hash($technicianData['password'], PASSWORD_DEFAULT));
        $userStmt->execute();

        $userId = $db->lastInsertId();

        // Insert into technicians table
        $techQuery = "INSERT INTO technicians (user_id, phone, expertise, experience, photo, status) 
                      VALUES (:userId, :phone, :expertise, :experience, :photo, 'pending')";
        $techStmt = $db->prepare($techQuery);
        $techStmt->bindParam(":userId", $userId);
        $techStmt->bindParam(":phone", $technicianData['phone']);
        $techStmt->bindParam(":expertise", $technicianData['expertise']);
        $techStmt->bindParam(":experience", $technicianData['experience']);
        $techStmt->bindParam(":photo", "/uploads/" . $technicianData['photo']);
        $techStmt->execute();

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

// Function to update technician
function updateTechnician($db, $technicianData) {
    $query = "UPDATE users u
              JOIN technicians t ON u.id = t.user_id
              SET u.firstName = :firstName, u.lastName = :lastName, u.email = :email,
                  t.expertise = :expertise, t.experience = :experience, t.photo = :photo,
                  t.phone = :phone, t.status = :status
              WHERE t.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':firstName', $technicianData['firstName']);
    $stmt->bindParam(':lastName', $technicianData['lastName']);
    $stmt->bindParam(':email', $technicianData['email']);
    $stmt->bindParam(':expertise', $technicianData['expertise']);
    $stmt->bindParam(':experience', $technicianData['experience']);
    $photo = "/uploads/" . $technicianData['photo'];
    $stmt->bindParam(':photo', $photo);
    $stmt->bindParam(':phone', $technicianData['phone']);
    $stmt->bindParam(':status', $technicianData['status']);
    $stmt->bindParam(':id', $technicianData['id']);
    return $stmt->execute();
}

// Function to delete technician
function deleteTechnician($db, $id) {
    $query = "DELETE u, t FROM users u
              JOIN technicians t ON u.id = t.user_id
              WHERE t.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$technicianId = isset($_GET['id']) ? $_GET['id'] : null;

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $technicianData = [
                'firstName' => $_POST['firstName'],
                'lastName' => $_POST['lastName'],
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                'phone' => $_POST['phone'],
                'expertise' => $_POST['expertise'],
                'experience' => $_POST['experience'],
                'photo' => $_FILES['photo']['name']
            ];
            if (addTechnician($db, $technicianData)) {
                $uploadFile = $_FILES['photo']['tmp_name'];
                $destination = $_SERVER['DOCUMENT_ROOT'] . "/uploads/" . $_FILES['photo']['name'];
                move_uploaded_file($uploadFile, $destination);
                header("Location: technician-management.php");
                exit();
            }
        }
        break;
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $technicianData = [
                'id' => $technicianId,
                'firstName' => $_POST['firstName'],
                'lastName' => $_POST['lastName'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'expertise' => $_POST['expertise'],
                'experience' => $_POST['experience'],
                'status' => $_POST['status'],
                'photo' => isset($_FILES['photo']['name']) && $_FILES['photo']['name'] ? $_FILES['photo']['name'] : $_POST['current_photo']
            ];
            if (updateTechnician($db, $technicianData)) {
                if ($_FILES['photo']['name']) {
                    $uploadFile = $_FILES['photo']['tmp_name'];
                    $destination = $_SERVER['DOCUMENT_ROOT'] . "/uploads/" . $_FILES['photo']['name'];
                    move_uploaded_file($uploadFile, $destination);
                }
                header("Location: technician-management.php");
                exit();
            }
        } else {
            $technician = getTechnicianById($db, $technicianId);
        }
        break;
    case 'delete':
        if (deleteTechnician($db, $technicianId)) {
            header("Location: technician-management.php");
            exit();
        }
        break;
    case 'view':
        $technician = getTechnicianById($db, $technicianId);
        break;
    default:
        $technicians = getAllTechnicians($db);
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
    <title>Technician Management - SupportHaven Admin</title>
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
                            <a class="nav-link active" href="technician-management.php"><i class="fas fa-user-tie me-2"></i>Technician Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="booking-management.php"><i class="fas fa-calendar-check me-2"></i>Booking Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="service-management.php"><i class="fas fa-cogs me-2"></i>Service Management</a>
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
                    <p class="lead">Technician Management</p>
                </div>

                <?php if ($action === 'list'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h2 class="mb-0"><i class="fas fa-user-tie me-2"></i>Technicians</h2>
                            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add New Technician</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Photo</th>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Expertise</th>
                                            <th>Experience</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($technicians as $technician): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($technician['photo']); ?>" alt="<?php echo htmlspecialchars($technician['firstName'] . ' ' . $technician['lastName']); ?>" class="technician-avatar">
                                            </td>
                                            <td><?php echo htmlspecialchars($technician['id']); ?></td>
                                            <td><?php echo htmlspecialchars($technician['firstName'] . ' ' . $technician['lastName']); ?></td>
                                            <td><?php echo htmlspecialchars($technician['email']); ?></td>
                                            <td><?php echo htmlspecialchars($technician['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($technician['expertise']); ?></td>
                                            <td><?php echo htmlspecialchars($technician['experience']); ?> years</td>
                                            <td><?php echo htmlspecialchars($technician['status']); ?></td>
                                            <td>
                                                <a href="?action=view&id=<?php echo $technician['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                                <a href="?action=edit&id=<?php echo $technician['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                <a href="?action=delete&id=<?php echo $technician['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this technician?');"><i class="fas fa-trash"></i></a>
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
                            <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i><?php echo $action === 'add' ? 'Add New Technician' : 'Edit Technician'; ?></h2>
                        </div>
                        <div class="card-body">
                            <form action="?action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $technicianId : ''; ?>" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo $action === 'edit' && isset($technician['firstName']) ? htmlspecialchars($technician['firstName']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo $action === 'edit' && isset($technician['lastName']) ? htmlspecialchars($technician['lastName']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $action === 'edit' && isset($technician['email']) ? htmlspecialchars($technician['email']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $action === 'edit' && isset($technician['phone']) ? htmlspecialchars($technician['phone']) : ''; ?>" required>
                                </div>
                                <?php if ($action === 'add'): ?>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label for="expertise" class="form-label">Expertise</label>
                                    <input type="text" class="form-control" id="expertise" name="expertise" value="<?php echo $action === 'edit' && isset($technician['expertise']) ? htmlspecialchars($technician['expertise']) : ''; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="experience" class="form-label">Experience (years)</label>
                                    <input type="number" class="form-control" id="experience" name="experience" value="<?php echo $action === 'edit' && isset($technician['experience']) ? htmlspecialchars($technician['experience']) : ''; ?>" required>
                                </div>
                                <?php if ($action === 'edit'): ?>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="pending" <?php echo $technician['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $technician['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="declined" <?php echo $technician['status'] === 'declined' ? 'selected' : ''; ?>>Declined</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Photo</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                    <?php if ($action === 'edit' && isset($technician['photo'])): ?>
                                        <input type="hidden" name="current_photo" value="<?php echo htmlspecialchars($technician['photo']); ?>">
                                        <img src="<?php echo htmlspecialchars($technician['photo']); ?>" alt="Current Photo" class="mt-2" style="max-width: 100px;">
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add Technician' : 'Update Technician'; ?></button>
                                <a href="?action=list" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                <?php elseif ($action === 'view'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h2 class="mb-0"><i class="fas fa-user-tie me-2"></i>Technician Details</h2>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <img src="<?php echo htmlspecialchars($technician['photo']); ?>" alt="<?php echo htmlspecialchars($technician['firstName'] . ' ' . $technician['lastName']); ?>" class="img-fluid rounded">
                                </div>
                                <div class="col-md-8">
                                    <dl class="row">
                                        <dt class="col-sm-3">ID</dt>
                                        <dd class="col-sm-9"><?php echo htmlspecialchars($technician['id']); ?></dd>

                                        <dt class="col-sm-3">Name</dt>
                                        <dd class="col-sm-9"><?php echo htmlspecialchars($technician['firstName'] . ' ' . $technician['lastName']); ?></dd>

                                        <dt class="col-sm-3">Email</dt>
                                        <dd class="col-sm-9"><?php echo htmlspecialchars($technician['email']); ?></dd>

                                        <dt class="col-sm-3">Phone</dt>
                                        <dd class="col-sm-9"><?php echo htmlspecialchars($technician['phone']); ?></dd>

                                        <dt class="col-sm-3">Expertise</dt>
                                        <dd class="col-sm-9"><?php echo htmlspecialchars($technician['expertise']); ?></dd>

                                        <dt class="col-sm-3">Experience</dt>
                                        <dd class="col-sm-9"><?php echo htmlspecialchars($technician['experience']); ?> years</dd>

                                        <dt class="col-sm-3">Status</dt>
                                        <dd class="col-sm-9"><?php echo htmlspecialchars($technician['status']); ?></dd>
                                    </dl>
                                </div>
                            </div>
                            <a href="?action=list" class="btn btn-primary mt-3">Back to List</a>
                            <a href="?action=edit&id=<?php echo $technician['id']; ?>" class="btn btn-warning mt-3">Edit Technician</a>
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