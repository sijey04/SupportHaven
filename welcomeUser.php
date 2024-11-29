<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's first name for personalized welcome
$firstName = explode(' ', $_SESSION['user_name'])[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to SupportHaven</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .service-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
        .advantage-icon {
            font-size: 2.5rem;
            color: #0066cc;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation Bar -->
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
                                <span class="text-gray-700"><?php echo htmlspecialchars($firstName); ?></span>
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

        <!-- Welcome Hero Section -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
            <div class="container mx-auto px-4">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Welcome to SupportHaven, <?php echo htmlspecialchars($firstName); ?>!</h1>
                <p class="text-xl mb-8">Your trusted partner for all your tech support needs.</p>
                <a href="booking.php" class="bg-white text-blue-600 px-8 py-3 rounded-full font-semibold hover:bg-gray-100 transition duration-300">Book a Service Now</a>
            </div>
        </div>

        <!-- Featured Services Section -->
        <div class="container mx-auto px-4 py-16">
            <h2 class="text-3xl font-bold text-center mb-12">Our Premium Services</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Computer Support -->
                <div class="service-card bg-white rounded-lg shadow-lg p-6">
                    <div class="text-4xl text-blue-600 mb-4">
                        <i class="fa fa-laptop"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Computer Support</h3>
                    <p class="text-gray-600 mb-4">Expert assistance with repairs, upgrades, and troubleshooting for all your computer needs.</p>
                    <ul class="text-gray-600 mb-4">
                        <li class="mb-2"><i class="fa fa-check text-green-500 mr-2"></i>Hardware repairs</li>
                        <li class="mb-2"><i class="fa fa-check text-green-500 mr-2"></i>Software installation</li>
                        <li><i class="fa fa-check text-green-500 mr-2"></i>Performance optimization</li>
                    </ul>
                </div>

                <!-- Network Solutions -->
                <div class="service-card bg-white rounded-lg shadow-lg p-6">
                    <div class="text-4xl text-blue-600 mb-4">
                        <i class="fa fa-wifi"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Network Solutions</h3>
                    <p class="text-gray-600 mb-4">Professional setup and optimization of your home or office network.</p>
                    <ul class="text-gray-600 mb-4">
                        <li class="mb-2"><i class="fa fa-check text-green-500 mr-2"></i>WiFi installation</li>
                        <li class="mb-2"><i class="fa fa-check text-green-500 mr-2"></i>Network security</li>
                        <li><i class="fa fa-check text-green-500 mr-2"></i>Coverage optimization</li>
                    </ul>
                </div>

                <!-- Smart Home -->
                <div class="service-card bg-white rounded-lg shadow-lg p-6">
                    <div class="text-4xl text-blue-600 mb-4">
                        <i class="fa fa-home"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Smart Home Setup</h3>
                    <p class="text-gray-600 mb-4">Transform your home with cutting-edge smart technology solutions.</p>
                    <ul class="text-gray-600 mb-4">
                        <li class="mb-2"><i class="fa fa-check text-green-500 mr-2"></i>Device integration</li>
                        <li class="mb-2"><i class="fa fa-check text-green-500 mr-2"></i>Automation setup</li>
                        <li><i class="fa fa-check text-green-500 mr-2"></i>Security systems</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Why Choose Us Section -->
        <div class="bg-gray-100 py-16">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold text-center mb-12">Why Choose SupportHaven?</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Expert Technicians -->
                    <div class="text-center">
                        <div class="advantage-icon mb-4">
                            <i class="fa fa-user-tie"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Expert Technicians</h3>
                        <p class="text-gray-600">Highly skilled and certified professionals</p>
                    </div>

                    <!-- 24/7 Support -->
                    <div class="text-center">
                        <div class="advantage-icon mb-4">
                            <i class="fa fa-clock"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">24/7 Support</h3>
                        <p class="text-gray-600">Round-the-clock technical assistance</p>
                    </div>

                    <!-- Affordable Pricing -->
                    <div class="text-center">
                        <div class="advantage-icon mb-4">
                            <i class="fa fa-tags"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Affordable Pricing</h3>
                        <p class="text-gray-600">Competitive rates with no hidden fees</p>
                    </div>

                    <!-- Satisfaction Guaranteed -->
                    <div class="text-center">
                        <div class="advantage-icon mb-4">
                            <i class="fa fa-shield-alt"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Guaranteed Satisfaction</h3>
                        <p class="text-gray-600">100% satisfaction guarantee on all services</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="bg-blue-600 text-white py-16">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold mb-4">Ready to Get Started?</h2>
                <p class="text-xl mb-8">Book your first service today and experience the SupportHaven difference.</p>
                <div class="space-x-4">
                    <a href="booking.php" class="bg-white text-blue-600 px-8 py-3 rounded-full font-semibold hover:bg-gray-100 transition duration-300">Book Now</a>
                    <a href="user-landing.php" class="border-2 border-white text-white px-8 py-3 rounded-full font-semibold hover:bg-blue-700 transition duration-300">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Contact Us</h3>
                    <p>Phone: (+63) 97087015677</p>
                    <p>Email: support@supporthaven.com</p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Quick Links</h3>
                    <ul>
                        <li><a href="services.php" class="hover:text-blue-400">Services</a></li>
                        <li><a href="pricing.php" class="hover:text-blue-400">Pricing</a></li>
                        <li><a href="faq.php" class="hover:text-blue-400">FAQs</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-blue-400"><i class="fa fa-facebook"></i></a>
                        <a href="#" class="hover:text-blue-400"><i class="fa fa-twitter"></i></a>
                        <a href="#" class="hover:text-blue-400"><i class="fa fa-instagram"></i></a>
                        <a href="#" class="hover:text-blue-400"><i class="fa fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-8">
                <p>&copy; 2024 SupportHaven. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Mobile menu toggle script -->
    <script>
        // Add your mobile menu toggle script here
    </script>
</body>
</html>
