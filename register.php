<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastepay_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

$register_error = "";
$register_success = "";


if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    

// Check if username exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $register_error = "Username already taken.";
} else {
    // Store password directly (not recommended for production)
    $insert = $conn->prepare("INSERT INTO users (username, password, fullname, email, phone) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("sssss", $username, $password, $fullname, $email, $phone);

    if ($insert->execute()) {
        $register_success = "Account created successfully. <a href='login.php'>Login now</a>.";
    } else {
        $register_error = "Failed to register. Try again.";
    }
    $insert->close();
}

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WastePay - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #60ad5e;
            --primary-dark: #1b5e20;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f7f0 0%, #e0f2e1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-container {
            max-width: 450px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }

        .login-body {
            padding: 30px;
        }

        .logo {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .login-form .form-control {
            border-radius: 10px;
            padding: 12px 20px;
            margin-bottom: 20px;
            border: 2px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .login-form .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.4);
        }

        .login-footer {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }

        .login-footer a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .error-message {
            background-color: #ffebee;
            color: #d32f2f;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-left: 4px solid #d32f2f;
        }

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="login-container">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-recycle"></i>
                </div>
                <h2>Register</h2>
                <p>Create a WastePay Account</p>
            </div>

            <div class="login-body">
                <?php if ($register_error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $register_error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($register_success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $register_success; ?>
                </div>
                <?php endif; ?>

                <form class="login-form" method="POST" autocomplete="off">
                    <input type="text" class="form-control" name="username" placeholder="household number" required>
                    
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                    <input type="text" class="form-control" name="fullname" placeholder="fullname" required>
  
                    <input type="text" class="form-control" name="email" placeholder="email" required>
                    <input type="text" class="form-control" name="phone" placeholder="phonenumber" required>
                    <div class="d-grid mb-3">
                        <button type="submit" name="register" class="btn btn-login">
                            <i class="fas fa-user-plus me-2"></i> Register
                        </button>
                    </div>
                </form>
            </div>

            <div class="login-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
