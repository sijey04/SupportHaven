<?php
session_start();
require_once 'connection.php';

// Create database connection
$database = new Connection();
$db = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process form submission
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $expertise = $_POST['expertise'];
    $experience = $_POST['experience'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

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
                    $errorMessage = "Failed to create upload directory. Please contact the administrator.";
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
                    $errorMessage = "Database error: " . $e->getMessage();
                }
            } else {
                $errorMessage = "Sorry, there was an error uploading your file.";
            }
        } else {
            $errorMessage = "Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload.";
        }
    } else {
        $errorMessage = "Please select a file to upload.";
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
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
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
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
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
    </script>
</body>
</html>