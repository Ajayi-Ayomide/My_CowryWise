<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if plan ID is provided
if (!isset($_GET['plan'])) {
    header("Location: ../dashboard.php");
    exit();
}

$plan_id = $_GET['plan'];
$errors = [];
$success = false;

// Get investment plan details
$stmt = $conn->prepare("SELECT * FROM investment_plans WHERE id = ?");
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plan) {
    header("Location: ../dashboard.php");
    exit();
}

// Get user's savings account
$stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ? AND account_type = 'savings'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    
    // Validation
    if ($amount < $plan['minimum_amount']) {
        $errors[] = "Amount must be at least ₦" . number_format($plan['minimum_amount'], 2);
    }
    
    if ($amount > $account['balance']) {
        $errors[] = "Insufficient funds in your savings account";
    }

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Create investment record
            $start_date = date('Y-m-d');
            $maturity_date = date('Y-m-d', strtotime("+{$plan['duration_months']} months"));
            
            $stmt = $conn->prepare("
                INSERT INTO user_investments 
                (user_id, investment_plan_id, amount, start_date, maturity_date) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iidss", $_SESSION['user_id'], $plan_id, $amount, $start_date, $maturity_date);
            $stmt->execute();

            // Create transaction record
            $reference = 'INV' . time() . rand(1000, 9999);
            $stmt = $conn->prepare("
                INSERT INTO transactions 
                (account_id, transaction_type, amount, reference_number, status) 
                VALUES (?, 'investment', ?, ?, 'completed')
            ");
            $stmt->bind_param("ids", $account['id'], $amount, $reference);
            $stmt->execute();

            // Update account balance
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $account['id']);
            $stmt->execute();

            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "An error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invest - CowryWise Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">CowryWise Demo</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Invest in <?php echo htmlspecialchars($plan['name']); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Investment successful! <a href="../dashboard.php">Return to Dashboard</a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-0"><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h5>Investment Plan Details</h5>
                            <p class="mb-1"><?php echo htmlspecialchars($plan['description']); ?></p>
                            <p class="mb-1">Interest Rate: <?php echo $plan['interest_rate']; ?>% per annum</p>
                            <p class="mb-1">Duration: <?php echo $plan['duration_months']; ?> months</p>
                            <p class="mb-1">Minimum Investment: ₦<?php echo number_format($plan['minimum_amount'], 2); ?></p>
                            <p class="mb-1">Risk Level: 
                                <span class="badge bg-<?php echo $plan['risk_level'] === 'low' ? 'success' : ($plan['risk_level'] === 'medium' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($plan['risk_level']); ?>
                                </span>
                            </p>
                        </div>

                        <div class="mb-4">
                            <h5>Your Savings Account</h5>
                            <p class="mb-1">Account Number: <?php echo htmlspecialchars($account['account_number']); ?></p>
                            <p class="mb-1">Available Balance: ₦<?php echo number_format($account['balance'], 2); ?></p>
                        </div>

                        <?php if (!$success): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Investment Amount (₦)</label>
                                    <input type="number" name="amount" class="form-control" 
                                           min="<?php echo $plan['minimum_amount']; ?>" 
                                           max="<?php echo $account['balance']; ?>" 
                                           step="0.01" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Invest Now</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 