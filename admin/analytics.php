<?php
session_start();
require_once __DIR__ . '/../auth_middleware.php';
checkRole('admin');
require_once __DIR__ . '/../connection.php';

$database = new Connection();
$db = $database->getConnection();

// Analytics functions
function getMonthlyEarnings($db) {
    $query = "SELECT 
                DATE_FORMAT(booking_date, '%Y-%m') as month,
                SUM(total_cost) as earnings,
                COUNT(*) as total_bookings
              FROM bookings 
              WHERE status = 'completed'
              GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
              ORDER BY month DESC
              LIMIT 12";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getServicePerformance($db) {
    $query = "SELECT 
                s.name as service_name,
                COUNT(b.id) as total_bookings,
                SUM(b.total_cost) as total_revenue,
                AVG(b.total_cost) as average_revenue
              FROM services s
              LEFT JOIN bookings b ON s.id = b.service_id
              WHERE b.status = 'completed'
              GROUP BY s.id, s.name
              ORDER BY total_revenue DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTechnicianPerformance($db) {
    $query = "SELECT 
                CONCAT(u.firstname, ' ', u.lastname) as technician_name,
                COUNT(b.id) as total_jobs,
                COUNT(CASE WHEN b.status = 'completed' THEN 1 END) as completed_jobs,
                AVG(tr.rating) as average_rating,
                SUM(b.total_cost) as total_revenue
              FROM users u
              JOIN technicians t ON u.id = t.user_id
              LEFT JOIN bookings b ON u.id = b.technician_id
              LEFT JOIN technician_reviews tr ON b.id = tr.booking_id
              GROUP BY u.id, u.firstname, u.lastname
              ORDER BY total_revenue DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOverallStats($db) {
    $query = "SELECT 
                (SELECT COUNT(*) FROM users WHERE user_role_id = 1) as total_customers,
                (SELECT COUNT(*) FROM users WHERE user_role_id = 2) as total_technicians,
                (SELECT COUNT(*) FROM bookings WHERE status = 'completed') as completed_bookings,
                (SELECT SUM(total_cost) FROM bookings WHERE status = 'completed') as total_revenue,
                (SELECT AVG(rating) FROM technician_reviews) as average_rating";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add these new analytics functions
function getCustomerSatisfactionMetrics($db) {
    $query = "SELECT 
                AVG(rating) as average_rating,
                COUNT(*) as total_reviews,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as satisfied_customers,
                COUNT(CASE WHEN rating < 3 THEN 1 END) as unsatisfied_customers
              FROM technician_reviews";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getBookingTrends($db) {
    $query = "SELECT 
                DATE_FORMAT(booking_date, '%Y-%m') as month,
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings
              FROM bookings
              GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
              ORDER BY month DESC
              LIMIT 6";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategoryPerformance($db) {
    $query = "SELECT 
                c.name as category_name,
                COUNT(b.id) as total_bookings,
                SUM(b.total_cost) as total_revenue,
                AVG(tr.rating) as average_rating
              FROM categories c
              LEFT JOIN services s ON c.id = s.category_id
              LEFT JOIN bookings b ON s.id = b.service_id
              LEFT JOIN technician_reviews tr ON b.id = tr.booking_id
              GROUP BY c.id, c.name
              ORDER BY total_bookings DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch data
$monthlyEarnings = getMonthlyEarnings($db);
$servicePerformance = getServicePerformance($db);
$technicianPerformance = getTechnicianPerformance($db);
$overallStats = getOverallStats($db);
$satisfactionMetrics = getCustomerSatisfactionMetrics($db);
$bookingTrends = getBookingTrends($db);
$categoryPerformance = getCategoryPerformance($db);

// Fetch admin name
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
    <title>Analytics - SupportHaven Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .sidebar .nav-link.active {
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
        .stats-card {
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
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
                            <a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i>User Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="technician-management.php"><i class="fas fa-user-tie me-2"></i>Technician Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="booking-management.php"><i class="fas fa-calendar-check me-2"></i>Booking Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="service-management.php"><i class="fas fa-cogs me-2"></i>Service Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="analytics.php"><i class="fas fa-chart-bar me-2"></i>Reports and Analytics</a>
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

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 main-content">
                <button class="btn btn-primary d-md-none mb-3" type="button" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="admin-header p-4">
                    <h2 class="display-9 fw-bold">Welcome, <?php echo $adminName ? $adminName : 'Admin'; ?></h2>
                    <p class="lead">Reports and Analytics</p>
                </div>

                <!-- Overall Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Revenue</h5>
                                <h2 class="mb-0">₱<?php echo number_format($overallStats['total_revenue'], 2); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Completed Jobs</h5>
                                <h2 class="mb-0"><?php echo $overallStats['completed_bookings']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Customers</h5>
                                <h2 class="mb-0"><?php echo $overallStats['total_customers']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Average Rating</h5>
                                <h2 class="mb-0"><?php echo number_format($overallStats['average_rating'], 1); ?> ⭐</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Monthly Revenue</h5>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Service Performance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Service Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Total Bookings</th>
                                        <th>Total Revenue</th>
                                        <th>Average Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($servicePerformance as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                        <td><?php echo $service['total_bookings']; ?></td>
                                        <td>₱<?php echo number_format($service['total_revenue'], 2); ?></td>
                                        <td>₱<?php echo number_format($service['average_revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Technician Performance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Technician Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Technician</th>
                                        <th>Total Jobs</th>
                                        <th>Completed Jobs</th>
                                        <th>Average Rating</th>
                                        <th>Total Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($technicianPerformance as $tech): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tech['technician_name']); ?></td>
                                        <td><?php echo $tech['total_jobs']; ?></td>
                                        <td><?php echo $tech['completed_jobs']; ?></td>
                                        <td><?php echo number_format($tech['average_rating'], 1); ?> ⭐</td>
                                        <td>₱<?php echo number_format($tech['total_revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Customer Satisfaction Metrics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Customer Satisfaction Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center mb-4">
                                    <h3 class="display-4 text-primary"><?php echo number_format($satisfactionMetrics['average_rating'], 1); ?></h3>
                                    <p class="text-muted">Average Rating</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center mb-4">
                                    <h3 class="display-4 text-success"><?php echo $satisfactionMetrics['total_reviews']; ?></h3>
                                    <p class="text-muted">Total Reviews</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center mb-4">
                                    <h3 class="display-4 text-success"><?php echo $satisfactionMetrics['satisfied_customers']; ?></h3>
                                    <p class="text-muted">Satisfied Customers (4★+)</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center mb-4">
                                    <h3 class="display-4 text-danger"><?php echo $satisfactionMetrics['unsatisfied_customers']; ?></h3>
                                    <p class="text-muted">Unsatisfied Customers (<3★)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Trends -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Booking Trends</h5>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="bookingTrendsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Service Category Performance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Service Category Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Total Bookings</th>
                                        <th>Total Revenue</th>
                                        <th>Average Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categoryPerformance as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td><?php echo $category['total_bookings'] ?? 0; ?></td>
                                        <td>₱<?php echo number_format($category['total_revenue'] ?? 0, 2); ?></td>
                                        <td>
                                            <?php if ($category['average_rating']): ?>
                                                <?php echo number_format($category['average_rating'], 1); ?> ⭐
                                            <?php else: ?>
                                                No ratings
                                            <?php endif; ?>
                                        </td>
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

    <script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthlyEarnings, 'month')); ?>,
            datasets: [{
                label: 'Monthly Revenue',
                data: <?php echo json_encode(array_column($monthlyEarnings, 'earnings')); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    // Booking Trends Chart
    const bookingTrendsCtx = document.getElementById('bookingTrendsChart').getContext('2d');
    new Chart(bookingTrendsCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($bookingTrends, 'month')); ?>,
            datasets: [{
                label: 'Total Bookings',
                data: <?php echo json_encode(array_column($bookingTrends, 'total_bookings')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }, {
                label: 'Completed',
                data: <?php echo json_encode(array_column($bookingTrends, 'completed_bookings')); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1
            }, {
                label: 'Cancelled',
                data: <?php echo json_encode(array_column($bookingTrends, 'cancelled_bookings')); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

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