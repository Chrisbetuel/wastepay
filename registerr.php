<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WastePay - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

            text-decoration: none;
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

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            color: #6c757d;
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
                <form class="login-form" id="registerForm">
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                    
                    <div class="password-container">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                    
                    <input type="text" class="form-control" name="fullname" placeholder="Full Name" required>
                    <input type="email" class="form-control" name="email" placeholder="Email" required>
                    <input type="tel" class="form-control" name="phone" placeholder="Phone Number (255XXXXXXXXX)" required>
                    <input type="text" class="form-control" name="house_number" placeholder="House Number" required>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-login" id="registerBtn">
                            <i class="fas fa-user-plus me-2"></i> Register
                        </button>
                    </div>

                    <div id="messageBox"></div>
                </form>
            </div>

            <div class="login-footer">
                <p>Already have an account? <a href="loginn.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Password toggle visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Form submission
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const registerBtn = document.getElementById('registerBtn');
            const messageBox = document.getElementById('messageBox');
            
            // Show loading state
            registerBtn.disabled = true;
            registerBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Registering...
            `;
            messageBox.innerHTML = '';

            try {
                // Prepare form data
                const formData = {
                    username: form.username.value.trim(),
                    password: form.password.value,
                    fullname: form.fullname.value.trim(),
                    email: form.email.value.trim(),
                    phone: form.phone.value.trim(),
                    house_number: form.house_number.value.trim()
                };

                // Validate phone number format (255XXXXXXXXX)
                if (!/^255\d{9}$/.test(formData.phone)) {
                    throw new Error('Phone number must start with 255 followed by 9 digits (e.g., 255712345678)');
                }

                // Validate email format
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
                    throw new Error('Please enter a valid email address');
                }

                // Validate password length
                if (formData.password.length < 8) {
                    throw new Error('Password must be at least 8 characters');
                }

                // Send registration request
                const response = await fetch('https://zenopay-g25p.onrender.com/api/auth/register/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (!response.ok) {
                    // Handle validation errors from server
                    const errors = Object.values(result).flat().join('<br>');
                    throw new Error(errors || 'Registration failed');
                }

                // Registration successful - now automatically log the user in
                const loginResponse = await fetch('http://127.0.0.1:8000/api/auth/token/', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: formData.username,
                        password: formData.password
                    })
                });

                const loginData = await loginResponse.json();

                if (!loginResponse.ok) {
                    // If auto-login fails, just show success message
                    await Swal.fire({
                        icon: 'success',
                        title: 'Registration Successful!',
                        html: 'Your account has been created.<br>You can now login with your credentials.',
                        confirmButtonText: 'Go to Login',
                        willClose: () => {
                            window.location.href = 'loginn.php';
                        }
                    });
                    return;
                }

                // Store tokens in localStorage
                localStorage.setItem('jwt', loginData.access);
                localStorage.setItem('refresh_token', loginData.refresh);

                // Show success message and redirect to dashboard
                await Swal.fire({
                    icon: 'success',
                    title: 'Registration & Login Successful!',
                    text: 'You are being redirected to your dashboard...',
                    timer: 2000,
                    showConfirmButton: false,
                    timerProgressBar: true,
                    willClose: () => {
                        window.location.href = 'user.php';
                    }
                });

            } catch (error) {
                // Show error message
                messageBox.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${error.message}
                    </div>
                `;
                
                // Scroll to error message
                messageBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
            } finally {
                // Reset button state
                registerBtn.disabled = false;
                registerBtn.innerHTML = `
                    <i class="fas fa-user-plus me-2"></i> Register
                `;
            }
        });

        // Redirect to login if already authenticated
        if (localStorage.getItem('jwt')) {
            window.location.href = 'user.php';
        }
    </script>
</body>
</html>