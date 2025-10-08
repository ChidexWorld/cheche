<?php
require_once '../config/database.php';
require_once '../config/session.php';

if (isLoggedIn()) {
    if (isInstructor()) {
        header('Location: instructor-dashboard.php');
    } else {
        header('Location: student-dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) && empty($password)) {
        $error = '❌ Please fill in both email and password fields';
    } elseif (empty($email)) {
        $error = '❌ Email address is required';
    } elseif (empty($password)) {
        $error = '❌ Password is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Please enter a valid email address';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $error = '❌ No account found with this email address. <a href="register.php">Sign up here</a>';
            } elseif (!password_verify($password, $user['password'])) {
                $error = '❌ Incorrect password. Please check your password and try again';
            } else {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                if ($user['role'] === 'instructor') {
                    header('Location: instructor-dashboard.php');
                } else {
                    header('Location: student-dashboard.php');
                }
                exit();
            }
        } catch (Exception $e) {
            $error = '❌ Login failed due to a system error. Please try again in a few moments.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cheche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/language-dropdown.css">
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
                <div class="language-dropdown">
                    <button class="language-toggle" onclick="toggleDropdown()">
                        🌍 <span id="currentLang">English</span> ▼
                    </button>
                    <div class="dropdown-content" id="languageDropdown">
                        <a href="#" onclick="changeLanguage('en')">English</a>
                        <a href="#" onclick="changeLanguage('ig')">Igbo</a>
                    </div>
                </div>
                <a href="index.php" data-translate>Home</a>
                <a href="register.php" class="btn-primary" data-translate>Sign Up</a>
            </div>
        </div>
    </nav>

    <div class="form-container">
        <h2 data-translate>Welcome Back</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email" data-translate>Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password" data-translate>Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div style="text-align: right; margin-bottom: 1rem;">
                <a href="forgot-password.php" style="color: #4a90e2; font-size: 0.9rem;" data-translate>Forgot your password?</a>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%;" data-translate>Login</button>
        </form>

        <p style="text-align: center; margin-top: 2rem;">
            <span data-translate>Don't have an account?</span> <a href="register.php" style="color: #4a90e2;" data-translate>Sign up here</a>
        </p>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/language.js"></script>
</body>
</html>