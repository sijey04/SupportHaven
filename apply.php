<?php
session_start();
require_once 'connection.php';

// Create database connection
$database = new Connection();
$db = $database->getConnection();   

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Server-side validation
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $expertise = $_POST['expertise'];
    $experience = $_POST['experience'];
    $password = $_POST['password'];

    if (empty($firstName)) {
        $errors[] = "First name is required.";
    }
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    if (empty($expertise)) {
        $errors[] = "Area of expertise is required.";
    }
    if (!is_numeric($experience) || $experience < 0) {
        $errors[] = "Valid years of experience is required.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    // Only process if there are no errors
    if (empty($errors)) {
        // Check if email already exists
        $checkEmailQuery = "SELECT COUNT(*) FROM users WHERE email = :email";
        $checkEmailStmt = $db->prepare($checkEmailQuery);
        $checkEmailStmt->bindParam(":email", $email);
        $checkEmailStmt->execute();
        
        if ($checkEmailStmt->fetchColumn() > 0) {
            $errors[] = "This email address is already registered. Please use a different email.";
        } else {
            $password = password_hash($password, PASSWORD_BCRYPT);

            // Handle file upload
            $targetDir = "uploads/"; 
            $fileName = basename($_FILES["photo"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);

            if(!empty($_FILES["photo"]["name"])){
                // Allow certain file formats
                $allowTypes = array('jpg','png','jpeg','gif');
                if(in_array($fileType, $allowTypes)){
                    // Create the uploads directory if it doesn't exist
                    if (!file_exists($targetDir)) {
                        if (!mkdir($targetDir, 0777, true)) {
                            $errors[] = "Failed to create upload directory. Please contact the administrator.";
                        }
                    }
                    
                    // Upload file to server
                    if(move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)){
                        // Start transaction
                        $db->beginTransaction();

                        try {
                            // Insert into users table first
                            $userQuery = "INSERT INTO users (firstname, lastname, email, password, user_role_id) 
                                          VALUES (:firstName, :lastName, :email, :password, 
                                                 (SELECT id FROM user_roles WHERE role_name = 'technician'))";
                            $userStmt = $db->prepare($userQuery);
                            $userStmt->bindParam(":firstName", $firstName);
                            $userStmt->bindParam(":lastName", $lastName);
                            $userStmt->bindParam(":email", $email);
                            $userStmt->bindParam(":password", $password);
                            $userStmt->execute();

                            $userId = $db->lastInsertId();

                            // Insert application into technicians table
                            $techQuery = "INSERT INTO technicians (user_id, phone, expertise, experience, photo, status) 
                                          VALUES (:userId, :phone, :expertise, :experience, :photo, 'pending')";
                            $techStmt = $db->prepare($techQuery);
                            $techStmt->bindParam(":userId", $userId);
                            $techStmt->bindParam(":phone", $phone);
                            $techStmt->bindParam(":expertise", $expertise);
                            $techStmt->bindParam(":experience", $experience);
                            $techStmt->bindParam(":photo", $fileName);
                            
                            $techStmt->execute();

                            // Commit the transaction
                            $db->commit();
                            $successMessage = "Your application has been submitted successfully!";
                        } catch (PDOException $e) {
                            // Rollback the transaction on error
                            $db->rollBack();
                            $errors[] = "Database error: " . $e->getMessage();
                        }
                    } else {
                        $errors[] = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $errors[] = "Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload.";
                }
            } else {
                $errors[] = "Please select a file to upload.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply as Technician - SupportHaven</title>
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
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Apply as a Technician</h1>
            
            <?php if (isset($successMessage)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Progress Bar -->
            <div class="mb-12">
                <div class="relative">
                    <div class="h-1 bg-gray-200 rounded-full">
                        <div class="h-1 bg-indigo-600 rounded-full transition-all duration-300" id="applicationProgress"></div>
                    </div>
                    <div class="flex justify-between mt-4">
                        <div class="step active" data-step="1">
                            <div class="w-10 h-10 bg-white border-2 border-indigo-600 rounded-full flex items-center justify-center text-indigo-600 font-semibold mx-auto">1</div>
                            <div class="text-sm font-medium text-gray-600 mt-2">Personal Info</div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="w-10 h-10 bg-white border-2 border-gray-300 rounded-full flex items-center justify-center text-gray-500 font-semibold mx-auto">2</div>
                            <div class="text-sm font-medium text-gray-600 mt-2">Professional</div>
                        </div>
                        <div class="step" data-step="3">
                            <div class="w-10 h-10 bg-white border-2 border-gray-300 rounded-full flex items-center justify-center text-gray-500 font-semibold mx-auto">3</div>
                            <div class="text-sm font-medium text-gray-600 mt-2">Account</div>
                        </div>
                        <div class="step" data-step="4">
                            <div class="w-10 h-10 bg-white border-2 border-gray-300 rounded-full flex items-center justify-center text-gray-500 font-semibold mx-auto">4</div>
                            <div class="text-sm font-medium text-gray-600 mt-2">Review</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application Form -->
            <form method="POST" action="" enctype="multipart/form-data" id="applicationForm" class="space-y-6">
                <!-- Step 1: Personal Information -->
                <div class="step-content" id="step1">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6 pb-2 border-b">Personal Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                   id="firstName" name="firstName" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                   id="lastName" name="lastName" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                   id="email" name="email" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                   id="phone" name="phone" required>
                        </div>
                    </div>
                    <div class="flex justify-end mt-6 pt-6 border-t">
                        <button type="button" class="next-step bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition duration-200">
                            Next Step
                        </button>
                    </div>
                </div>

                <!-- Step 2: Professional Information -->
                <div class="step-content hidden" id="step2">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6 pb-2 border-b">Professional Information</h3>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Area of Expertise</label>
                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                    id="expertise" name="expertise" required>
                                <option value="">Select your area of expertise</option>
                                <option value="Computer Repair">Computer Repair</option>
                                <option value="Network Setup">Network Setup</option>
                                <option value="Software Installation">Software Installation</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Years of Experience</label>
                            <input type="number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                   id="experience" name="experience" min="0" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Profile Photo</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                                <div class="space-y-1 text-center">
                                    <div class="flex text-sm text-gray-600">
                                        <label for="photo" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Upload a file</span>
                                            <input id="photo" name="photo" type="file" class="sr-only" required>
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                                </div>
                            </div>
                            <div id="file-chosen" class="mt-2 text-sm text-gray-500"></div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-6 pt-6 border-t">
                        <button type="button" class="prev-step bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition duration-200">
                            Previous
                        </button>
                        <button type="button" class="next-step bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition duration-200">
                            Next Step
                        </button>
                    </div>
                </div>

                <!-- Step 3: Account Information -->
                <div class="step-content hidden" id="step3">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6 pb-2 border-b">Account Information</h3>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                   id="password" name="password" required minlength="8">
                            <p class="mt-1 text-sm text-gray-500">Password must be at least 8 characters long</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <input type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                   id="confirmPassword" name="confirmPassword" required>
                        </div>
                    </div>
                    <div class="flex justify-between mt-6 pt-6 border-t">
                        <button type="button" class="prev-step bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition duration-200">
                            Previous
                        </button>
                        <button type="button" class="next-step bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition duration-200">
                            Next Step
                        </button>
                    </div>
                </div>

                <!-- Step 4: Review -->
                <div class="step-content hidden" id="step4">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6 pb-2 border-b">Review Your Information</h3>
                    <div class="space-y-6" id="reviewInfo">
                        <!-- This will be populated by JavaScript -->
                    </div>
                    <div class="flex justify-between mt-6 pt-6 border-t">
                        <button type="button" class="prev-step bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition duration-200">
                            Previous
                        </button>
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                            Submit Application
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Add your existing JavaScript with minor adjustments for the new classes -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('applicationForm');
        const steps = document.querySelectorAll('.step-content');
        const progressBar = document.getElementById('applicationProgress');
        const stepIndicators = document.querySelectorAll('.step');
        let currentStep = 1;

        // Update progress bar and step indicators
        function updateProgress() {
            const progress = ((currentStep - 1) / (steps.length - 1)) * 100;
            progressBar.style.width = `${progress}%`;
            
            // Update step indicators
            stepIndicators.forEach((step, index) => {
                const stepNum = index + 1;
                if (stepNum < currentStep) {
                    step.classList.add('completed');
                    step.querySelector('div:first-child').classList.replace('border-gray-300', 'border-indigo-600');
                    step.querySelector('div:first-child').classList.replace('text-gray-500', 'text-indigo-600');
                } else if (stepNum === currentStep) {
                    step.classList.add('active');
                    step.querySelector('div:first-child').classList.replace('border-gray-300', 'border-indigo-600');
                    step.querySelector('div:first-child').classList.replace('text-gray-500', 'text-indigo-600');
                } else {
                    step.classList.remove('completed', 'active');
                    step.querySelector('div:first-child').classList.replace('border-indigo-600', 'border-gray-300');
                    step.querySelector('div:first-child').classList.replace('text-indigo-600', 'text-gray-500');
                }
            });
        }

        // Show specific step
        function showStep(stepNumber) {
            steps.forEach((step, index) => {
                step.classList.add('hidden');
            });
            document.getElementById(`step${stepNumber}`).classList.remove('hidden');
            currentStep = stepNumber;
            updateProgress();

            // Add this condition to update review info when showing step 4
            if (stepNumber === 4) {
                updateReviewInfo();
            }
        }

        // Next button click handlers
        document.querySelectorAll('.next-step').forEach(button => {
            button.addEventListener('click', function() {
                // Add validation here if needed
                if (currentStep < steps.length) {
                    showStep(currentStep + 1);
                }
            });
        });

        // Previous button click handlers
        document.querySelectorAll('.prev-step').forEach(button => {
            button.addEventListener('click', function() {
                if (currentStep > 1) {
                    showStep(currentStep - 1);
                }
            });
        });

        // File upload preview
        const fileInput = document.getElementById('photo');
        const fileChosen = document.getElementById('file-chosen');

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileChosen.textContent = this.files[0].name;
            } else {
                fileChosen.textContent = '';
            }
        });

        // Initialize the form
        showStep(1);

        // Add this new function to populate review info
        function updateReviewInfo() {
            const reviewInfo = document.getElementById('reviewInfo');
            reviewInfo.innerHTML = `
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Personal Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">First Name</p>
                                <p class="font-medium">${document.getElementById('firstName').value}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Last Name</p>
                                <p class="font-medium">${document.getElementById('lastName').value}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="font-medium">${document.getElementById('email').value}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Phone</p>
                                <p class="font-medium">${document.getElementById('phone').value}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Professional Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Area of Expertise</p>
                                <p class="font-medium">${document.getElementById('expertise').value}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Years of Experience</p>
                                <p class="font-medium">${document.getElementById('experience').value} years</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Profile Photo</p>
                                <p class="font-medium">${document.getElementById('photo').files[0]?.name || 'No file selected'}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Account Information</h4>
                        <div>
                            <p class="text-sm text-gray-600">Password</p>
                            <p class="font-medium">********</p>
                        </div>
                    </div>
                </div>
            `;
        }
    });
    </script>
</body>
</html>
