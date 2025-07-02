<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>WastePay Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --primary: #2e7d32;
      --primary-light: #60ad5e;
      --primary-dark: #1b5e20;
      --secondary: #ff9800;
      --accent: #f44336;
    }
    
    body {
      background: #f0f7f0;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .dashboard-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }
    
    .navbar {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .card {
      border-radius: 0.75rem;
      box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      margin-bottom: 1.5rem;
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 1rem 2rem rgba(0,0,0,0.15);
    }
    
    .debt-card.pending {
      border-left: 4px solid var(--secondary);
    }
    
    .debt-card.overdue {
      border-left: 4px solid var(--accent);
    }
    
    .debt-card.paid {
      border-left: 4px solid var(--primary);
    }
    
    /* Updated badge styles for better visibility */
    .badge-paid {
      background-color: var(--primary);
      color: white;
      padding: 0.35em 0.65em;
      font-size: 0.75em;
      font-weight: 700;
      border-radius: 0.25rem;
    }
    
    .badge-pending {
      background-color: var(--secondary);
      color: white;
      padding: 0.35em 0.65em;
      font-size: 0.75em;
      font-weight: 700;
      border-radius: 0.25rem;
    }
    
    .badge-overdue {
      background-color: var(--accent);
      color: white;
      padding: 0.35em 0.65em;
      font-size: 0.75em;
      font-weight: 700;
      border-radius: 0.25rem;
    }
    
    .badge-completed {
      background-color: #c8e6c9;
      color: #2e7d32;
    }
    
    .badge-failed {
      background-color: #ffcdd2;
      color: #d32f2f;
    }
    
    .btn-wastepay {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: white;
      border: none;
      border-radius: 0.5rem;
      padding: 0.5rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .btn-wastepay:hover {
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
    }
    
    .spinner-border-sm {
      vertical-align: middle;
      margin-left: 5px;
    }
    
    .welcome-section {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      border-radius: 0.75rem;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }
    
    .stat-circle {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      font-weight: 700;
      margin: 0 auto 1rem;
    }
    
    .stat-amount {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-weight: 700;
    }
    
    .stat-amount::before {
      content: 'Tsh ';
      font-size: 0.8em;
      opacity: 0.8;
    }
    
    .stat-label {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }
    
    .stat-description {
      font-size: 0.85rem;
      color: #6c757d;
      opacity: 0.9;
    }
    
    .overdue-stat {
      background: rgba(244, 67, 54, 0.1);
      color: #d32f2f;
      border: 3px solid #ffcdd2;
    }
    
    .pending-stat {
      background: rgba(255, 152, 0, 0.1);
      color: #ff9800;
      border: 3px solid #ffecb3;
    }
    
    .paid-stat {
      background: rgba(46, 125, 50, 0.1);
      color: #2e7d32;
      border: 3px solid #c8e6c9;
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
      animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .nav-link {
      color: var(--primary);
      font-weight: 600;
      padding: 0.5rem 1rem;
      border-radius: 2rem;
      transition: all 0.3s ease;
    }
    
    .nav-link:hover, .nav-link.active {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: white;
    }
    
    .amount-display {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-weight: 700;
    }
    
    .amount-display::before {
      content: 'Tsh ';
      font-size: 0.9em;
      opacity: 0.8;
    }
    
    .progress-bar-container {
      margin-top: 0.5rem;
    }
    
    /* User info styles */
    #userContactInfo {
      font-size: 0.9rem;
      opacity: 0.9;
    }
    
    #userContactInfo i {
      width: 20px;
      text-align: center;
      margin-right: 5px;
    }
    
    .swal2-popup .row {
      margin-bottom: 1rem;
    }
    
    .swal2-popup p {
      margin-bottom: 0.5rem;
      padding: 0.5rem 0;
      border-bottom: 1px solid #eee;
    }
    
    .swal2-popup p:last-child {
      border-bottom: none;
    }
    
    /* Table styles */
    .table-hover tbody tr:hover {
      background-color: rgba(46, 125, 50, 0.05);
      cursor: pointer;
    }
    
    .table-hover td {
      vertical-align: middle;
    }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="#">
        <i class="fas fa-recycle me-2"></i>
        <span class="fw-bold">WastePay</span>
      </a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link active" href="#" data-tab="dashboard"><i class="fas fa-home me-1"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#" data-tab="history"><i class="fas fa-history me-1"></i> History</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <i class="fas fa-user me-1"></i> <span id="userFullname">Loading...</span>
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#" id="profileBtn"><i class="fas fa-user-circle me-2"></i> Profile</a></li>
              <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#" id="logoutBtn"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="dashboard-container">
    <!-- Dashboard Tab -->
    <div id="dashboard" class="tab-content active">
      <!-- Welcome Section -->
      <div class="welcome-section">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h2><i class="fas fa-home me-2"></i> Welcome back, <span id="welcomeUserName" class="user-name">Loading...</span>!</h2>
            <p class="mb-0" id="userContactInfo">
              <span id="userEmail"></span>
              <span id="userPhone"></span>
            </p>
          </div>
          <div class="col-md-4 text-end">
            <div class="d-inline-block bg-white p-3 rounded shadow-sm text-dark">
              <span class="text-muted">Payment per month:</span><br>
              <span class="fw-bold fs-4 text-danger amount-display" id="monthlyPaymentAmount">0</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Stats Overview -->
      <div class="row mb-4">
        <div class="col-md-4 mb-3">
          <div class="card text-center p-4">
            <div class="stat-circle overdue-stat">
              <span class="stat-amount" id="overdueAmount">0</span>
            </div>
            <h5 class="stat-label">Overdue</h5>
            <p class="stat-description">Amount past due date</p>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card text-center p-4">
            <div class="stat-circle pending-stat">
              <span class="stat-amount" id="pendingAmount">0</span>
            </div>
            <h5 class="stat-label">Pending</h5>
            <p class="stat-description">Current amount due</p>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="card text-center p-4">
            <div class="stat-circle paid-stat">
              <span id="paidBillsCount">0</span>
            </div>
            <h5 class="stat-label">Paid Bills</h5>
            <p class="stat-description">Successful payments</p>
          </div>
        </div>
      </div>
      
      <!-- Debt Summary Card -->
      <div class="card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="fw-bold"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Debt Summary</h4>
          <span class="badge bg-danger" id="actionRequiredBadge" style="display: none;">Action Required</span>
        </div>
        
        <div class="mb-4" id="overdueSection" style="display: none;">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span>Overdue Amount</span>
            <span class="fw-bold text-danger amount-display" id="overdueAmountSummary">0</span>
          </div>
          <div class="progress progress-bar-container" style="height: 10px;">
            <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
          </div>
        </div>
        
        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span>Current Due</span>
            <span class="fw-bold text-warning amount-display" id="pendingAmountSummary">0</span>
          </div>
          <div class="progress progress-bar-container" style="height: 10px;">
            <div class="progress-bar bg-warning" role="progressbar" style="width: 0%" id="pendingProgressBar"></div>
          </div>
        </div>
        
        <div class="d-flex justify-content-between">
          <div id="dueDatesInfo">
            <!-- Will be populated by JavaScript -->
          </div>
          <button class="btn btn-wastepay align-self-center" id="payNowBtn">
            <i class="fas fa-credit-card me-1"></i> Pay Now
          </button>
        </div>
      </div>
      
      <!-- Bills List -->
      <h4 class="mb-3"><i class="fas fa-file-invoice-dollar me-2"></i>Current Bills</h4>
      <div id="billsList" class="row">
        <div class="col-12 text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading bills...</p>
        </div>
      </div>
    </div>
    
    <!-- History Tab -->
    <div id="history" class="tab-content">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-success"><i class="fas fa-history me-2"></i>Payment History</h2>
        <div class="d-flex align-items-center">
          <div class="d-inline-block bg-white p-3 rounded shadow-sm me-3">
            <span class="text-muted">Total Paid:</span>
            <span class="fw-bold fs-4 text-success amount-display" id="totalPaidAmount">0</span>
          </div>
          <button class="btn btn-outline-primary" id="refreshHistoryBtn">
            <i class="fas fa-sync-alt me-1"></i> Refresh
          </button>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="paymentHistoryTable">
                <tr>
                  <td colspan="4" class="text-center">Loading payment history...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // API Configuration
    const API_BASE_URL = 'http://127.0.0.1:8000/api/';
    let authToken = localStorage.getItem('jwt') || '';
    
    // Check authentication on load
    if (!authToken) {
      window.location.href = 'loginn.php';
    }
    
    // Format currency with consistent styling
    function formatCurrency(amount, includeSymbol = true) {
      const formatted = parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
      return includeSymbol ? `Tsh ${formatted}` : formatted;
    }
    
    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
      // Set up tab navigation
      document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
          document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
          
          this.classList.add('active');
          const tabId = this.getAttribute('data-tab');
          document.getElementById(tabId).classList.add('active');
        });
      });
      
      // Set up logout button
      document.getElementById('logoutBtn').addEventListener('click', function() {
        localStorage.removeItem('jwt');
        window.location.href = 'loginn.php';
      });
      
      // Set up profile button
      document.getElementById('profileBtn').addEventListener('click', showProfileModal);
      
      // Set up pay now button
      document.getElementById('payNowBtn').addEventListener('click', showPaymentModal);
      
      // Set up refresh history button
      document.getElementById('refreshHistoryBtn').addEventListener('click', loadPaymentHistory);
      
      // Load data
      loadUserData();
      loadDebts();
      loadPaymentHistory();
    });
    
    // Load user data
    async function loadUserData() {
      try {
        // Show loading state
        const welcomeElement = document.getElementById('welcomeUserName');
        if (welcomeElement) {
          welcomeElement.textContent = 'Loading...';
        }
        
        // Fetch from API
        const response = await fetch(`${API_BASE_URL}auth/user/`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${authToken}`,
            'Content-Type': 'application/json'
          }
        });
        
        // Handle unauthorized (401) responses
        if (response.status === 401) {
          localStorage.removeItem('jwt');
          window.location.href = 'loginn.php';
          return;
        }
        
        if (!response.ok) {
          throw new Error(`Failed to fetch user data: ${response.status}`);
        }
        
        const userData = await response.json();
        
        // Update user information in the UI
        const displayName = userData.fullname || userData.username;
        document.getElementById('userFullname').textContent = displayName;
        document.getElementById('welcomeUserName').textContent = displayName;
        
        // Update email and phone if available
        if (userData.email) {
          document.getElementById('userEmail').innerHTML = 
            `<i class="fas fa-envelope me-1"></i>${userData.email}`;
        }
        
        if (userData.phone) {
          const phoneElement = document.getElementById('userPhone');
          phoneElement.innerHTML = userData.email ? 
            `<br><i class="fas fa-phone me-1"></i>${userData.phone}` : 
            `<i class="fas fa-phone me-1"></i>${userData.phone}`;
        }
        
        // Add house number if available
        if (userData.house_number) {
          const contactInfo = document.getElementById('userContactInfo');
          contactInfo.innerHTML += 
            `<br><i class="fas fa-home me-1"></i>House No: ${userData.house_number}`;
        }

      } catch (error) {
        console.error('Error loading user data:', error);
        
        // Fallback display
        document.getElementById('userFullname').textContent = 'User';
        document.getElementById('welcomeUserName').textContent = 'User';
        
        // Only show error if it's not an authorization issue
        if (!error.message.includes('401')) {
          Swal.fire({
            icon: 'error',
            title: 'Failed to load user data',
            text: 'Could not load your profile information',
            timer: 3000
          });
        }
      }
    }

    // Show user profile modal
    function showProfileModal() {
      // Fetch user data again to ensure we have the latest
      fetch(`${API_BASE_URL}auth/user/`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json'
        }
      })
      .then(response => response.json())
      .then(userData => {
        const profileHtml = `
          <div class="text-center mb-4">
            <i class="fas fa-user-circle fa-5x text-primary"></i>
            <h4 class="mt-3">${userData.fullname || userData.username}</h4>
          </div>
          <div class="row">
            <div class="col-md-6">
              <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> ${userData.email || 'Not provided'}</p>
              <p><strong><i class="fas fa-phone me-2"></i>Phone:</strong> ${userData.phone || 'Not provided'}</p>
            </div>
            <div class="col-md-6">
              <p><strong><i class="fas fa-home me-2"></i>House Number:</strong> ${userData.house_number || 'Not provided'}</p>
              <p><strong><i class="fas fa-user me-2"></i>Username:</strong> ${userData.username}</p>
            </div>
          </div>
        `;
        
        Swal.fire({
          title: 'User Profile',
          html: profileHtml,
          confirmButtonText: 'Close',
          width: '600px'
        });
      })
      .catch(error => {
        console.error('Error fetching profile data:', error);
        Swal.fire({
          icon: 'error',
          title: 'Failed to load profile',
          text: 'Could not load your profile information',
        });
      });
    }

    // Load debts data
    async function loadDebts() {
      try {
        // Show loading state
        document.getElementById('billsList').innerHTML = `
          <div class="col-12 text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading bills...</p>
          </div>
        `;
        
        // Fetch from API
        const response = await fetch(`${API_BASE_URL}payments/debts/`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${authToken}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (!response.ok) {
          throw new Error('Failed to fetch debts');
        }
        
        const debts = await response.json();
        
        // Calculate summary stats
        let overdue = 0;
        let pending = 0;
        let paidCount = 0;
        let totalDebt = 0;
        let monthlyPayment = 0;
        
        debts.forEach(debt => {
          if (debt.status === 'overdue') overdue += debt.balance;
          if (debt.status === 'pending') pending += debt.balance;
          if (debt.status === 'paid') paidCount++;
          totalDebt += debt.balance;
          monthlyPayment = debt.amount; // Simplified
        });
        
        // Update UI with formatted amounts
        document.getElementById('overdueAmount').textContent = formatCurrency(overdue, false);
        document.getElementById('pendingAmount').textContent = formatCurrency(pending, false);
        document.getElementById('paidBillsCount').textContent = paidCount;
        document.getElementById('monthlyPaymentAmount').textContent = formatCurrency(monthlyPayment, false);
        
        document.getElementById('overdueAmountSummary').textContent = formatCurrency(overdue, false);
        document.getElementById('pendingAmountSummary').textContent = formatCurrency(pending, false);
        
        // Show/hide overdue section
        if (overdue > 0) {
          document.getElementById('overdueSection').style.display = 'block';
          document.getElementById('actionRequiredBadge').style.display = 'inline-block';
        }
        
        // Update progress bar
        document.getElementById('pendingProgressBar').style.width = `${(pending / Math.max(totalDebt, 1)) * 100}%`;
        
        // Update due dates
        let overdueDate = '';
        let dueDate = '';
        
        debts.forEach(debt => {
          if (debt.status === 'overdue' && !overdueDate) {
            overdueDate = formatDate(new Date(debt.due_date));
          }
          if (debt.status === 'pending' && !dueDate) {
            dueDate = formatDate(new Date(debt.due_date));
          }
        });
        
        let dueDatesHtml = '';
        if (overdueDate) {
          dueDatesHtml += `<p class="mb-1"><i class="fas fa-calendar-exclamation me-2 text-danger"></i>Overdue Since: ${overdueDate}</p>`;
        }
        if (dueDate) {
          dueDatesHtml += `<p class="mb-0"><i class="fas fa-calendar-day me-2 text-warning"></i>Due Date: ${dueDate}</p>`;
        }
        document.getElementById('dueDatesInfo').innerHTML = dueDatesHtml;
        
        // Render bills
        renderBills(debts);
        
      } catch (error) {
        console.error('Error loading debts:', error);
        document.getElementById('billsList').innerHTML = `
          <div class="col-12">
            <div class="alert alert-danger">
              Failed to load bills. Please try again later.
            </div>
          </div>
        `;
      }
    }
    
    // Render bills list
    function renderBills(debts) {
      if (debts.length === 0) {
        document.getElementById('billsList').innerHTML = `
          <div class="col-12">
            <div class="alert alert-info">
              You don't have any bills at this time.
            </div>
          </div>
        `;
        return;
      }
      
      let html = '';
      
      debts.forEach(debt => {
        const date = new Date(debt.due_date);
        const statusClass = debt.status === 'overdue' ? 'overdue' : 
                          debt.status === 'paid' ? 'paid' : 'pending';
        
        html += `
          <div class="col-md-6">
            <div class="card debt-card ${statusClass}">
              <div class="card-body">
                <h5 class="card-title">Amount: <strong class="amount-display">${formatCurrency(debt.amount, false)}</strong></h5>
                <p class="card-text mb-2">
                  <strong>Balance:</strong> <span class="amount-display">${formatCurrency(debt.balance, false)}</span><br>
                  <strong>Due Date:</strong> ${formatDate(date)}<br>
                  <strong>Status:</strong> <span class="badge badge-${debt.status}">${capitalizeFirstLetter(debt.status)}</span>
                </p>
                ${debt.status === 'pending' ? `
                <button class="btn btn-wastepay pay-btn" 
                  data-id="${debt.id}" 
                  data-amount="${debt.balance}">
                  <i class="fas fa-credit-card me-1"></i> Pay <span class="amount-display">${formatCurrency(debt.balance, false)}</span>
                </button>
                ` : ''}
              </div>
            </div>
          </div>
        `;
      });
      
      document.getElementById('billsList').innerHTML = html;
      
      // Attach event listeners to pay buttons
      document.querySelectorAll('.pay-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const debtId = btn.dataset.id;
          const amount = btn.dataset.amount;
          initiatePayment(debtId, amount);
        });
      });
    }
    
    // Load payment history from API
    async function loadPaymentHistory() {
      try {
        // Show loading state
        document.getElementById('paymentHistoryTable').innerHTML = `
          <tr>
            <td colspan="4" class="text-center">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2">Loading payment history...</p>
            </td>
          </tr>
        `;

        // Disable refresh button during load
        const refreshBtn = document.getElementById('refreshHistoryBtn');
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Loading...';

        // Fetch from API
        const response = await fetch(`${API_BASE_URL}payments/status/`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${authToken}`,
            'Content-Type': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error('Failed to fetch payment history');
        }

        const paymentHistory = await response.json();

        // Calculate total paid amount (only completed payments)
        const totalPaid = paymentHistory
          .filter(payment => payment.status === 'completed')
          .reduce((sum, payment) => sum + parseFloat(payment.amount), 0);
        
        document.getElementById('totalPaidAmount').textContent = formatCurrency(totalPaid, false);

        // Render payment history
        let html = '';
        if (paymentHistory.length === 0) {
          html = `
            <tr>
              <td colspan="4" class="text-center text-muted">No payment history found</td>
            </tr>
          `;
        } else {
          paymentHistory.forEach(payment => {
            const date = new Date(payment.payment_date);
            const statusClass = payment.status === 'completed' ? 'badge-completed' : 
                              payment.status === 'failed' ? 'badge-failed' : 'badge-pending';
            const statusText = payment.status === 'completed' ? 'Completed' : 
                             payment.status === 'failed' ? 'Failed' : 'Pending';
            
            html += `
              <tr class="payment-row" data-payment-id="${payment.reference}">
                <td>${formatDateTime(date)}</td>
                <td class="fw-bold ${payment.status === 'completed' ? 'text-success' : 'text-secondary'} amount-display">
                  ${formatCurrency(payment.amount, false)}
                </td>
                <td>${payment.method || 'M-Pesa'}</td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
              </tr>
            `;
          });
        }

        document.getElementById('paymentHistoryTable').innerHTML = html;

        // Add click handlers to payment rows
        document.querySelectorAll('.payment-row').forEach(row => {
          row.addEventListener('click', () => {
            const paymentId = row.getAttribute('data-payment-id');
            showPaymentDetails(paymentId);
          });
        });

      } catch (error) {
        console.error('Error loading payment history:', error);
        document.getElementById('paymentHistoryTable').innerHTML = `
          <tr>
            <td colspan="4" class="text-center text-danger">Failed to load payment history</td>
          </tr>
        `;
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to load payment history. Please try again later.',
          timer: 3000
        });
      } finally {
        // Re-enable refresh button
        const refreshBtn = document.getElementById('refreshHistoryBtn');
        refreshBtn.disabled = false;
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Refresh';
      }
    }

    // Show payment details in a modal
    async function showPaymentDetails(paymentId) {
      try {
        // Fetch payment details from API
        const response = await fetch(`${API_BASE_URL}payments/details/${paymentId}/`, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${authToken}`,
            'Content-Type': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error('Failed to fetch payment details');
        }

        const paymentDetails = await response.json();

        // Show details in a modal
        Swal.fire({
          title: 'Payment Details',
          html: `
            <div class="text-start">
              <p><strong>Reference:</strong> ${paymentDetails.reference || 'N/A'}</p>
              <p><strong>Amount:</strong> ${formatCurrency(paymentDetails.amount)}</p>
              <p><strong>Method:</strong> ${paymentDetails.method || 'M-Pesa'}</p>
              <p><strong>Status:</strong> <span class="badge ${paymentDetails.status === 'completed' ? 'badge-completed' : 
                                         paymentDetails.status === 'failed' ? 'badge-failed' : 'badge-pending'}">
                ${paymentDetails.status === 'completed' ? 'Completed' : 
                 paymentDetails.status === 'failed' ? 'Failed' : 'Pending'}
              </span></p>
              <p><strong>Date:</strong> ${formatDateTime(new Date(paymentDetails.payment_date))}</p>
              ${paymentDetails.transaction_id ? `<p><strong>Transaction ID:</strong> ${paymentDetails.transaction_id}</p>` : ''}
            </div>
          `,
          confirmButtonText: 'Close',
          width: '600px'
        });

      } catch (error) {
        console.error('Error showing payment details:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to load payment details.',
          timer: 3000
        });
      }
    }

    // Show payment modal for "Pay Now" button
    async function showPaymentModal() {
      try {
        // Get mobile number input
        const { value: mobile } = await Swal.fire({
          title: 'Enter your mobile number',
          input: 'text',
          inputLabel: 'Format: 255XXXXXXXXX',
          inputPlaceholder: '255712345678',
          inputAttributes: { 
            maxlength: 12,
            autocapitalize: 'off',
            autocorrect: 'off'
          },
          showCancelButton: true,
          confirmButtonText: 'Proceed to Pay',
          cancelButtonText: 'Cancel',
          inputValidator: (value) => {
            if (!value) return 'Please enter your mobile number';
            if (!/^255\d{9}$/.test(value)) return 'Enter valid number (255XXXXXXXXX)';
          }
        });

        if (!mobile) return;

        // Calculate total amount to pay
        const overdue = parseFloat(document.getElementById('overdueAmountSummary').textContent.replace(/,/g, '')) || 0;
        const pending = parseFloat(document.getElementById('pendingAmountSummary').textContent.replace(/,/g, '')) || 0;
        const totalAmount = overdue + pending;

        if (totalAmount <= 0) {
          await Swal.fire('Info', 'You have no outstanding payments to make', 'info');
          return;
        }

        // Confirm payment
        const { isConfirmed } = await Swal.fire({
          title: 'Confirm Payment',
          html: `You are about to pay <strong>${formatCurrency(totalAmount)}</strong> to WastePay.<br><br>Phone: ${mobile}`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Confirm Payment',
          cancelButtonText: 'Cancel'
        });

        if (!isConfirmed) return;

        // Process payment
        await processPaymentRequest({
          amount: totalAmount,
          mobile_number: mobile,
          debt_id: 'all' // Indicates paying all outstanding
        });

      } catch (error) {
        console.error('Payment error:', error);
        Swal.fire('Error', error.message || 'Payment failed', 'error');
      }
    }

    // Initiate payment from bill card
    async function initiatePayment(debtId, amount) {
      try {
        // Get mobile number input
        const { value: mobile } = await Swal.fire({
          title: 'Enter your mobile number',
          input: 'text',
          inputLabel: 'Format: 255XXXXXXXXX',
          inputPlaceholder: '255712345678',
          inputAttributes: { 
            maxlength: 12,
            autocapitalize: 'off',
            autocorrect: 'off'
          },
          showCancelButton: true,
          confirmButtonText: 'Proceed to Pay',
          cancelButtonText: 'Cancel',
          inputValidator: (value) => {
            if (!value) return 'Please enter your mobile number';
            if (!/^255\d{9}$/.test(value)) return 'Enter valid number (255XXXXXXXXX)';
          }
        });

        if (!mobile) return;

        // Confirm payment
        const { isConfirmed } = await Swal.fire({
          title: 'Confirm Payment',
          html: `You are about to pay <strong>${formatCurrency(amount)}</strong> to WastePay.<br><br>Phone: ${mobile}`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Confirm Payment',
          cancelButtonText: 'Cancel'
        });

        if (!isConfirmed) return;

        // Process payment
        await processPaymentRequest({
          amount: parseFloat(amount),
          mobile_number: mobile,
          debt_id: debtId
        });

      } catch (error) {
        console.error('Payment error:', error);
        Swal.fire('Error', error.message || 'Payment failed', 'error');
      }
    }

    // Unified payment processing function
    async function processPaymentRequest(paymentData) {
      let processingSwal;
      
      try {
        // Show processing modal
        processingSwal = Swal.fire({
          title: 'Processing Payment',
          html: 'Please wait while we process your payment...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        // Debug log
        console.log('Sending payment request:', paymentData);

        // Make API call
        const response = await fetch(`${API_BASE_URL}payments/pay/`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authToken}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            amount: paymentData.amount,
            payment_method: 'mno',
            mobile_number: paymentData.mobile_number,
            debt_id: paymentData.debt_id
          })
        });

        // Get response data
        const responseData = await response.json().catch(() => ({}));
        
        // Debug log
        console.log('Payment response:', { status: response.status, data: responseData });

        if (!response.ok) {
          throw new Error(responseData.detail || `Payment failed with status ${response.status}`);
        }

        // Close processing modal
        await processingSwal.close();

        // Show success
        await Swal.fire({
          icon: 'success',
          title: 'Payment Initiated',
          text: 'Please check your phone to complete the payment',
          timer: 3000,
          showConfirmButton: false
        });

        // Refresh data
        loadDebts();
        loadPaymentHistory();

      } catch (error) {
        console.error('Payment processing error:', error);
        
        // Close processing modal if it exists
        if (processingSwal) {
          await processingSwal.close();
        }
        
        // Show error
        await Swal.fire({
          icon: 'error',
          title: 'Payment Failed',
          text: error.message || 'Could not process payment. Please try again.',
          confirmButtonText: 'OK'
        });
      }
    }

    // Helper functions
    function formatDate(date) {
      return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
      });
    }
    
    function formatDateTime(date) {
      return date.toLocaleString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
    
    function capitalizeFirstLetter(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    }
  </script>
</body>
</html>