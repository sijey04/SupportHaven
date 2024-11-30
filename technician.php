<?php
session_start();
require_once __DIR__ . '/connection.php';

// Check if user is logged in and has technician role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'technician') {
    header("Location: login.php");
    exit();
}

// Check if technician is approved
$database = new Connection();
$db = $database->getConnection();

$techQuery = "SELECT status FROM technicians WHERE user_id = :user_id";
$techStmt = $db->prepare($techQuery);
$techStmt->bindParam(":user_id", $_SESSION['user_id']);
$techStmt->execute();
$techStatus = $techStmt->fetchColumn();

if ($techStatus !== 'approved') {
    // Clear the session
    session_destroy();
    // Redirect to login with message
    header("Location: login.php?error=pending_approval");
    exit();
}

$technicianId = $_SESSION['user_id'];

// Define the $action variable
$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

// Define the $bookingId variable
$bookingId = isset($_GET['id']) ? $_GET['id'] : null;

// Add this at the top after session_start()
$svg_path = __DIR__ . '/images/default-avatar.svg';
if (!file_exists($svg_path)) {
    error_log("SVG file not found at: " . $svg_path);
}

// Add this after your existing session_start() and before the HTML
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (in_array($_FILES['profile_photo']['type'], $allowed_types) && $_FILES['profile_photo']['size'] <= $max_size) {
        $upload_dir = 'uploads/profile_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $technicianId . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_path)) {
            // Update database with new image path
            $update_query = "UPDATE users SET avatar = :photo WHERE id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":photo", $filename);
            $update_stmt->bindParam(":user_id", $technicianId);
            
            if ($update_stmt->execute()) {
                $_SESSION['success_message'] = "Profile image updated successfully!";
                // Update the profile array with new image
                $profile['photo'] = $filename;
            } else {
                $_SESSION['error_message'] = "Failed to update profile image in database.";
            }
        } else {
            $_SESSION['error_message'] = "Failed to upload image.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid file type or size. Please upload a JPG, PNG, or GIF file under 5MB.";
    }
    
    // Redirect to refresh the page
    header("Location: technician.php?action=profile");
    exit();
}

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
function getJobHistory($db, $technicianId, $filters = []) {
    $query = "SELECT b.id, b.booking_date, b.booking_time, s.name AS service_name, 
                     u.firstname, u.lastname, b.status, b.total_cost, b.location
              FROM bookings b
              JOIN services s ON b.service_id = s.id
              JOIN users u ON b.user_id = u.id
              WHERE b.technician_id = :technician_id";

    // Add filter conditions
    if (!empty($filters['date_from'])) {
        $query .= " AND b.booking_date >= :date_from";
    }
    if (!empty($filters['date_to'])) {
        $query .= " AND b.booking_date <= :date_to";
    }
    if (!empty($filters['status'])) {
        $query .= " AND b.status = :status";
    } else {
        $query .= " AND b.status IN ('completed', 'pending', 'accepted')";
    }

    $query .= " ORDER BY b.booking_date DESC, b.booking_time DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':technician_id', $technicianId);

    // Bind filter parameters
    if (!empty($filters['date_from'])) {
        $stmt->bindParam(':date_from', $filters['date_from']);
    }
    if (!empty($filters['date_to'])) {
        $stmt->bindParam(':date_to', $filters['date_to']);
    }
    if (!empty($filters['status'])) {
        $stmt->bindParam(':status', $filters['status']);
    }

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

    // Add query to get the last 3 reviews for this technician
    $reviewsQuery = "SELECT tr.*, u.firstname, u.lastname 
                    FROM technician_reviews tr
                    JOIN users u ON tr.user_id = u.id
                    WHERE tr.technician_id = (SELECT technician_id FROM bookings WHERE id = :booking_id)
                    ORDER BY tr.created_at DESC
                    LIMIT 3";
    $reviewsStmt = $db->prepare($reviewsQuery);
    $reviewsStmt->bindParam(':booking_id', $jobId);
    $reviewsStmt->execute();
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

    $jobDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $jobDetails['reviews'] = $reviews;

    return $jobDetails;
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

// Function to get unassigned bookings
function getUnassignedBookings($db) {
    $query = "SELECT b.id, b.booking_date, b.booking_time, s.name AS service_name, 
              u.firstname, u.lastname, b.location, b.total_cost, b.status
              FROM bookings b
              JOIN services s ON b.service_id = s.id
              JOIN users u ON b.user_id = u.id
              WHERE b.technician_id IS NULL 
              AND b.status = 'pending'
              AND b.booking_date >= CURDATE()
              ORDER BY b.booking_date, b.booking_time";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add function to assign booking to technician
function assignBooking($db, $bookingId, $technicianId) {
    $query = "UPDATE bookings 
              SET technician_id = :technician_id, 
                  status = 'accepted' 
              WHERE id = :booking_id 
              AND technician_id IS NULL";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':technician_id', $technicianId);
    $stmt->bindParam(':booking_id', $bookingId);
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

// Add this near the top with other action handlers
if ($action === 'assign_booking' && isset($_POST['booking_id'])) {
    if (assignBooking($db, $_POST['booking_id'], $technicianId)) {
        $_SESSION['success_message'] = "Job successfully assigned to you.";
        header("Location: technician.php?action=bookings");
    } else {
        $_SESSION['error_message'] = "Failed to assign job. It may have been taken by another technician.";
        header("Location: technician.php?action=available_jobs");
    }
    exit();
}

// Fetch data based on the action
$upcomingBookings = ($action === 'dashboard' || $action === 'bookings') ? getUpcomingBookings($db, $technicianId) : [];
$filters = [
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null,
    'status' => $_GET['status'] ?? null
];
$jobHistory = getJobHistory($db, $technicianId, $filters);

// Fetch technician details
$technicianQuery = "SELECT t.expertise, t.experience, t.status, t.skill_rating, u.avatar as photo 
                    FROM technicians t
                    JOIN users u ON t.user_id = u.id 
                    WHERE t.user_id = :user_id";
$technicianStmt = $db->prepare($technicianQuery);
$technicianStmt->bindParam(':user_id', $technicianId);
$technicianStmt->execute();
$technicianDetails = $technicianStmt->fetch(PDO::FETCH_ASSOC);

// Add this function to get status counts
function getStatusCounts($db, $technicianId) {
    $query = "SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_jobs,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_jobs
              FROM bookings 
              WHERE technician_id = :technician_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':technician_id', $technicianId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

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
                        <a href="?action=available_jobs" class="text-gray-700 hover:text-blue-500 transition duration-300">Available Jobs</a>
                        
                        <!-- Updated profile dropdown -->
                        <div x-data="{ profileOpen: false }" class="relative">
                            <button @click="profileOpen = !profileOpen" 
                                    class="flex items-center space-x-2 focus:outline-none hover:text-blue-500 transition duration-300">
                                <?php if (!empty($technicianDetails['photo']) && file_exists('uploads/profile_images/' . $technicianDetails['photo'])): ?>
                                    <img src="uploads/profile_images/<?php echo htmlspecialchars($technicianDetails['photo']); ?>" 
                                         alt="Profile" 
                                         class="w-8 h-8 rounded-full object-cover">
                                <?php else: ?>
                                    <img src="images/default-avatar.svg" 
                                         alt="Default Profile" 
                                         class="w-8 h-8 rounded-full bg-gray-50">
                                <?php endif; ?>
                                <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <i class="fas fa-chevron-down text-gray-500"></i>
                            </button>
                            <div x-show="profileOpen" 
                                 @click.away="profileOpen = false" 
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                                <a href="?action=profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user-circle mr-2"></i>My Profile
                                </a>
                                <a href="?action=settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2"></i>Settings
                                </a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
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
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php 
                        echo htmlspecialchars($_SESSION['success_message']); 
                        unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php 
                        echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>

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
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-semibold mb-4">Available Jobs</h3>
                        <?php 
                        // Fetch unassigned bookings
                        $unassignedQuery = "SELECT b.*, s.name as service_name, u.firstname, u.lastname, u.email
                                            FROM bookings b
                                            JOIN services s ON b.service_id = s.id
                                            JOIN users u ON b.user_id = u.id
                                            WHERE b.technician_id IS NULL 
                                            AND b.status = 'pending'
                                            AND b.booking_date >= CURDATE()
                                            ORDER BY b.booking_date ASC, b.booking_time ASC
                                            LIMIT 5";
                        $unassignedStmt = $db->prepare($unassignedQuery);
                        $unassignedStmt->execute();
                        $unassignedBookings = $unassignedStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($unassignedBookings) > 0): 
                        ?>
                            <ul class="space-y-4">
                                <?php foreach ($unassignedBookings as $booking): ?>
                                    <li class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-4">
                                        <div class="mb-2 sm:mb-0">
                                            <div class="flex items-center">
                                                <span class="w-2 h-2 bg-yellow-400 rounded-full mr-2"></span>
                                                <span class="font-medium"><?php echo htmlspecialchars($booking['service_name']); ?></span>
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                <i class="far fa-calendar mr-1"></i>
                                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> at 
                                                <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                <i class="far fa-user mr-1"></i>
                                                <?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname']); ?>
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                <?php echo htmlspecialchars($booking['location']); ?>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <span class="text-lg font-bold text-purple-600 mb-2">
                                                ₱<?php echo number_format($booking['total_cost'], 2); ?>
                                            </span>
                                            <form action="?action=assign_booking" method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to take this job?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" 
                                                        class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition duration-200 text-sm">
                                                    <i class="fas fa-check mr-1"></i>
                                                    Take Job
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="mt-4 text-right">
                                <a href="?action=available_jobs" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                                    View All Available Jobs →
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="text-gray-400 mb-2">
                                    <i class="fas fa-clipboard-list text-4xl"></i>
                                </div>
                                <p class="text-gray-600">No available jobs at the moment</p>
                            </div>
                        <?php endif; ?>
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
                    
                    <?php 
                    $statusCounts = getStatusCounts($db, $technicianId);
                    ?>
                    <!-- Status Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="text-gray-500 text-sm mb-1">Total Jobs</div>
                            <div class="text-2xl font-bold"><?php echo $statusCounts['total_jobs']; ?></div>
                        </div>
                        <div class="bg-green-50 rounded-lg shadow p-4">
                            <div class="text-green-600 text-sm mb-1">Completed</div>
                            <div class="text-2xl font-bold"><?php echo $statusCounts['completed_jobs']; ?></div>
                        </div>
                        <div class="bg-yellow-50 rounded-lg shadow p-4">
                            <div class="text-yellow-600 text-sm mb-1">Pending</div>
                            <div class="text-2xl font-bold"><?php echo $statusCounts['pending_jobs']; ?></div>
                        </div>
                        <div class="bg-blue-50 rounded-lg shadow p-4">
                            <div class="text-blue-600 text-sm mb-1">Accepted</div>
                            <div class="text-2xl font-bold"><?php echo $statusCounts['accepted_jobs']; ?></div>
                        </div>
                    </div>

                    <!-- Update the filter form in the history section -->
                    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                        <form class="grid grid-cols-1 md:grid-cols-12 gap-4" id="historyFilterForm" method="GET">
                            <!-- Add hidden input to maintain the action parameter -->
                            <input type="hidden" name="action" value="history">
                            
                            <!-- Date From - 3 columns -->
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                                <input type="date" name="date_from" id="date_from" 
                                       class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500"
                                       value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                            </div>

                            <!-- Date To - 3 columns -->
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                                <input type="date" name="date_to" id="date_to" 
                                       class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500"
                                       value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                            </div>

                            <!-- Status - 3 columns -->
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" id="status" 
                                        class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">All Status</option>
                                    <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="accepted" <?php echo isset($_GET['status']) && $_GET['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                </select>
                            </div>

                            <!-- Buttons - 3 columns -->
                            <div class="md:col-span-3 flex items-end space-x-2">
                                <button type="submit" 
                                        class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-200 flex items-center justify-center">
                                    <i class="fas fa-filter mr-2"></i>
                                    Filter
                                </button>
                                <a href="?action=history" 
                                   class="bg-gray-100 text-gray-600 hover:bg-gray-200 px-4 py-2 rounded-lg transition duration-200 flex items-center justify-center">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </form>
                    </div>

                    <?php if (count($jobHistory) > 0): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal">
                                        <th class="py-3 px-6 text-left">Date</th>
                                        <th class="py-3 px-6 text-left">Time</th>
                                        <th class="py-3 px-6 text-left">Service</th>
                                        <th class="py-3 px-6 text-left">Customer</th>
                                        <th class="py-3 px-6 text-left">Location</th>
                                        <th class="py-3 px-6 text-left">Status</th>
                                        <th class="py-3 px-6 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 text-sm">
                                    <?php foreach ($jobHistory as $job): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                                            <td class="py-3 px-6"><?php echo date('M d, Y', strtotime($job['booking_date'])); ?></td>
                                            <td class="py-3 px-6"><?php echo date('h:i A', strtotime($job['booking_time'])); ?></td>
                                            <td class="py-3 px-6"><?php echo htmlspecialchars($job['service_name']); ?></td>
                                            <td class="py-3 px-6"><?php echo htmlspecialchars($job['firstname'] . ' ' . $job['lastname']); ?></td>
                                            <td class="py-3 px-6"><?php echo htmlspecialchars($job['location']); ?></td>
                                            <td class="py-3 px-6">
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php 
                                                    echo match($job['status']) {
                                                        'completed' => 'bg-green-100 text-green-800',
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'accepted' => 'bg-blue-100 text-blue-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($job['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-6 text-right">₱<?php echo number_format($job['total_cost'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow-md p-6 text-center">
                            <div class="text-gray-400 mb-2">
                                <i class="fas fa-history text-4xl"></i>
                            </div>
                            <p class="text-gray-600">No job history available</p>
                        </div>
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
                <?php elseif ($action === 'available_jobs'): ?>
                    <h2 class="text-2xl font-bold mb-4">Available Jobs</h2>
                    <?php 
                    $unassignedBookings = getUnassignedBookings($db);
                    if (count($unassignedBookings) > 0): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                        <th class="py-3 px-6 text-left">Date & Time</th>
                                        <th class="py-3 px-6 text-left">Service</th>
                                        <th class="py-3 px-6 text-left">Customer</th>
                                        <th class="py-3 px-6 text-left">Location</th>
                                        <th class="py-3 px-6 text-left">Payment</th>
                                        <th class="py-3 px-6 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 text-sm">
                                    <?php foreach ($unassignedBookings as $booking): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                                            <td class="py-3 px-6">
                                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                                <br>
                                                <span class="text-gray-500">
                                                    <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-6"><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td class="py-3 px-6">
                                                <?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname']); ?>
                                            </td>
                                            <td class="py-3 px-6"><?php echo htmlspecialchars($booking['location']); ?></td>
                                            <td class="py-3 px-6">₱<?php echo number_format($booking['total_cost'], 2); ?></td>
                                            <td class="py-3 px-6 text-center">
                                                <form action="?action=assign_booking" method="POST" 
                                                      onsubmit="return confirm('Are you sure you want to take this job?');">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" 
                                                            class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition duration-300">
                                                        <i class="fas fa-check mr-2"></i>Take Job
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow-md p-6 text-center">
                            <i class="fas fa-clipboard-list text-gray-400 text-5xl mb-4"></i>
                            <p class="text-gray-600">No available jobs at the moment.</p>
                        </div>
                    <?php endif; ?>
                <?php elseif ($action === 'profile'): ?>
                    <?php
                    // Fetch technician's complete profile
                    $profileQuery = "SELECT u.*, t.expertise, t.experience, t.phone, u.avatar as photo, t.skill_rating,
                                       COUNT(b.id) as total_jobs,
                                       COUNT(CASE WHEN b.status = 'completed' THEN 1 END) as completed_jobs
                                    FROM users u
                                    JOIN technicians t ON u.id = t.user_id
                                    LEFT JOIN bookings b ON u.id = b.technician_id
                                    WHERE u.id = :user_id
                                    GROUP BY u.id";
                    $profileStmt = $db->prepare($profileQuery);
                    $profileStmt->bindParam(':user_id', $technicianId);
                    $profileStmt->execute();
                    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
                    ?>

                    <div class="container mx-auto px-4 py-8">
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <!-- Profile Header -->
                            <div class="relative h-48 bg-gradient-to-r from-purple-600 to-blue-600">
                                <div class="absolute -bottom-12 left-8">
                                    <div class="relative">
                                        <div class="relative">
                                            <form id="profilePhotoForm" method="POST" enctype="multipart/form-data">
                                                <?php if (!empty($profile['photo']) && file_exists('uploads/profile_images/' . $profile['photo'])): ?>
                                                    <img src="uploads/profile_images/<?php echo htmlspecialchars($profile['photo']); ?>" 
                                                         alt="Profile Photo" 
                                                         class="w-32 h-32 rounded-full border-4 border-white object-cover bg-white">
                                                <?php else: ?>
                                                    <img src="images/default-avatar.svg" 
                                                         alt="Default Profile" 
                                                         class="w-32 h-32 rounded-full border-4 border-white bg-gray-50">
                                                <?php endif; ?>
                                                <label for="profile_photo" class="absolute bottom-0 right-0 bg-purple-600 text-white rounded-full p-2 cursor-pointer hover:bg-purple-700 transition">
                                                    <i class="fas fa-camera"></i>
                                                    <input type="file" 
                                                           id="profile_photo" 
                                                           name="profile_photo" 
                                                           class="hidden" 
                                                           accept="image/jpeg,image/png,image/gif"
                                                           onchange="document.getElementById('profilePhotoForm').submit();">
                                                </label>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Profile Content -->
                            <div class="pt-16 px-8 pb-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <!-- Personal Information -->
                                    <div>
                                        <h3 class="text-xl font-semibold mb-4">Personal Information</h3>
                                        <form action="?action=update_profile" method="POST" class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                                <div class="mt-1 flex space-x-4">
                                                    <input type="text" name="firstname" value="<?php echo htmlspecialchars($profile['firstname']); ?>" 
                                                           class="flex-1 rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                                    <input type="text" name="lastname" value="<?php echo htmlspecialchars($profile['lastname']); ?>" 
                                                           class="flex-1 rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                                <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" 
                                                       class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>" 
                                                       class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Professional Information -->
                                    <div>
                                        <h3 class="text-xl font-semibold mb-4">Professional Information</h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Expertise</label>
                                                <input type="text" name="expertise" value="<?php echo htmlspecialchars($profile['expertise']); ?>" 
                                                       class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                                                <input type="number" name="experience" value="<?php echo htmlspecialchars($profile['experience']); ?>" 
                                                       class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Rating</label>
                                                <div class="flex items-center mt-1">
                                                    <div class="text-yellow-400 text-xl">
                                                        <?php
                                                        $rating = $profile['skill_rating'] ?? 0;
                                                        $fullStars = floor($rating);
                                                        $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                                        
                                                        for ($i = 0; $i < $fullStars; $i++) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        }
                                                        if ($hasHalfStar) {
                                                            echo '<i class="fas fa-star-half-alt"></i>';
                                                        }
                                                        for ($i = ceil($rating); $i < 5; $i++) {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                        ?>
                                                    </div>
                                                    <span class="ml-2 text-gray-600"><?php echo number_format($rating, 1); ?> / 5.0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statistics -->
                                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="bg-purple-50 rounded-lg p-6">
                                        <div class="text-purple-600 text-xl mb-2">
                                            <i class="fas fa-clipboard-list"></i>
                                        </div>
                                        <div class="text-2xl font-bold"><?php echo $profile['total_jobs']; ?></div>
                                        <div class="text-gray-600">Total Jobs</div>
                                    </div>
                                    <div class="bg-green-50 rounded-lg p-6">
                                        <div class="text-green-600 text-xl mb-2">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="text-2xl font-bold"><?php echo $profile['completed_jobs']; ?></div>
                                        <div class="text-gray-600">Completed Jobs</div>
                                    </div>
                                    <div class="bg-blue-50 rounded-lg p-6">
                                        <div class="text-blue-600 text-xl mb-2">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                        <div class="text-2xl font-bold">
                                            <?php 
                                            echo $profile['total_jobs'] > 0 
                                                ? number_format(($profile['completed_jobs'] / $profile['total_jobs']) * 100, 1) 
                                                : '0'; 
                                            ?>%
                                        </div>
                                        <div class="text-gray-600">Completion Rate</div>
                                    </div>
                                </div>

                                <!-- Save Button -->
                                <div class="mt-8 flex justify-end">
                                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition duration-200">
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($action === 'settings'): ?>
                    <?php
                    // Fetch technician settings
                    $settingsQuery = "SELECT u.*, t.phone, t.expertise, t.experience 
                                      FROM users u 
                                      JOIN technicians t ON u.id = t.user_id 
                                      WHERE u.id = :user_id";
                    $settingsStmt = $db->prepare($settingsQuery);
                    $settingsStmt->bindParam(':user_id', $technicianId);
                    $settingsStmt->execute();
                    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

                    // Handle settings update
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
                        $updates = [];
                        $params = [':user_id' => $technicianId];

                        // Update users table
                        $userUpdates = [];
                        if (!empty($_POST['email'])) {
                            $userUpdates[] = "email = :email";
                            $params[':email'] = $_POST['email'];
                        }
                        if (!empty($_POST['firstname'])) {
                            $userUpdates[] = "firstname = :firstname";
                            $params[':firstname'] = $_POST['firstname'];
                        }
                        if (!empty($_POST['lastname'])) {
                            $userUpdates[] = "lastname = :lastname";
                            $params[':lastname'] = $_POST['lastname'];
                        }

                        if (!empty($userUpdates)) {
                            $userQuery = "UPDATE users SET " . implode(", ", $userUpdates) . " WHERE id = :user_id";
                            $userStmt = $db->prepare($userQuery);
                            if ($userStmt->execute($params)) {
                                $_SESSION['success_message'] = "Profile settings updated successfully!";
                            }
                        }

                        // Update technicians table
                        $techUpdates = [];
                        $techParams = [':user_id' => $technicianId];
                        
                        if (!empty($_POST['phone'])) {
                            $techUpdates[] = "phone = :phone";
                            $techParams[':phone'] = $_POST['phone'];
                        }
                        if (!empty($_POST['expertise'])) {
                            $techUpdates[] = "expertise = :expertise";
                            $techParams[':expertise'] = $_POST['expertise'];
                        }
                        if (isset($_POST['experience'])) {
                            $techUpdates[] = "experience = :experience";
                            $techParams[':experience'] = $_POST['experience'];
                        }

                        if (!empty($techUpdates)) {
                            $techQuery = "UPDATE technicians SET " . implode(", ", $techUpdates) . " WHERE user_id = :user_id";
                            $techStmt = $db->prepare($techQuery);
                            if ($techStmt->execute($techParams)) {
                                $_SESSION['success_message'] = "Technician settings updated successfully!";
                            }
                        }

                        // Handle password change
                        if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                            if ($_POST['new_password'] === $_POST['confirm_password']) {
                                if (password_verify($_POST['current_password'], $settings['password'])) {
                                    $passwordQuery = "UPDATE users SET password = :password WHERE id = :user_id";
                                    $passwordStmt = $db->prepare($passwordQuery);
                                    $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                                    if ($passwordStmt->execute([':password' => $hashedPassword, ':user_id' => $technicianId])) {
                                        $_SESSION['success_message'] = "Password updated successfully!";
                                    }
                                } else {
                                    $_SESSION['error_message'] = "Current password is incorrect.";
                                }
                            } else {
                                $_SESSION['error_message'] = "New passwords do not match.";
                            }
                        }

                        // Redirect to refresh the page
                        header("Location: technician.php?action=settings");
                        exit();
                    }
                    ?>

                    <div class="container mx-auto px-4 py-8">
                        <h2 class="text-2xl font-bold mb-6">Settings</h2>
                        
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <form method="POST" class="divide-y divide-gray-200">
                                <!-- Account Settings -->
                                <div class="p-6 space-y-6">
                                    <h3 class="text-lg font-semibold">Account Settings</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                                            <input type="text" name="firstname" value="<?php echo htmlspecialchars($settings['firstname']); ?>" 
                                                   class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                            <input type="text" name="lastname" value="<?php echo htmlspecialchars($settings['lastname']); ?>" 
                                                   class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Email</label>
                                            <input type="email" name="email" value="<?php echo htmlspecialchars($settings['email']); ?>" 
                                                   class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($settings['phone']); ?>" 
                                                   class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Professional Settings -->
                                <div class="p-6 space-y-6">
                                    <h3 class="text-lg font-semibold">Professional Settings</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Expertise</label>
                                            <input type="text" name="expertise" value="<?php echo htmlspecialchars($settings['expertise']); ?>" 
                                                   class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                                            <input type="number" name="experience" value="<?php echo htmlspecialchars($settings['experience']); ?>" 
                                                   class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Password Change -->
                                <div class="p-6 space-y-6">
                                    <h3 class="text-lg font-semibold">Change Password</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                            <input type="password" name="current_password" 
                                                   class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">New Password</label>
                                            <input type="password" name="new_password" 
                                                   class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                            <input type="password" name="confirm_password" 
                                                   class="mt-1 block w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Save Button -->
                                <div class="px-6 py-4 bg-gray-50">
                                    <button type="submit" name="update_settings" 
                                            class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition duration-200">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Add this JavaScript at the bottom of the file -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');

        // Set min date for date_to based on date_from
        dateFrom.addEventListener('change', function() {
            dateTo.min = this.value;
            if (dateTo.value && dateTo.value < this.value) {
                dateTo.value = this.value;
            }
        });

        // Set max date for date_from based on date_to
        dateTo.addEventListener('change', function() {
            dateFrom.max = this.value;
            if (dateFrom.value && dateFrom.value > this.value) {
                dateFrom.value = this.value;
            }
        });
    });
    </script>
</body>
</html>