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

// Function to check if a service is favorited
function isServiceFavorited($db, $user_id, $service_id) {
    $query = "SELECT id FROM favorites WHERE user_id = :user_id AND service_id = :service_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':service_id', $service_id);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Add this to handle favorite/unfavorite actions
if (isset($_POST['action']) && isset($_POST['service_id'])) {
    $service_id = $_POST['service_id'];
    
    if ($_POST['action'] === 'favorite') {
        $query = "INSERT INTO favorites (user_id, service_id) VALUES (:user_id, :service_id)";
    } else {
        $query = "DELETE FROM favorites WHERE user_id = :user_id AND service_id = :service_id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':service_id', $service_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false]);
        exit;
    }
}

// Function to check if a booking has been reviewed
function hasReview($db, $bookingId) {
    $query = "SELECT id FROM technician_reviews WHERE booking_id = :booking_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':booking_id', $bookingId);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $bookingId = $_POST['booking_id'];
    $technicianId = $_POST['technician_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    $query = "INSERT INTO technician_reviews (booking_id, user_id, technician_id, rating, comment) 
              VALUES (:booking_id, :user_id, :technician_id, :rating, :comment)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':booking_id', $bookingId);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':technician_id', $technicianId);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':comment', $comment);

    if ($stmt->execute()) {
        // Update technician's skill rating
        $updateQuery = "UPDATE technicians SET skill_rating = (
            SELECT AVG(rating) FROM technician_reviews WHERE technician_id = :technician_id
        ) WHERE user_id = :technician_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':technician_id', $technicianId);
        $updateStmt->execute();

        $_SESSION['success_message'] = "Review submitted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to submit review.";
    }
    header("Location: my-services.php");
    exit();
}

// Function to cancel booking
function cancelBooking($db, $bookingId, $userId) {
    $query = "UPDATE bookings 
              SET status = 'cancelled' 
              WHERE id = :booking_id 
              AND user_id = :user_id 
              AND status NOT IN ('completed', 'cancelled')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':booking_id', $bookingId);
    $stmt->bindParam(':user_id', $userId);
    return $stmt->execute();
}

// Function to reschedule booking
function rescheduleBooking($db, $bookingId, $userId, $newDate, $newTime) {
    $query = "UPDATE bookings 
              SET booking_date = :new_date,
                  booking_time = :new_time,
                  status = 'pending'
              WHERE id = :booking_id 
              AND user_id = :user_id 
              AND status NOT IN ('completed', 'cancelled')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':booking_id', $bookingId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':new_date', $newDate);
    $stmt->bindParam(':new_time', $newTime);
    return $stmt->execute();
}

// Handle cancel and reschedule actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_booking'])) {
        if (cancelBooking($db, $_POST['booking_id'], $user_id)) {
            $_SESSION['success_message'] = "Booking cancelled successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to cancel booking.";
        }
        header("Location: my-services.php");
        exit();
    }

    if (isset($_POST['reschedule_booking'])) {
        if (rescheduleBooking($db, $_POST['booking_id'], $user_id, $_POST['new_date'], $_POST['new_time'])) {
            $_SESSION['success_message'] = "Booking rescheduled successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to reschedule booking.";
        }
        header("Location: my-services.php");
        exit();
    }
}
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
                                    <div class="h-8 w-8 rounded mr-3 flex items-center justify-center bg-purple-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <?php
                                            // Choose icon based on service name or category
                                            $iconPath = match(true) {
                                                stripos($service['service_name'], 'repair') !== false => 
                                                    'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
                                                stripos($service['service_name'], 'network') !== false => 
                                                    'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                                                stripos($service['service_name'], 'software') !== false => 
                                                    'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                                                stripos($service['service_name'], 'virus') !== false => 
                                                    'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                                                stripos($service['service_name'], 'data') !== false => 
                                                    'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
                                                default => 
                                                    'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'
                                            };
                                            ?>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $iconPath; ?>"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-medium"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                        <div class="text-sm text-gray-500">ID: #<?php echo $service['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if ($service['technician_firstname']): ?>
                                        <img src="images/default-avatar.svg" class="h-8 w-8 rounded-full mr-3" alt="Technician">
                                        <div>
                                            <?php echo htmlspecialchars($service['technician_firstname'] . ' ' . $service['technician_lastname']); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center">
                                            <span class="h-8 w-8 rounded-full mr-3 bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-user-clock text-gray-400"></i>
                                            </span>
                                            <div class="text-gray-500">
                                                Pending Technician
                                            </div>
                                        </div>
                                    <?php endif; ?>
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
                            <td class="px-6 py-4 flex space-x-2">
                                <button onclick="showServiceDetails(<?php echo htmlspecialchars(json_encode($service)); ?>)" 
                                        class="text-purple-600 hover:text-purple-900">
                                    View Details
                                </button>
                                <?php if ($service['status'] === 'completed' && !hasReview($db, $service['id'])): ?>
                                    <button onclick="showReviewModal(<?php echo $service['id']; ?>, <?php echo $service['technician_id']; ?>)" 
                                            class="text-sm bg-yellow-100 text-yellow-600 hover:bg-yellow-200 px-3 py-1 rounded-full transition duration-200">
                                        <i class="fas fa-star mr-1"></i>
                                        Rate Service
                                    </button>
                                <?php endif; ?>
                                <?php if ($service['status'] !== 'completed' && $service['status'] !== 'cancelled'): ?>
                                    <button onclick="showRescheduleModal(<?php echo $service['id']; ?>)" 
                                            class="text-sm bg-blue-100 text-blue-600 hover:bg-blue-200 px-3 py-1 rounded-full transition duration-200">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        Reschedule
                                    </button>
                                    <button onclick="confirmCancel(<?php echo $service['id']; ?>)" 
                                            class="text-sm bg-red-100 text-red-600 hover:bg-red-200 px-3 py-1 rounded-full transition duration-200">
                                        <i class="fas fa-times mr-1"></i>
                                        Cancel
                                    </button>
                                <?php endif; ?>
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
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-xl font-bold">Available Services</h2>
                        <p class="text-gray-600 text-sm mt-1">Browse and book our services</p>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <input type="text" 
                           id="searchInput" 
                           placeholder="Search services..." 
                           class="flex-1 rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500">
                    <select id="categoryFilter" class="rounded-lg border-gray-300 text-sm">
                        <option value="">All Categories</option>
                        <?php 
                        // Fetch unique categories
                        $categoryQuery = "SELECT DISTINCT c.id, c.name 
                                         FROM categories c 
                                         JOIN services s ON c.id = s.category_id 
                                         ORDER BY c.name ASC";
                        $categoryStmt = $db->prepare($categoryQuery);
                        $categoryStmt->execute();
                        $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($categories as $category): 
                        ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="booking.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-200">
                        Book New Service
                    </a>
                </div>
            </div>
            <div id="servicesGrid" class="grid grid-cols-3 gap-6 p-6">
                <?php foreach ($availableServices as $service): ?>
                <div class="service-card bg-white rounded-xl border hover:shadow-lg transition p-6" 
                     data-category="<?php echo htmlspecialchars($service['category_id']); ?>"
                     data-name="<?php echo htmlspecialchars(strtolower($service['name'])); ?>"
                     data-price="<?php echo htmlspecialchars($service['price']); ?>">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-purple-600 bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-tools text-xl"></i>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="toggleFavorite(this, <?php echo $service['id']; ?>)" 
                                    class="favorite-btn text-xl <?php echo isServiceFavorited($db, $user_id, $service['id']) ? 'text-red-500' : 'text-gray-300'; ?> hover:scale-110 transition-all">
                                <i class="fas fa-heart"></i>
                            </button>
                            <span class="text-lg font-bold">₱<?php echo number_format($service['price'], 2); ?></span>
                        </div>
                    </div>
                    <h3 class="font-bold mb-2"><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($service['description'] ?? 'No description available'); ?></p>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars($service['category_name']); ?></span>
                        <a href="booking.php?service_id=<?php echo $service['id']; ?>" 
                           class="text-purple-600 hover:text-purple-900 text-sm font-medium">
                            Book Now →
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        let services = document.querySelectorAll('.service-card');
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');

        // Initialize event listeners only if elements exist
        if (searchInput) {
            searchInput.addEventListener('input', debounce(filterServices, 300));
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', filterServices);
        }

        function filterServices() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const selectedCategory = categoryFilter ? categoryFilter.value : '';

            services.forEach(service => {
                const serviceCategory = service.dataset.category;
                const serviceName = service.dataset.name;
                const matchesSearch = !searchTerm || serviceName.includes(searchTerm);
                const matchesCategory = !selectedCategory || serviceCategory === selectedCategory;
                
                if (matchesSearch && matchesCategory) {
                    service.classList.remove('hidden');
                    service.style.opacity = '0';
                    setTimeout(() => {
                        service.style.opacity = '1';
                    }, 50);
                } else {
                    service.classList.add('hidden');
                }
            });

            // Show "no results" message if needed
            updateNoResultsMessage();
        }

        function updateNoResultsMessage() {
            const visibleServices = document.querySelectorAll('.service-card:not(.hidden)');
            const noResultsMessage = document.getElementById('noResultsMessage');
            const servicesGrid = document.getElementById('servicesGrid');
            
            if (visibleServices.length === 0 && servicesGrid) {
                if (!noResultsMessage) {
                    const message = document.createElement('div');
                    message.id = 'noResultsMessage';
                    message.className = 'col-span-3 text-center py-8';
                    message.innerHTML = `
                        <div class="text-gray-500">
                            <i class="fas fa-search mb-2 text-2xl"></i>
                            <p>No services found matching your criteria</p>
                        </div>
                    `;
                    servicesGrid.appendChild(message);
                }
            } else if (noResultsMessage) {
                noResultsMessage.remove();
            }
        }

        // Add debounce function to prevent too many rapid updates
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Add price sorting
        function addPriceSorting() {
            const sortSelect = document.createElement('select');
            sortSelect.className = 'rounded-lg border-gray-300 text-sm';
            sortSelect.innerHTML = `
                <option value="">Sort by Price</option>
                <option value="low">Price: Low to High</option>
                <option value="high">Price: High to Low</option>
            `;
            
            sortSelect.addEventListener('change', function() {
                const servicesArray = Array.from(services);
                servicesArray.sort((a, b) => {
                    const priceA = parseFloat(a.dataset.price);
                    const priceB = parseFloat(b.dataset.price);
                    return this.value === 'low' ? priceA - priceB : priceB - priceA;
                });
                
                const grid = document.getElementById('servicesGrid');
                servicesArray.forEach(service => grid.appendChild(service));
            });
            
            // Insert before the Book New Service button
            categoryFilter.parentNode.insertBefore(sortSelect, categoryFilter.nextSibling);
        }

        // Initialize price sorting
        addPriceSorting();

        // Add smooth transitions
        document.head.insertAdjacentHTML('beforeend', `
            <style>
                .service-card {
                    transition: all 0.3s ease-in-out;
                }
                .service-card.hidden {
                    opacity: 0;
                    transform: scale(0.95);
                }
            </style>
        `);
    </script>

    <!-- Add these modals before closing the body tag -->
    <!-- Service Details Modal -->
    <div id="serviceDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full" style="z-index: 100;">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
            <div class="flex flex-col">
                <div class="flex justify-between items-center border-b pb-4">
                    <h3 class="text-xl font-semibold text-gray-800">Service Details</h3>
                    <button onclick="closeModal('serviceDetailsModal')" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="py-4" id="serviceDetailsContent">
                    <!-- Content will be dynamically inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full" style="z-index: 100;">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
            <div class="flex flex-col">
                <div class="flex justify-between items-center border-b pb-4">
                    <h3 class="text-xl font-semibold text-gray-800">Book Service</h3>
                    <button onclick="closeModal('bookingModal')" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="py-4" id="bookingModalContent">
                    <!-- Content will be dynamically inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Add this JavaScript before closing body tag -->
    <script>
    function showServiceDetails(service) {
        const modalContent = document.getElementById('serviceDetailsContent');
        const statusColor = getStatusColor(service.status);
        
        const content = `
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold text-lg">${service.service_name}</h4>
                        <p class="text-gray-600">Booking #${service.id}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm ${statusColor}">
                        ${capitalizeFirstLetter(service.status)}
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Date</p>
                        <p class="font-medium">${formatDate(service.booking_date)}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Time</p>
                        <p class="font-medium">${formatTime(service.booking_time)}</p>
                    </div>
                </div>

                <div>
                    <p class="text-sm text-gray-600">Location</p>
                    <p class="font-medium">${service.location}</p>
                </div>

                ${service.technician_firstname ? `
                    <div>
                        <p class="text-sm text-gray-600">Technician</p>
                        <p class="font-medium">${service.technician_firstname} ${service.technician_lastname}</p>
                        ${service.skill_rating ? `
                            <div class="flex items-center mt-1">
                                <div class="text-yellow-400 text-sm">
                                    ${generateStarRating(service.skill_rating)}
                                </div>
                                <span class="text-sm text-gray-600 ml-1">(${service.skill_rating})</span>
                            </div>
                        ` : ''}
                    </div>
                ` : `
                    <div>
                        <p class="text-sm text-gray-600">Technician</p>
                        <p class="text-gray-500">
                            <i class="fas fa-user-clock mr-2"></i>
                            Pending Technician Assignment
                        </p>
                    </div>
                `}

                <div class="border-t pt-4 mt-4">
                    <div class="flex justify-between items-center">
                        <p class="text-gray-600">Total Cost</p>
                        <p class="text-xl font-bold text-indigo-600">₱${parseFloat(service.total_cost).toFixed(2)}</p>
                    </div>
                </div>

                ${service.technician_id ? `
                    <div class="border-t pt-4 mt-4">
                        <h4 class="font-semibold text-gray-700 mb-3">Technician Reviews</h4>
                        <div class="space-y-4" id="reviewsContainer">
                            <!-- Reviews will be loaded here -->
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        
        modalContent.innerHTML = content;
        document.getElementById('serviceDetailsModal').classList.remove('hidden');

        // If there's a technician, fetch their reviews
        if (service.technician_id) {
            fetchTechnicianReviews(service.technician_id);
        }
    }

    // Add this function to fetch reviews
    function fetchTechnicianReviews(technicianId) {
        fetch(`get_reviews.php?technician_id=${technicianId}`)
            .then(response => response.json())
            .then(reviews => {
                const reviewsContainer = document.getElementById('reviewsContainer');
                if (reviews.length > 0) {
                    reviewsContainer.innerHTML = reviews.map(review => `
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium">${review.firstname} ${review.lastname}</p>
                                    <div class="flex items-center mt-1">
                                        <div class="text-yellow-400">
                                            ${generateStarRating(review.rating)}
                                        </div>
                                        <span class="text-sm text-gray-600 ml-2">
                                            ${formatDate(review.created_at)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            ${review.comment ? `
                                <p class="text-gray-600 mt-2 text-sm">
                                    "${review.comment}"
                                </p>
                            ` : ''}
                        </div>
                    `).join('');
                } else {
                    reviewsContainer.innerHTML = `
                        <p class="text-gray-500 text-center">No reviews yet</p>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching reviews:', error);
            });
    }

    function showBookingModal(service) {
        const modalContent = document.getElementById('bookingModalContent');
        
        const content = `
            <div class="space-y-4">
                <div class="mb-6">
                    <h4 class="font-semibold text-lg mb-2">${service.name}</h4>
                    <p class="text-gray-600">${service.description || 'No description available'}</p>
                    <p class="text-lg font-bold text-indigo-600 mt-2">₱${parseFloat(service.price).toFixed(2)}</p>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 mb-2">Category</p>
                    <p class="font-medium">${service.category_name}</p>
                </div>

                <div class="border-t pt-4 mt-4">
                    <a href="booking.php?service_id=${service.id}" 
                       class="block w-full bg-purple-600 text-white text-center py-2 px-4 rounded-lg hover:bg-purple-700 transition duration-200">
                        Proceed to Booking
                    </a>
                </div>
            </div>
        `;
        
        modalContent.innerHTML = content;
        document.getElementById('bookingModal').classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function getStatusColor(status) {
        const colors = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'accepted': 'bg-blue-100 text-blue-800',
            'completed': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }

    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    function formatTime(timeString) {
        return new Date(`2000-01-01T${timeString}`).toLocaleTimeString([], { 
            hour: '2-digit', 
            minute: '2-digit'
        });
    }

    function generateStarRating(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        let stars = '';
        
        for (let i = 0; i < fullStars; i++) {
            stars += '<i class="fas fa-star"></i>';
        }
        if (hasHalfStar) {
            stars += '<i class="fas fa-star-half-alt"></i>';
        }
        const emptyStars = 5 - Math.ceil(rating);
        for (let i = 0; i < emptyStars; i++) {
            stars += '<i class="far fa-star"></i>';
        }
        
        return stars;
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['serviceDetailsModal', 'bookingModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                closeModal(modalId);
            }
        });
    }

    // Close modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = ['serviceDetailsModal', 'bookingModal'];
            modals.forEach(closeModal);
        }
    });

    function toggleFavorite(button, serviceId) {
        const isFavorited = button.classList.contains('text-red-500');
        const action = isFavorited ? 'unfavorite' : 'favorite';

        // Add animation class
        button.classList.add('animate-bounce');
        setTimeout(() => button.classList.remove('animate-bounce'), 1000);

        fetch('my-services.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=${action}&service_id=${serviceId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (action === 'favorite') {
                    button.classList.remove('text-gray-300');
                    button.classList.add('text-red-500');
                } else {
                    button.classList.remove('text-red-500');
                    button.classList.add('text-gray-300');
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Add smooth transition styles
    document.head.insertAdjacentHTML('beforeend', `
        <style>
            .favorite-btn {
                transition: all 0.3s ease;
            }
            .favorite-btn:hover {
                transform: scale(1.1);
            }
            .favorite-btn:active {
                transform: scale(0.95);
            }
            @keyframes bounce {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.2); }
            }
            .animate-bounce {
                animation: bounce 0.5s ease;
            }
        </style>
    `);
    </script>

    <!-- Add this modal HTML before closing body tag -->
    <div id="reviewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full" style="z-index: 100;">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
            <div class="flex flex-col">
                <div class="flex justify-between items-center border-b pb-4">
                    <h3 class="text-xl font-semibold text-gray-800">Rate Technician</h3>
                    <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" class="py-4" id="reviewForm">
                    <input type="hidden" name="booking_id" id="review_booking_id">
                    <input type="hidden" name="technician_id" id="review_technician_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                        <div class="flex space-x-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" 
                                    class="star-rating text-2xl text-gray-300 hover:text-yellow-400 focus:outline-none transition-colors"
                                    data-rating="<?php echo $i; ?>"
                                    onclick="setRating(<?php echo $i; ?>)">
                                ★
                            </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="rating_input" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Comment</label>
                        <textarea name="comment" rows="3" 
                                  class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500"
                                  placeholder="Share your experience..."></textarea>
                    </div>
                    
                    <button type="submit" name="submit_review" 
                            class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-200">
                        Submit Review
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Add this JavaScript -->
    <script>
    function showReviewModal(bookingId, technicianId) {
        document.getElementById('review_booking_id').value = bookingId;
        document.getElementById('review_technician_id').value = technicianId;
        document.getElementById('reviewModal').classList.remove('hidden');
        resetRating();
    }

    function closeReviewModal() {
        document.getElementById('reviewModal').classList.add('hidden');
        resetRating();
    }

    function setRating(rating) {
        document.getElementById('rating_input').value = rating;
        const stars = document.querySelectorAll('.star-rating');
        stars.forEach((star, index) => {
            star.classList.toggle('text-yellow-400', index < rating);
            star.classList.toggle('text-gray-300', index >= rating);
        });
    }

    function resetRating() {
        document.getElementById('rating_input').value = '';
        document.getElementById('reviewForm').reset();
        const stars = document.querySelectorAll('.star-rating');
        stars.forEach(star => {
            star.classList.remove('text-yellow-400');
            star.classList.add('text-gray-300');
        });
    }

    // Close modal when clicking outside
    document.getElementById('reviewModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeReviewModal();
        }
    });

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeReviewModal();
        }
    });
    </script>

    <!-- Add this cancel confirmation modal -->
    <div id="cancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full" style="z-index: 100;">
        <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 shadow-lg rounded-lg bg-white">
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-circle text-red-500 text-5xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Cancel Booking</h3>
                    <p class="text-gray-600">Are you sure you want to cancel this booking? This action cannot be undone.</p>
                </div>
                <div class="flex justify-center space-x-4">
                    <button onclick="closeCancelModal()" 
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition duration-200">
                        No, Keep it
                    </button>
                    <form id="cancelForm" method="POST" class="inline">
                        <input type="hidden" name="booking_id" id="cancel_booking_id">
                        <input type="hidden" name="cancel_booking" value="1">
                        <button type="submit" 
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-200">
                            Yes, Cancel it
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update the reschedule modal to be centered -->
    <div id="rescheduleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full" style="z-index: 100;">
        <div class="relative top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 shadow-lg rounded-lg bg-white">
            <div class="flex flex-col">
                <div class="flex justify-between items-center border-b p-4">
                    <h3 class="text-xl font-semibold text-gray-800">Reschedule Service</h3>
                    <button onclick="closeRescheduleModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" class="p-6" id="rescheduleForm">
                    <input type="hidden" name="booking_id" id="reschedule_booking_id">
                    <input type="hidden" name="reschedule_booking">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Date</label>
                        <input type="date" name="new_date" 
                               class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500"
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Time</label>
                        <input type="time" name="new_time" 
                               class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500"
                               required>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeRescheduleModal()"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition duration-200">
                            Cancel
                        </button>
                        <button type="submit" name="reschedule_booking" 
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition duration-200">
                            Confirm Reschedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update the JavaScript for cancel functionality -->
    <script>
    function showCancelModal(bookingId) {
        document.getElementById('cancel_booking_id').value = bookingId;
        document.getElementById('cancelModal').classList.remove('hidden');
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').classList.add('hidden');
    }

    // Update the confirmCancel function
    function confirmCancel(bookingId) {
        showCancelModal(bookingId);
    }

    // Update the event listeners
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCancelModal();
            closeRescheduleModal();
        }
    });

    // Close modals when clicking outside
    document.getElementById('cancelModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCancelModal();
        }
    });

    document.getElementById('rescheduleModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRescheduleModal();
        }
    });
    </script>

    <!-- Add these functions to your JavaScript section -->
    <script>
    // Add these functions for reschedule functionality
    function showRescheduleModal(bookingId) {
        document.getElementById('reschedule_booking_id').value = bookingId;
        document.getElementById('rescheduleModal').classList.remove('hidden');
        document.getElementById('rescheduleForm').reset(); // Reset form when opening
    }

    function closeRescheduleModal() {
        document.getElementById('rescheduleModal').classList.add('hidden');
        document.getElementById('rescheduleForm').reset();
    }

    // Update your existing event listeners to include reschedule modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCancelModal();
            closeRescheduleModal();
            closeReviewModal();
        }
    });

    // Make sure all modals can be closed by clicking outside
    document.getElementById('rescheduleModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRescheduleModal();
        }
    });

    // Add validation for the reschedule form
    document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
        const newDate = this.querySelector('input[name="new_date"]').value;
        const newTime = this.querySelector('input[name="new_time"]').value;
        
        if (!newDate || !newTime) {
            e.preventDefault();
            alert('Please select both date and time for rescheduling.');
            return false;
        }

        const selectedDateTime = new Date(newDate + 'T' + newTime);
        const now = new Date();

        if (selectedDateTime < now) {
            e.preventDefault();
            alert('Please select a future date and time.');
            return false;
        }
    });
    </script>
</body>
</html>
