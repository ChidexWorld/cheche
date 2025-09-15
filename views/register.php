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
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if username or email already exists
            $existing = $db->select('users', ['username' => $username]);
            if (!empty($existing)) {
                $error = 'Username already exists';
            } else {
                $existing = $db->select('users', ['email' => $email]);
                if (!empty($existing)) {
                    $error = 'Email already exists';
                } else {
                    // Create new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $userData = [
                        'full_name' => $full_name,
                        'username' => $username,
                        'email' => $email,
                        'password' => $hashed_password,
                        'role' => $role
                    ];
                    
                    if ($db->insert('users', $userData)) {
                        $success = 'Account created successfully! You can now login.';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again.';
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
        <h2>Join Cheche</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required 
                       value="<?php echo htmlspecialchars($full_name ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($username ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="role">I want to</label>
                <select id="role" name="role" required>
                    <option value="student" <?php echo ($role ?? 'student') === 'student' ? 'selected' : ''; ?>>
                        Learn (Student)
                    </option>
                    <option value="instructor" <?php echo ($role ?? '') === 'instructor' ? 'selected' : ''; ?>>
                        Teach (Instructor)
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       minlength="6" placeholder="At least 6 characters">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%;">Create Account</button>
        </form>
        
        <p style="text-align: center; margin-top: 2rem;">
            Already have an account? <a href="login.php" style="color: #4a90e2;">Login here</a>
        </p>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>