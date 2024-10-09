<?php
session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Create database connection
$database = new Connection();
$db = $database->getConnection();

// Fetch the latest booking for the user
$user_id = $_SESSION['user_id'];
$query = "SELECT b.*, s.name AS service_name, s.price AS service_price, c.name AS category_name, 
                 CONCAT(u.firstName, ' ', u.lastName) AS technician_name
          FROM bookings b
          JOIN services s ON b.service_id = s.id
          JOIN categories c ON s.category_id = c.id
          LEFT JOIN users u ON b.technician_id = u.id
          WHERE b.user_id = :user_id
          ORDER BY b.created_at DESC
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    // Handle the case where no booking is found
    $error_message = "No booking found. Please try again.";
} else {
    // Ensure total_cost is set
    $booking['total_cost'] = $booking['total_cost'] ?? $booking['service_price'];
}

// Fetch user details
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if firstName and lastName are set before using them
$firstName = isset($user['firstName']) ? $user['firstName'] : '';
$lastName = isset($user['lastName']) ? $user['lastName'] : '';
$fullName = trim($firstName . ' ' . $lastName);

if (empty($fullName)) {
    $fullName = isset($user['email']) ? $user['email'] : 'Guest';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Successful - Haven</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/animate.min.css" rel="stylesheet"> 
    <link href="css/prettyPhoto.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet"> 
    <link rel="shortcut icon" href="images/ico/favicon.ico">
</head>
<body class="bg-gray-100">
    <header id="header">
        <nav id="main-nav" class="navbar navbar-default navbar-fixed-top" role="banner">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="index.html"><img src="images/logo.png" alt="logo"></a>
                </div>
                
                <div class="collapse navbar-collapse navbar-right">
                    <ul class="nav navbar-nav">
                        <li><a href="#"><i class="fa fa-user"></i> <?php echo htmlspecialchars($fullName); ?></a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div><!--/.container-->
        </nav><!--/nav-->
    </header>
<br>
<br>
<br>
<br>
<br>
    <!-- Main Content -->
    <main class="container mx-auto mt-10 p-8 bg-white shadow-lg rounded-lg max-w-2xl">
        <h1 class="text-4xl font-bold text-center mb-8 text-green-600">Booking Successful!</h1>
        
        <div class="text-center mb-8">
            <p class="text-2xl">Thank you, <?php echo htmlspecialchars($fullName); ?>!</p>
            <p class="text-xl mt-2">Your booking has been confirmed.</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-md rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Thank you for your booking, <?php echo htmlspecialchars($fullName); ?>!</h2>
                <p class="mb-4">Your booking has been confirmed. Here are the details:</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['id']); ?></p>
                        <p><strong>Service Category:</strong> <?php echo htmlspecialchars($booking['category_name']); ?></p>
                        <p><strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?></p>
                        <p><strong>Technician:</strong> <?php echo htmlspecialchars($booking['technician_name'] ?? 'Not assigned'); ?></p>
                    </div>
                    <div>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($booking['booking_date']); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars($booking['booking_time']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['location']); ?></p>
                        <p><strong>Total Cost:</strong> ₱<?php echo number_format($booking['total_cost'], 2); ?></p>
                    </div>
                </div>
                
                <p class="mt-4">We've sent a confirmation email with these details to your registered email address.</p>
            </div>
            
            <div class="text-center">
                <a href="user-landing.php" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition duration-300">Return to Home</a>
            </div>
        <?php endif; ?>
    </main>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            
    <footer class="bg-gray-200 text-center p-4 mt-8 fixed-bottom">
        <p class="text-gray-600">&copy; 2024 SupportHaven. All rights reserved.</p>
    </footer>

    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/mousescroll.js"></script>
    <script src="js/smoothscroll.js"></script>
    <script src="js/jquery.prettyPhoto.js"></script>
    <script src="js/jquery.isotope.min.js"></script>
    <script src="js/jquery.inview.min.js"></script>
    <script src="js/wow.min.js"></script>
    <script src="js/custom-scripts.js"></script>
</body>
</html>
