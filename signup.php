<?php
require_once 'connection.php';
require_once 'auth_functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Connection();
    $db = $database->getConnection();

    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if the 'users' table exists, if not create it
    $checkTableQuery = "SHOW TABLES LIKE 'users'";
    $tableExists = $db->query($checkTableQuery)->rowCount() > 0;

    if (!$tableExists) {
        $createTableQuery = "CREATE TABLE users (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            firstname VARCHAR(50) NOT NULL,
            lastname VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            user_role_id INT(11) NOT NULL,
            auth_provider ENUM('local', 'google', 'facebook') DEFAULT 'local',
            oauth_id VARCHAR(255) DEFAULT NULL,
            UNIQUE KEY unique_oauth (auth_provider, oauth_id),
            KEY idx_email (email),
            CONSTRAINT users_ibfk_1 FOREIGN KEY (user_role_id) REFERENCES user_roles (id)
        )";
        $db->exec($createTableQuery);
    }

    if (!validatePassword($_POST['password'])) {
        $message = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
    } else {
        // Get the default user role (assuming it's the user role with the lowest ID)
        $roleQuery = "SELECT id FROM user_roles ORDER BY id ASC LIMIT 1";
        $roleStmt = $db->query($roleQuery);
        $defaultRoleId = $roleStmt->fetchColumn();

        if (!$defaultRoleId) {
            $message = "Error: No user roles defined. Please contact the administrator.";
        } else {
            $query = "INSERT INTO users (firstname, lastname, email, password, user_role_id, auth_provider) 
                      VALUES (:firstName, :lastName, :email, :password, :userRoleId, 'local')";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":firstName", $firstName);
            $stmt->bindParam(":lastName", $lastName);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":userRoleId", $defaultRoleId);

            try {
                if($stmt->execute()) {
                    $message = "Registration successful! Redirecting to login page...";
                    $redirect = true;
                } else {
                    $message = "Registration failed. Please try again.";
                    $redirect = false;
                }
            } catch (PDOException $e) {
                $message = "Error: " . $e->getMessage();
                $redirect = false;
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
    <title>SupportHaven - Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f2ef;
            font-family: -apple-system, system-ui, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', 'Fira Sans', Ubuntu, Oxygen, 'Oxygen Sans', Cantarell, 'Droid Sans', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Lucida Grande', Helvetica, Arial, sans-serif;
        }
        .signup-container {
            max-width: 400px;
            margin-top: 50px;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .form-control {
            border-radius: 4px;
            height: 48px;
        }
        .btn-primary {
            background-color: #0a66c2;
            border: none;
            font-weight: 600;
            height: 48px;
        }
        .btn-primary:hover {
            background-color: #004182;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: rgba(0,0,0,0.6);
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(0,0,0,0.15);
        }
        .divider::before {
            margin-right: .25em;
        }
        .divider::after {
            margin-left: .25em;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container signup-container">
        <div class="text-center mb-4">
            <a href="index.html"><img src="images/logo.png" alt="SupportHaven Logo" class="logo"></a>
      </div>
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">Join SupportHaven</h2>
                <?php if(isset($message)) { ?>
                    <div class="alert alert-info" role="alert">
                        <?php echo $message; ?>
                    </div>
                <?php } ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <input type="text" class="form-control" name="firstName" placeholder="First Name" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" class="form-control" name="lastName" placeholder="Last Name" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="mb-3 position-relative">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password (6 or more characters)" required>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fa fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">I agree to the SupportHaven User Agreement, Privacy Policy, and Cookie Policy.</label>
                    </div>
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary">Agree & Join</button>
                    </div>
                </form>
                <div class="divider mb-3">or</div>
                <div class="d-grid gap-2">
                    <a href="#" class="btn btn-outline-secondary" onclick="signUpWithGoogle()">
                        <i class="fa fa-google me-2"></i> Sign up with Google
                    </a>
                    <a href="#" class="btn btn-outline-secondary" onclick="signUpWithFacebook()">
                        <i class="fa fa-facebook me-2"></i> Sign up with Facebook
                    </a>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            Already on SupportHaven? <a href="login.php" class="text-decoration-none">Sign in</a>
        </div>
        <br>
        <br>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js"></script>
    <script>
        function signUpWithGoogle() {
            // Implement Google Sign-Up logic here
            console.log('Google Sign-Up clicked');
        }

        function signUpWithFacebook() {
            // Implement Facebook Sign-Up logic here
            console.log('Facebook Sign-Up clicked');
        }

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        <?php if(isset($redirect) && $redirect): ?>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>