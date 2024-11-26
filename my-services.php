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

// Fetch recent services
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
$recentServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available services
$query = "SELECT s.*, c.name as category_name
          FROM services s
          JOIN categories c ON s.category_id = c.id
          ORDER BY s.name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$availableServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Services</title>
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
                <a href="user-landing.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 rounded-lg ">
                    <i class="fas fa-home w-5 mr-3"></i> Home
                </a>
                <a href="my-services.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg bg-gray-100">
                    <i class="fas fa-palette w-5 mr-3"></i> My Services
                </a>
                <a href="favorites.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-heart w-5 mr-3"></i> Favorites
                </a>
                <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
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
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold">My Services</h1>
            <p class="text-gray-600 mt-2">Manage your service bookings and explore available services</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-3 gap-6 mb-8">
            <div class="bg-purple-50 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-purple-600 bg-purple-100 p-3 rounded-lg">
                        <i class="fas fa-calendar-check text-xl"></i>
                    </span>
                    <span class="text-sm text-purple-600 font-medium">This Month</span>
                </div>
                <h3 class="text-2xl font-bold mb-1"><?php echo count($recentServices); ?></h3>
                <p class="text-gray-600">Total Bookings</p>
            </div>

            <div class="bg-blue-50 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-blue-600 bg-blue-100 p-3 rounded-lg">
                        <i class="fas fa-clock text-xl"></i>
                    </span>
                    <span class="text-sm text-blue-600 font-medium">Status</span>
                </div>
                <h3 class="text-2xl font-bold mb-1">
                    <?php 
                    $pendingCount = array_filter($recentServices, function($service) {
                        return $service['status'] === 'pending';
                    });
                    echo count($pendingCount);
                    ?>
                </h3>
                <p class="text-gray-600">Pending Services</p>
            </div>

            <div class="bg-green-50 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-green-600 bg-green-100 p-3 rounded-lg">
                        <i class="fas fa-check-circle text-xl"></i>
                    </span>
                    <span class="text-sm text-green-600 font-medium">Completed</span>
                </div>
                <h3 class="text-2xl font-bold mb-1">
                    <?php 
                    $completedCount = array_filter($recentServices, function($service) {
                        return $service['status'] === 'completed';
                    });
                    echo count($completedCount);
                    ?>
                </h3>
                <p class="text-gray-600">Completed Services</p>
            </div>
        </div>

        <!-- Recent Services -->
        <div class="bg-white rounded-xl shadow-sm mb-8 overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Recent Services</h2>
                <p class="text-gray-600 text-sm mt-1">Your latest service bookings</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-500">Service</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-500">Technician</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-500">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-500">Status</th>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($recentServices as $service): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <img src="images/service-icon.png" class="h-8 w-8 rounded mr-3" alt="Service">
                                    <div>
                                        <div class="font-medium"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                        <div class="text-sm text-gray-500">ID: #<?php echo $service['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <img src="images/default-avatar.png" class="h-8 w-8 rounded-full mr-3" alt="Technician">
                                    <div>
                                        <?php echo htmlspecialchars($service['technician_firstname'] . ' ' . $service['technician_lastname']); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-medium"><?php echo date('M d, Y', strtotime($service['booking_date'])); ?></div>
                                    <div class="text-gray-500"><?php echo date('h:i A', strtotime($service['booking_time'])); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-sm rounded-full <?php 
                                    echo match($service['status']) {
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'accepted' => 'bg-blue-100 text-blue-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                ?>">
                                    <?php echo ucfirst(htmlspecialchars($service['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button class="text-purple-600 hover:text-purple-900">View Details</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Available Services -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold">Available Services</h2>
                        <p class="text-gray-600 text-sm mt-1">Browse and book our services</p>
                    </div>
                    <div class="flex space-x-4">
                        <select class="rounded-lg border-gray-300 text-sm" onchange="filterServices(this.value)">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm">
                            Book New Service
                        </button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-6 p-6">
                <?php foreach ($availableServices as $service): ?>
                <div class="bg-white rounded-xl border hover:shadow-lg transition p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-purple-600 bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-tools text-xl"></i>
                        </div>
                        <span class="text-lg font-bold">₱<?php echo number_format($service['price'], 2); ?></span>
                    </div>
                    <h3 class="font-bold mb-2"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($service['description'] ?? 'No description available'); ?></p>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars($service['category_name']); ?></span>
                        <button class="text-purple-600 hover:text-purple-900 text-sm font-medium">
                            Book Now →
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        function filterServices(category) {
            // Add filtering functionality here
            console.log('Filtering by category:', category);
        }
    </script>
</body>
</html>
