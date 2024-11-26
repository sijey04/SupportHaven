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
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/main.css">
    <style>
        .logo-container {
            text-align: center;
            padding: 20px 0;
        }
        .logo-container img {
            height: 60px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .custom-file-upload {
            border: 1px solid #ccc;
            display: inline-block;
            padding: 6px 12px;
            cursor: pointer;
            background-color: #f8f9fa;
            color: #495057;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .custom-file-upload:hover {
            background-color: #e9ecef;
        }
        #photo {
            display: none;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .password-field {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <a href="index.html">
            <img src="images/logo.png" alt="SupportHaven Logo">
        </a>
    </div>

    <div class="container">
        <h1>Apply as a Technician</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" id="applicationForm">
            <div class="form-group">
                <label for="firstName">First Name</label>
                <input type="text" class="form-control" id="firstName" name="firstName" required>
            </div>
            <div class="form-group">
                <label for="lastName">Last Name</label>
                <input type="text" class="form-control" id="lastName" name="lastName" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" class="form-control" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="expertise">Area of Expertise</label>
                <select class="form-control" id="expertise" name="expertise" required>
                    <option value="">Select your area of expertise</option>
                    <option value="Computer Repair">Computer Repair</option>
                    <option value="Mobile Device Support">Mobile Device Support</option>
                    <option value="Handyman Services">Handyman Services</option>
                </select>
            </div>
            <div class="form-group">
                <label for="experience">Years of Experience</label>
                <input type="number" class="form-control" id="experience" name="experience" min="0" required>
            </div>
            <div class="form-group password-field">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <span class="password-toggle" onclick="togglePassword()">
                    <i class="fa fa-eye"></i>
                </span>
            </div>
            <div class="form-group">
                <label for="photo">Upload Your Photo</label>
                <label for="photo" class="custom-file-upload">
                    <i class="fa fa-cloud-upload"></i> Choose File
                </label>
                <input type="file" class="form-control-file" id="photo" name="photo" required>
                <span id="file-chosen">No file chosen</span>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Submit Application</button>
        </form>
    </div>

    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        const actualBtn = document.getElementById('photo');
        const fileChosen = document.getElementById('file-chosen');

        actualBtn.addEventListener('change', function(){
            fileChosen.textContent = this.files[0].name;
        });

        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Client-side form validation
        document.getElementById('applicationForm').addEventListener('submit', function(event) {
            let isValid = true;
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const expertise = document.getElementById('expertise').value;
            const experience = document.getElementById('experience').value;
            const password = document.getElementById('password').value;
            const photo = document.getElementById('photo').value;

            if (firstName === '') {
                isValid = false;
                alert('Please enter your first name.');
            } else if (lastName === '') {
                isValid = false;
                alert('Please enter your last name.');
            } else if (email === '' || !email.includes('@')) {
                isValid = false;
                alert('Please enter a valid email address.');
            } else if (phone === '') {
                isValid = false;
                alert('Please enter your phone number.');
            } else if (expertise === '') {
                isValid = false;
                alert('Please select your area of expertise.');
            } else if (experience === '' || isNaN(experience) || experience < 0) {
                isValid = false;
                alert('Please enter a valid number of years of experience.');
            } else if (password.length < 8) {
                isValid = false;
                alert('Password must be at least 8 characters long.');
            } else if (photo === '') {
                isValid = false;
                alert('Please select a photo to upload.');
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
