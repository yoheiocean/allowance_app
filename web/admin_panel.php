<?php
session_start(); // Start the session

// Define the admin password
$admin_password = "ChangeMe"; // Change this to your desired password

// Check if the admin password is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin-password'])) {
    $submitted_password = $_POST['admin-password'];

    // Check if the submitted password is correct
    if ($submitted_password === $admin_password) {
        $_SESSION['is_admin'] = true; // Set session variable for admin access
    } else {
        echo "<p style='color: red;'>Invalid password. Please try again. Actually, don't even try.</p>";
    }
}

// If user is not logged in as admin, show the login form
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Show the admin login form
    ?>
    <link rel="stylesheet" href="/static/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <img class="dad-pfp" src="/dad.png">
    <h2>Dad's Login</h2>
    <form action="admin_panel.php" method="POST">
        <label for="admin-password">Enter Password:</label>
        <input type="password" id="admin-password" name="admin-password" required>
        <button type="submit">Login</button>
    </form>
    <a href="/index.html">Back to Home</a>
    <?php
    exit(); // Stop execution if not logged in
}

// Connect to SQLite database
$dsn = "sqlite:allowance_tracker.db";
try {
    $pdo = new PDO($dsn);
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Handle form submission for clearing the database
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_database'])) {

    // Delete all transactions
    $sql = "DELETE FROM transactions";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

        // Reset all user balances to zero
        $sql = "UPDATE users SET balance = 0"; // Set the balance of all users to zero
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

    // Feedback to admin
    echo "All records in the database have been cleared successfully!";
}

// Handle form submission for adding transactions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['amount'], $_POST['description'], $_POST['user_id'], $_POST['transaction_type'])) {
    $user_id = $_POST['user_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $transaction_type = $_POST['transaction_type'];

    // Adjust the amount based on the transaction type
    if ($transaction_type == 'withdraw') {
        $amount = -abs($amount); // Make the amount negative for withdrawals
    } // Deposits remain positive

    // Insert transaction into the database
    $sql = "INSERT INTO transactions (user_id, amount, description, transaction_type) VALUES (:user_id, :amount, :description, :transaction_type)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id' => $user_id,
        'amount' => $amount,
        'description' => $description,
        'transaction_type' => $transaction_type
    ]);

    // Update the user's balance in the users table
    $sql = "UPDATE users SET balance = balance + :amount WHERE id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'amount' => $amount,
        'user_id' => $user_id
    ]);

    // Feedback to admin
    echo "Transaction recorded successfully!";
}

// Handle form submission for applying interest
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['interest_percentage'])) {
    $interest_percentage = (float)$_POST['interest_percentage']; // Get the interest percentage

    // Fetch current balances before applying interest
    $sql = "SELECT id, balance FROM users";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        $user_id = $user['id'];
        $current_balance = $user['balance'];
        $interest_amount = $current_balance * ($interest_percentage / 100); // Calculate interest based on the original balance

        // Debugging: Print values
        echo "<pre>";
        echo "User ID: " . $user_id . "\n";
        echo "Current Balance: " . $current_balance . "\n";
        echo "Interest Percentage: " . $interest_percentage . "\n";
        echo "Interest Amount: " . $interest_amount . "\n";
        echo "</pre>";

        // Update the user's balance by adding the interest amount
        $new_balance = $current_balance + $interest_amount; // Calculate new balance

        $sql = "UPDATE users SET balance = :new_balance WHERE id = :user_id"; // Update with new balance
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'new_balance' => $new_balance,
            'user_id' => $user_id
        ]);

        // Insert the interest as a transaction for each user
        $sql = "INSERT INTO transactions (user_id, amount, description, transaction_type) VALUES (:user_id, :amount, :description, 'interest')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'amount' => $interest_amount,
            'description' => 'Interest applied at ' . number_format($interest_percentage, 1) . '%'
        ]);
    }

    // Feedback to admin
    echo "Interest applied and transactions recorded successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Investment Tracker</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>

    <h1>Admin Panel</h1>
    <h2>Add Transaction</h2>
    <form action="admin_panel.php" method="POST">
        <label for="user">Select User:</label>
        <select name="user_id" id="user">
            <option value="1">Child1</option>
            <option value="2">Child2</option>
        </select>

    <select name="transaction_type" id="transaction_type" required>
        <option value="deposit">Deposit</option>
        <option value="withdraw">Withdraw</option>
    </select>
    <br>
        <label for="amount">Amount:</label>
        <input type="number" name="amount" id="amount" step="0.01" placeholder="0.00" required>
    <br>
        <label for="description">Description:</label>
        <input type="text" name="description" id="description" placeholder="Transaction memo" required>
    <br>
        <button type="submit" name="submit_transaction">Submit Transaction</button>
    </form>

    <!-- Interest Application Form -->
    <h2>Apply Interest to All Users</h2>
    <form action="admin_panel.php" method="POST">
        <label for="interest_percentage">Interest Percentage:</label>
        <input type="number" name="interest_percentage" id="interest_percentage" step="0.01" placeholder="e.g. 2.5" required>
    <br>
        <button type="submit" name="apply_interest">Apply Interest</button>
    </form>
    <!-- Clear Database Form -->
    <h2>Clear All Transaction Data (Development Purpose)</h2>
    <form action="admin_panel.php" method="POST">
        <button type="submit" name="clear_database" onclick="return confirm('Are you sure you want to clear all data from the database? This action cannot be undone.');">Clear Database</button>
    </form>

    <h2>Dashboards</h2>
    <p><a href="dashboard.php?user_id=1">Go to Child1's Dashboard</a></p>
    <p><a href="dashboard.php?user_id=2">Go to Child2's Dashboard</a></p>
    <p><a href="index.html">Go to HOME</a></p>
    <p><a href="logout.php">Logout</a></p> <!-- Link to logout -->

</body>
</html>
