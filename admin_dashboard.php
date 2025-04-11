<?php
require_once 'config.php';

// Check if user is logged in and has admin access
if (!isset($_SESSION['user_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Check admin permissions
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles ur
                      JOIN role_permissions rp ON ur.role_id = rp.role_id
                      JOIN permissions p ON rp.permission_id = p.id
                      WHERE ur.user_id = ? AND p.name = 'access_admin'");
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetchColumn()) {
    header('Location: index.php');
    exit();
}

// Handle template upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_template') {
        try {
            // Handle template thumbnail upload
            $thumbnail = '';
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
                $upload_dir = 'uploads/templates/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $thumbnail = $upload_dir . uniqid() . '_' . basename($_FILES['thumbnail']['name']);
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbnail);
            }

            // Insert template into database
            $stmt = $pdo->prepare("INSERT INTO templates (name, description, html_content, css_content, thumbnail, created_at) 
                                 VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['html_content'],
                $_POST['css_content'],
                $thumbnail
            ]);
            $success_message = "Template added successfully!";
        } catch(PDOException $e) {
            $error_message = "Error adding template: " . $e->getMessage();
        }
    } elseif ($_POST['action'] == 'delete_template' && isset($_POST['template_id'])) {
        try {
            // Get template info for thumbnail deletion
            $stmt = $pdo->prepare("SELECT thumbnail FROM templates WHERE id = ?");
            $stmt->execute([$_POST['template_id']]);
            $template = $stmt->fetch();
            
            // Delete the template
            $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
            $stmt->execute([$_POST['template_id']]);
            
            // Delete thumbnail file if exists
            if ($template && isset($template['thumbnail']) && $template['thumbnail'] && file_exists($template['thumbnail'])) {
                unlink($template['thumbnail']);
            }
            
            $success_message = "Template deleted successfully!";
        } catch(PDOException $e) {
            $error_message = "Error deleting template: " . $e->getMessage();
        }
    }
}

// Get user statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_templates FROM templates");
$total_templates = $stmt->fetch()['total_templates'];

// Get all templates
$stmt = $pdo->query("SELECT * FROM templates ORDER BY created_at DESC");
$templates = $stmt->fetchAll();

// Check if is_active column exists
$columnExists = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    $columnExists = $stmt->rowCount() > 0;
} catch(PDOException $e) {
    // Column doesn't exist
}

// Get recent users with appropriate query based on column existence
if ($columnExists) {
    $stmt = $pdo->query("SELECT id, name, email, created_at, is_active FROM users ORDER BY created_at DESC LIMIT 5");
} else {
    $stmt = $pdo->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
}
$recent_users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .template-card {
            margin-bottom: 20px;
        }
        .template-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <h3><i class="fas fa-users"></i> Total Users</h3>
                    <h2><?php echo $total_users; ?></h2>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <h3><i class="fas fa-file-alt"></i> Total Templates</h3>
                    <h2><?php echo $total_templates; ?></h2>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Recent Users</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined Date</th>
                                <?php if ($columnExists): ?>
                                <th>Status</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <?php if ($columnExists): ?>
                                <td>
                                    <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Template Management -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Resume Templates</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                    <i class="fas fa-plus"></i> Add Template
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($templates as $template): ?>
                    <div class="col-md-4">
                        <div class="card template-card">
                            <?php if (isset($template['thumbnail']) && $template['thumbnail']): ?>
                            <img src="<?php echo htmlspecialchars($template['thumbnail']); ?>" class="template-thumbnail" alt="Template thumbnail">
                            <?php else: ?>
                            <div class="template-thumbnail bg-secondary d-flex align-items-center justify-content-center text-white">
                                <i class="fas fa-file-alt fa-3x"></i>
                            </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($template['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($template['description']); ?></p>
                                <button class="btn btn-sm btn-danger" onclick="deleteTemplate(<?php echo $template['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Template Modal -->
    <div class="modal fade" id="addTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_template">
                        <div class="mb-3">
                            <label class="form-label">Template Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">HTML Content</label>
                            <textarea class="form-control" name="html_content" rows="10"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CSS Content</label>
                            <textarea class="form-control" name="css_content" rows="10"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thumbnail</label>
                            <input type="file" class="form-control" name="thumbnail" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Template</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteTemplate(templateId) {
            if (confirm('Are you sure you want to delete this template?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_template">
                    <input type="hidden" name="template_id" value="${templateId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 