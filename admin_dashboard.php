<?php
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

// Handle form submissions
$notification_success = "";
$notification_error = "";
$user_deleted = false;
$debt_updated = false;
$bulk_update_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle user deletion
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $user_deleted = true;
        }
        $stmt->close();
    }

    // Handle notification sending
    if (isset($_POST['send_notification'])) {
        $user_id = $_POST['user_id'];
        $message = trim($_POST['message']);
        $type = $_POST['type'];

        if (empty($message)) {
            $notification_error = "Please enter a notification message.";
        } else {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $message, $type);
            if ($stmt->execute()) {
                $notification_success = "Notification sent successfully!";
            } else {
                $notification_error = "Error sending notification: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // Handle user registration
    if (isset($_POST['register_user'])) {
        $username = $_POST['username'];
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert user into users table
            $stmt = $conn->prepare("INSERT INTO users (username, fullname, email, phone, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $fullname, $email, $phone, $password);

            if (!$stmt->execute()) {
                throw new Exception("Error registering user: " . $conn->error);
            }

            $new_user_id = $conn->insert_id;
            $stmt->close();

            // Check if debt already exists for this user
            $check_stmt = $conn->prepare("SELECT id FROM debts WHERE user_id = ?");
            $check_stmt->bind_param("i", $new_user_id);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows === 0) {
                // No existing debt, insert one
                $initial_amount = 0.00;
                $due_date = NULL;
                $status = 'pending';
                $debt_stmt = $conn->prepare("INSERT INTO debts (user_id, amount, due_date, status) VALUES (?, ?, ?, ?)");
                $debt_stmt->bind_param("idss", $new_user_id, $initial_amount, $due_date, $status);
                if (!$debt_stmt->execute()) {
                    throw new Exception("Error creating debt record: " . $conn->error);
                }
                $debt_stmt->close();
            }

            $check_stmt->close();
            $conn->commit();
            $notification_success = "User registered and debt record initialized successfully!";

        } catch (Exception $e) {
            $conn->rollback();
            $notification_error = $e->getMessage();
        }
    }

    // Handle single debt update
    if (isset($_POST['update_debt'])) {
        $debt_id = $_POST['debt_id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE debts SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $debt_id);
        if ($stmt->execute()) {
            $debt_updated = true;
        }
        $stmt->close();
    }

    // Handle bulk debt status update
    if (isset($_POST['bulk_update_debts'])) {
        $status = $_POST['bulk_status'];

        $stmt = $conn->prepare("UPDATE debts SET status = ?");
        $stmt->bind_param("s", $status);
        if ($stmt->execute()) {
            $bulk_update_success = true;
        }
        $stmt->close();
    }
}

// Fetch counts
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_payments = $conn->query("SELECT SUM(amount) as total FROM payments")->fetch_assoc()['total'] ?? 0;
$total_debts = $conn->query("SELECT SUM(amount) as total FROM debts WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;

// Fetch users
$users = $conn->query("SELECT * FROM users ORDER BY reg_date DESC");

// Fetch payments
$payments = $conn->query("SELECT p.*, u.username FROM payments p LEFT JOIN users u ON p.user_id = u.id ORDER BY payment_date DESC LIMIT 10");

// Fetch debts
$debts = $conn->query("SELECT d.*, u.username FROM debts d LEFT JOIN users u ON d.user_id = u.id ORDER BY due_date DESC LIMIT 10");

// Fetch notifications
$notifications = $conn->query("SELECT n.*, u.username FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY created_at DESC LIMIT 10");

// Weekly Report
$weekly_start = date('Y-m-d', strtotime('monday this week'));
$weekly_end = date('Y-m-d', strtotime('sunday this week'));

$weekly_payments_result = $conn->query("SELECT DATE(payment_date) as day, SUM(amount) as total FROM payments WHERE payment_date BETWEEN '$weekly_start' AND '$weekly_end' GROUP BY DATE(payment_date)");
$weekly_payments = [];
while ($row = $weekly_payments_result->fetch_assoc()) {
    $weekly_payments[$row['day']] = $row['total'];
}

// Monthly Report
$monthly_start = date('Y-m-01');
$monthly_end = date('Y-m-t');

$monthly_payments_result = $conn->query("SELECT DATE(payment_date) as day, SUM(amount) as total FROM payments WHERE payment_date BETWEEN '$monthly_start' AND '$monthly_end' GROUP BY DATE(payment_date)");
$monthly_payments = [];
while ($row = $monthly_payments_result->fetch_assoc()) {
    $monthly_payments[$row['day']] = $row['total'];
}

// Recent Activity
$recent_activity = $conn->query("
    (SELECT 'user' as type, id, fullname as title, CONCAT('Registered on ', reg_date) as description, reg_date as date FROM users ORDER BY reg_date DESC LIMIT 2)
    UNION
    (SELECT 'payment' as type, id, CONCAT('Payment of Tsh ', FORMAT(amount, 0)) as title, CONCAT('Via ', method) as description, payment_date as date FROM payments ORDER BY payment_date DESC LIMIT 2)
    UNION
    (SELECT 'notification' as type, id, 'Notification sent' as title, message as description, created_at as date FROM notifications ORDER BY created_at DESC LIMIT 1)
    ORDER BY date DESC LIMIT 5
");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - WastePay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #60ad5e;
            --secondary: #ff9800;
            --accent: #f44336;
            --dark: #343a40;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f7f0;
            color: #212529;
            overflow-x: hidden;
            
        }
        
        .navbar {
            background-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 100;
            position: relative;
        }
        .sidebar {
            height: 100vh;
            background-color: var(--dark);
            padding-top: 20px;
            width: 260px;
            position: fixed;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 90;
            left: 0;
            top: 0;
        }
        .sidebar a {
            color: #ffffff;
            font-weight: 500;
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0 30px 30px 0;
            margin-bottom: 5px;
            position: relative;
            z-index: 95;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: var(--primary);
            color: white;
            transform: translateX(5px);
        }
        .sidebar a i {
            width: 24px;
            text-align: center;
            margin-right: 12px;
        }
        .main-content {
            margin-left: 280px;
            padding: 30px;
            flex-grow: 1;
            transition: all 0.3s ease;
            position: relative;
            z-index: 50;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: none;
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
            z-index: 60;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }
        .card h5 {
            font-weight: 700;
            color: var(--primary);
        }
        .btn-wastepay {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 70;
        }
        .btn-wastepay:hover {
            background-color: var(--primary-light);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .btn-outline-wastepay {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }
        .btn-outline-wastepay:hover {
            background-color: var(--primary);
            color: white;
        }
        .table thead th {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        .table-striped>tbody>tr:nth-of-type(odd)>* {
            --bs-table-bg-type: rgba(46, 125, 50, 0.05);
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 5px rgba(46, 125, 50, 0.25);
            outline: none;
        }
        footer {
            margin-top: 3rem;
            text-align: center;
            color: #6c757d;
            padding: 20px 0;
            background-color: rgba(0,0,0,0.03);
            border-radius: 15px;
            position: relative;
            z-index: 60;
        }
        /* Scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--primary-light);
            border-radius: 4px;
        }
        /* Tab content */
        .tab-content > div {
            display: none;
        }
        .tab-content > div.active {
            display: block;
        }
        .stat-card {
            text-align: center;
            padding: 25px 15px;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background-color: var(--primary);
        }
        .stat-card h3 {
            font-weight: 800;
            margin: 15px 0;
            color: var(--primary);
        }
        .stat-card i {
            font-size: 2.5rem;
            color: var(--primary-light);
            opacity: 0.8;
        }
        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
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
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .notification-item {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }
        .notification-item.unread {
            background-color: rgba(46, 125, 50, 0.08);
            border-left: 4px solid var(--accent);
        }
        .toggle-sidebar {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 110;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .user-activity { background-color: rgba(52, 152, 219, 0.2); color: #3498db; }
        .payment-activity { background-color: rgba(46, 204, 113, 0.2); color: #2ecc71; }
        .notification-activity { background-color: rgba(155, 89, 182, 0.2); color: #9b59b6; }
        .debt-update-card {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            position: relative;
            z-index: 70;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .status-current {
            background-color: #ff9800;
            color: #333;
        }
        .status-overdue {
            background-color: #f44336;
            color: white;
        }
        .status-paid {
            background-color: #4caf50;
            color: white;
        }
        .bulk-update-btn {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            border: none;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .bulk-update-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Fix for sidebar overlapping */
        @media (min-width: 993px) {
            .sidebar {
                z-index: 90;
            }
            .main-content {
                margin-left: 280px;
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 100;
            }
            .sidebar.active {
                transform: translateX(0);
                width: 260px;
                box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .toggle-sidebar {
                display: flex;
                z-index: 110;
            }
            /* Overlay when sidebar is active */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.5);
                z-index: 95;
            }
            .sidebar.active ~ .sidebar-overlay {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Toggle Sidebar Button -->
    <button class="toggle-sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="fas fa-recycle me-2"></i>
                <span>WastePay Admin</span>
            </a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#" title="Logout">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>


    <!-- Dashboard Tab -->
    <div id="dashboard" class="tab-pane active">
        <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Dashboard Overview</h2>
                <div>
                    <span class="me-2">Today: <?php echo date('F j, Y'); ?></span>
                </div>
            </div>
            
            <div class="row gy-4 mb-5">
                <div class="col-md-4">
                    <div class="card stat-card">
                        <i class="fas fa-users"></i>
                        <h5>Total Users</h5>
                        <h3><?php echo $total_users; ?></h3>
                        <p><i class="fas fa-arrow-up text-success me-1"></i> 12% from last month</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card">
                        <i class="fas fa-dollar-sign"></i>
                        <h5>Total Payments</h5>
                        <h3>Tsh <?php echo number_format($total_payments, 0, '.', ','); ?></h3>
                        <p><i class="fas fa-arrow-up text-success me-1"></i> 8% from last month</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <h5>Pending Debts</h5>
                        <h3>Tsh <?php echo number_format($total_debts, 0, '.', ','); ?></h3>
                        <p><i class="fas fa-arrow-down text-danger me-1"></i> 5% from last month</p>
                    </div>
                </div>
            </div>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php while ($activity = $recent_activity->fetch_assoc()): 
                            $icon_class = "";
                            $bg_class = "";
                            $icon = "";
                            
                            if ($activity['type'] == 'user') {
                                $icon_class = "user-activity";
                                $bg_class = "bg-primary";
                                $icon = "fas fa-user-plus";
                            } elseif ($activity['type'] == 'payment') {
                                $icon_class = "payment-activity";
                                $bg_class = "bg-success";
                                $icon = "fas fa-credit-card";
                            } else {
                                $icon_class = "notification-activity";
                                $bg_class = "bg-warning";
                                $icon = "fas fa-bell";
                            }
                            
                            $date = new DateTime($activity['date']);
                        ?>
                        <div class="d-flex border-bottom pb-3 mb-3">
                            <div class="activity-icon <?php echo $icon_class; ?>">
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo $activity['title']; ?></h6>
                                <p class="mb-0 text-muted"><?php echo $activity['description']; ?></p>
                                <small><?php echo $date->format('M j, g:i a'); ?></small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <a href="#" data-tab="register" class="card h-100 text-center p-4 text-decoration-none">
                                        <div class="bg-primary text-white rounded-circle p-3 mx-auto mb-3">
                                            <i class="fas fa-user-plus fa-2x"></i>
                                        </div>
                                        <h6>Add User</h6>
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="#" data-tab="notifications" class="card h-100 text-center p-4 text-decoration-none">
                                        <div class="bg-success text-white rounded-circle p-3 mx-auto mb-3">
                                            <i class="fas fa-bell fa-2x"></i>
                                        </div>
                                        <h6>Send Notification</h6>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="#" data-tab="payments" class="card h-100 text-center p-4 text-decoration-none">
                                        <div class="bg-info text-white rounded-circle p-3 mx-auto mb-3">
                                            <i class="fas fa-dollar-sign fa-2x"></i>
                                        </div>
                                        <h6>View Payments</h6>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="#" data-tab="debts" class="card h-100 text-center p-4 text-decoration-none">
                                        <div class="bg-warning text-white rounded-circle p-3 mx-auto mb-3">
                                            <i class="fas fa-file-invoice-dollar fa-2x"></i>
                                        </div>
                                        <h6>Manage Debts</h6>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Tab -->
        <div id="users" class="tab-pane">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>User Management</h2>
                <div>
                    <button class="btn btn-wastepay" data-tab="register">
                        <i class="fas fa-user-plus me-1"></i> Add New User
                    </button>
                </div>
            </div>
            
            <?php if ($user_deleted): ?>
            <div class="alert alert-success">User deleted successfully!</div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($user['reg_date'])); ?></td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td class="action-buttons">
                                        <a href="#" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                        <a href="#" class="btn btn-sm btn-primary" title="Message">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Register User Tab -->
        <div id="register" class="tab-pane">
            <h2 class="mb-4">Register New User</h2>

            <?php if ($notification_success): ?>
                <div class="alert alert-success"><?php echo $notification_success; ?></div>
            <?php endif; ?>
            <?php if ($notification_error): ?>
                <div class="alert alert-danger"><?php echo $notification_error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="#register" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label for="fullname" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="fullname" name="fullname" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" name="register_user" class="btn btn-wastepay">
                            <i class="fas fa-user-plus me-1"></i> Register User
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payments Tab -->
        <div id="payments" class="tab-pane">
            <h2 class="mb-4">Payment History</h2>
            
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Payments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $payments->data_seek(0); ?>
                                <?php while ($payment = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['username']); ?></td>
                                    <td class="fw-bold text-success">Tsh <?php echo number_format($payment['amount'], 0, '.', ','); ?></td>
                                    <td><?php echo htmlspecialchars($payment['method']); ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo $payment['reference']; ?></span></td>
                                    <td><?php echo date('M j, H:i', strtotime($payment['payment_date'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debts Tab -->
        <div id="debts" class="tab-pane">
            <h2 class="mb-4">Debt Management</h2>
            
            <?php if ($debt_updated): ?>
                <div class="alert alert-success">Debt status updated successfully!</div>
            <?php endif; ?>
            
            <?php if ($bulk_update_success): ?>
                <div class="alert alert-success">Bulk debt status update completed!</div>
            <?php endif; ?>
            
            <div class="debt-update-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="text-white mb-2"><i class="fas fa-sync-alt me-2"></i> Update Debt Status for All Users</h4>
                        <p class="mb-0">Bulk update all debts in the system to a new status</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <form method="POST" class="d-flex align-items-center justify-content-end">
                            <select class="form-select me-2" name="bulk_status" style="max-width: 150px;">
                                <option value="pending">Current</option>
                                <option value="overdue">Overdue</option>
                                <option value="paid">Paid</option>
                            </select>
                            <button type="submit" name="bulk_update_debts" class="btn bulk-update-btn">
                                <i class="fas fa-bolt me-1"></i> Update All
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Debts</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $debts->data_seek(0); ?>
                                <?php while ($debt = $debts->fetch_assoc()): 
                                    $status_class = $debt['status'] == 'overdue' ? 'status-overdue' : 
                                                  ($debt['status'] == 'paid' ? 'status-paid' : 'status-current');
                                    $due_date = new DateTime($debt['due_date']);
                                ?>
                                <tr>
                                    <td><?php echo $debt['id']; ?></td>
                                    <td><?php echo htmlspecialchars($debt['username']); ?></td>
                                    <td class="fw-bold">Tsh <?php echo number_format($debt['amount'], 0, '.', ','); ?></td>
                                    <td><?php echo $due_date->format('M j, Y'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($debt['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <button class="btn btn-sm btn-primary update-debt" 
                                            data-id="<?php echo $debt['id']; ?>"
                                            data-status="<?php echo $debt['status']; ?>">
                                            <i class="fas fa-sync"></i> Update
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div id="notifications" class="tab-pane">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Notification Center</h2>
                <button class="btn btn-wastepay" data-bs-toggle="modal" data-bs-target="#sendNotificationModal">
                    <i class="fas fa-paper-plane me-1"></i> Send Notification
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-5 mb-4">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Send Notification</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($notification_success): ?>
                                <div class="alert alert-success"><?php echo $notification_success; ?></div>
                            <?php endif; ?>
                            <?php if ($notification_error): ?>
                                <div class="alert alert-danger"><?php echo $notification_error; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" action="#notifications">
                                <div class="mb-3">
                                    <label class="form-label">Select User</label>
                                    <select class="form-select" name="user_id" required>
                                        <option value="0">All Users</option>
                                        <?php 
                                        $users->data_seek(0); // Reset pointer
                                        while ($user = $users->fetch_assoc()): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['fullname']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notification Type</label>
                                    <select class="form-select" name="type" required>
                                        <option value="info">Information</option>
                                        <option value="payment">Payment Reminder</option>
                                        <option value="debt">Debt Alert</option>
                                        <option value="system">System Update</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Message</label>
                                    <textarea class="form-control" name="message" rows="5" placeholder="Type your notification message..." required></textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="reset" class="btn btn-outline-secondary me-2">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </button>
                                    <button type="submit" name="send_notification" class="btn btn-wastepay">
                                        <i class="fas fa-paper-plane me-1"></i> Send Notification
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Notifications</h5>
                            <button class="btn btn-sm btn-outline-primary" id="refresh-notifications">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <?php while ($note = $notifications->fetch_assoc()): 
                                $type_class = $note['type'] == 'debt' ? 'danger' : ($note['type'] == 'payment' ? 'success' : 'info');
                                $status_class = $note['status'] == 'unread' ? 'unread' : '';
                                $date = new DateTime($note['created_at']);
                            ?>
                            <div class="notification-item <?php echo $status_class; ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="badge bg-<?php echo $type_class; ?> me-2">
                                            <?php echo ucfirst($note['type']); ?>
                                        </span>
                                        <span class="text-muted">To: <?php echo htmlspecialchars($note['username']); ?></span>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $date->format('M j, H:i'); ?>
                                    </small>
                                </div>
                                <p class="mb-0"><?php echo htmlspecialchars($note['message']); ?></p>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p class="mb-0">&copy; 2025 WastePay. All rights reserved.</p>
    </footer>

    <!-- Send Notification Modal -->
    <div class="modal fade" id="sendNotificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i> Send Notification</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="#notifications">
                        <div class="mb-3">
                            <label class="form-label">Select User</label>
                            <select class="form-select" name="user_id" required>
                                <option value="0">All Users</option>
                                <?php 
                                $users->data_seek(0); // Reset pointer
                                while ($user = $users->fetch_assoc()): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['fullname']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notification Type</label>
                            <select class="form-select" name="type" required>
                                <option value="info">Information</option>
                                <option value="payment">Payment Reminder</option>
                                <option value="debt">Debt Alert</option>
                                <option value="system">System Update</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="4" placeholder="Type your notification message..." required></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <button type="submit" name="send_notification" class="btn btn-wastepay">
                                <i class="fas fa-paper-plane me-1"></i> Send Notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Debt Modal -->
    <div class="modal fade" id="updateDebtModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-sync me-2"></i> Update Debt Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="#debts">
                        <input type="hidden" name="debt_id" id="update_debt_id">
                        <div class="mb-4">
                            <label class="form-label">Update Debt Status</label>
                            <select class="form-select" name="status" id="update_debt_status" required>
                                <option value="pending">Pending</option>
                                <option value="current">Current</option>
                                <option value="overdue">Overdue</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <button type="submit" name="update_debt" class="btn btn-wastepay">
                                <i class="fas fa-save me-1"></i> Update Debt
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab navigation handling
        const tabs = document.querySelectorAll('.sidebar a');
        const panes = document.querySelectorAll('.tab-pane');

        tabs.forEach(tab => {
            tab.addEventListener('click', e => {
                e.preventDefault();

                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const target = tab.getAttribute('data-tab');
                panes.forEach(pane => {
                    if (pane.id === target) {
                        pane.classList.add('active');
                    } else {
                        pane.classList.remove('active');
                    }
                });
                
                // Close sidebar on mobile after selecting
                if (window.innerWidth < 992) {
                    document.querySelector('.sidebar').classList.remove('active');
                    document.querySelector('.sidebar-overlay').style.display = 'none';
                }
            });
        });
        
        // Toggle sidebar on mobile
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            if (document.querySelector('.sidebar').classList.contains('active')) {
                document.querySelector('.sidebar-overlay').style.display = 'block';
            } else {
                document.querySelector('.sidebar-overlay').style.display = 'none';
            }
        });
        
        // Close sidebar when clicking overlay
        document.querySelector('.sidebar-overlay').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('active');
            this.style.display = 'none';
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.toggle-sidebar');
            
            if (window.innerWidth < 992 && sidebar.classList.contains('active') && 
                !sidebar.contains(event.target) && event.target !== toggleBtn) {
                sidebar.classList.remove('active');
                document.querySelector('.sidebar-overlay').style.display = 'none';
            }
        });
        
        // Quick action cards functionality
        document.querySelectorAll('[data-tab]').forEach(card => {
            card.addEventListener('click', function(e) {
                if (this.tagName === 'A') {
                    e.preventDefault();
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Update active tab
                    tabs.forEach(t => t.classList.remove('active'));
                    document.querySelector(`.sidebar a[data-tab="${targetTab}"]`).classList.add('active');
                    
                    panes.forEach(pane => {
                        if (pane.id === targetTab) {
                            pane.classList.add('active');
                        } else {
                            pane.classList.remove('active');
                        }
                    });
                }
            });
        });
        
        // Update debt functionality
        document.querySelectorAll('.update-debt').forEach(btn => {
            btn.addEventListener('click', function() {
                const debtId = this.getAttribute('data-id');
                const status = this.getAttribute('data-status');
                
                document.getElementById('update_debt_id').value = debtId;
                document.getElementById('update_debt_status').value = status;
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('updateDebtModal'));
                modal.show();
            });
        });
        
        // Refresh notifications
        document.getElementById('refresh-notifications').addEventListener('click', function() {
            location.reload();
        });
        
        // Initialize with dashboard active
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('dashboard').classList.add('active');
            document.querySelector('.sidebar a[data-tab="dashboard"]').classList.add('active');
        });
    </script>
</body>
</html>