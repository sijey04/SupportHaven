<?php
session_start();
require_once __DIR__ . '/connection.php';

// Check if user is logged in and has technician role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'technician') {
    header("Location: login.php");
    exit();
}

$database = new Connection();
$db = $database->getConnection();

$technicianId = $_SESSION['user_id'];

// Function to get upcoming bookings
function getUpcomingbookings($db, $technicianId) {
    $query = "SELECT b.id, b.booking_date, b.booking_time, s.name AS service_name, u.firstName, u.lastName 
              FROM bookings b
              JOIN services s ON b.service_id = s.id
              JOIN users u ON b.user_id = u.id
              WHERE b.technician_id = :technician_id AND b.booking_date >= CURDATE()
              ORDER BY b.booking_date, b.booking_time";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':technician_id', $technicianId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get job history
function getJobHistory($db, $technicianId) {
    $query = "SELECT b.id, b.booking_date, b.booking_time, s.name AS service_name, u.firstName, u.lastName 
              FROM bookings b
              JOIN services s ON b.service_id = s.id
              JOIN users u ON b.user_id = u.id
              WHERE b.technician_id = :technician_id AND b.booking_date < CURDATE()
              ORDER BY b.booking_date DESC, b.booking_time DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':technician_id', $technicianId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get job details
function getJobDetails($db, $bookingId) {
    $query = "SELECT b.id, b.booking_date, b.booking_time, s.name AS service_name, u.firstName, u.lastName, u.email, u.phone, b.notes
              FROM bookings b
              JOIN services s ON b.service_id = s.id
              JOIN users u ON b.user_id = u.id
              WHERE b.id = :booking_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':booking_id', $bookingId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$upcomingbookings = getUpcomingbookings($db, $technicianId);
$jobHistory = getJobHistory($db, $technicianId);

$action = $_GET['action'] ?? 'dashboard';
$bookingId = $_GET['id'] ?? null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #ffffff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            min-height: 100vh;
        }
        .main-content {
            padding: 20px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .nav-link {
            color: var(--secondary-color);
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="images/logo.png" alt="Logo" class="logo">
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $action === 'dashboard' ? 'active' : ''; ?>" href="?action=dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $action === 'bookings' ? 'active' : ''; ?>" href="?action=bookings">
                                <i class="fas fa-calendar-check me-2"></i>Upcoming Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $action === 'history' ? 'active' : ''; ?>" href="?action=history">
                                <i class="fas fa-history me-2"></i>Job History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <?php if ($action === 'dashboard'): ?>
                    <h2 class="mb-4">Technician Dashboard</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">Upcoming Bookings</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (count($upcomingbookings) > 0): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach (array_slice($upcomingbookings, 0, 5) as $booking): ?>
                                                <li class="list-group-item">
                                                    <i class="far fa-calendar-alt me-2"></i>
                                                    <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> at <?php echo date('h:i A', strtotime($booking['booking_time'])); ?> - 
                                                    <?php echo htmlspecialchars($booking['service_name']); ?> for 
                                                    <?php echo htmlspecialchars($booking['firstName'] . ' ' . $booking['lastName']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted">No upcoming bookings.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="card-title mb-0">Recent Job History</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (count($jobHistory) > 0): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach (array_slice($jobHistory, 0, 5) as $job): ?>
                                                <li class="list-group-item">
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    <?php echo date('M d, Y', strtotime($job['booking_date'])); ?> - 
                                                    <?php echo htmlspecialchars($job['service_name']); ?> for 
                                                    <?php echo htmlspecialchars($job['firstName'] . ' ' . $job['lastName']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted">No job history available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($action === 'bookings'): ?>
                    <h2 class="mb-4">Upcoming Bookings</h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Customer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingbookings as $booking): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['firstName'] . ' ' . $booking['lastName']); ?></td>
                                        <td>
                                            <a href="?action=details&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($action === 'history'): ?>
                    <h2 class="mb-4">Job History</h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Customer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jobHistory as $job): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($job['booking_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($job['booking_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($job['service_name']); ?></td>
                                        <td><?php echo htmlspecialchars($job['firstName'] . ' ' . $job['lastName']); ?></td>
                                        <td>
                                            <a href="?action=details&id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($action === 'details' && $bookingId): ?>
                    <?php
                    $jobDetails = getJobDetails($db, $bookingId);
                    if ($jobDetails):
                    ?>
                        <h2 class="mb-4">Job Details</h2>
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($jobDetails['service_name']); ?></h5>
                                <p class="card-text">
                                    <strong><i class="far fa-calendar-alt me-2"></i>Date:</strong> <?php echo date('M d, Y', strtotime($jobDetails['booking_date'])); ?><br>
                                    <strong><i class="far fa-clock me-2"></i>Time:</strong> <?php echo date('h:i A', strtotime($jobDetails['booking_time'])); ?><br>
                                    <strong><i class="far fa-user me-2"></i>Customer:</strong> <?php echo htmlspecialchars($jobDetails['firstName'] . ' ' . $jobDetails['lastName']); ?><br>
                                    <strong><i class="far fa-envelope me-2"></i>Email:</strong> <?php echo htmlspecialchars($jobDetails['email']); ?><br>
                                    <strong><i class="fas fa-phone me-2"></i>Phone:</strong> <?php echo htmlspecialchars($jobDetails['phone']); ?><br>
                                    <strong><i class="far fa-sticky-note me-2"></i>Notes:</strong> <?php echo nl2br(htmlspecialchars($jobDetails['notes'])); ?>
                                </p>
                            </div>
                        </div>
                        <a href="?action=dashboard" class="btn btn-primary mt-3">Back to Dashboard</a>
                    <?php else: ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>Job details not found.
                        </div>
                        <a href="?action=dashboard" class="btn btn-primary">Back to Dashboard</a>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>