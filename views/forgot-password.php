<?php
require_once '../config/database.php';
require_once '../config/env.php';

$message = '';
$error = '';
$show_password_form = false;
$verified_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Email verification
    if (isset($_POST['email']) && !isset($_POST['new_password'])) {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $error = 'Email address is required';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            try {
                $database = new Database();
                $conn = $database->getConnection();

                // Check if user exists
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $show_password_form = true;
                    $verified_email = $email;
                    $message = 'Email verified! Please enter your new password below.';
                } else {
                    $error = 'No account found with that email address.';
                }
            } catch (Exception $e) {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
    // Step 2: Password reset
    else if (isset($_POST['new_password']) && isset($_POST['verified_email'])) {
        $verified_email = trim($_POST['verified_email']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($new_password)) {
            $error = 'New password is required';
            $show_password_form = true;
        } else if (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long';
            $show_password_form = true;
        } else if ($new_password !== $confirm_password) {
            $error = 'Passwords do not match';
            $show_password_form = true;
        } else {
            try {
                $database = new Database();
                $conn = $database->getConnection();

                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $verified_email]);

                $message = 'Password updated successfully! You can now <a href="login.php">login</a> with your new password.';
            } catch (Exception $e) {
                $error = 'Failed to update password. Please try again.';
                $show_password_form = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Cheche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php" style="text-decoration: none; color: inherit;">
                    <h2>Cheche</h2>
                </a>
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="login.php" class="btn-secondary">Login</a>
            </div>
        </div>
    </nav>

    <div class="form-container">
        <h2>Forgot Your Password?</h2>
        <?php if (!$show_password_form): ?>
        <p style="text-align: center; color: #666; margin-bottom: 2rem;">
            Enter your email address to verify your account and reset your password.
        </p>
        <?php else: ?>
        <p style="text-align: center; color: #666; margin-bottom: 2rem;">
            Email verified! Now enter your new password.
        </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if (!$message || $show_password_form): ?>
        <form method="POST" action="">
            <?php if (!$show_password_form): ?>
            <!-- Step 1: Email verification -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="Enter your email address">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%;">Verify Email</button>
            <?php else: ?>
            <!-- Step 2: Password reset -->
            <input type="hidden" name="verified_email" value="<?= htmlspecialchars($verified_email) ?>">

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required
                       placeholder="Enter your new password" minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       placeholder="Confirm your new password" minlength="6">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%;">Update Password</button>
            <?php endif; ?>
        </form>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 2rem;">
            Remember your password? <a href="login.php" style="color: #4a90e2;">Login here</a>
        </p>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>