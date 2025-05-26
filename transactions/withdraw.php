<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if account ID is provided
if (!isset($_GET['account'])) {
    header("Location: ../dashboard.php");
    exit();
}

$account_id = $_GET['account'];
$errors = [];
$success = false;

// Verify account belongs to user
$stmt = $conn->prepare("SELECT * FROM accounts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $account_id, $_SESSION['user_id']);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    header("Location: ../dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    
    if ($amount <= 0) {
        $errors[] = "Amount must be greater than 0";
    }
    
    if ($amount > $account['balance']) {
        $errors[] = "Insufficient funds";
    }

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Create transaction record
            $reference = 'WTH' . time() . rand(1000, 9999);
            $stmt = $conn->prepare("INSERT INTO transactions (account_id, transaction_type, amount, reference_number, status) VALUES (?, 'withdrawal', ?, ?, 'completed')");
            $stmt->bind_param("ids", $account_id, $amount, $reference);
            $stmt->execute();

            // Update account balance
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $account_id);
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
    <title>Withdraw - CowryWise Demo</title>
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
                        <h3 class="text-center">Withdraw Money</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                Withdrawal successful! <a href="../dashboard.php">Return to Dashboard</a>
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
                            <h5>Account Details</h5>
                            <p class="mb-1">Account Number: <?php echo htmlspecialchars($account['account_number']); ?></p>
                            <p class="mb-1">Available Balance: ₦<?php echo number_format($account['balance'], 2); ?></p>
                        </div>

                        <?php if (!$success): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Amount to Withdraw (₦)</label>
                                    <input type="number" name="amount" class="form-control" min="0" max="<?php echo $account['balance']; ?>" step="0.01" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Withdraw</button>
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