<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('myresumes.php');
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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            redirect('myresumes.php');
        } else {
            $error = "Invalid email or password";
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo SITE_NAME; ?> | Login</title>
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
        .about-section {
            max-width: 500px;
            padding: 2rem;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .container-custom {
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
            padding: 1rem;
            width: 100%;
        }
        @media (max-width: 992px) {
            .container-custom {
                flex-direction: column;
            }
            .form-signin, .about-section {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container-custom">
        <main class="form-signin bg-white shadow rounded">
            <form method="POST">
                <div class="d-flex gap-2 justify-content-center">
                    <img class="mb-4" src="logo.png" alt="" height="70">
                    <div>
                        <h1 class="h3 fw-normal my-1"><b>ProFileUp</h1>
                        <p class="m-0">Login to your account</p>
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
                
                <button class="btn btn-primary w-100 py-2" type="submit">Login <i class="bi bi-box-arrow-in-right"></i></button>
                <div class="d-flex justify-content-between my-3">
                    <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                    <a href="register.php" class="text-decoration-none">Register</a>
                </div>
            </form>
        </main>

        <div class="about-section">
            <h2 class="h4 mb-4">About ProFileUp</h2>
            <p class="mb-3">Welcome to ProFileUp, your professional resume management platform designed to help you create, manage, and track your resumes with ease.</p>
            
            <h3 class="h5 mt-4 mb-3"><i class="bi bi-check-circle-fill text-primary"></i> Key Features</h3>
            <ul class="list-unstyled">
                <li class="mb-2"><i class="bi bi-check text-success"></i> Create multiple professional resumes</li>
                <li class="mb-2"><i class="bi bi-check text-success"></i> Track resume views and downloads</li>
                <li class="mb-2"><i class="bi bi-check text-success"></i> Easy-to-use template system</li>
                <li class="mb-2"><i class="bi bi-check text-success"></i> Secure cloud storage</li>
            </ul>
            
            <h3 class="h5 mt-4 mb-3"><i class="bi bi-people-fill text-primary"></i> Who Can Benefit?</h3>
            <p>Whether you're a job seeker, freelancer, or professional looking to update your profile, ProFileUp provides the tools you need to stand out in today's competitive market.</p>
            
            <div class="mt-4 pt-3 border-top">
                <p class="small text-muted m-0">Get started today and take control of your professional profile!</p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>