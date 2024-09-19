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

// Fetch user details
$user_id = $_SESSION['user_id'];
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

// Fetch the latest booking details
$query = "SELECT * FROM bookings WHERE user_id = :user_id ORDER BY id DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

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

        <?php if ($booking): ?>
        <div class="bg-gray-100 p-6 rounded-lg mb-8 shadow-inner">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">Booking Details</h2>
            <div class="grid grid-cols-2 gap-4">
                <p class="text-gray-700"><strong>Service Category:</strong></p>
                <p><?php echo htmlspecialchars($booking['service_category']); ?></p>
                <p class="text-gray-700"><strong>Service:</strong></p>
                <p><?php echo htmlspecialchars($booking['service_name']); ?></p>
                <p class="text-gray-700"><strong>Date:</strong></p>
                <p><?php echo htmlspecialchars($booking['booking_date']); ?></p>
                <p class="text-gray-700"><strong>Time:</strong></p>
                <p><?php echo htmlspecialchars($booking['booking_time']); ?></p>
                <p class="text-gray-700"><strong>Location:</strong></p>
                <p><?php echo htmlspecialchars($booking['location']); ?></p>
                <p class="text-gray-700"><strong>Technician:</strong></p>
                <p><?php echo isset($booking['technician']) ? htmlspecialchars($booking['technician']) : 'Not assigned'; ?></p>
                <p class="text-gray-700"><strong>Payment Method:</strong></p>
                <p><?php echo htmlspecialchars($booking['payment_method']); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center">
            <a href="index.php" class="bg-blue-500 text-white py-3 px-6 rounded-full hover:bg-blue-600 transition duration-300 inline-block text-lg font-semibold">Return to Home</a>
        </div>
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
