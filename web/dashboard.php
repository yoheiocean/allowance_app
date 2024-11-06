<?php
// Connect to SQLite database
$dsn = "sqlite:allowance_tracker.db";
try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Get the user_id from the query parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;

// Get the current page from the query parameter (default is 1)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Set the number of items per page
$items_per_page = 10;

// Calculate the offset for the SQL query
$offset = ($page - 1) * $items_per_page;

// Fetch user details
$sql = "SELECT name FROM users WHERE id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user exists
if ($user === false) {
    echo "<p>User not found. Please check the user ID.</p>";
    exit(); // Stop further execution if user doesn't exist
}

// Fetch user balance directly from users table
$sql = "SELECT balance FROM users WHERE id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$user_balance = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the total balance
$sql = "SELECT SUM(amount) as total_balance FROM transactions WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$balance = $stmt->fetch(PDO::FETCH_ASSOC);

// Use null coalescing to set default balance if not found
$total_balance = number_format($balance['total_balance'] ?? 0, 2);

// Fetch transaction history with pagination
$sql = "SELECT * FROM transactions WHERE user_id = :user_id ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

// Bind user_id as integer
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
// Bind limit and offset as integers
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Execute the statement
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count the total number of transactions
$sql = "SELECT COUNT(*) FROM transactions WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$total_transactions = $stmt->fetchColumn();

// Calculate the total number of pages
$total_pages = ceil($total_transactions / $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['name']); ?>'s Dashboard - Investment Tracker</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <h1><?php echo htmlspecialchars($user['name']); ?></h1>

    <!-- Display Total Balance -->
    <h2>Current Balance: <span class="current-balance">$<?php echo number_format($balance['total_balance'] ?? 0, 2); ?></span></h2>

    <!-- Links to switch between children -->
    <p><a href="dashboard.php?user_id=1">Child1's Bankbook</a> | <a href="dashboard.php?user_id=2">Child2's Bankbook</a></p>

    <!-- Display Transaction History -->
    <h3>Transaction History:</h3>

    <ul>
        <?php foreach ($transactions as $transaction): ?>
            <li class="transaction-history <?php echo htmlspecialchars($transaction['transaction_type']); ?>">
                <div class="transaction-amount">
                    <?php
                    $className = htmlspecialchars($transaction['transaction_type']); // transaction type
                    if ($className === 'interest' || $className === 'deposit') {
                        echo '<span class="' . htmlspecialchars($className) . '">&#x25B2;</span>';
                    } else {
                        echo '<span class="' . htmlspecialchars($className) . '">&#x25BC;</span>';
                    }
                    ?>
                    <span class="amount">$<?php echo number_format($transaction['amount'], 2); ?> </span>
                </div>
                <div class="transaction-description">
                    <span class="date"><?php echo date('Y-m-d', strtotime($transaction['timestamp'])); ?> </span>
                    <br>
                    <span class="transaction-type"><?php echo htmlspecialchars(ucfirst($transaction['transaction_type'])); ?>:</span><br>
                    <span class="description"><?php echo htmlspecialchars($transaction['description']); ?> </span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Pagination Links -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&user_id=<?php echo $user_id; ?>">Previous</a> 
        <?php endif; ?>
        <?php if ($page > 1 && $page < $total_pages): ?>
             | 
        <?php endif; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&user_id=<?php echo $user_id; ?>">Next</a>
        <?php endif; ?>
    </div>

    <!-- Dad's Page -->
    <p class="dad"><a href="admin_panel.php">Dad's Page</a></p>
    <script src="static/js/reload.js"></script>
</body>
</html>
