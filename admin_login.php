<?php
require_once 'config.php';

// If already logged in as admin, redirect to admin panel
if (isset($_SESSION['user_id']) && hasPermission('access_admin')) {
    redirect('admin.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Check if user has admin access permission
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles ur
                                  JOIN role_permissions rp ON ur.role_id = rp.role_id
                                  JOIN permissions p ON rp.permission_id = p.id
                                  WHERE ur.user_id = ? AND p.name = 'access_admin'");
            $stmt->execute([$user['id']]);
            $hasAdminAccess = $stmt->fetchColumn() > 0;
            
            if ($hasAdminAccess) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = true;
                redirect('admin.php');
            } else {
                $error = "You don't have permission to access the admin panel";
            }
        } else {
            $error = "Invalid email or password";
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Helper function to check if user has a specific permission
function hasPermission($permissionName) {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles ur
                              JOIN role_permissions rp ON ur.role_id = rp.role_id
                              JOIN permissions p ON rp.permission_id = p.id
                              WHERE ur.user_id = ? AND p.name = ?");
        $stmt->execute([$_SESSION['user_id'], $permissionName]);
        return $stmt->fetchColumn() > 0;
    } catch(PDOException $e) {
        error_log("Permission check failed: " . $e->getMessage());
        return false;
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo SITE_NAME; ?> | Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            height: 100vh;
            background: rgb(249, 249, 249);
            background: radial-gradient(circle, rgba(249, 249, 249, 1) 0%, rgba(240, 232, 127, 1) 49%, rgba(246, 243, 132, 1) 100%);
        }
        .form-signin {
            max-width: 330px;
            padding: 1rem;
        }
        .form-signin .form-floating:focus-within {
            z-index: 2;
        }
        .form-signin input[type="email"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
        .admin-badge {
            background-color: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="w-100">
        <main class="form-signin w-100 m-auto bg-white shadow rounded">
            <form method="POST">
                <div class="d-flex gap-2 justify-content-center">
                    <img class="mb-4" src="logo.png" alt="" height="70">
                    <div>
                        <h1 class="h3 fw-normal my-1"><b>CV</b> Maker <span class="admin-badge">ADMIN</span></h1>
                        <p class="m-0">Admin Login</p>
                    </div>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="form-floating">
                    <input type="email" class="form-control" id="floatingEmail" name="email" placeholder="name@example.com" required>
                    <label for="floatingEmail"><i class="bi bi-envelope"></i> Email address</label>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
                    <label for="floatingPassword"><i class="bi bi-key"></i> Password</label>
                </div>
                
                <button class="btn btn-danger w-100 py-2" type="submit">Admin Login <i class="bi bi-box-arrow-in-right"></i></button>
                <div class="d-flex justify-content-between my-3">
                    <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                    <a href="login.php" class="text-decoration-none">User Login</a>
                </div>
            </form>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 