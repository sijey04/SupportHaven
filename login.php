<?php
// Add this at the top of your login.php file
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set secure session cookie parameters before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);

// Start the session after setting the parameters
session_start();

// Update the require_once statement to use the correct path
require_once __DIR__ . '/connection.php';

$database = new Connection();
$db = $database->getConnection();

// Add this line to check if the connection is successful
if (!$db) {
    die("Connection failed: " . $database->getError());
}

// Implement login attempt limiting
$max_attempts = 5;
$lockout_time = 900; // 15 minutes

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Check if the user is locked out
    if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
        $error = "Too many failed attempts. Please try again later.";
    } else {
        $query = "SELECT u.*, ur.role_name FROM users u 
                  LEFT JOIN user_roles ur ON u.user_role_id = ur.id 
                  WHERE u.email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                // Reset login attempts on successful login
                unset($_SESSION['login_attempts']);
                unset($_SESSION['lockout_time']);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['firstName'] . ' ' . $user['lastName'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role_name']; 
                error_log("Login successful for user: " . $_SESSION['user_email']);
                
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Redirect based on user role
                if ($_SESSION['user_role'] == 'technician') {
                    $redirect_url = 'technician.php';
                } elseif ($_SESSION['user_role'] == 'admin') {
                    $redirect_url = 'admin/admin.php';
                } else {
                    $redirect_url = 'booking.php';
                }

                // Add this line to check if the header is sent
                if (headers_sent()) {
                    echo "Headers already sent. Redirecting using JavaScript.";
                    echo "<script>window.location.href = '" . $redirect_url . "';</script>";
                } else {
                    header("Location: " . $redirect_url);
                }
                exit();
            } else {
                $error = "Invalid password. Please try again.";
                // Increment login attempts
                $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
            }
        } else {
            $error = "Email not found. Please check your email or sign up.";
            // Increment login attempts
            $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
        }

        // Check if max attempts reached
        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_attempts) {
            $_SESSION['lockout_time'] = time() + $lockout_time;
            $error = "Too many failed attempts. Please try again later.";
        }
    }
}
// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'technician') {
        $redirect_url = 'technician.php';
    } elseif ($_SESSION['user_role'] == 'admin') {
        $redirect_url = 'admin/admin.php';
    } else {
        $redirect_url = 'booking.php';
    }
    // Add this line to check if the header is sent
    if (headers_sent()) {
        echo "Headers already sent. Redirecting using JavaScript.";
        echo "<script>window.location.href = '" . $redirect_url . "';</script>";
    } else {
        header("Location: " . $redirect_url);
    }
    exit();
}

// Generate and store CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SupportHaven - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f2ef;
            font-family: -apple-system, system-ui, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', 'Fira Sans', Ubuntu, Oxygen, 'Oxygen Sans', Cantarell, 'Droid Sans', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Lucida Grande', Helvetica, Arial, sans-serif;
        }
        .login-container {
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
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="text-center mb-4">
            <a href="index.php"><img src="images/logo.png" alt="SupportHaven Logo" class="logo"></a>
        </div>
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">Sign in</h2>
                <?php if (isset($error)) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php } ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="mb-3">
                        <a href="#" class="text-decoration-none">Forgot password?</a>
                    </div>
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary">Sign in</button>
                    </div>
                </form>
                <div class="divider mb-3">or</div>
                <div class="d-grid gap-2">
                    <a href="/auth/google" class="btn btn-outline-secondary" onclick="signInWithGoogle()">
                        <i class="fa fa-google me-2"></i> Sign in with Google
                    </a>
                    <a href="/auth/apple" class="btn btn-outline-secondary" onclick="signInWithApple()">
                        <i class="fa fa-apple me-2"></i> Sign in with Apple
                    </a>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            New to SupportHaven? <a href="signup.php" class="text-decoration-none">Join now</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js"></script>
    <script>
        function signInWithGoogle() {
            google.accounts.id.initialize({
                client_id: 'YOUR_GOOGLE_CLIENT_ID',
                callback: handleGoogleSignIn
            });
            google.accounts.id.prompt();
        }

        function handleGoogleSignIn(response) {
            // Send the response.credential to your server for verification
            fetch('/auth/google', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                },
                body: JSON.stringify({ token: response.credential }),
            })
            .then(response => response.json())
            .then(data => {
                // Handle the server response
                console.log('Success:', data);
                // Redirect based on user role
                window.location.href = data.redirect_url;
            })
            .catch((error) => {
                console.error('Error:', error);
            });
        }

        function signInWithApple() {
            AppleID.auth.init({
                clientId : 'YOUR_APPLE_CLIENT_ID',
                scope : 'name email',
                redirectURI: 'https://your-redirect-uri.com/auth/apple/callback',
                state : 'origin:web',
                usePopup : true
            });

            AppleID.auth.signIn()
                .then(function(response) {
                    // Send the authorization code to your server
                    fetch('/auth/apple', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                        },
                        body: JSON.stringify({ code: response.authorization.code }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Handle the server response
                        console.log('Success:', data);
                        // Redirect based on user role
                        window.location.href = data.redirect_url;
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                    });
                })
                .catch(function(error) {
                    console.error('Apple Sign-In Error:', error);
                });
        }
    </script>
</body>
</html>
