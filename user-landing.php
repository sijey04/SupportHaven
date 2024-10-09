    <?php
session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Connection();
$db = $database->getConnection();

// Fetch user details
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent bookings
$query = "SELECT b.*, s.name as service_name, t.firstname as technician_firstname, t.lastname as technician_lastname, t.id as technician_id, tech.skill_rating
          FROM bookings b 
          JOIN services s ON b.service_id = s.id 
          LEFT JOIN users t ON b.technician_id = t.id
          LEFT JOIN technicians tech ON t.id = tech.user_id
          WHERE b.user_id = :user_id 
          ORDER BY b.booking_date DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch upcoming appointments
$query = "SELECT b.*, s.name as service_name, t.firstname as technician_firstname, t.lastname as technician_lastname, t.id as technician_id, tech.skill_rating
          FROM bookings b 
          JOIN services s ON b.service_id = s.id 
          LEFT JOIN users t ON b.technician_id = t.id
          LEFT JOIN technicians tech ON t.id = tech.user_id
          WHERE b.user_id = :user_id AND b.booking_date >= CURDATE() 
          ORDER BY b.booking_date ASC LIMIT 3";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch service categories
$query = "SELECT * FROM categories ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating']) && isset($_POST['technician_id'])) {
    $rating = $_POST['rating'];
    $technician_id = $_POST['technician_id'];
    
    // Update the technician's skill_rating
    $update_query = "UPDATE technicians SET skill_rating = 
                    (SELECT AVG(new_rating) FROM 
                        (SELECT :rating AS new_rating 
                         UNION ALL 
                         SELECT skill_rating FROM technicians WHERE user_id = :technician_id) t
                    )
                    WHERE user_id = :technician_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":rating", $rating);
    $update_stmt->bindParam(":technician_id", $technician_id);
    $update_stmt->execute();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - SupportHaven</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50 font-sans">
    <header class="bg-white shadow-md">
        <nav class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="index.html">
                        <img src="images/logo.png" alt="SupportHaven Logo" class="h-12 mr-3">
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="booking.php" class="text-gray-700 hover:text-blue-500 transition duration-300">Book Service</a>
                    <a href="appointments.php" class="text-gray-700 hover:text-blue-500 transition duration-300">My Appointments</a>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                            <i class="fas fa-user-circle text-gray-700 text-2xl"></i>
                            <span class="text-gray-700"><?php echo htmlspecialchars($user['firstname']); ?></span>
                            <i class="fas fa-chevron-down text-gray-500"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                            <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mx-auto mt-8 px-4">
        <h1 class="text-4xl font-bold mb-8 text-gray-800">Welcome back, <?php echo htmlspecialchars($user['firstname']); ?>!</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 transition duration-300 hover:shadow-lg">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Quick Actions</h2>
                <div class="space-y-3">
                    <a href="booking.php" class="block bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition duration-300">
                        <i class="fas fa-calendar-plus mr-2"></i>Book a New Service
                    </a>
                    <a href="#upcoming" class="block bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition duration-300">
                        <i class="fas fa-calendar-alt mr-2"></i>View Upcoming Appointments
                    </a>
                    <a href="service-history.php" class="block bg-purple-500 text-white px-4 py-2 rounded-md hover:bg-purple-600 transition duration-300">
                        <i class="fas fa-history mr-2"></i>Check Service History
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 transition duration-300 hover:shadow-lg">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Account Overview</h2>
                <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p class="mb-4"><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
                <a href="profile.php" class="text-blue-500 hover:underline">View Full Profile</a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 transition duration-300 hover:shadow-lg">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Notifications</h2>
                <!-- Add notifications or alerts here -->
                <p class="text-gray-600">You have no new notifications.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 transition duration-300 hover:shadow-lg">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Recent Bookings</h2>
                <?php if (empty($recentBookings)): ?>
                    <p class="text-gray-600">No recent bookings found.</p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($recentBookings as $booking): ?>
                            <li class="border-b pb-4">
                                <span class="font-semibold text-lg"><?php echo htmlspecialchars($booking['service_name']); ?></span>
                                <p class="text-sm text-gray-600">
                                    <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> at 
                                    <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Technician: <?php echo htmlspecialchars($booking['technician_firstname'] . ' ' . $booking['technician_lastname']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Status: <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                </p>
                                <?php if ($booking['status'] === 'completed'): ?>
                                    <div class="mt-2">
                                        <form action="" method="POST" class="flex items-center">
                                            <input type="hidden" name="technician_id" value="<?php echo $booking['technician_id']; ?>">
                                            <label for="rating" class="mr-2">Rate Technician:</label>
                                            <select name="rating" id="rating" class="border rounded px-2 py-1 mr-2">
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                            </select>
                                            <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Submit</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                <?php if ($booking['skill_rating']): ?>
                                    <p class="text-sm text-gray-600 mt-2">
                                        Technician Rating: <?php echo number_format($booking['skill_rating'], 2); ?>/5
                                    </p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div id="upcoming" class="bg-white rounded-lg shadow-md p-6 transition duration-300 hover:shadow-lg">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Upcoming Appointments</h2>
                <?php if (empty($upcomingAppointments)): ?>
                    <p class="text-gray-600">No upcoming appointments.</p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($upcomingAppointments as $appointment): ?>
                            <li class="border-b pb-4">
                                <span class="font-semibold text-lg"><?php echo htmlspecialchars($appointment['service_name']); ?></span>
                                <p class="text-sm text-gray-600">
                                    <?php echo date('M d, Y', strtotime($appointment['booking_date'])); ?> at 
                                    <?php echo date('h:i A', strtotime($appointment['booking_time'])); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Technician: <?php echo htmlspecialchars($appointment['technician_firstname'] . ' ' . $appointment['technician_lastname']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Location: <?php echo htmlspecialchars($appointment['location']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Status: <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                                </p>
                                <?php if ($appointment['skill_rating']): ?>
                                    <p class="text-sm text-gray-600">
                                        Technician Rating: <?php echo number_format($appointment['skill_rating'], 2); ?>/5
                                    </p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8 transition duration-300 hover:shadow-lg">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">Service Categories</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($categories as $category): ?>
                    <a href="booking.php?category=<?php echo $category['id']; ?>" class="bg-gray-100 p-4 rounded-md text-center hover:bg-gray-200 transition duration-300">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 transition duration-300 hover:shadow-lg">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">Need Help?</h2>
            <p class="mb-4 text-gray-600">Our support team is always here to assist you.</p>
            <div class="space-x-4">
                <a href="contact.php" class="text-blue-500 hover:underline">Contact Support</a>
                <a href="faq.php" class="text-blue-500 hover:underline">View FAQ</a>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white mt-12 py-8">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <img src="images/logo.png" alt="SupportHaven Logo" class="h-12 mb-4">
                    <p>&copy; 2024 SupportHaven. All rights reserved.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="about.php" class="hover:text-gray-300 transition duration-300">About Us</a></li>
                        <li><a href="services.php" class="hover:text-gray-300 transition duration-300">Our Services</a></li>
                        <li><a href="contact.php" class="hover:text-gray-300 transition duration-300">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="terms.php" class="hover:text-gray-300 transition duration-300">Terms of Service</a></li>
                        <li><a href="privacy.php" class="hover:text-gray-300 transition duration-300">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/custom-scripts.js"></script>
</body>
</html>