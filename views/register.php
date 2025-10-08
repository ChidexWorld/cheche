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
$error_safe_html = false;
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    
    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = '❌ Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Please enter a valid email address';
    } elseif (strlen($username) < 3) {
        $error = '❌ Username must be at least 3 characters long';
    } elseif (strlen($password) < 6) {
        $error = '❌ Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = '❌ Passwords do not match';
    } elseif (!in_array($role, ['student', 'instructor'])) {
        $error = '❌ Invalid role selected';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if username or email already exists
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                if ($existing['username'] === $username) {
                    $error = '❌ Username already taken. <a href="login.php" style="color: #4a90e2; text-decoration: underline;">Login instead?</a>';
                    $error_safe_html = true;
                } else {
                    $error = '❌ Email already registered. <a href="login.php" style="color: #4a90e2; text-decoration: underline;">Login instead?</a>';
                    $error_safe_html = true;
                }
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (full_name, username, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                
                if ($stmt->execute([
                    $full_name,
                    $username,
                    $email,
                    $hashed_password,
                    $role
                ])) {
                    // Set all session variables
                    $_SESSION['user_id'] = $db->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = $role;
                    
                    // Redirect based on role
                    if ($role === 'instructor') {
                        header('Location: instructor-dashboard.php');
                    } else {
                        header('Location: student-dashboard.php');
                    }
                    exit();
                } else {
                    $error = '❌ Registration failed. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = '❌ Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Cheche</title>
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
                <a href="login.php" class="btn-secondary" data-translate>Login</a>
            </div>
        </div>
    </nav>

    <div class="form-container">
        <h2 data-translate>Join Cheche</h2>
        <p style="text-align: center; color: #666; margin-bottom: 2rem;">
            <span data-translate>Start your learning journey or share your knowledge</span>
        </p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php 
                if ($error_safe_html) {
                    echo $error;
                } else {
                    echo htmlspecialchars($error);
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="full_name" data-translate>Full Name</label>
                <input type="text" id="full_name" name="full_name" required 
                       value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
                       placeholder="John Doe">
            </div>
            
            <div class="form-group">
                <label for="username" data-translate>Username</label>
                <input type="text" id="username" name="username" required 
                       minlength="3"
                       value="<?php echo htmlspecialchars($username ?? ''); ?>"
                       placeholder="johndoe">
                <small style="color: #666; font-size: 0.85rem;">At least 3 characters</small>
            </div>
            
            <div class="form-group">
                <label for="email" data-translate>Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                       placeholder="john@example.com">
            </div>
            
            <div class="form-group">
                <label for="role" data-translate>I want to</label>
                <select id="role" name="role" required>
                    <option value="student" <?php echo ($role ?? 'student') === 'student' ? 'selected' : ''; ?> data-translate>
                        Learn (Student)
                    </option>
                    <option value="instructor" <?php echo ($role ?? '') === 'instructor' ? 'selected' : ''; ?> data-translate>
                        Teach (Instructor)
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password" data-translate>Password</label>
                <input type="password" id="password" name="password" required 
                       minlength="6" placeholder="At least 6 characters">
                <small style="color: #666; font-size: 0.85rem;">Minimum 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" data-translate>Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%;" data-translate>Create Account</button>
        </form>
        
        <p style="text-align: center; margin-top: 2rem;">
            <span data-translate>Already have an account?</span> 
            <a href="login.php" style="color: #4a90e2; font-weight: 500;" data-translate>Login here</a>
        </p>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/language.js"></script>
    
    <script>
        // Client-side password confirmation validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('❌ Passwords do not match!');
                document.getElementById('confirm_password').focus();
            }
        });
    </script>
</body>
</html>