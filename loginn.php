<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - WastePay</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: #e8f5e9;
      font-family: Arial, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
    }
    .login-box {
      width: 100%;
      max-width: 400px;
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .btn-login {
      background: #2e7d32;
      color: white;
      font-weight: 600;
      padding: 10px;
      transition: all 0.3s ease;
    }
    .btn-login:hover {
      background: #1b5e20;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(30, 100, 40, 0.3);
    }
    .form-control {
      padding: 12px;
      border-radius: 8px;
    }
    .form-control:focus {
      border-color: #2e7d32;
      box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
    }
    #error-msg {
      transition: all 0.3s ease;
    }
    .login-logo {
      text-align: center;
      margin-bottom: 20px;
    }
    .login-logo img {
      height: 60px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <!-- <div class="login-logo">
      <img src="https://via.placeholder.com/150x60?text=WastePay" alt="WastePay Logo">
    </div> -->
    
    <h2 class="text-center mb-4">Login to WastePay Account</h2>

    <div id="error-msg" class="alert alert-danger d-none"></div>
    
    <form id="login-form">
      <div class="mb-3">
        <label for="username" class="form-label">Household Number / Username</label>
        <input type="text" id="username" class="form-control" required placeholder="Enter username">
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" class="form-control" required placeholder="Enter password">
        <div class="form-text text-end">
          <a href="loginn.php" class="text-decoration-none">Forgot password?</a>
        </div>
      </div>
      <div class="d-grid mb-3">
        <button type="submit" class="btn btn-login" id="login-btn">
          Login
        </button>
      </div>
      <div class="text-center">
        <p class="mb-0">Don't have an account? <a href="registerr.php" class="text-decoration-none">Register here</a></p>
      </div>
    </form>
  </div>

  <script>
    const loginForm = document.getElementById('login-form');
    const errorMsg = document.getElementById('error-msg');
    const loginBtn = document.getElementById('login-btn');

    // Check if user is already logged in
    if (localStorage.getItem('jwt')) {
      window.location.href = 'user.php';
    }

    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value.trim();

      // Validate inputs
      if (!username || !password) {
        showError('Please enter both username and password');
        return;
      }

      // Show loading state
      loginBtn.disabled = true;
      loginBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Logging in...
      `;
      errorMsg.classList.add('d-none');

      try {
        const response = await fetch('https://zenopay-g25p.onrender.com/api/auth/token/', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ username, password })
        });

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.detail || 'Invalid credentials');
        }

        // Save tokens
        localStorage.setItem('jwt', data.access);
        localStorage.setItem('refresh_token', data.refresh);

        // Show success message and redirect
        await Swal.fire({
          icon: 'success',
          title: 'Login Successful!',
          text: 'Redirecting to your dashboard...',
          timer: 1500,
          showConfirmButton: false,
          timerProgressBar: true,
          willClose: () => {
            window.location.href = 'user.php';
          }
        });

      } catch (error) {
        showError(error.message);
        console.error('Login error:', error);
      } finally {
        // Reset button state
        loginBtn.disabled = false;
        loginBtn.innerHTML = 'Login';
      }
    });

    function showError(message) {
      errorMsg.textContent = message;
      errorMsg.classList.remove('d-none');
      
      // Animate the error message
      errorMsg.style.opacity = 0;
      errorMsg.style.transform = 'translateY(-10px)';
      setTimeout(() => {
        errorMsg.style.opacity = 1;
        errorMsg.style.transform = 'translateY(0)';
      }, 10);
    }
  </script>
</body>
</html>