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
                            $photoPath = "uploads/" . $fileName;
                            $techStmt->bindParam(":photo", $photoPath);
                            
                            $techStmt->execute();

                            $technicianId = $db->lastInsertId();

                            // Handle document uploads
                            $uploadDir = "uploads/documents/";
                            if (!file_exists($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }

                            // Handle ID document
                            if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
                                $idFileName = 'ID_' . time() . '_' . basename($_FILES['id_document']['name']);
                                $idTargetPath = $uploadDir . $idFileName;
                                
                                if (move_uploaded_file($_FILES['id_document']['tmp_name'], $idTargetPath)) {
                                    $docQuery = "INSERT INTO technician_documents (technician_id, document_type, file_name) 
                                                VALUES (:tech_id, 'id', :file_name)";
                                    $docStmt = $db->prepare($docQuery);
                                    $docStmt->execute([
                                        ':tech_id' => $technicianId,
                                        ':file_name' => $idFileName
                                    ]);
                                }
                            }

                            // Handle certification documents
                            if (isset($_FILES['certifications'])) {
                                foreach ($_FILES['certifications']['tmp_name'] as $key => $tmp_name) {
                                    if ($_FILES['certifications']['error'][$key] === UPLOAD_ERR_OK) {
                                        $certFileName = 'CERT_' . time() . '_' . basename($_FILES['certifications']['name'][$key]);
                                        $certTargetPath = $uploadDir . $certFileName;
                                        
                                        if (move_uploaded_file($tmp_name, $certTargetPath)) {
                                            $docQuery = "INSERT INTO technician_documents (technician_id, document_type, file_name) 
                                                        VALUES (:tech_id, 'certification', :file_name)";
                                            $docStmt = $db->prepare($docQuery);
                                            $docStmt->execute([
                                                ':tech_id' => $technicianId,
                                                ':file_name' => $certFileName
                                            ]);
                                        }
                                    }
                                }
                            }

                            // Commit the transaction
                            $db->commit();
                            $_SESSION['application_submitted'] = true;
                            header('Location: application-pending.php');
                            exit();
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
    <style>
        /* New Progress Bar Styles */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            position: relative;
            padding: 0 1rem;
        }

        .progress-line {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            height: 2px;
            background: #e5e7eb;
            width: 100%;
            z-index: 1;
        }

        .progress-line-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: #4f46e5;
            transition: width 0.3s ease;
        }

        .step {
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            width: 120px;
        }

        .step-circle {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: white;
            border: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .step.active .step-circle {
            border-color: #4f46e5;
            color: #4f46e5;
        }

        .step.completed .step-circle {
            background: #4f46e5;
            border-color: #4f46e5;
            color: white;
        }

        .step-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }

        .step.active .step-label {
            color: #4f46e5;
        }

        .step.completed .step-label {
            color: #4f46e5;
        }
    </style>
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
            <div class="progress-steps">
                <div class="progress-line">
                    <div id="progressLineFill" class="progress-line-fill" style="width: 0%"></div>
                </div>
                <div class="step active">
                    <div class="step-circle">1</div>
                    <span class="step-label">Personal Info</span>
                </div>
                <div class="step">
                    <div class="step-circle">2</div>
                    <span class="step-label">Professional</span>
                </div>
                <div class="step">
                    <div class="step-circle">3</div>
                    <span class="step-label">Account</span>
                </div>
                <div class="step">
                    <div class="step-circle">4</div>
                    <span class="step-label">Review</span>
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
                                <option value="All of the Above">All of the Above</option>
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
                        <!-- Required Documents Section -->
                        <div class="space-y-4 mt-6">
                            <h3 class="text-lg font-semibold text-gray-800">Required Documents</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Valid ID (Government-issued)</label>
                                <input type="file" name="id_document" accept=".pdf,.jpg,.jpeg,.png" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <p class="mt-1 text-sm text-gray-500">Upload a clear copy of your valid government ID (e.g., Driver's License, Passport, SSS ID)</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Technical Certifications</label>
                                <input type="file" name="certifications[]" accept=".pdf,.jpg,.jpeg,.png" multiple 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <p class="mt-1 text-sm text-gray-500">Upload any relevant technical certifications (CompTIA, Cisco, etc.)</p>
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
        </div>
    </div>

    <!-- Add your existing JavaScript with minor adjustments for the new classes -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const steps = document.querySelectorAll('.step');
        const stepContents = document.querySelectorAll('.step-content');
        const nextButtons = document.querySelectorAll('.next-step');
        const prevButtons = document.querySelectorAll('.prev-step');
        const progressBar = document.getElementById('applicationProgress');
        let currentStep = 1;

        function showStep(stepNumber) {
            // Update progress line
            const progress = ((stepNumber - 1) / (steps.length - 1)) * 100;
            document.getElementById('progressLineFill').style.width = `${progress}%`;
            
            steps.forEach((step, index) => {
                if (index + 1 === stepNumber) {
                    step.classList.add('active');
                } else if (index + 1 < stepNumber) {
                    step.classList.add('completed');
                } else {
                    step.classList.remove('active', 'completed');
                }
            });

            stepContents.forEach((content, index) => {
                if (index + 1 === stepNumber) {
                    content.classList.remove('hidden');
                } else {
                    content.classList.add('hidden');
                }
            });

            // Update review information when reaching step 4
            if (stepNumber === 4) {
                updateReviewInfo();
            }
        }

        nextButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (currentStep < 4) {
                    currentStep++;
                    showStep(currentStep);
                    if (currentStep === 4) {
                        updateReviewInfo();
                    }
                }
            });
        });

        prevButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
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
            // Make sure all form elements exist before accessing their values
            if (!reviewInfo) return;

            reviewInfo.innerHTML = `
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Personal Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">First Name</p>
                                <p class="font-medium">${document.getElementById('firstName')?.value || ''}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Last Name</p>
                                <p class="font-medium">${document.getElementById('lastName')?.value || ''}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="font-medium">${document.getElementById('email')?.value || ''}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Phone</p>
                                <p class="font-medium">${document.getElementById('phone')?.value || ''}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Professional Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Area of Expertise</p>
                                <p class="font-medium">${document.getElementById('expertise')?.value || ''}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Years of Experience</p>
                                <p class="font-medium">${document.getElementById('experience')?.value || ''} years</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Profile Photo</p>
                                <p class="font-medium">${document.getElementById('photo')?.files[0]?.name || 'No file selected'}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Government ID</p>
                                <p class="font-medium">${document.getElementById('id_document')?.files[0]?.name || 'No file selected'}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Technical Certifications</p>
                                <p class="font-medium">${Array.from(document.getElementById('certifications')?.files || []).map(file => file.name).join(', ') || 'No files selected'}</p>
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
