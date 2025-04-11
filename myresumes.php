<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Handle delete resume
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM resumes WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
        $_SESSION['success'] = "Resume deleted successfully";
        redirect('myresumes.php');
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error deleting resume: " . $e->getMessage();
        redirect('myresumes.php');
    }
}

// Fetch user's resumes
$resumes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM resumes WHERE user_id = ? ORDER BY updated_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $resumes = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error fetching resumes: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Resumes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            height: 100vh;
            background: rgb(249, 249, 249);
            background: radial-gradient(circle, rgba(249, 249, 249, 1) 0%, rgba(240, 232, 127, 1) 49%, rgba(246, 243, 132, 1) 100%);
        }
    </style>
</head>
<body>
    <nav class="navbar bg-body-tertiary shadow">
        <div class="container">
            <a class="navbar-brand" href="myresumes.php">
                <img src="logo.png" alt="Logo" height="24" class="d-inline-block align-text-top">
                Resume Builder
            </a>
            <div>
                <a href="profile.php" class="btn btn-sm btn-dark"><i class="bi bi-person-circle"></i> Profile</a>
                <a href="logout.php" class="btn btn-sm btn-danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="bg-white rounded shadow p-2 mt-4" style="min-height:80vh">
            <div class="d-flex justify-content-between border-bottom">
                <h5>Resumes</h5>
                <div>
                    <a href="createresume.php" class="text-decoration-none"><i class="bi bi-file-earmark-plus"></i> Add New</a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (empty($resumes)): ?>
                <div class="text-center py-3 border rounded mt-3" style="background-color: rgba(236, 236, 236, 0.56);">
                    <i class="bi bi-file-text"></i> No Resumes Available
                </div>
            <?php else: ?>
                <div class="d-flex flex-wrap mt-3">
                    <?php foreach ($resumes as $resume): ?>
                        <div class="col-12 col-md-6 p-2">
                            <div class="p-2 border rounded">
                                <h5><?php echo htmlspecialchars($resume['title']); ?></h5>
                                <p class="small text-secondary m-0" style="font-size:12px">
                                    <i class="bi bi-clock-history"></i> Last Updated <?php echo date('d F, Y h:i A', strtotime($resume['updated_at'])); ?>
                                </p>
                                <div class="d-flex gap-2 mt-1">
                                    <a href="resume.php?id=<?php echo $resume['id']; ?>" class="text-decoration-none small"><i class="bi bi-file-text"></i> Open</a>
                                    <a href="createresume.php?id=<?php echo $resume['id']; ?>" class="text-decoration-none small"><i class="bi bi-pencil-square"></i> Edit</a>
                                    <a href="myresumes.php?delete=<?php echo $resume['id']; ?>" class="text-decoration-none small" onclick="return confirm('Are you sure you want to delete this resume?')"><i class="bi bi-trash2"></i> Delete</a>
                                    <a href="share.php?id=<?php echo $resume['id']; ?>" class="text-decoration-none small"><i class="bi bi-share"></i> Share</a>
                                    <a href="clone.php?id=<?php echo $resume['id']; ?>" class="text-decoration-none small"><i class="bi bi-copy"></i> Clone</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>