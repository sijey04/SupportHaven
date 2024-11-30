<?php
session_start();
if (!isset($_SESSION['application_submitted']) && !isset($_SESSION['tech_status'])) {
    header('Location: apply.php');
    exit();
}

$message = '';
$status = '';

if (isset($_SESSION['tech_status'])) {
    $status = $_SESSION['tech_status'];
    if ($status === 'pending') {
        $message = 'Your application is still under review. We will notify you via email once a decision has been made.';
    } elseif ($status === 'declined') {
        $message = 'We regret to inform you that your application has been declined. Please contact support for more information.';
    }
    // Clear the status from session after displaying
    unset($_SESSION['tech_status']);
} else {
    $message = 'Thank you for applying! Your application has been submitted successfully and is under review.';
    // Clear the submission flag
    unset($_SESSION['application_submitted']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Pending - SupportHaven</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <a href="index.html" class="block w-48 mx-auto">
                <img src="images/logo.png" alt="SupportHaven Logo" class="h-12">
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-16 max-w-2xl">
        <div class="bg-white rounded-xl shadow-lg p-8 text-center">
            <div class="mb-6">
                <i class="fas fa-clock text-5xl text-indigo-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Application Status</h1>
            <p class="text-gray-600 mb-6">
                <?php echo htmlspecialchars($message); ?>
            </p>
            <div class="space-y-4">
                <p class="text-sm text-gray-500">
                    While you wait, you can:
                </p>
                <ul class="text-gray-600 space-y-2">
                    <li>• Check your email regularly for updates</li>
                    <li>• Prepare any additional documentation that might be requested</li>
                    <li>• Review our technician guidelines and policies</li>
                </ul>
            </div>
            <div class="mt-8">
                <a href="index.html" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition duration-200">
                    Return to Homepage
                </a>
            </div>
        </div>
    </div>
</body>
</html> 