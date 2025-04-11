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

// Run the SQL setup if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['run_setup'])) {
    try {
        // Read the SQL file
        $sql = file_get_contents('admin_setup.sql');
        
        // Execute the SQL
        $pdo->exec($sql);
        
        $success = true;
    } catch(PDOException $e) {
        $error = "Database setup failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - Resume Builder</title>
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
        .setup-steps {
            margin-bottom: 30px;
        }
        .setup-step {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fc;
        }
        .setup-step h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .setup-step p {
            margin-bottom: 0;
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
            <h1>Admin Panel Setup</h1>
            <p>Configure the database for the admin panel</p>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Database setup completed successfully!
            <div class="mt-3">
                <a href="admin.php" class="btn btn-success">Go to Admin Panel</a>
            </div>
        </div>
        <?php elseif ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php else: ?>
        <div class="setup-steps">
            <div class="setup-step">
                <h3><i class="fas fa-database"></i> Database Tables</h3>
                <p>This setup will create the following tables if they don't exist:</p>
                <ul>
                    <li>roles - Stores user roles</li>
                    <li>permissions - Stores permissions</li>
                    <li>role_permissions - Maps roles to permissions</li>
                    <li>user_roles - Maps users to roles</li>
                </ul>
            </div>
            
            <div class="setup-step">
                <h3><i class="fas fa-user-shield"></i> Default Roles and Permissions</h3>
                <p>The setup will create default roles and permissions:</p>
                <ul>
                    <li>Admin role with all permissions</li>
                    <li>Editor role with limited permissions</li>
                    <li>User role for regular users</li>
                </ul>
            </div>
            
            <div class="setup-step">
                <h3><i class="fas fa-user"></i> Default Admin User</h3>
                <p>A default admin user will be created if it doesn't exist:</p>
                <ul>
                    <li>Email: admin@example.com</li>
                    <li>Password: admin123</li>
                </ul>
                <p class="text-danger"><strong>Important:</strong> Please change the default password after first login!</p>
            </div>
        </div>
        
        <div class="setup-form">
            <form method="POST">
                <button type="submit" name="run_setup" class="btn btn-setup">
                    <i class="fas fa-cog"></i> Run Setup
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 