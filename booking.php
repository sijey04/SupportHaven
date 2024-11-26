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
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            /* Updated styles for a more modern look */
            .step-progress {
                position: relative;
                padding: 40px 0;
                margin-bottom: 60px;
                max-width: 800px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .step-progress-bar {
                width: 100%;
                height: 6px;
                background: #e5e7eb;
                border-radius: 8px;
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .step-progress-bar-fill {
                height: 100%;
                background: linear-gradient(90deg, #4f46e5, #6366f1);
                border-radius: 8px;
                transition: width 0.4s ease;
                box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
            }
            
            .step-progress-circles {
                display: flex;
                justify-content: space-between;
                align-items: center;
                position: relative;
                z-index: 1;
            }
            
            .step-circle {
                width: 48px;
                height: 48px;
                background: white;
                border: 3px solid #e5e7eb;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                color: #6b7280;
                position: relative;
                transition: all 0.4s ease;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            
            .step-circle.active {
                border-color: #4f46e5;
                color: #4f46e5;
                transform: scale(1.1);
                box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
            }
            
            .step-circle.completed {
                background: #4f46e5;
                border-color: #4f46e5;
                color: white;
            }
            
            .step-label {
                position: absolute;
                top: -30px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 0.9rem;
                font-weight: 500;
                color: #4b5563;
                white-space: nowrap;
            }

            /* Card styles */
            .booking-card {
                background: white;
                border-radius: 16px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
                padding: 2rem;
                margin-bottom: 2rem;
                transition: transform 0.3s ease;
            }

            .booking-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
            }

            /* Form control styles */
            .form-control {
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                padding: 0.75rem 1rem;
                transition: all 0.3s ease;
            }

            .form-control:focus {
                border-color: #4f46e5;
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            }

            /* Button styles */
            .btn-primary {
                background: linear-gradient(135deg, #4f46e5, #6366f1);
                color: white;
                border: none;
                border-radius: 12px;
                padding: 0.875rem 1.75rem;
                font-weight: 600;
                font-size: 0.95rem;
                letter-spacing: 0.025em;
                transition: all 0.3s ease;
                box-shadow: 0 4px 6px rgba(99, 102, 241, 0.15);
                position: relative;
                overflow: hidden;
            }

            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 12px rgba(99, 102, 241, 0.2);
                background: linear-gradient(135deg, #4338ca, #4f46e5);
            }

            .btn-primary:active {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(99, 102, 241, 0.1);
            }

            .btn-secondary {
                background: #f3f4f6;
                color: #4b5563;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                padding: 0.75rem 1.5rem;
                font-weight: 600;
                font-size: 0.95rem;
                transition: all 0.3s ease;
                letter-spacing: 0.025em;
            }

            .btn-secondary:hover {
                background: #e5e7eb;
                color: #374151;
                border-color: #d1d5db;
                transform: translateY(-2px);
            }

            .btn-secondary:active {
                transform: translateY(0);
            }

            .btn-success {
                background: linear-gradient(135deg, #059669, #10b981);
                color: white;
                border: none;
                border-radius: 12px;
                padding: 0.875rem 1.75rem;
                font-weight: 600;
                font-size: 0.95rem;
                letter-spacing: 0.025em;
                transition: all 0.3s ease;
                box-shadow: 0 4px 6px rgba(16, 185, 129, 0.15);
            }

            .btn-success:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 12px rgba(16, 185, 129, 0.2);
                background: linear-gradient(135deg, #047857, #059669);
            }

            .btn-success:active {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);
            }

            /* Add styles for button containers */
            .button-container {
                display: flex;
                gap: 1rem;
                margin-top: 1.5rem;
            }

            .button-container.justify-end {
                justify-content: flex-end;
            }

            .button-container.justify-between {
                justify-content: space-between;
            }

            /* Add loading state styles */
            .btn-loading {
                position: relative;
                cursor: wait;
            }

            .btn-loading::after {
                content: '';
                position: absolute;
                width: 1rem;
                height: 1rem;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                border: 2px solid transparent;
                border-radius: 50%;
                border-top-color: currentColor;
                animation: spin 0.6s linear infinite;
            }

            @keyframes spin {
                to {
                    transform: translate(-50%, -50%) rotate(360deg);
                }
            }

            /* Summary card styles */
            .summary-card {
                background: #f8fafc;
                border-radius: 12px;
                padding: 1.5rem;
                margin-top: 1rem;
            }

            .summary-item {
                display: flex;
                justify-content: space-between;
                padding: 0.75rem 0;
                border-bottom: 1px solid #e5e7eb;
            }

            .summary-item:last-child {
                border-bottom: none;
            }

            /* Animation for step transitions */
            .step-content {
                opacity: 0;
                transform: translateY(10px);
                transition: all 0.4s ease;
            }

            .step-content.active {
                opacity: 1;
                transform: translateY(0);
            }

            /* Add to your existing styles */
            .location-input-container {
                position: relative;
                width: 100%;
            }
            
            .location-icon {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                cursor: pointer;
                color: #4f46e5;
                z-index: 1;
                font-size: 18px;
                padding: 8px;the
                transition: color 0.3s ease;
            }
            
            .location-icon:hover {
                color: #6366f1;
            }
            
            /* Add padding to the input to prevent text from going under the icon */
            .location-input-container input {
                padding-right: 40px !important;
            }
            
            #map-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1000;
            }
            
            .map-container {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 90%;
                max-width: 800px;
                background: white;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            
            #map {
                height: 400px;
                width: 100%;
                border-radius: 8px;
            }
            
            .map-controls {
                margin-top: 15px;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
        </style>
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
        <main class="container mx-auto px-4 py-8 mt-20 max-w-4xl">        
            <div class="text-center mb-12">
                <h1 class="text-3xl font-bold mb-4 mt-10">Book Your Service</h1>
                <p class="text-gray-600">Complete the steps below to schedule your service</p>
            </div>

            <!-- Progress Bar -->
            <div class="step-progress">
                <div class="step-progress-bar">
                    <div class="step-progress-bar-fill" id="progress-fill"></div>
                </div>
                <div class="step-progress-circles">
                    <div class="step-circle active" id="step-1">
                        <span class="step-label">Select Service</span>
                        1
                    </div>
                    <div class="step-circle" id="step-2">
                        <span class="step-label">Schedule</span>
                        2
                    </div>
                    <div class="step-circle" id="step-3">
                        <span class="step-label">Payment</span>
                        3
                    </div>
                </div>
            </div>

            <!-- Updated Form Structure -->
            <form id="booking-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <!-- Step 1: Service Selection -->
                <div class="step-content" id="step-1-content">
                    <div class="booking-card">
                        <h2 class="text-2xl font-semibold mb-6">Select Your Service</h2>
                        <div class="space-y-6">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Service Category</label>
                                <select id="serviceCategory" name="serviceCategory" class="form-control w-full" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Service Type</label>
                                <select id="serviceName" name="serviceName" class="form-control w-full" required>
                                    <option value="">Select a service</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo htmlspecialchars($service['id']); ?>" 
                                                data-category="<?php echo htmlspecialchars($service['category_id']); ?>"
                                                data-price="<?php echo htmlspecialchars($service['price']); ?>">
                                            <?php echo htmlspecialchars($service['name']); ?> - ₱<?php echo htmlspecialchars($service['price']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="button-container justify-end">
                        <button type="button" class="btn-primary next-step">
                            Continue to Schedule
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Schedule -->
                <div class="step-content hidden" id="step-2-content">
                    <div class="booking-card">
                        <h2 class="text-2xl font-semibold mb-6">Schedule Your Service</h2>
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                                    <input type="date" id="date" name="date" 
                                           class="form-control w-full" 
                                           min="<?php echo date('Y-m-d'); ?>" 
                                           required>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                                    <select id="time" name="time" class="form-control w-full" required>
                                        <option value="">Select time</option>
                                        <option value="09:00:00">9:00 AM</option>
                                        <option value="11:00:00">11:00 AM</option>
                                        <option value="13:00:00">1:00 PM</option>
                                        <option value="15:00:00">3:00 PM</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Service Location</label>
                                <div class="location-input-container">
                                    <input type="text" id="location" name="location" 
                                           class="form-control w-full" 
                                           placeholder="Enter your complete address" required>
                                    <i class="fas fa-map-marker-alt location-icon" id="show-map" 
                                       title="Select location from map"></i>
                                </div>
                                <input type="hidden" id="latitude" name="latitude">
                                <input type="hidden" id="longitude" name="longitude">
                            </div>
                        </div>
                    </div>
                    <div class="button-container justify-between">
                        <button type="button" class="btn-secondary prev-step">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Previous
                        </button>
                        <button type="button" class="btn-primary next-step">
                            Continue to Payment
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Payment -->
                <div class="step-content hidden" id="step-3-content">
                    <div class="booking-card">
                        <h2 class="text-2xl font-semibold mb-6">Review and Payment</h2>
                        
                        <!-- Payment Note - Moved to top -->
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>Important Note:</strong> Please prepare the exact amount of ₱<span id="summary-cost-note">0.00</span> 
                                        to be paid in cash to the technician after the service is successfully completed.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Summary -->
                        <div class="summary-card mb-6">
                            <h3 class="font-semibold mb-4">Booking Summary</h3>
                            <div class="space-y-3">
                                <div class="summary-item">
                                    <span class="text-gray-600">Service:</span>
                                    <span id="summary-service" class="font-medium"></span>
                                </div>
                                <div class="summary-item">
                                    <span class="text-gray-600">Date & Time:</span>
                                    <span id="summary-datetime" class="font-medium"></span>
                                </div>
                                <div class="summary-item">
                                    <span class="text-gray-600">Location:</span>
                                    <span id="summary-location" class="font-medium"></span>
                                </div>
                                <div class="summary-item">
                                    <span class="text-gray-600">Total Cost:</span>
                                    <span class="text-xl font-bold text-indigo-600">₱<span id="summary-cost">0.00</span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            Payment is cash-only and should be made directly to the technician after the service is completed.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <i class="fas fa-money-bill-wave text-gray-600 text-xl"></i>
                                <span class="font-medium text-gray-700">Cash Payment upon Service Completion</span>
                            </div>
                            <input type="hidden" name="paymentMethod" value="cash">
                        </div>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="button-container justify-between">
                        <button type="button" class="btn-secondary prev-step">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Previous
                        </button>
                        <button type="submit" name="submit" class="btn-success">
                            <i class="fas fa-check mr-2"></i>
                            Confirm Booking
                        </button>
                    </div>
                </div>
            </form>
        </main>
        <div id="map-modal">
            <div class="map-container">
                <h3 class="text-xl font-semibold mb-4">Select Location</h3>
                <div id="map"></div>
                <div class="map-controls">
                    <button type="button" class="btn-secondary" id="close-map">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                    <button type="button" class="btn-primary" id="confirm-location">
                        <i class="fas fa-check mr-2"></i>
                        Confirm Location
                    </button>
                </div>
            </div>
        </div>
        <script src="js/jquery.js"></script>
        <script src="js/bootstrap.min.js"></script> 
        <script src="js/mousescroll.js"></script>
        <script src="js/smoothscroll.js"></script>
        <script src="js/jquery.prettyPhoto.js"></script>
        <script src="js/jquery.isotope.min.js"></script>
        <script src="js/jquery.inview.min.js"></script>
        <script src="js/wow.min.js"></script>
        <script src="js/custom-scripts.js"></script>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
        <script>
            let currentStep = 1;
            const totalSteps = 3;

            function updateProgress() {
                const progressFill = document.getElementById('progress-fill');
                const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
                progressFill.style.width = `${progress}%`;

                // Update circles
                for (let i = 1; i <= totalSteps; i++) {
                    const circle = document.getElementById(`step-${i}`);
                    if (i < currentStep) {
                        circle.classList.add('completed');
                        circle.classList.remove('active');
                    } else if (i === currentStep) {
                        circle.classList.add('active');
                        circle.classList.remove('completed');
                    } else {
                        circle.classList.remove('active', 'completed');
                    }
                }
            }

            function showStep(step) {
                if (step < 1 || step > totalSteps) return;
                
                document.querySelectorAll('.step-content').forEach(content => {
                    content.classList.add('hidden');
                    content.classList.remove('active');
                });
                
                const newContent = document.getElementById(`step-${step}-content`);
                newContent.classList.remove('hidden');
                // Trigger reflow
                void newContent.offsetWidth;
                newContent.classList.add('active');
                currentStep = step;
                updateProgress();
            }

            function validateCurrentStep() {
                const currentContent = document.getElementById(`step-${currentStep}-content`);
                const requiredFields = currentContent.querySelectorAll('[required]');
                let valid = true;

                requiredFields.forEach(field => {
                    if (!field.value) {
                        valid = false;
                        field.classList.add('border-red-500');
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });

                return valid;
            }

            function updateSummary() {
                if (currentStep === 3) {
                    const serviceSelect = document.getElementById('serviceName');
                    const selectedService = serviceSelect.options[serviceSelect.selectedIndex];
                    const date = document.getElementById('date').value;
                    const time = document.getElementById('time').value;
                    const price = selectedService.dataset.price;
                    
                    document.getElementById('summary-service').textContent = selectedService.text;
                    document.getElementById('summary-datetime').textContent = `${date} at ${time}`;
                    document.getElementById('summary-location').textContent = document.getElementById('location').value;
                    document.getElementById('summary-cost').textContent = price;
                    document.getElementById('summary-cost-note').textContent = price;
                }
            }

            // Function to handle next step
            function handleNextStep() {
                if (validateCurrentStep()) {
                    showStep(currentStep + 1);
                    updateSummary();
                    scrollToTop();
                }
            }

            // Function to handle previous step
            function handlePrevStep() {
                showStep(currentStep - 1);
                scrollToTop();
            }

            function scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            // Initialize event listeners
            document.addEventListener('DOMContentLoaded', () => {
                // Add click handlers for next buttons
                document.querySelectorAll('.next-step').forEach(button => {
                    button.addEventListener('click', handleNextStep);
                });

                // Add click handlers for previous buttons
                document.querySelectorAll('.prev-step').forEach(button => {
                    button.addEventListener('click', handlePrevStep);
                });

                // Initialize first step
                showStep(1);
                updateProgress();

                // Add change handler for service category
                document.getElementById('serviceCategory').addEventListener('change', function() {
                    const categoryId = this.value;
                    const serviceSelect = document.getElementById('serviceName');
                    const serviceOptions = Array.from(serviceSelect.options);

                    // Reset service selection
                    serviceSelect.innerHTML = '<option value="">Select a service</option>';

                    // Filter and add relevant services
                    serviceOptions.forEach(option => {
                        if (option.dataset.category === categoryId || !categoryId) {
                            serviceSelect.appendChild(option.cloneNode(true));
                        }
                    });
                });
            });

            let map, marker, geocoder;
            
            function initMap() {
                // Initialize the map centered on Zamboanga City
                const zamboangaCity = [6.9214, 122.0790]; // Coordinates for Zamboanga City
                map = L.map('map').setView(zamboangaCity, 13); // Zoom level 13 for city view
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                
                // Add a boundary rectangle for Zamboanga City (approximate)
                const bounds = L.latLngBounds(
                    [6.8714, 121.9790], // Southwest corner
                    [6.9714, 122.1790]  // Northeast corner
                );
                
                // Restrict map panning to Zamboanga City bounds
                map.setMaxBounds(bounds);
                map.setMinZoom(12);     // Don't allow zooming out too far
                map.setMaxZoom(18);     // Allow detailed zoom
                
                // Add geocoder control with restricted bounds
                geocoder = L.Control.geocoder({
                    defaultMarkGeocode: false,
                    placeholder: 'Search in Zamboanga City...',
                    bounds: bounds,
                    geocoder: L.Control.Geocoder.nominatim({
                        geocodingQueryParams: {
                            viewbox: '121.9790,6.8714,122.1790,6.9714',
                            bounded: 1,
                            countrycodes: 'ph'
                        }
                    })
                })
                .on('markgeocode', function(e) {
                    const latlng = e.geocode.center;
                    // Check if location is within Zamboanga City bounds
                    if (bounds.contains(latlng)) {
                        setMarker(latlng);
                        map.setView(latlng, 16);
                    } else {
                        alert('Please select a location within Zamboanga City');
                    }
                })
                .addTo(map);
                
                // Add click handler to map
                map.on('click', function(e) {
                    // Check if clicked location is within bounds
                    if (bounds.contains(e.latlng)) {
                        setMarker(e.latlng);
                    } else {
                        alert('Please select a location within Zamboanga City');
                    }
                });

                // Add a marker for Zamboanga City center
                L.marker(zamboangaCity)
                    .addTo(map)
                    .bindPopup('Zamboanga City')
                    .openPopup();
            }
            
            function setMarker(latlng) {
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker(latlng, { draggable: true }).addTo(map);
                
                // Update marker position on drag
                marker.on('dragend', function(e) {
                    const newPos = e.target.getLatLng();
                    // Check if new position is within bounds
                    if (bounds.contains(newPos)) {
                        marker = e.target;
                        updateAddress(newPos);
                    } else {
                        // If dragged outside bounds, reset to previous position
                        marker.setLatLng(latlng);
                        alert('Please stay within Zamboanga City limits');
                    }
                });
                
                updateAddress(latlng);
            }
            
            function updateAddress(latlng) {
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${latlng.lat}&lon=${latlng.lng}&format=json`)
                    .then(response => response.json())
                    .then(data => {
                        let address = data.display_name;
                        // Ensure Zamboanga City is in the address
                        if (!address.includes('Zamboanga City')) {
                            address += ', Zamboanga City';
                        }
                        document.getElementById('location').value = address;
                    });
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                const mapModal = document.getElementById('map-modal');
                const showMapBtn = document.getElementById('show-map');
                const closeMapBtn = document.getElementById('close-map');
                const confirmLocationBtn = document.getElementById('confirm-location');
                
                showMapBtn.addEventListener('click', function() {
                    mapModal.style.display = 'block';
                    if (!map) {
                        initMap();
                    }
                });
                
                closeMapBtn.addEventListener('click', function() {
                    mapModal.style.display = 'none';
                });
                
                confirmLocationBtn.addEventListener('click', function() {
                    if (marker) {
                        const latlng = marker.getLatLng();
                        document.getElementById('latitude').value = latlng.lat;
                        document.getElementById('longitude').value = latlng.lng;
                        
                        // Update the location input with reverse geocoding
                        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${latlng.lat}&lon=${latlng.lng}&format=json`)
                            .then(response => response.json())
                            .then(data => {
                                document.getElementById('location').value = data.display_name;
                            });
                    }
                    mapModal.style.display = 'none';
                });
                
                // Close modal when clicking outside
                mapModal.addEventListener('click', function(e) {
                    if (e.target === mapModal) {
                        mapModal.style.display = 'none';
                    }
                });
            });

            // Set minimum date to today
            document.addEventListener('DOMContentLoaded', function() {
                const dateInput = document.getElementById('date');
                const today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('min', today);
                
                // Add event listener to prevent past dates if manually entered
                dateInput.addEventListener('change', function() {
                    const selectedDate = new Date(this.value);
                    const currentDate = new Date();
                    currentDate.setHours(0, 0, 0, 0); // Reset time part for accurate date comparison
                    
                    if (selectedDate < currentDate) {
                        alert('Please select a present or future date');
                        this.value = today;
                    }
                });
            });
        </script>
    </body>
    </html>
