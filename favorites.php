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

// Fetch favorite services
$query = "SELECT s.*, c.name as category_name
          FROM favorites f
          JOIN services s ON f.service_id = s.id
          JOIN categories c ON s.category_id = c.id
          WHERE f.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .favorite-btn {
            transition: all 0.3s ease;
        }
        .favorite-btn:hover {
            transform: scale(1.1);
        }
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        .animate-bounce {
            animation: bounce 0.5s ease;
        }
    </style>
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
                <a href="favorites.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-heart w-5 mr-3"></i> Favorites
                </a>
                <!-- Other links -->
            </nav>
        </div>
        
        <div class="mt-auto p-6">   
            <a href="logout.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i> Logout
            </a>
        </div>
    </aside>  <!-- Sidebar -->
    <aside class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-6">
            <a href="index.html">
                <img src="images/logo.png" alt="SupportHaven" class="h-12 mb-8 pl-4">
            </a>
            
            <nav class="space-y-2">
                <a href="user-landing.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg ">
                    <i class="fas fa-home w-5 mr-3"></i> Home
                </a>
                <a href="my-services.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-palette w-5 mr-3"></i> My Services
                </a>
                <a href="favorites.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg bg-gray-100">
                    <i class="fas fa-heart w-5 mr-3"></i> Favorites
                </a>
                <a href="account-settings.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-user-cog w-5 mr-3"></i> Account settings
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
        <h1 class="text-3xl font-bold mb-8">My Favorite Services</h1>
        <div class="grid grid-cols-3 gap-6">
            <?php foreach ($favorites as $favorite): ?>
            <div class="bg-white rounded-xl overflow-hidden border hover:shadow-lg transition">
                <div class="relative h-48 flex items-center justify-center bg-gray-50">
                    <img src="images/3d-glassy-glass-heart.gif" 
                         alt="Service" 
                         class="w-32 h-32 object-contain"
                         style="mix-blend-mode: multiply;">
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($favorite['name']); ?></h3>
                        <button onclick="toggleFavorite(this, <?php echo $favorite['id']; ?>)" 
                                class="text-red-500 hover:scale-110 transition-all">
                            <i class="fas fa-heart text-xl"></i>
                        </button>
                    </div>
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <span><?php echo htmlspecialchars($favorite['category_name']); ?></span>
                        <span class="font-semibold text-purple-600">₱<?php echo number_format($favorite['price'], 2); ?></span>
                    </div>
                    <div class="mt-4">
                        <a href="booking.php?service_id=<?php echo $favorite['id']; ?>" 
                           class="block w-full text-center bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition duration-200">
                            Book Now
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($favorites)): ?>
        <div class="bg-white rounded-xl p-8 text-center">
            <div class="w-24 h-24 mx-auto mb-4">
                <img src="images/3d-glassy-glass-heart.gif" 
                     alt="Empty Favorites" 
                     class="w-full h-full object-contain"
                     style="mix-blend-mode: multiply;">
            </div>
            <h3 class="text-xl font-semibold mb-2">No Favorite Services Yet</h3>
            <p class="text-gray-600 mb-4">Start adding services to your favorites!</p>
            <a href="my-services.php" 
               class="inline-block bg-purple-600 text-white py-2 px-6 rounded-lg hover:bg-purple-700 transition duration-200">
                Browse Services
            </a>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
