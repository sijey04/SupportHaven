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

// Fetch user details with error handling
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Validate if user exists, if not redirect to login
if (!$user) {
    session_destroy();
    header("Location: login.php?error=invalid_user");
    exit();
}

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
    <title>SupportHaven Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-6">
            <a href="index.html">
                <img src="images/logo.png" alt="SupportHaven" class="h-12 mb-8 pl-4">
            </a>
            
            <nav class="space-y-2">
                <a href="user-landing.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg bg-gray-100">
                    <i class="fas fa-home w-5 mr-3"></i> Home
                </a>
                <a href="my-services.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-palette w-5 mr-3"></i> My Services
                </a>
                <a href="favorites.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-heart w-5 mr-3"></i> Favorites
                </a>
                <a href="account-settings.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-user-cog w-5 mr-3"></i> Account settings
                </a>
                <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-history w-5 mr-3"></i> History
                </a>
            </nav>
        </div>
        
        <div class="mt-auto p-6">   
            <a href="logout.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 p-8">
        <!-- Top Navigation -->
        <nav class="flex justify-between items-center mb-8">
            <div class="flex space-x-6">
                <a href="#" class="font-medium">Dashboard</a>
                <a href="#" class="text-gray-500">Discover services</a>
                <a href="#" class="text-gray-500">Connect</a>
            </div>
            <div class="flex items-center space-x-4">
                <img src="<?php echo htmlspecialchars($user['avatar'] ?? 'images/default-avatar.png'); ?>" 
                     class="w-8 h-8 rounded-full">
                <button class="bg-purple-600 text-white px-4 py-2 rounded-lg">
                    Book Service <i class="fas fa-magic ml-2"></i>
                </button>
            </div>
        </nav>

        <!-- Welcome Section -->
        <div class="mb-12">
            <div class="flex items-center mb-2">
                <span class="text-3xl">👋</span>
                <h1 class="text-3xl font-bold ml-2">Hey <?php echo htmlspecialchars($user['firstname']); ?>!</h1>
            </div>
            <p class="text-gray-600">Let's get your support needs taken care of today!</p>
        </div>

        <!-- Action Cards -->
        <div class="grid grid-cols-2 gap-6 mb-12">
            <a href="booking.php" class="bg-purple-900 text-white rounded-2xl p-8 flex items-center justify-between cursor-pointer hover:opacity-95 transition">
            <div>
                <h2 class="text-2xl font-bold mb-2">Book new service</h2>
                <p class="text-purple-200">Get expert help right away</p>
            </div>
            <i class="fas fa-arrow-right text-2xl"></i>
            </a>
            
            <a href="my-services.php" class="bg-white rounded-2xl p-8 border hover:shadow-lg transition cursor-pointer">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Discover Services</h2>
                    <p class="text-gray-600">Browse our available support options</p>
                </div>
            </a>
        </div>

        <!-- Recent Projects -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold mb-6">Your recent services</h2>
            <p class="text-gray-600 mb-6">Continue where you left off</p>
            
            <div class="grid grid-cols-3 gap-6">
                <?php foreach (array_slice($recentBookings, 0, 3) as $booking): ?>
                <div class="bg-white rounded-xl overflow-hidden border hover:shadow-lg transition h-[280px]">
                    <div class="h-[160px] w-full">
                        <img src="images/fix.gif" alt="Service" class="w-full h-full object-contain p-4">
                    </div>
                    <div class="p-4 h-[120px]">
                        <h3 class="font-semibold mb-1"><?php echo htmlspecialchars($booking['service_name']); ?></h3>
                        <p class="text-sm text-gray-600">
                            <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                        </p>
                        <button class="mt-3 text-sm bg-gray-100 px-3 py-1 rounded-full">
                            View details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Rest of the content can remain the same but styled to match -->
    </main>

    <style>
        /* Custom styles */
        .aspect-w-16 {
            position: relative;
            padding-bottom: 56.25%;
        }
        .aspect-w-16 > * {
            position: absolute;
            height: 100%;
            width: 100%;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }

        /* New styles for the sidebar */
        .sidebar-icon {
            width: 1.25rem;
            text-align: center;
        }
    </style>
</body>
</html>