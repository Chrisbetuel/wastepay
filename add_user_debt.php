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

// Variables for feedback
$notification_success = "";
$notification_error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user_to_debt'])) {
    $user_id = $_POST['debt_user_id'];
    $amount = $_POST['amount'];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : NULL;
    $status = $_POST['status'];

    // Check if user already has a debt record
    $check = $conn->prepare("SELECT id FROM debts WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $notification_error = "User already has a debt record.";
    } else {
        $insert = $conn->prepare("INSERT INTO debts (user_id, amount, due_date, status) VALUES (?, ?, ?, ?)");
        $insert->bind_param("idss", $user_id, $amount, $due_date, $status);

        if ($insert->execute()) {
            $notification_success = "User added to debt management successfully.";
        } else {
            $notification_error = "Error: " . $conn->error;
        }

        $insert->close();
    }

    $check->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User to Debt Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { background: #f9f9f9; padding: 20px; border-radius: 8px; width: 400px; }
        label { display: block; margin-top: 10px; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; }
        button { margin-top: 15px; padding: 10px 20px; background: green; color: white; border: none; cursor: pointer; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>

    <h2>Add User to Debt Management</h2>

    <?php if (!empty($notification_success)): ?>
        <p class="success"><?php echo $notification_success; ?></p>
    <?php endif; ?>
    <?php if (!empty($notification_error)): ?>
        <p class="error"><?php echo $notification_error; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="debt_user_id">Select User:</label>
        <select name="debt_user_id" required>
            <option value="">-- Choose User --</option>
            <?php
                $users = $conn->query("SELECT id, username FROM users");
                while ($user = $users->fetch_assoc()) {
                    echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['username']) . "</option>";
                }
            ?>
        </select>

        <label for="amount">Initial Amount (TSh):</label>
        <input type="number" step="0.01" name="amount" value="0.00" required>

        <label for="due_date">Due Date:</label>
        <input type="date" name="due_date">

        <label for="status">Status:</label>
        <select name="status">
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
        </select>

        <button type="submit" name="add_user_to_debt">Add to Debt Management</button>
    </form>

</body>
</html>
