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

// Function to fetch all bookings
function getAllBookings($db, $filters = []) {
    $query = "SELECT b.id, u.firstname, u.lastname, s.name as service_name, 
              t.firstname as tech_firstname, t.lastname as tech_lastname, 
              b.booking_date, b.booking_time, b.location, b.payment_method, 
              b.total_cost, b.created_at, b.status
              FROM bookings b 
              JOIN users u ON b.user_id = u.id
              LEFT JOIN services s ON b.service_id = s.id
              LEFT JOIN users t ON b.technician_id = t.id";

    $conditions = [];
    $params = [];

    if (!empty($filters['search'])) {
        $conditions[] = "(u.firstname LIKE :search OR u.lastname LIKE :search OR s.name LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    if (!empty($filters['date_from'])) {
        $conditions[] = "b.booking_date >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $conditions[] = "b.booking_date <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    $query .= " ORDER BY b.booking_date DESC, b.booking_time DESC";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get booking by ID
function getBookingById($db, $id) {
    $query = "SELECT b.*, u.firstname, u.lastname, s.name as service_name, 
              t.firstname as tech_firstname, t.lastname as tech_lastname
              FROM bookings b 
              JOIN users u ON b.user_id = u.id
              LEFT JOIN services s ON b.service_id = s.id
              LEFT JOIN users t ON b.technician_id = t.id
              WHERE b.id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to update booking
function updateBooking($db, $bookingData) {
    $query = "UPDATE bookings SET 
              service_id = :service_id,
              technician_id = :technician_id,
              booking_date = :booking_date,
              booking_time = :booking_time,
              location = :location,
              payment_method = :payment_method,
              total_cost = :total_cost,
              status = :status
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':service_id', $bookingData['service_id']);
    $stmt->bindParam(':technician_id', $bookingData['technician_id']);
    $stmt->bindParam(':booking_date', $bookingData['booking_date']);
    $stmt->bindParam(':booking_time', $bookingData['booking_time']);
    $stmt->bindParam(':location', $bookingData['location']);
    $stmt->bindParam(':payment_method', $bookingData['payment_method']);
    $stmt->bindParam(':total_cost', $bookingData['total_cost']);
    $stmt->bindParam(':status', $bookingData['status']);
    $stmt->bindParam(':id', $bookingData['id']);
    return $stmt->execute();
}

// Function to delete booking
function deleteBooking($db, $id) {
    $query = "DELETE FROM bookings WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

// Function to get all services
function getAllServices($db) {
    $query = "SELECT id, name FROM services";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get all technicians
function getAllTechnicians($db) {
    $query = "SELECT u.id, u.firstname, u.lastname 
              FROM users u 
              JOIN technicians t ON u.id = t.user_id 
              WHERE t.status = 'approved'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$bookingId = isset($_GET['id']) ? $_GET['id'] : null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'edit') {
        $bookingData = [
            'id' => $bookingId,
            'service_id' => $_POST['service_id'],
            'technician_id' => $_POST['technician_id'],
            'booking_date' => $_POST['booking_date'],
            'booking_time' => $_POST['booking_time'],
            'location' => $_POST['location'],
            'payment_method' => $_POST['payment_method'],
            'total_cost' => $_POST['total_cost'],
            'status' => $_POST['status']
        ];
        if (updateBooking($db, $bookingData)) {
            header("Location: booking-management.php");
            exit();
        }
    }
}

// Handle actions
switch ($action) {
    case 'edit':
        $booking = getBookingById($db, $bookingId);
        $services = getAllServices($db);
        $technicians = getAllTechnicians($db);
        break;
    case 'delete':
        if (deleteBooking($db, $bookingId)) {
            header("Location: booking-management.php");
            exit();
        }
        break;
    case 'view':
        $booking = getBookingById($db, $bookingId);
        break;
    default:
        $filters = [
            'search' => isset($_GET['search']) ? $_GET['search'] : '',
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
            'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : ''
        ];
        $bookings = getAllBookings($db, $filters);
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
    <title>Booking Management - SupportHaven Admin</title>
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
                            <a class="nav-link active" href="booking-management.php"><i class="fas fa-calendar-check me-2"></i>Booking Management</a>
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
                    <p class="lead">Booking Management</p>
                </div>

                <?php if ($action === 'list'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Bookings</h2>
                        </div>
                        <div class="card-body">
                            <form action="" method="get" class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" class="form-control" name="date_from" value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" class="form-control" name="date_to" value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                                    </div>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Customer</th>
                                            <th>Service</th>
                                            <th>Technician</th>
                                            <th>Date & Time</th>
                                            <th>Total Cost</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['id']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['tech_firstname'] . ' ' . $booking['tech_lastname']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['booking_date'] . ' ' . $booking['booking_time']); ?></td>
                                            <td>₱<?php echo htmlspecialchars($booking['total_cost']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['status']); ?></td>
                                            <td>
                                                <a href="?action=view&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                                <a href="?action=edit&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                <a href="?action=delete&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this booking?');"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($action === 'edit'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h2 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Booking</h2>
                        </div>
                        <div class="card-body">
                            <form action="?action=edit&id=<?php echo $bookingId; ?>" method="post">
                                <div class="mb-3">
                                    <label for="service_id" class="form-label">Service</label>
                                    <select class="form-control" id="service_id" name="service_id" required>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?php echo $service['id']; ?>" <?php echo $booking['service_id'] == $service['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($service['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="technician_id" class="form-label">Technician</label>
                                    <select class="form-control" id="technician_id" name="technician_id" required>
                                        <?php foreach ($technicians as $technician): ?>
                                            <option value="<?php echo $technician['id']; ?>" <?php echo $booking['technician_id'] == $technician['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="booking_date" class="form-label">Booking Date</label>
                                    <input type="date" class="form-control" id="booking_date" name="booking_date" value="<?php echo htmlspecialchars($booking['booking_date']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="booking_time" class="form-label">Booking Time</label>
                                    <input type="time" class="form-control" id="booking_time" name="booking_time" value="<?php echo htmlspecialchars($booking['booking_time']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($booking['location']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select class="form-control" id="payment_method" name="payment_method" required>
                                        <option value="credit_card" <?php echo $booking['payment_method'] == 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                                        <option value="paypal" <?php echo $booking['payment_method'] == 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                        <option value="cash" <?php echo $booking['payment_method'] == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="total_cost" class="form-label">Total Cost (₱)</label>
                                    <input type="number" step="0.01" class="form-control" id="total_cost" name="total_cost" value="<?php echo htmlspecialchars($booking['total_cost']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="accepted" <?php echo $booking['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                        <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Booking</button>
                                <a href="?action=list" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                <?php elseif ($action === 'view'): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Booking Details</h2>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">Booking ID</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($booking['id']); ?></dd>

                                <dt class="col-sm-3">Customer</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname']); ?></dd>

                                <dt class="col-sm-3">Service</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($booking['service_name']); ?></dd>

                                <dt class="col-sm-3">Technician</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($booking['tech_firstname'] . ' ' . $booking['tech_lastname']); ?></dd>

                                <dt class="col-sm-3">Date & Time</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($booking['booking_date'] . ' ' . $booking['booking_time']); ?></dd>

                                <dt class="col-sm-3">Location</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($booking['location']); ?></dd>

                                <dt class="col-sm-3">Payment Method</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($booking['payment_method']); ?></dd>

                                <dt class="col-sm-3">Total Cost</dt>
                                <dd class="col-sm-9">₱<?php echo htmlspecialchars($booking['total_cost']); ?></dd>

                                <dt class="col-sm-3">Status</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($booking['status']); ?></dd>
                            </dl>
                            <a href="?action=list" class="btn btn-primary mt-3">Back to List</a>
                            <a href="?action=edit&id=<?php echo $booking['id']; ?>" class="btn btn-warning mt-3">Edit Booking</a>
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