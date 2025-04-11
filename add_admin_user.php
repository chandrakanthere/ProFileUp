<?php
require_once 'config.php';

// Check if user is logged in and has admin access
if (!isset($_SESSION['user_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Function to check if user has admin permission
function hasAdminPermission() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles ur
                              JOIN role_permissions rp ON ur.role_id = rp.role_id
                              JOIN permissions p ON rp.permission_id = p.id
                              WHERE ur.user_id = ? AND p.name = 'access_admin'");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchColumn() > 0;
    } catch(PDOException $e) {
        error_log("Permission check failed: " . $e->getMessage());
        return false;
    }
}

// Only allow users with admin permission to run this script
if (!hasAdminPermission()) {
    header('Location: admin_login.php?error=unauthorized');
    exit();
}

$success = false;
$error = '';
$email = 'chandrakant_236053@saitm.ac.in';
$password = '1234567890';
$name = 'Chandrakant';

// Run the setup if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_admin'])) {
    try {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // User exists, update password and ensure admin role
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $existingUser['id']]);
            $userId = $existingUser['id'];
        } else {
            // Create new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $hashedPassword]);
            $userId = $pdo->lastInsertId();
        }
        
        // Get admin role ID
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin'");
        $stmt->execute();
        $adminRole = $stmt->fetch();
        
        if ($adminRole) {
            // Check if user already has admin role
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?");
            $stmt->execute([$userId, $adminRole['id']]);
            $hasAdminRole = $stmt->fetchColumn() > 0;
            
            if (!$hasAdminRole) {
                // Assign admin role to user
                $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $stmt->execute([$userId, $adminRole['id']]);
            }
            
            $success = true;
        } else {
            $error = "Admin role not found. Please run the admin setup first.";
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin User - Resume Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            padding: 40px 0;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 30px;
        }
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .setup-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #4e73df;
        }
        .setup-header p {
            color: #6c757d;
        }
        .user-info {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fc;
            border-radius: 5px;
        }
        .user-info h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
        }
        .user-info ul {
            list-style-type: none;
            padding-left: 0;
        }
        .user-info li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }
        .user-info li i {
            position: absolute;
            left: 0;
            top: 3px;
            color: #4e73df;
        }
        .setup-form {
            text-align: center;
        }
        .btn-setup {
            background-color: #4e73df;
            color: white;
            padding: 10px 30px;
            border-radius: 5px;
            border: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-setup:hover {
            background-color: #224abe;
            color: white;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>Add Admin User</h1>
            <p>Add a specific user as an administrator</p>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Admin user added successfully!
            <div class="mt-3">
                <a href="admin.php" class="btn btn-success">Go to Admin Panel</a>
            </div>
        </div>
        <?php elseif ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php else: ?>
        <div class="user-info">
            <h3><i class="fas fa-user-shield"></i> User Information</h3>
            <ul>
                <li><i class="fas fa-user"></i> <strong>Name:</strong> <?php echo $name; ?></li>
                <li><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo $email; ?></li>
                <li><i class="fas fa-key"></i> <strong>Password:</strong> <?php echo $password; ?></li>
                <li><i class="fas fa-user-tag"></i> <strong>Role:</strong> Administrator</li>
            </ul>
            <p class="text-danger"><strong>Important:</strong> Please change the password after first login!</p>
        </div>
        
        <div class="setup-form">
            <form method="POST">
                <button type="submit" name="add_admin" class="btn btn-setup">
                    <i class="fas fa-user-plus"></i> Add Admin User
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 