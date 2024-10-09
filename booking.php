    <?php
    // Initialize variables to avoid undefined variable warnings
    $serviceCategory = isset($_POST['serviceCategory']) ? $_POST['serviceCategory'] : '';
    $serviceName = isset($_POST['serviceName']) ? $_POST['serviceName'] : '';
    $technician = isset($_POST['technician']) ? $_POST['technician'] : '';
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    $time = isset($_POST['time']) ? $_POST['time'] : '';
    $location = isset($_POST['location']) ? $_POST['location'] : '';
    $totalCost = isset($_POST['totalCost']) ? $_POST['totalCost'] : '0.00';
    $paymentMethod = isset($_POST['paymentMethod']) ? $_POST['paymentMethod'] : '';

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

    // Process form submission
    if (isset($_POST['submit'])) {
        $serviceId = isset($_POST['serviceName']) ? $_POST['serviceName'] : '';
        $date = isset($_POST['date']) ? $_POST['date'] : '';
        $time = isset($_POST['time']) ? $_POST['time'] : '';
        $location = isset($_POST['location']) ? $_POST['location'] : '';
        $paymentMethod = isset($_POST['paymentMethod']) ? $_POST['paymentMethod'] : '';
        $totalCost = isset($_POST['totalCost']) ? $_POST['totalCost'] : '0.00';
        $technicianName = isset($_POST['technician']) ? $_POST['technician'] : '';

        // Fetch technician_id based on the technician's name
        $technicianQuery = "SELECT t.user_id 
                            FROM technicians t
                            JOIN users u ON t.user_id = u.id
                            WHERE CONCAT(u.firstName, ' ', u.lastName) = :technician_name";
        $technicianStmt = $db->prepare($technicianQuery);
        $technicianStmt->bindParam(":technician_name", $technicianName);
        $technicianStmt->execute();
        $technicianResult = $technicianStmt->fetch(PDO::FETCH_ASSOC);
        $technicianId = $technicianResult ? $technicianResult['user_id'] : null;

        // Fetch the service price from the services table
        $servicePriceQuery = "SELECT price FROM services WHERE id = :service_id";
        $servicePriceStmt = $db->prepare($servicePriceQuery);
        $servicePriceStmt->bindParam(":service_id", $serviceId);
        $servicePriceStmt->execute();
        $servicePriceResult = $servicePriceStmt->fetch(PDO::FETCH_ASSOC);
        $totalCost = $servicePriceResult ? $servicePriceResult['price'] : '0.00';

        // Insert booking into database
        $query = "INSERT INTO bookings (user_id, service_id, technician_id, booking_date, booking_time, location, payment_method, total_cost) 
                  VALUES (:user_id, :service_id, :technician_id, :booking_date, :booking_time, :location, :payment_method, :total_cost)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":service_id", $serviceId);
        $stmt->bindParam(":technician_id", $technicianId);
        $stmt->bindParam(":booking_date", $date);
        $stmt->bindParam(":booking_time", $time);
        $stmt->bindParam(":location", $location);
        $stmt->bindParam(":payment_method", $paymentMethod);
        $stmt->bindParam(":total_cost", $totalCost);

        try {
            $stmt->execute();
            // Redirect to a success page or display a success message
            header("Location: booking-success.php");
            exit();
        } catch (PDOException $e) {
            // Log the error and display a user-friendly message
            error_log("Booking error: " . $e->getMessage());
            $error_message = "An error occurred while processing your booking. Please try again later.";
        }
    }

    // Fetch technicians from the database
    $query = "SELECT t.*, u.firstName, u.lastName 
              FROM technicians t
              JOIN users u ON t.user_id = u.id
              WHERE t.photo IS NOT NULL AND t.status = 'approved'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch categories from the database
    $query = "SELECT * FROM categories";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch services from the database
    $query = "SELECT s.*, c.name as category_name 
              FROM services s
              JOIN categories c ON s.category_id = c.id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="webthemez">
        <title>SupportHaven - Booking</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="css/bootstrap.min.css" rel="stylesheet">
        <link href="css/font-awesome.min.css" rel="stylesheet">
        <link href="css/animate.min.css" rel="stylesheet"> 
        <link href="css/prettyPhoto.css" rel="stylesheet">
        <link href="css/styles.css" rel="stylesheet"> 
        <link rel="shortcut icon" href="images/ico/favicon.ico">
    </head>

    <body id="home">
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
        </header><!--/header-->
        <main class="container mx-auto px-4 py-8 mt-20">        
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-center mb-10 mt-10">Book a Tech Service</h1>
                <a href="user-landing.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Back to Dashboard
                </a>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <!-- Service Selection -->
                <div class="bg-white shadow-md rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4">Step 1: Select Service</h2>
                    <p class="mb-4">Choose the category and specific service you need assistance with. Our range of services covers various tech-related issues and support.</p>
                    <div class="mb-4">
                        <label for="serviceCategory" class="block text-sm font-medium text-gray-700 mb-1">Service Category</label>
                        <select id="serviceCategory" name="serviceCategory" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="serviceName" class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                        <select id="serviceName" name="serviceName" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select a service</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['id']); ?>" data-category="<?php echo htmlspecialchars($service['category_id']); ?>" data-price="<?php echo htmlspecialchars($service['price']); ?>" data-description="<?php echo htmlspecialchars($service['description']); ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> - ₱<?php echo htmlspecialchars($service['price']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="serviceDescription" class="mt-4 text-gray-600"></div>
                </div>

                <!-- Technician Availability -->
                <div class="bg-white shadow-md rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4">Step 2: Choose Date, Time, and Location</h2>
                    <div class="mb-4">
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Choose Date</label>
                        <input type="date" id="date" name="date" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Choose Time</label>
                        <select id="time" name="time" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option selected>Select a time slot</option>
                            <option value="9am">9:00 AM</option>
                            <option value="11am">11:00 AM</option>
                            <option value="1pm">1:00 PM</option>
                            <option value="3pm">3:00 PM</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Enter Your Location</label>
                        <input type="text" id="location" name="location" placeholder="City, ZIP code" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Technician Selection -->
                <div class="bg-white shadow-md rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4">Step 3: Select a Technician</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php foreach ($technicians as $tech): ?>
                        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                            <img src="uploads/<?php echo htmlspecialchars($tech['photo'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($tech['firstName'] ?? '') . ' ' . htmlspecialchars($tech['lastName'] ?? ''); ?>" class="w-full h-[500px] object-cover">
                            <div class="p-4 text-center">
                                <h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($tech['firstName'] ?? 'Unknown') . ' ' . htmlspecialchars($tech['lastName'] ?? 'Technician'); ?></h3>
                                <p class="text-gray-600 mb-2">Specializes in <?php echo htmlspecialchars($tech['expertise'] ?? 'Various Services'); ?></p>
                                <p class="text-gray-600 mb-4">Experience: <?php echo htmlspecialchars($tech['experience'] ?? 'N/A'); ?> years</p>
                                <button type="button" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition duration-300" onclick="selectTechnician('<?php echo htmlspecialchars($tech['firstName'] ?? 'Unknown') . ' ' . htmlspecialchars($tech['lastName'] ?? 'Technician'); ?>')">Select</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="technician" name="technician" value="">
                </div>

                <!-- Summary and Payment -->
                <div class="bg-white shadow-md rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4">Step 4: Review & Confirm Booking</h2>
                    <div class="mb-4">
                        <p><strong>Service Category:</strong> <span id="summaryServiceCategory"></span></p>
                        <p><strong>Service:</strong> <span id="summaryServiceName"></span></p>
                        <p><strong>Service Description:</strong> <span id="summaryServiceDescription"></span></p>
                        <p><strong>Technician:</strong> <span id="summaryTechnician"></span></p>
                        <p><strong>Date & Time:</strong> <span id="summaryDateTime"></span></p>
                        <p><strong>Location:</strong> <span id="summaryLocation"></span></p>
                        <p><strong>Total Cost:</strong> ₱<span id="summaryTotalCost">0.00</span></p>
                    </div>
                    <div class="mb-4">
                        <label for="paymentMethod" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select id="paymentMethod" name="paymentMethod" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option selected>Select payment method</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="paypal">PayPal</option>
                            <option value="gcash">GCash</option>
                            <option value="cash">Cash on Meet-up</option>
                            <option value="paymaya">PayMaya</option>
                        </select>
                    </div>
                    <input type="hidden" id="totalCost" name="totalCost" value="0.00">
                    <button type="submit" name="submit" class="w-full bg-green-500 text-white py-3 px-4 rounded-md hover:bg-green-600 transition duration-300 text-lg font-semibold">Confirm Booking</button>
                </div>
            </form>
        </main>
        <script src="js/jquery.js"></script>
        <script src="js/bootstrap.min.js"></script> 
        <script src="js/mousescroll.js"></script>
        <script src="js/smoothscroll.js"></script>
        <script src="js/jquery.prettyPhoto.js"></script>
        <script src="js/jquery.isotope.min.js"></script>
        <script src="js/jquery.inview.min.js"></script>
        <script src="js/wow.min.js"></script>
        <script src="js/custom-scripts.js"></script>
        <script>
            function selectTechnician(name) {
                document.getElementById('technician').value = name;
                document.getElementById('summaryTechnician').textContent = name;
            }

            // Update summary in real-time
            document.addEventListener('input', function (event) {
                if (event.target.id === 'serviceCategory') {
                    document.getElementById('summaryServiceCategory').textContent = event.target.options[event.target.selectedIndex].text;
                } else if (event.target.id === 'serviceName') {
                    const selectedOption = event.target.options[event.target.selectedIndex];
                    document.getElementById('summaryServiceName').textContent = selectedOption.text;
                    document.getElementById('summaryServiceDescription').textContent = selectedOption.dataset.description || 'No description available';
                    document.getElementById('serviceDescription').textContent = selectedOption.dataset.description || 'No description available';
                    document.getElementById('summaryTotalCost').textContent = selectedOption.dataset.price || '0.00';
                    document.getElementById('totalCost').value = selectedOption.dataset.price || '0.00';
                } else if (event.target.id === 'date' || event.target.id === 'time') {
                    document.getElementById('summaryDateTime').textContent = document.getElementById('date').value + ', ' + document.getElementById('time').value;
                } else if (event.target.id === 'location') {
                    document.getElementById('summaryLocation').textContent = event.target.value;
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                const serviceCategorySelect = document.getElementById('serviceCategory');
                const serviceNameSelect = document.getElementById('serviceName');
                const serviceOptions = Array.from(serviceNameSelect.options);

                serviceCategorySelect.addEventListener('change', function() {
                    const selectedCategoryId = this.value;
                    serviceNameSelect.innerHTML = '<option value="">Select a service</option>';

                    serviceOptions.forEach(option => {
                        if (option.dataset.category === selectedCategoryId || selectedCategoryId === '') {
                            serviceNameSelect.appendChild(option.cloneNode(true));
                        }
                    });
                });

                serviceNameSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const price = selectedOption.dataset.price || '0.00';
                    document.getElementById('summaryTotalCost').textContent = price;
                    document.getElementById('totalCost').value = price;
                    document.getElementById('summaryServiceDescription').textContent = selectedOption.dataset.description || 'No description available';
                    document.getElementById('serviceDescription').textContent = selectedOption.dataset.description || 'No description available';
                });
            });
        </script>
    </body>
    </html>
