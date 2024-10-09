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

// Define the $action variable
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

// Define the $bookingId variable
$bookingId = isset($_GET['id']) ? $_GET['id'] : null;

// Function to get upcoming bookings
function getUpcomingBookings($db, $technicianId) {
    $query = "SELECT b.id, b.booking_date, b.booking_time, s.name AS service_name, u.firstname, u.lastname, b.status, b.total_cost 
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
    $query = "SELECT b.id, b.booking_date, b.booking_time, s.name AS service_name, u.firstname, u.lastname, b.status, b.total_cost 
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
function getJobDetails($db, $jobId) {
    $query = "
        SELECT 
            b.id,
            b.booking_date,
            b.booking_time,
            s.name AS service_name,
            u.firstname,
            u.lastname,
            u.email,
            t.phone,
            b.location AS notes,
            b.status,
            t.expertise,
            t.experience,
            t.photo,
            t.skill_rating,
            b.total_cost
        FROM 
            bookings b
        JOIN 
            services s ON b.service_id = s.id
        JOIN 
            users u ON b.user_id = u.id
        JOIN
            technicians t ON b.technician_id = t.user_id
        WHERE 
            b.id = :id
    ";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $jobId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to update booking status
function updateBookingStatus($db, $bookingId, $technicianId, $status) {
    $query = "UPDATE bookings SET status = :status WHERE id = :booking_id AND technician_id = :technician_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':booking_id', $bookingId, PDO::PARAM_INT);
    $stmt->bindParam(':technician_id', $technicianId, PDO::PARAM_INT);
    return $stmt->execute();
}

// Handle booking status update
if ($action === 'update_status' && $bookingId) {
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, ['accepted', 'completed', 'cancelled'])) {
        if (updateBookingStatus($db, $bookingId, $technicianId, $newStatus)) {
            $_SESSION['success_message'] = "Booking status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update booking status.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid status.";
    }
    header("Location: technician.php?action=bookings");
    exit();
}

// Fetch data based on the action
$upcomingBookings = ($action === 'dashboard' || $action === 'bookings') ? getUpcomingBookings($db, $technicianId) : [];
$jobHistory = ($action === 'dashboard' || $action === 'history') ? getJobHistory($db, $technicianId) : [];

// Fetch technician details
$technicianQuery = "SELECT expertise, experience, photo, status, skill_rating FROM technicians WHERE user_id = :user_id";
$technicianStmt = $db->prepare($technicianQuery);
$technicianStmt->bindParam(':user_id', $technicianId);
$technicianStmt->execute();
$technicianDetails = $technicianStmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
</head>
<body class="bg-gray-100">
    <header class="bg-white shadow-md">
        <nav class="container mx-auto px-4 py-3">
            <div class="flex flex-wrap justify-between items-center">
                <div class="flex items-center mb-2 sm:mb-0">
                    <a href="index.html">
                        <img src="images/logo.png" alt="SupportHaven Logo" class="h-12 mr-3">
                    </a>
                </div>
                <div x-data="{ open: false }" class="w-full sm:w-auto">
                    <button @click="open = !open" class="text-gray-500 w-full sm:hidden">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div :class="{'hidden sm:flex': !open}" class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4 mt-2 sm:mt-0">
                        <a href="?action=dashboard" class="text-gray-700 hover:text-blue-500 transition duration-300">Dashboard</a>
                        <a href="?action=bookings" class="text-gray-700 hover:text-blue-500 transition duration-300">Upcoming Bookings</a>
                        <a href="?action=history" class="text-gray-700 hover:text-blue-500 transition duration-300">Job History</a>
                        <div x-data="{ profileOpen: false }" class="relative">
                            <button @click="profileOpen = !profileOpen" class="flex items-center space-x-2 focus:outline-none">
                                <i class="fas fa-user-circle text-gray-700 text-2xl"></i>
                                <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <i class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                            <div x-show="profileOpen" @click.away="profileOpen = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1">
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-wrap -mx-4">
            <main class="w-full px-4">
                <?php if ($action === 'dashboard'): ?>
                    <h2 class="text-2xl font-bold mb-4">Technician Dashboard</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-xl font-semibold mb-4">Upcoming Bookings</h3>
                            <?php if (count($upcomingBookings) > 0): ?>
                                <ul class="space-y-4">
                                    <?php foreach (array_slice($upcomingBookings, 0, 5) as $booking): ?>
                                        <li class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                                            <div class="mb-2 sm:mb-0">
                                                <i class="far fa-calendar-alt mr-2"></i>
                                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> at <?php echo date('h:i A', strtotime($booking['booking_time'])); ?> - 
                                                <?php echo htmlspecialchars($booking['service_name']); ?> for 
                                                <?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname']); ?>
                                                <br>
                                                <small class="text-gray-500">Total Cost: ₱<?php echo number_format($booking['total_cost'], 2); ?></small>
                                            </div>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $booking['status'] === 'accepted' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800'; ?>"><?php echo ucfirst($booking['status']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-gray-500">No upcoming bookings.</p>
                            <?php endif; ?>
                        </div>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-xl font-semibold mb-4">Recent Job History</h3>
                            <?php if (count($jobHistory) > 0): ?>
                                <ul class="space-y-4">
                                    <?php foreach (array_slice($jobHistory, 0, 5) as $job): ?>
                                        <li class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                                            <div class="mb-2 sm:mb-0">
                                                <i class="fas fa-check-circle mr-2"></i>
                                                <?php echo date('M d, Y', strtotime($job['booking_date'])); ?> - 
                                                <?php echo htmlspecialchars($job['service_name']); ?> for 
                                                <?php echo htmlspecialchars($job['firstname'] . ' ' . $job['lastname']); ?>
                                                <br>
                                                <small class="text-gray-500">Total Cost: ₱<?php echo number_format($job['total_cost'], 2); ?></small>
                                            </div>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-200 text-blue-800"><?php echo ucfirst($job['status']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-gray-500">No job history available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($action === 'bookings'): ?>
                    <h2 class="text-2xl font-bold mb-4">Upcoming Bookings</h2>
                    <?php if (count($upcomingBookings) > 0): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                        <th class="py-3 px-6 text-left">Date</th>
                                        <th class="py-3 px-6 text-left">Time</th>
                                        <th class="py-3 px-6 text-left">Service</th>
                                        <th class="py-3 px-6 text-left">Customer</th>
                                        <th class="py-3 px-6 text-left">Status</th>
                                        <th class="py-3 px-6 text-left">Total Cost</th>
                                        <th class="py-3 px-6 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 text-sm font-light">
                                    <?php foreach ($upcomingBookings as $booking): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                                            <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                            <td class="py-3 px-6 text-left"><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></td>
                                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname']); ?></td>
                                            <td class="py-3 px-6 text-left">
                                                <span class="px-2 py-1 font-semibold leading-tight rounded-full <?php echo $booking['status'] === 'accepted' ? 'text-green-700 bg-green-100' : 'text-yellow-700 bg-yellow-100'; ?>"><?php echo ucfirst($booking['status']); ?></span>
                                            </td>
                                            <td class="py-3 px-6 text-left">₱<?php echo number_format($booking['total_cost'], 2); ?></td>
                                            <td class="py-3 px-6 text-center">
                                                <form action="?action=update_status&id=<?php echo $booking['id']; ?>" method="POST" class="inline-block">
                                                    <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 mb-2 sm:mb-0">
                                                        <option value="accepted" <?php echo $booking['status'] === 'accepted' ? 'selected' : ''; ?>>Accept</option>
                                                        <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Complete</option>
                                                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancel</option>
                                                    </select>
                                                    <button type="submit" class="bg-blue-500 text-white active:bg-blue-600 font-bold uppercase text-xs px-4 py-2 rounded shadow hover:shadow-md outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150">Update</button>
                                                </form>
                                                <a href="?action=details&id=<?php echo $booking['id']; ?>" class="bg-gray-500 text-white active:bg-gray-600 font-bold uppercase text-xs px-4 py-2 rounded shadow hover:shadow-md outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150">Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No upcoming bookings.</p>
                    <?php endif; ?>
                <?php elseif ($action === 'history'): ?>
                    <h2 class="text-2xl font-bold mb-4">Job History</h2>
                    <?php if (count($jobHistory) > 0): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                        <th class="py-3 px-6 text-left">Date</th>
                                        <th class="py-3 px-6 text-left">Time</th>
                                        <th class="py-3 px-6 text-left">Service</th>
                                        <th class="py-3 px-6 text-left">Customer</th>
                                        <th class="py-3 px-6 text-left">Status</th>
                                        <th class="py-3 px-6 text-left">Total Cost</th>
                                        <th class="py-3 px-6 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 text-sm font-light">
                                    <?php foreach ($jobHistory as $job): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                                            <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo date('M d, Y', strtotime($job['booking_date'])); ?></td>
                                            <td class="py-3 px-6 text-left"><?php echo date('h:i A', strtotime($job['booking_time'])); ?></td>
                                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($job['service_name']); ?></td>
                                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($job['firstname'] . ' ' . $job['lastname']); ?></td>
                                            <td class="py-3 px-6 text-left">
                                                <span class="px-2 py-1 font-semibold leading-tight rounded-full text-blue-700 bg-blue-100"><?php echo ucfirst($job['status']); ?></span>
                                            </td>
                                            <td class="py-3 px-6 text-left">₱<?php echo number_format($job['total_cost'], 2); ?></td>
                                            <td class="py-3 px-6 text-center">
                                                <a href="?action=details&id=<?php echo $job['id']; ?>" class="bg-gray-500 text-white active:bg-gray-600 font-bold uppercase text-xs px-4 py-2 rounded shadow hover:shadow-md outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150">Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No job history available.</p>
                    <?php endif; ?>
                <?php elseif ($action === 'details' && $bookingId): ?>
                    <?php $jobDetails = getJobDetails($db, $bookingId); ?>
                    <?php if ($jobDetails): ?>
                        <h2 class="text-2xl font-bold mb-4">Job Details</h2>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-xl font-semibold mb-4"><?php echo htmlspecialchars($jobDetails['service_name']); ?></h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($jobDetails['booking_date'])); ?></p>
                                    <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($jobDetails['booking_time'])); ?></p>
                                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($jobDetails['firstname'] . ' ' . $jobDetails['lastname']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($jobDetails['email']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($jobDetails['phone']); ?></p>
                                </div>
                                <div>
                                    <p><strong>Notes:</strong> <?php echo htmlspecialchars($jobDetails['notes']); ?></p>
                                    <p><strong>Status:</strong> <?php echo ucfirst($jobDetails['status']); ?></p>
                                    <p><strong>Total Cost:</strong> ₱<?php echo number_format($jobDetails['total_cost'], 2); ?></p>
                                    <p><strong>Technician Expertise:</strong> <?php echo htmlspecialchars($jobDetails['expertise']); ?></p>
                                    <p><strong>Technician Experience:</strong> <?php echo htmlspecialchars($jobDetails['experience']); ?> years</p>
                                    <p><strong>Technician Skill Rating:</strong> <?php echo number_format($jobDetails['skill_rating'], 2); ?>/5.00</p>
                                </div>
                            </div>
                            <form action="?action=update_status&id=<?php echo $jobDetails['id']; ?>" method="POST" class="mt-4">
                                <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-auto p-2.5 mb-2 sm:mb-0">
                                    <option value="accepted" <?php echo $jobDetails['status'] === 'accepted' ? 'selected' : ''; ?>>Accept</option>
                                    <option value="completed" <?php echo $jobDetails['status'] === 'completed' ? 'selected' : ''; ?>>Complete</option>
                                    <option value="cancelled" <?php echo $jobDetails['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancel</option>
                                </select>
                                <button type="submit" class="mt-2 bg-blue-500 text-white active:bg-blue-600 font-bold uppercase text-xs px-4 py-2 rounded shadow hover:shadow-md outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150">Update Status</button>
                            </form>
                        </div>
                        <a href="?action=<?php echo $jobDetails['booking_date'] >= date('Y-m-d') ? 'bookings' : 'history'; ?>" class="mt-4 inline-block bg-blue-500 text-white active:bg-blue-600 font-bold uppercase text-xs px-4 py-2 rounded shadow hover:shadow-md outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150">Back to List</a>
                    <?php else: ?>
                        <p class="text-red-500">Job details not found.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>