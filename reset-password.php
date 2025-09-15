<?php
require_once 'config/database.php';
require_once 'config/env.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? '';
$valid_token = false;
$email = '';

if (empty($token)) {
    $error = 'Invalid or missing reset token';
} else {
    // Validate token
    $reset_file = __DIR__ . '/data/password_resets.json';
    if (file_exists($reset_file)) {
        $resets = json_decode(file_get_contents($reset_file), true) ?: [];

        foreach ($resets as $reset) {
            if ($reset['token'] === $token && strtotime($reset['expires_at']) > time()) {
                $valid_token = true;
                $email = $reset['email'];
                break;
            }
        }
    }

    if (!$valid_token) {
        $error = 'Invalid or expired reset token. Please request a new password reset.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $error = 'Password is required';
    } else if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Update user password
            $updated = $conn->update('users', [
                'password' => $hashed_password
            ], [
                'email' => $email
            ]);

            if ($updated) {
                // Remove used reset token
                $resets = json_decode(file_get_contents($reset_file), true) ?: [];
                $resets = array_filter($resets, function($reset) use ($token) {
                    return $reset['token'] !== $token;
                });
                file_put_contents($reset_file, json_encode($resets, JSON_PRETTY_PRINT));

                $message = 'Your password has been reset successfully. You can now login with your new password.';
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Cheche</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
        <h2>Reset Your Password</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <p style="text-align: center; margin-top: 2rem;">
                <a href="login.php" class="btn-primary">Login Now</a>
            </p>
        <?php elseif ($valid_token): ?>
            <p style="text-align: center; color: #666; margin-bottom: 2rem;">
                Enter your new password below.
            </p>

            <form method="POST" action="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required
                           minlength="6" placeholder="At least 6 characters">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Re-enter your new password">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">Update Password</button>
            </form>
        <?else: ?>
            <p style="text-align: center; margin-top: 2rem;">
                <a href="forgot-password.php" class="btn-primary">Request New Reset Link</a>
            </p>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 2rem;">
            Remember your password? <a href="login.php" style="color: #4a90e2;">Login here</a>
        </p>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Client-side password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            function validatePasswords() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }

            if (password && confirmPassword) {
                password.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);
            }
        });
    </script>
</body>
</html>