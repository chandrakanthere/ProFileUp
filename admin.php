<?php
require_once 'admin_auth.php';

// Initialize variables
$error = '';
$success = isset($_SESSION['admin_message']) ? $_SESSION['admin_message'] : '';
unset($_SESSION['admin_message']);

// Get admin statistics
$stats = [];
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $stats['total_users'] = $stmt->fetch()['total_users'];
    
    // Total resumes
    $stmt = $pdo->query("SELECT COUNT(*) as total_resumes FROM resumes");
    $stats['total_resumes'] = $stmt->fetch()['total_resumes'];
    
    // Recent activity
    $stmt = $pdo->query("SELECT r.id, r.title, r.template, r.updated_at, u.name as user_name 
                         FROM resumes r JOIN users u ON r.user_id = u.id 
                         ORDER BY r.updated_at DESC LIMIT 5");
    $recent_resumes = $stmt->fetchAll();
    
    // Templates
    $stmt = $pdo->query("SELECT * FROM templates ORDER BY downloads DESC");
    $templates = $stmt->fetchAll();
    
    // Get template categories
    $stmt = $pdo->query("SELECT DISTINCT category FROM templates");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
} catch(PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'delete_resume':
                if (!in_array('manage_resumes', $userPermissions)) {
                    throw new Exception("You don't have permission to delete resumes");
                }
                $stmt = $pdo->prepare("DELETE FROM resumes WHERE id = ?");
                $stmt->execute([$_POST['resume_id']]);
                $_SESSION['admin_message'] = "Resume deleted successfully";
                break;
                
            case 'update_template':
                if (!in_array('manage_templates', $userPermissions)) {
                    throw new Exception("You don't have permission to manage templates");
                }
                $stmt = $pdo->prepare("UPDATE templates SET name = ?, category = ?, status = ?, description = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['category'],
                    $_POST['status'],
                    $_POST['description'],
                    $_POST['template_id']
                ]);
                $_SESSION['admin_message'] = "Template updated successfully";
                break;
                
            case 'add_template':
                if (!in_array('manage_templates', $userPermissions)) {
                    throw new Exception("You don't have permission to add templates");
                }
                
                // Handle file upload
                $thumbnail = '';
                if (!empty($_FILES['thumbnail']['name'])) {
                    $uploadDir = 'templates/thumbnails/';
                    $fileName = uniqid() . '_' . basename($_FILES['thumbnail']['name']);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetPath)) {
                        $thumbnail = $targetPath;
                    } else {
                        throw new Exception("Failed to upload thumbnail");
                    }
                }
                
                $stmt = $pdo->prepare("INSERT INTO templates (name, category, status, description, thumbnail, html_content, css_content) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['category'],
                    $_POST['status'],
                    $_POST['description'],
                    $thumbnail,
                    $_POST['html_content'] ?? '',
                    $_POST['css_content'] ?? ''
                ]);
                $_SESSION['admin_message'] = "Template added successfully";
                break;
                
            case 'delete_template':
                if (!in_array('manage_templates', $userPermissions)) {
                    throw new Exception("You don't have permission to delete templates");
                }
                $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
                $stmt->execute([$_POST['template_id']]);
                $_SESSION['admin_message'] = "Template deleted successfully";
                break;
                
            case 'assign_role':
                if (!in_array('manage_users', $userPermissions)) {
                    throw new Exception("You don't have permission to manage user roles");
                }
                // First remove existing roles
                $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
                $stmt->execute([$_POST['user_id']]);
                
                // Add new roles
                if (!empty($_POST['roles'])) {
                    $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                    foreach ($_POST['roles'] as $roleId) {
                        $stmt->execute([$_POST['user_id'], $roleId]);
                    }
                }
                $_SESSION['admin_message'] = "User roles updated successfully";
                break;
                
            case 'update_user_status':
                if (!in_array('manage_users', $userPermissions)) {
                    throw new Exception("You don't have permission to manage users");
                }
                $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
                $stmt->execute([$_POST['status'], $_POST['user_id']]);
                $_SESSION['admin_message'] = "User status updated successfully";
                break;
        }
        header("Location: admin.php");
        exit();
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all roles for user management
$allRoles = [];
try {
    $stmt = $pdo->query("SELECT * FROM roles");
    $allRoles = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error fetching roles: " . $e->getMessage();
}

// Get all users for management (if permission)
$users = [];
if (in_array('manage_users', $userPermissions)) {
    try {
        $stmt = $pdo->query("SELECT u.id, u.name, u.email, u.is_active, GROUP_CONCAT(r.name) as roles 
                            FROM users u
                            LEFT JOIN user_roles ur ON u.id = ur.user_id
                            LEFT JOIN roles r ON ur.role_id = r.id
                            GROUP BY u.id");
        $users = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = "Error fetching users: " . $e->getMessage();
    }
}

// Get all resumes for management (if permission)
$allResumes = [];
if (in_array('manage_resumes', $userPermissions)) {
    try {
        $stmt = $pdo->query("SELECT r.*, u.name as user_name FROM resumes r JOIN users u ON r.user_id = u.id ORDER BY r.updated_at DESC");
        $allResumes = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = "Error fetching resumes: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Resume Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
            overflow-x: hidden;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            position: fixed;
            height: 100%;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar .logo {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .logo h2 {
            font-size: 1.2rem;
            margin-bottom: 0.2rem;
        }
        
        .sidebar .logo small {
            font-size: 0.7rem;
            opacity: 0.8;
        }
        
        .nav-menu {
            padding: 1rem 0;
        }
        
        .nav-item {
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: rgba(255,255,255,0.1);
        }
        
        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 20px;
            transition: all 0.3s;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
            font-weight: 600;
        }
        
        .disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .permission-required {
            position: relative;
        }
        
        .permission-required:after {
            content: "🔒";
            position: absolute;
            right: 5px;
            top: 5px;
            font-size: 12px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 700px;
            border-radius: 5px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .template-thumbnail {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .status-draft {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .template-card {
            transition: transform 0.3s;
        }
        
        .template-card:hover {
            transform: translateY(-5px);
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .admin-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .admin-user-info {
            display: flex;
            align-items: center;
        }
        
        .admin-user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .admin-user-info .dropdown-toggle::after {
            display: none;
        }
        
        .admin-user-info .dropdown-menu {
            min-width: 200px;
        }
        
        .admin-user-info .dropdown-item {
            padding: 0.5rem 1rem;
        }
        
        .admin-user-info .dropdown-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .admin-user-info .dropdown-divider {
            margin: 0.5rem 0;
        }
        
        .admin-user-info .dropdown-item.logout {
            color: #dc3545;
        }
        
        .admin-user-info .dropdown-item.logout:hover {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar with dynamic permissions -->
        <div class="sidebar">
            <div class="logo">
                <h2>ResumeBuilder</h2>
                <small>Admin Panel</small>
            </div>
            
            <div class="nav-menu">
                <div class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </div>
                
                <?php if (in_array('manage_users', $userPermissions)): ?>
                <div class="nav-item" onclick="showSection('user-management')">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </div>
                <?php endif; ?>
                
                <?php if (in_array('manage_resumes', $userPermissions)): ?>
                <div class="nav-item" onclick="showSection('resume-management')">
                    <i class="fas fa-file-alt"></i>
                    <span>Resume Management</span>
                </div>
                <?php endif; ?>
                
                <?php if (in_array('manage_templates', $userPermissions)): ?>
                <div class="nav-item" onclick="showSection('template-management')">
                    <i class="fas fa-paint-brush"></i>
                    <span>Template Management</span>
                </div>
                <?php endif; ?>
                
                <?php if (in_array('view_analytics', $userPermissions)): ?>
                <div class="nav-item" onclick="showSection('analytics')">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </div>
                <?php endif; ?>
                
                <div class="nav-item" onclick="window.location.href='admin_logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Admin Header with User Info -->
            <div class="admin-header">
                <h1>Admin Dashboard</h1>
                <div class="admin-user-info dropdown">
                    <a class="dropdown-toggle d-flex align-items-center text-dark text-decoration-none" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($currentUser['name']) ?>&background=random" alt="Admin">
                        <span class="ms-2"><?= htmlspecialchars($currentUser['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item logout" href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <!-- Dashboard Section -->
            <div id="dashboard-section">
                <h2 class="mb-4">Dashboard Overview</h2>
                
                <div class="row">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <h2 class="mb-0"><?= $stats['total_users'] ?></h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Resumes</h5>
                                <h2 class="mb-0"><?= $stats['total_resumes'] ?></h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Active Templates</h5>
                                <h2 class="mb-0"><?= count(array_filter($templates, function($t) { return $t['status'] == 'active'; })) ?></h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">Recent Activity</h5>
                                <h2 class="mb-0"><?= count($recent_resumes) ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold">Recent Resumes</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>User</th>
                                                <th>Template</th>
                                                <th>Updated</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_resumes as $resume): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($resume['title']) ?></td>
                                                <td><?= htmlspecialchars($resume['user_name']) ?></td>
                                                <td><?= htmlspecialchars($resume['template']) ?></td>
                                                <td><?= date('M d, Y H:i', strtotime($resume['updated_at'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold">Popular Templates</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Downloads</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($templates, 0, 5) as $template): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($template['name']) ?></td>
                                                <td><?= htmlspecialchars($template['category']) ?></td>
                                                <td><?= $template['downloads'] ?></td>
                                                <td>
                                                    <span class="status-badge status-<?= $template['status'] ?>">
                                                        <?= ucfirst($template['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Management Section (only visible with permission) -->
            <?php if (in_array('manage_users', $userPermissions)): ?>
            <div id="user-management-section" class="d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>User Management</h2>
                    <button class="btn btn-primary" onclick="openAddUserModal()">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Roles</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="status-badge <?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($user['roles'] ?? 'No roles') ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="openEditUserModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>', '<?= htmlspecialchars($user['roles']) ?>')">
                                                <i class="fas fa-edit"></i> Edit Roles
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="update_user_status">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $user['is_active'] ? '0' : '1' ?>">
                                                <button type="submit" class="btn btn-sm <?= $user['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                                    <?= $user['is_active'] ? '<i class="fas fa-ban"></i> Deactivate' : '<i class="fas fa-check"></i> Activate' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Resume Management Section (only visible with permission) -->
            <?php if (in_array('manage_resumes', $userPermissions)): ?>
            <div id="resume-management-section" class="d-none">
                <h2 class="mb-4">Resume Management</h2>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>User</th>
                                        <th>Template</th>
                                        <th>Created</th>
                                        <th>Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allResumes as $resume): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($resume['title']) ?></td>
                                        <td><?= htmlspecialchars($resume['user_name']) ?></td>
                                        <td><?= htmlspecialchars($resume['template']) ?></td>
                                        <td><?= date('M d, Y', strtotime($resume['created_at'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($resume['updated_at'])) ?></td>
                                        <td>
                                            <a href="resume.php?id=<?= $resume['id'] ?>" class="btn btn-sm btn-info" target="_blank">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete_resume">
                                                <input type="hidden" name="resume_id" value="<?= $resume['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this resume?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Template Management Section (only visible with permission) -->
            <?php if (in_array('manage_templates', $userPermissions)): ?>
            <div id="template-management-section" class="d-none">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Template Management</h2>
                    <button class="btn btn-primary" onclick="openAddTemplateModal()">
                        <i class="fas fa-plus"></i> Add Template
                    </button>
                </div>
                
                <div class="row">
                    <?php foreach ($templates as $template): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card template-card h-100">
                            <?php if ($template['thumbnail']): ?>
                            <img src="<?= htmlspecialchars($template['thumbnail']) ?>" class="card-img-top template-thumbnail" alt="<?= htmlspecialchars($template['name']) ?>">
                            <?php else: ?>
                            <div class="card-img-top template-thumbnail bg-secondary d-flex align-items-center justify-content-center">
                                <i class="fas fa-file-alt fa-3x text-white"></i>
                            </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($template['name']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($template['category']) ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="status-badge status-<?= $template['status'] ?>">
                                        <?= ucfirst($template['status']) ?>
                                    </span>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-download"></i> <?= $template['downloads'] ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <button class="btn btn-sm btn-primary" onclick="openEditTemplateModal(<?= $template['id'] ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete_template">
                                    <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this template?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                                <a href="#" class="btn btn-sm btn-success float-end">
                                    <i class="fas fa-eye"></i> Preview
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Analytics Section (only visible with permission) -->
            <?php if (in_array('view_analytics', $userPermissions)): ?>
            <div id="analytics-section" class="d-none">
                <h2 class="mb-4">Analytics</h2>
                <div class="card">
                    <div class="card-body">
                        <p>Analytics dashboard will be displayed here.</p>
                        <!-- Placeholder for charts and graphs -->
                        <div style="height: 300px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <p class="text-muted">Charts and graphs will be displayed here</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- User Role Edit Modal -->
            <div id="editUserModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeEditUserModal()">&times;</span>
                    <h2>Edit User Roles</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="assign_role">
                        <input type="hidden" id="edit_user_id" name="user_id" value="">
                        <div class="form-group mb-3">
                            <label id="edit_user_name" class="form-label fw-bold"></label>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Roles:</label>
                            <div class="row">
                                <?php foreach ($allRoles as $role): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="<?= $role['id'] ?>" id="role_<?= $role['id'] ?>">
                                        <label class="form-check-label" for="role_<?= $role['id'] ?>">
                                            <?= htmlspecialchars($role['name']) ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
            
            <!-- Add Template Modal -->
            <div id="addTemplateModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeAddTemplateModal()">&times;</span>
                    <h2>Add New Template</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_template">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Thumbnail</label>
                                <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">HTML Content</label>
                            <textarea name="html_content" class="form-control" rows="6"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">CSS Content</label>
                            <textarea name="css_content" class="form-control" rows="6"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Template</button>
                    </form>
                </div>
            </div>
            
            <!-- Edit Template Modal -->
            <div id="editTemplateModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeEditTemplateModal()">&times;</span>
                    <h2>Edit Template</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_template">
                        <input type="hidden" id="edit_template_id" name="template_id" value="">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="name" id="edit_template_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category" id="edit_template_category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_template_status" class="form-select" required>
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Thumbnail</label>
                                <input type="file" name="thumbnail" class="form-control" accept="image/*">
                                <small class="text-muted">Leave empty to keep current thumbnail</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_template_description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">HTML Content</label>
                            <textarea name="html_content" id="edit_template_html" class="form-control" rows="6"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">CSS Content</label>
                            <textarea name="css_content" id="edit_template_css" class="form-control" rows="6"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Template</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide sections
        function showSection(sectionId) {
            document.querySelectorAll('[id$="-section"]').forEach(section => {
                section.classList.add('d-none');
            });
            document.getElementById(sectionId + '-section').classList.remove('d-none');
            
            // Update active nav item
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`.nav-item[onclick="showSection('${sectionId}')"]`).classList.add('active');
        }
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // User role management
        function openEditUserModal(userId, userName, currentRoles) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_user_name').textContent = userName;
            
            // Clear all checkboxes first
            document.querySelectorAll('input[name="roles[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check current roles
            if (currentRoles) {
                const roles = currentRoles.split(',');
                roles.forEach(roleName => {
                    const role = Array.from(document.querySelectorAll('input[name="roles[]"]')).find(
                        r => r.nextElementSibling.textContent.trim() === roleName.trim()
                    );
                    if (role) role.checked = true;
                });
            }
            
            document.getElementById('editUserModal').style.display = 'block';
        }
        
        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }
        
        // Template management
        function openAddTemplateModal() {
            document.getElementById('addTemplateModal').style.display = 'block';
        }
        
        function closeAddTemplateModal() {
            document.getElementById('addTemplateModal').style.display = 'none';
        }
        
        function openEditTemplateModal(templateId) {
            // In a real application, you would fetch the template data via AJAX
            // For this example, we'll just set the ID
            document.getElementById('edit_template_id').value = templateId;
            document.getElementById('editTemplateModal').style.display = 'block';
            
            // In a real implementation, you would populate the form fields with the template data
            // Example:
            // fetch(`get_template.php?id=${templateId}`)
            //     .then(response => response.json())
            //     .then(data => {
            //         document.getElementById('edit_template_name').value = data.name;
            //         document.getElementById('edit_template_category').value = data.category;
            //         // ... populate other fields
            //     });
        }
        
        function closeEditTemplateModal() {
            document.getElementById('editTemplateModal').style.display = 'none';
        }
    </script>
</body>
</html>