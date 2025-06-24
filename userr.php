<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['users'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['users']['id']; // Correct session key

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastepay_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user debts
$debts = $conn->query("SELECT * FROM debts WHERE user_id = $user_id ORDER BY due_date ASC");

// Calculate total debt
$total_debt = 0;
$overdue = 0;
$pending = 0;
while ($debt = $debts->fetch_assoc()) {
    $total_debt += $debt['amount'];
    if ($debt['status'] == 'overdue') {
        $overdue += $debt['amount'];
    } else if ($debt['status'] == 'pending') {
        $pending += $debt['amount'];
    }
}
$debts->data_seek(0); // Reset for reuse

// Get payments
$payments = $conn->query("SELECT * FROM payments WHERE user_id = $user_id ORDER BY payment_date DESC LIMIT 4");

// Get notifications
$notifications = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 3");
$unread_count = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND status = 'unread'")
    ->fetch_assoc()['count'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle payment submission
    if (isset($_POST['make_payment'])) {
        $amount = floatval($_POST['amount']);
        $method = $conn->real_escape_string($_POST['payment_method']);
        $reference = $conn->real_escape_string($_POST['reference']);

        $conn->begin_transaction();

        try {
            // Insert into payments
            $stmt = $conn->prepare("INSERT INTO payments (user_id, amount, method, reference) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idss", $user_id, $amount, $method, $reference);
            $stmt->execute();

            // Pay off debts
            $debt_query = $conn->query("SELECT * FROM debts WHERE user_id = $user_id AND status IN ('overdue', 'pending') ORDER BY due_date ASC");
            $remaining = $amount;

            while ($debt = $debt_query->fetch_assoc()) {
                if ($remaining <= 0) break;

                if ($debt['amount'] <= $remaining) {
                    $stmt = $conn->prepare("UPDATE debts SET status = 'paid' WHERE id = ?");
                    $stmt->bind_param("i", $debt['id']);
                    $stmt->execute();
                    $remaining -= $debt['amount'];
                }
            }

            // Send notification
            $message = "Payment of Tsh " . number_format($amount, 0) . " received via $method.";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'payment')");
            $stmt->bind_param("is", $user_id, $message);
            $stmt->execute();

            $conn->commit();
            $payment_success = "Payment of Tsh " . number_format($amount, 0) . " processed successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $payment_error = "Payment failed: " . $e->getMessage();
        }
    }

    // Handle suggestion form
    if (isset($_POST['submit_suggestion'])) {
        $category = $conn->real_escape_string($_POST['category']);
        $suggestion = $conn->real_escape_string($_POST['suggestion']);

        $stmt = $conn->prepare("INSERT INTO suggestions (user_id, category, suggestion) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $category, $suggestion);
        $stmt->execute();

        $suggestion_success = "Thank you for your suggestion! We'll review it soon.";
    }

    // Handle installment request
    if (isset($_POST['request_installment'])) {
        $debt_id = intval($_POST['debt_id']);
        $plan = $conn->real_escape_string($_POST['plan']);
        $reason = $conn->real_escape_string($_POST['reason']);

        $stmt = $conn->prepare("INSERT INTO installment_requests (user_id, debt_id, plan, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $debt_id, $plan, $reason);
        $stmt->execute();

        $installment_success = "Your installment plan request has been submitted for review.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WastePay - Household Waste Payment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #60ad5e;
            --primary-dark: #1b5e20;
            --secondary: #ff9800;
            --accent: #f44336;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --card-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f7f0;
            color: var(--dark-text);
            background: linear-gradient(135deg, #f0f7f0 0%, #e0f2e1 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .notification-btn {
            position: relative;
            color: white;
            font-size: 1.2rem;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .notification-dropdown {
            max-height: 400px;
            overflow-y: auto;
            width: 350px;
        }
        
        .notification-item {
            border-left: 3px solid var(--accent);
            transition: all 0.3s ease;
        }
        
        .notification-item:hover {
            background-color: rgba(244, 67, 54, 0.05);
            transform: translateX(3px);
        }
        
        .dashboard-card {
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            overflow: hidden;
            background: white;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .debt-card {
            border-left: 4px solid var(--accent);
        }
        
        .history-card {
            border-left: 4px solid var(--secondary);
        }
        
        .payment-card {
            border-left: 4px solid var(--primary);
        }
        
        .suggestion-card {
            border-left: 4px solid #9c27b0;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-pending {
            background-color: #ffecb3;
            color: #ff9800;
        }
        
        .badge-paid {
            background-color: #c8e6c9;
            color: #2e7d32;
        }
        
        .badge-overdue {
            background-color: #ffcdd2;
            color: #d32f2f;
        }
        
        .btn-wastepay {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .btn-wastepay:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }
        
        .btn-outline-wastepay {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
            font-weight: 600;
        }
        
        .btn-outline-wastepay:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        
        .chart-container {
            height: 250px;
            position: relative;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }
        
        .payment-method:hover, .payment-method.active {
            border-color: var(--primary);
            background-color: rgba(46, 125, 50, 0.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .payment-method i {
            font-size: 2rem;
            margin-right: 15px;
            color: var(--primary);
        }
        
        footer {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: white;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .installment-option {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }
        
        .installment-option:hover, .installment-option.active {
            border-color: var(--primary);
            background-color: rgba(46, 125, 50, 0.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .suggestion-box {
            min-height: 120px;
            border-radius: 10px;
        }
        
        .debt-progress {
            height: 10px;
            border-radius: 5px;
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
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        
        .balance-card {
            background: linear-gradient(135deg, #1b5e20 0%, var(--primary) 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .action-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 25px 15px;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
        }
        
        .action-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .history-table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .history-table thead {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .bills-container {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            padding: 25px;
        }
        
        .bill-card {
            border-left: 4px solid var(--primary);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .bill-card:hover {
            transform: translateX(5px);
        }
        
        .bill-card.overdue {
            border-left-color: var(--accent);
            background: rgba(244, 67, 54, 0.05);
        }
        
        .bill-card.paid {
            border-left-color: #2e7d32;
            background: rgba(46, 125, 50, 0.05);
        }
        
        .stat-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 15px;
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
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-tab="dashboard"><i class="fas fa-home me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-tab="history"><i class="fas fa-history me-1"></i> History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-tab="bills"><i class="fas fa-file-invoice-dollar me-1"></i> Bills</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> <?php echo $user['fullname']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-question-circle me-2"></i> Help</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="log_out.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link notification-btn" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown p-2">
                            <li class="mb-2">
                                <h6 class="dropdown-header fw-bold">Notifications</h6>
                            </li>
                            <?php while ($notification = $notifications->fetch_assoc()): ?>
                            <li>
                                <div class="notification-item p-3 mb-2 bg-light rounded">
                                    <div class="d-flex justify-content-between">
                                        <?php if ($notification['type'] == 'debt'): ?>
                                        <span class="badge badge-overdue status-badge">Debt</span>
                                        <?php elseif ($notification['type'] == 'payment'): ?>
                                        <span class="badge badge-paid status-badge">Payment</span>
                                        <?php else: ?>
                                        <span class="badge badge-pending status-badge">Info</span>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            <?php 
                                            $date = new DateTime($notification['created_at']);
                                            echo $date->format('M d, Y');
                                            ?>
                                        </small>
                                    </div>
                                    <p class="mb-0 mt-2"><?php echo $notification['message']; ?></p>
                                </div>
                            </li>
                            <?php endwhile; ?>
                            <?php $notifications->data_seek(0); ?>
                            <li class="mt-2">
                                <a href="#" class="btn btn-outline-wastepay w-100">View All Notifications</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content active">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-home me-2"></i> Welcome back, <?php echo $user['fullname']; ?>!</h2>
                        <p class="mb-0">Manage your waste payments efficiently and sustainably.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-inline-block bg-white p-3 rounded shadow-sm text-dark">
                            <span class="text-muted">payment per month:</span><br>
                            <span class="fw-bold fs-4 text-danger">Tsh <?php echo number_format($total_debt, 0, '.', ','); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Overview -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="dashboard-card text-center p-4">
                        <div class="stat-circle overdue-stat">
                            Tsh <?php echo number_format($overdue, 0, '.', ','); ?>
                        </div>
                        <h5>Overdue</h5>
                        <p class="text-muted">Amount past due date</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="dashboard-card text-center p-4">
                        <div class="stat-circle pending-stat">
                            Tsh <?php echo number_format($pending, 0, '.', ','); ?>
                        </div>
                        <h5>Pending</h5>
                        <p class="text-muted">Current amount due</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="dashboard-card text-center p-4">
                        <div class="stat-circle paid-stat">
                            <?php 
                            $paid_count = $conn->query("SELECT COUNT(*) as count FROM payments WHERE user_id = $user_id")->fetch_assoc()['count'];
                            echo $paid_count;
                            ?>
                        </div>
                        <h5>Paid Bills</h5>
                        <p class="text-muted">Successful payments</p>
                    </div>
                </div>
            </div>
            
            <!-- Debt Summary Card -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="dashboard-card debt-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Debt Summary</h4>
                            <?php if ($overdue > 0): ?>
                            <span class="badge bg-danger">Action Required</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($overdue > 0): ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Overdue Amount</span>
                                <span class="fw-bold text-danger">Tsh <?php echo number_format($overdue, 0, '.', ','); ?></span>
                            </div>
                            <div class="progress debt-progress">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Current Due</span>
                                <span class="fw-bold text-warning">Tsh <?php echo number_format($pending, 0, '.', ','); ?></span>
                            </div>
                            <div class="progress debt-progress">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo min(($pending / max($total_debt, 1)) * 100, 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <div>
                                <?php 
                                $overdue_date = '';
                                $due_date = '';
                                
                                // Reset debts pointer
                                $debts->data_seek(0);
                                while ($debt = $debts->fetch_assoc()) {
                                    if ($debt['status'] == 'overdue') {
                                        $overdue_date = (new DateTime($debt['due_date']))->format('M d, Y');
                                    }
                                    if ($debt['status'] == 'pending' && $due_date == '') {
                                        $due_date = (new DateTime($debt['due_date']))->format('M d, Y');
                                    }
                                }
                                $debts->data_seek(0); // Reset for later use
                                
                                if ($overdue_date): ?>
                                <p class="mb-1"><i class="fas fa-calendar-exclamation me-2 text-danger"></i>Overdue Since: <?php echo $overdue_date; ?></p>
                                <?php endif; ?>
                                <?php if ($due_date): ?>
                                <p class="mb-0"><i class="fas fa-calendar-day me-2 text-warning"></i>Due Date: <?php echo $due_date; ?></p>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-wastepay align-self-center" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i class="fas fa-credit-card me-1"></i> Pay Now
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Payment History Card -->
                <div class="col-lg-4">
                    <div class="dashboard-card history-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold"><i class="fas fa-history me-2 text-warning"></i>Recent Payments</h4>
                            <a href="#" class="text-success">View All</a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = $payments->fetch_assoc()): 
                                        $date = new DateTime($payment['payment_date']);
                                    ?>
                                    <tr>
                                        <td><?php echo $date->format('M d'); ?></td>
                                        <td class="fw-bold text-success">Tsh <?php echo number_format($payment['amount'], 0, '.', ','); ?></td>
                                        <td><?php echo $payment['method']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php $payments->data_seek(0); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Cards -->
            <div class="row g-4 mb-4">
                <!-- Payment Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="action-card">
                        <div class="bg-success text-white rounded-circle p-4 mb-3">
                            <i class="fas fa-credit-card fa-2x"></i>
                        </div>
                        <h5 class="fw-bold">Make Payment</h5>
                        <p class="text-muted small">Pay your waste collection bill securely</p>
                        <button class="btn btn-wastepay mt-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
                            Pay Now
                        </button>
                    </div>
                </div>
                
                <!-- Installment Plan Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="action-card">
                        <div class="bg-warning text-white rounded-circle p-4 mb-3">
                            <i class="fas fa-calendar-alt fa-2x"></i>
                        </div>
                        <h5 class="fw-bold">Installment Plan</h5>
                        <p class="text-muted small">Request flexible payment options</p>
                        <button class="btn btn-outline-wastepay mt-2" data-bs-toggle="modal" data-bs-target="#installmentModal">
                            Request Plan
                        </button>
                    </div>
                </div>
                
                <!-- Suggestion Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="action-card">
                        <div class="bg-purple text-white rounded-circle p-4 mb-3" style="background-color: #9c27b0;">
                            <i class="fas fa-lightbulb fa-2x"></i>
                        </div>
                        <h5 class="fw-bold">Suggestions</h5>
                        <p class="text-muted small">Share your ideas for improvement</p>
                        <button class="btn btn-outline-wastepay mt-2" data-bs-toggle="modal" data-bs-target="#suggestionModal">
                            Submit Idea
                        </button>
                    </div>
                </div>
                
                <!-- Support Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="action-card">
                        <div class="bg-info text-white rounded-circle p-4 mb-3">
                            <i class="fas fa-headset fa-2x"></i>
                        </div>
                        <h5 class="fw-bold">Support</h5>
                        <p class="text-muted small">Get help with any issues</p>
                        <button class="btn btn-outline-wastepay mt-2" data-bs-toggle="modal" data-bs-target="#supportModal">
                            Contact Us
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- History Tab -->
        <div id="history" class="tab-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-success"><i class="fas fa-history me-2"></i>Payment History</h2>
                <div class="d-inline-block bg-white p-3 rounded shadow-sm">
                    <span class="text-muted">Total Paid:</span>
                    <span class="fw-bold fs-4 text-success">Tsh <?php 
                    $total_paid = $conn->query("SELECT SUM(amount) as total FROM payments WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
                    echo number_format($total_paid, 0, '.', ','); 
                    ?></span>
                </div>
            </div>
            
            <div class="history-table">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $all_payments = $conn->query("SELECT * FROM payments WHERE user_id = $user_id ORDER BY payment_date DESC");
                        while ($payment = $all_payments->fetch_assoc()): 
                            $date = new DateTime($payment['payment_date']);
                        ?>
                        <tr>
                            <td><?php echo $date->format('M d, Y H:i'); ?></td>
                            <td class="fw-bold text-success">Tsh <?php echo number_format($payment['amount'], 0, '.', ','); ?></td>
                            <td><?php echo $payment['method']; ?></td>
                            <td><small class="text-muted"><?php echo $payment['reference']; ?></small></td>
                            <td><span class="badge bg-success">Completed</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Bills Tab -->
        <div id="bills" class="tab-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-success"><i class="fas fa-file-invoice-dollar me-2"></i>My Bills</h2>
                <div class="d-inline-block bg-white p-3 rounded shadow-sm">
                    <span class="text-muted">Total Due:</span>
                    <span class="fw-bold fs-4 text-danger">Tsh <?php echo number_format($total_debt, 0, '.', ','); ?></span>
                </div>
            </div>
            
            <div class="bills-container">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="mb-4">Current Bills</h4>
                        
                        <?php while ($debt = $debts->fetch_assoc()): 
                            $date = new DateTime($debt['due_date']);
                            $status_class = $debt['status'] == 'overdue' ? 'overdue' : ($debt['status'] == 'paid' ? 'paid' : '');
                        ?>
                        <div class="bill-card <?php echo $status_class; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">Waste Collection Fee</h5>
                                    <p class="mb-0">Period: <?php echo $date->format('F Y'); ?></p>
                                </div>
                                <div class="text-end">
                                    <h4 class="mb-1 <?php echo $debt['status'] == 'overdue' ? 'text-danger' : 'text-dark'; ?>">
                                        Tsh <?php echo number_format($debt['amount'], 0, '.', ','); ?>
                                    </h4>
                                    <span class="badge <?php 
                                        if ($debt['status'] == 'overdue') echo 'bg-danger';
                                        elseif ($debt['status'] == 'paid') echo 'bg-success';
                                        else echo 'bg-warning';
                                    ?>">
                                        <?php echo ucfirst($debt['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <small class="text-muted">Due Date: <?php echo $date->format('M d, Y'); ?></small>
                                </div>
                                <div>
                                    <?php if ($debt['status'] != 'paid'): ?>
                                    <button class="btn btn-sm btn-wastepay">Pay Now</button>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-success" disabled>Paid</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php $debts->data_seek(0); ?>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="dashboard-card p-4">
                            <h4 class="mb-4">Billing Summary</h4>
                            
                            <div class="mb-4">
                                <h6>Payment Methods</h6>
                                <div class="payment-method active">
                                    <i class="fab fa-mpesa"></i>
                                    <div>
                                        <h6 class="mb-0">M-Pesa</h6>
                                    </div>
                                </div>
                                <div class="payment-method">
                                    <i class="fab fa-cc-visa"></i>
                                    <div>
                                        <h6 class="mb-0">Credit/Debit Card</h6>
                                    </div>
                                </div>
                                <div class="payment-method">
                                    <i class="fas fa-university"></i>
                                    <div>
                                        <h6 class="mb-0">Bank Transfer</h6>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6>Payment Statistics</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>On-time Payments</span>
                                    <span class="fw-bold">85%</span>
                                </div>
                                <div class="progress debt-progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 85%"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Average Payment</span>
                                    <span class="fw-bold">Tsh 120,000</span>
                                </div>
                            </div>
                            
                            <button class="btn btn-wastepay w-100" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i class="fas fa-credit-card me-1"></i> Make Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5 class="fw-bold"><i class="fas fa-recycle me-2"></i>WastePay</h5>
                    <p>Efficient household waste payment system for sustainable habitats and cleaner environments.</p>
                </div>
                <div class="col-md-2 mb-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">Dashboard</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Payments</a></li>
                        <li><a href="#" class="text-white text-decoration-none">History</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Support</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-3">
                    <h6>Contact Us</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> support@wastepay.com</li>
                        <li><i class="fas fa-phone me-2"></i> +255 712 345678</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Dar es Salaam, Tanzania</li>
                    </ul>
                </div>
                <div class="col-md-3 mb-3">
                    <h6>Connect With Us</h6>
                    <div class="d-flex">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-2x"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-2x"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-2x"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin fa-2x"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-white">
            <div class="text-center">
                <p class="mb-0">&copy; 2025 WastePay. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Make Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php if (isset($payment_success)): ?>
                        <div class="alert alert-success"><?php echo $payment_success; ?></div>
                        <?php elseif (isset($payment_error)): ?>
                        <div class="alert alert-danger"><?php echo $payment_error; ?></div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Payment Summary</h6>
                                <div class="card border-0 bg-light p-3 mb-4">
                                    <table class="table table-sm mb-0">
                                        <?php if ($overdue > 0): ?>
                                        <tr>
                                            <th>Overdue Amount</th>
                                            <td class="text-danger fw-bold">Tsh <?php echo number_format($overdue, 0, '.', ','); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <th>Current Due</th>
                                            <td class="fw-bold">Tsh <?php echo number_format($pending, 0, '.', ','); ?></td>
                                        </tr>
                                        <tr class="fw-bold">
                                            <th>Total Payable</th>
                                            <td class="text-success">Tsh <?php echo number_format($total_debt, 0, '.', ','); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <h6>Select Payment Method</h6>
                                <div class="mb-3">
                                    <div class="payment-method active" onclick="selectPaymentMethod('M-Pesa')">
                                        <i class="fab fa-mpesa"></i>
                                        <div>
                                            <h6 class="mb-0">M-Pesa</h6>
                                            <small class="text-muted">Pay via M-Pesa mobile money</small>
                                        </div>
                                    </div>
                                    <div class="payment-method" onclick="selectPaymentMethod('Credit Card')">
                                        <i class="fab fa-cc-visa"></i>
                                        <div>
                                            <h6 class="mb-0">Credit/Debit Card</h6>
                                            <small class="text-muted">Visa, Mastercard, etc.</small>
                                        </div>
                                    </div>
                                    <div class="payment-method" onclick="selectPaymentMethod('Bank Transfer')">
                                        <i class="fas fa-university"></i>
                                        <div>
                                            <h6 class="mb-0">Bank Transfer</h6>
                                            <small class="text-muted">Direct bank transfer</small>
                                        </div>
                                    </div>
                                    <input type="hidden" id="payment_method" name="payment_method" value="M-Pesa">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Payment Details</h6>
                                <div class="mb-3">
                                    <label class="form-label">Amount to Pay (Tsh)</label>
                                    <input type="number" class="form-control" name="amount" value="<?php echo $total_debt; ?>" min="1000" step="1000" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" value="<?php echo $user['phone']; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Payment Reference</label>
                                    <input type="text" class="form-control" name="reference" value="WP-<?php echo time(); ?>" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="make_payment" class="btn btn-wastepay">
                                        <i class="fas fa-paper-plane me-2"></i> Initiate Payment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Installment Plan Modal -->
    <div class="modal fade" id="installmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Installment Plan Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php if (isset($installment_success)): ?>
                        <div class="alert alert-success"><?php echo $installment_success; ?></div>
                        <?php endif; ?>
                        <div class="mb-4">
                            <p>You can request to pay your outstanding balance of <span class="fw-bold">Tsh <?php echo number_format($total_debt, 0, '.', ','); ?></span> in installments. Select a debt to create a plan for:</p>
                            
                            <div class="mb-3">
                                <label class="form-label">Select Debt</label>
                                <select class="form-select" name="debt_id" required>
                                    <?php while ($debt = $debts->fetch_assoc()): 
                                        $date = new DateTime($debt['due_date']);
                                        $status_class = $debt['status'] == 'overdue' ? 'text-danger' : ($debt['status'] == 'paid' ? 'text-success' : 'text-warning');
                                    ?>
                                    <option value="<?php echo $debt['id']; ?>">
                                        Tsh <?php echo number_format($debt['amount'], 0, '.', ','); ?> - 
                                        Due: <?php echo $date->format('M d, Y'); ?> - 
                                        <span class="<?php echo $status_class; ?>"><?php echo ucfirst($debt['status']); ?></span>
                                    </option>
                                    <?php endwhile; ?>
                                    <?php $debts->data_seek(0); ?>
                                </select>
                            </div>
                            
                            <h6 class="mb-3">Choose Installment Plan</h6>
                            <div class="installment-option active" onclick="selectInstallmentPlan('2 Months Plan')">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="plan" id="option1" value="2 Months Plan" checked>
                                    <label class="form-check-label fw-bold" for="option1">
                                        2 Months Plan
                                    </label>
                                </div>
                                <p class="mb-0 mt-2">Tsh <?php echo number_format($total_debt / 2, 0, '.', ','); ?> per month for 2 months</p>
                            </div>
                            
                            <div class="installment-option" onclick="selectInstallmentPlan('3 Months Plan')">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="plan" id="option2" value="3 Months Plan">
                                    <label class="form-check-label fw-bold" for="option2">
                                        3 Months Plan
                                    </label>
                                </div>
                                <p class="mb-0 mt-2">Tsh <?php echo number_format($total_debt / 3, 0, '.', ','); ?> per month for 3 months</p>
                            </div>
                            
                            <div class="installment-option" onclick="selectInstallmentPlan('Custom Plan')">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="plan" id="option3" value="Custom Plan">
                                    <label class="form-check-label fw-bold" for="option3">
                                        Custom Plan
                                    </label>
                                </div>
                                <p class="mb-0 mt-2">Suggest your own installment plan</p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason for Installment Request</label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="Explain why you need an installment plan..." required></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="request_installment" class="btn btn-wastepay">
                                <i class="fas fa-paper-plane me-2"></i> Submit Request
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Suggestion Modal -->
    <div class="modal fade" id="suggestionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-purple text-white" style="background-color: #9c27b0;">
                    <h5 class="modal-title"><i class="fas fa-lightbulb me-2"></i>Submit Suggestion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php if (isset($suggestion_success)): ?>
                        <div class="alert alert-success"><?php echo $suggestion_success; ?></div>
                        <?php endif; ?>
                        <p>We value your feedback! Please share your suggestions to help us improve our service.</p>
                        
                        <div class="mb-3">
                            <label class="form-label">Suggestion Category</label>
                            <select class="form-select" name="category" required>
                                <option value="" selected disabled>Select category</option>
                                <option>Payment Process</option>
                                <option>Billing System</option>
                                <option>User Interface</option>
                                <option>Waste Collection</option>
                                <option>Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Your Suggestion</label>
                            <textarea class="form-control suggestion-box" name="suggestion" placeholder="Type your suggestion here..." required></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="submit_suggestion" class="btn btn-wastepay" style="background-color: #9c27b0;">
                                <i class="fas fa-paper-plane me-2"></i> Submit Suggestion
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Support Modal -->
    <div class="modal fade" id="supportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-headset me-2"></i>Contact Support</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Our support team is ready to help you with any issues or questions.</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" placeholder="What is your inquiry about?">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="4" placeholder="Describe your issue or question..."></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button class="btn btn-wastepay">
                            <i class="fas fa-paper-plane me-2"></i> Send Message
                        </button>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6>Other Support Options</h6>
                    <ul>
                        <li>Email: support@wastepay.com</li>
                        <li>Phone: +255 712 345678</li>
                        <li>Live Chat: Available during business hours</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment method selection
        function selectPaymentMethod(method) {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById('payment_method').value = method;
        }
        
        // Installment option selection
        function selectInstallmentPlan(plan) {
            document.querySelectorAll('.installment-option').forEach(o => o.classList.remove('active'));
            event.currentTarget.classList.add('active');
            event.currentTarget.querySelector('input').checked = true;
        }
        
        // Initialize with first payment method selected
        document.addEventListener('DOMContentLoaded', function() {
            selectPaymentMethod('M-Pesa');
            selectInstallmentPlan('2 Months Plan');
            
            // Tab navigation
            const navLinks = document.querySelectorAll('.nav-link');
            const tabContents = document.querySelectorAll('.tab-content');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links
                    navLinks.forEach(l => l.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Hide all tab contents
                    tabContents.forEach(tab => tab.classList.remove('active'));
                    
                    // Show the selected tab content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>