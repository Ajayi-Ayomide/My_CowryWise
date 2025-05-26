<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Get user's accounts
$stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user's recent transactions
$stmt = $conn->prepare("
    SELECT t.*, a.account_number 
    FROM transactions t 
    JOIN accounts a ON t.account_id = a.id 
    WHERE a.user_id = ? 
    ORDER BY t.created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get investment plans
$result = $conn->query("SELECT * FROM investment_plans");
$investment_plans = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CowryWise Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">CowryWise Demo</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Your Accounts</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($accounts as $account): ?>
                            <div class="border p-3 mb-3 rounded">
                                <h5><?php echo ucfirst($account['account_type']); ?> Account</h5>
                                <p class="mb-1">Account Number: <?php echo htmlspecialchars($account['account_number']); ?></p>
                                <p class="mb-1">Balance: ₦<?php echo number_format($account['balance'], 2); ?></p>
                                <div class="mt-2">
                                    <a href="transactions/deposit.php?account=<?php echo $account['id']; ?>" class="btn btn-primary btn-sm">Deposit</a>
                                    <a href="transactions/withdraw.php?account=<?php echo $account['id']; ?>" class="btn btn-secondary btn-sm">Withdraw</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Recent Transactions</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($transactions)): ?>
                            <p>No recent transactions</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                                                <td><?php echo ucfirst($transaction['transaction_type']); ?></td>
                                                <td>₦<?php echo number_format($transaction['amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($transaction['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Investment Plans</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($investment_plans as $plan): ?>
                            <div class="border p-3 mb-3 rounded">
                                <h5><?php echo htmlspecialchars($plan['name']); ?></h5>
                                <p class="mb-1">Interest Rate: <?php echo $plan['interest_rate']; ?>%</p>
                                <p class="mb-1">Duration: <?php echo $plan['duration_months']; ?> months</p>
                                <p class="mb-1">Minimum Amount: ₦<?php echo number_format($plan['minimum_amount'], 2); ?></p>
                                <p class="mb-2">Risk Level: 
                                    <span class="badge bg-<?php echo $plan['risk_level'] === 'low' ? 'success' : ($plan['risk_level'] === 'medium' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($plan['risk_level']); ?>
                                    </span>
                                </p>
                                <a href="investments/invest.php?plan=<?php echo $plan['id']; ?>" class="btn btn-primary btn-sm">Invest Now</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 