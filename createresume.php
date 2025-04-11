<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$resumeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $resumeId > 0;
$resume = [];
$personalInfo = [];
$education = [];
$experience = [];
$skills = [];

// Fetch resume data if editing
if ($isEdit) {
    try {
        // Check if resume belongs to user
        $stmt = $pdo->prepare("SELECT * FROM resumes WHERE id = ? AND user_id = ?");
        $stmt->execute([$resumeId, $_SESSION['user_id']]);
        $resume = $stmt->fetch();
        
        if (!$resume) {
            $_SESSION['error'] = "Resume not found or you don't have permission to edit it";
            redirect('myresumes.php');
        }
        
        // Fetch personal info
        $stmt = $pdo->prepare("SELECT * FROM personal_info WHERE resume_id = ?");
        $stmt->execute([$resumeId]);
        $personalInfo = $stmt->fetch();
        
        // Fetch education
        $stmt = $pdo->prepare("SELECT * FROM education WHERE resume_id = ? ORDER BY id");
        $stmt->execute([$resumeId]);
        $education = $stmt->fetchAll();
        
        // Fetch experience
        $stmt = $pdo->prepare("SELECT * FROM experience WHERE resume_id = ? ORDER BY id");
        $stmt->execute([$resumeId]);
        $experience = $stmt->fetchAll();
        
        // Fetch skills
        $stmt = $pdo->prepare("SELECT * FROM skills WHERE resume_id = ? ORDER BY id");
        $stmt->execute([$resumeId]);
        $skills = $stmt->fetchAll();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error fetching resume data: " . $e->getMessage();
        redirect('myresumes.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Save resume title
        $title = sanitizeInput($_POST['title']);
        if (empty($title)) {
            throw new Exception("Resume title is required");
        }
        
        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE resumes SET title = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $resumeId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO resumes (user_id, title) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title]);
            $resumeId = $pdo->lastInsertId();
        }
        
        // Save personal info
        $personalData = [
            'full_name' => sanitizeInput($_POST['full_name']),
            'email' => sanitizeInput($_POST['email']),
            'phone' => sanitizeInput($_POST['phone']),
            'address' => sanitizeInput($_POST['address']),
            'dob' => sanitizeInput($_POST['dob']),
            'gender' => sanitizeInput($_POST['gender']),
            'religion' => sanitizeInput($_POST['religion']),
            'nationality' => sanitizeInput($_POST['nationality']),
            'marital_status' => sanitizeInput($_POST['marital_status']),
            'hobbies' => sanitizeInput($_POST['hobbies']),
            'languages' => sanitizeInput($_POST['languages']),
            'objective' => sanitizeInput($_POST['objective'])
        ];
        
        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE personal_info SET 
                full_name = ?, email = ?, phone = ?, address = ?, dob = ?, gender = ?, 
                religion = ?, nationality = ?, marital_status = ?, hobbies = ?, languages = ?, objective = ?
                WHERE resume_id = ?");
            $stmt->execute(array_merge(array_values($personalData), [$resumeId]));
        } else {
            $stmt = $pdo->prepare("INSERT INTO personal_info (resume_id, full_name, email, phone, address, dob, gender, religion, nationality, marital_status, hobbies, languages, objective) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(array_merge([$resumeId], array_values($personalData)));
        }
        
        // Handle education
        $pdo->prepare("DELETE FROM education WHERE resume_id = ?")->execute([$resumeId]);
        if (isset($_POST['education'])) {
            $stmt = $pdo->prepare("INSERT INTO education (resume_id, degree, institution, completion_year, description) VALUES (?, ?, ?, ?, ?)");
            foreach ($_POST['education'] as $edu) {
                $stmt->execute([
                    $resumeId,
                    sanitizeInput($edu['degree']),
                    sanitizeInput($edu['institution']),
                    sanitizeInput($edu['year']),
                    sanitizeInput($edu['description'])
                ]);
            }
        }
        
        // Handle experience
        $pdo->prepare("DELETE FROM experience WHERE resume_id = ?")->execute([$resumeId]);
        if (isset($_POST['experience'])) {
            $stmt = $pdo->prepare("INSERT INTO experience (resume_id, job_title, employer, start_date, end_date, description) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($_POST['experience'] as $exp) {
                $stmt->execute([
                    $resumeId,
                    sanitizeInput($exp['job_title']),
                    sanitizeInput($exp['employer']),
                    sanitizeInput($exp['start_date']),
                    sanitizeInput($exp['end_date']),
                    sanitizeInput($exp['description'])
                ]);
            }
        }
        
        // Handle skills
        $pdo->prepare("DELETE FROM skills WHERE resume_id = ?")->execute([$resumeId]);
        if (isset($_POST['skills'])) {
            $stmt = $pdo->prepare("INSERT INTO skills (resume_id, skill_name) VALUES (?, ?)");
            foreach ($_POST['skills'] as $skill) {
                $stmt->execute([
                    $resumeId,
                    sanitizeInput($skill['name'])
                ]);
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Resume " . ($isEdit ? "updated" : "created") . " successfully";
        redirect('myresumes.php');
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error saving resume: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Create'; ?> Resume</title>
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
                <h5><?php echo $isEdit ? 'Edit' : 'Create'; ?> Resume</h5>
                <div>
                    <a href="myresumes.php" class="text-decoration-none"><i class="bi bi-arrow-left-circle"></i> Back</a>
                </div>
            </div>

            <div>
                <form class="row g-3 p-3" method="POST">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="col-12">
                        <label class="form-label">Resume Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo $isEdit ? htmlspecialchars($resume['title']) : ''; ?>" required>
                    </div>
                    
                    <h5 class="mt-3 text-secondary"><i class="bi bi-person-badge"></i> Personal Information</h5>
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" placeholder="Your Name" class="form-control" value="<?php echo $isEdit && $personalInfo ? htmlspecialchars($personalInfo['full_name']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" placeholder="example@abc.com" class="form-control" value="<?php echo $isEdit && $personalInfo ? htmlspecialchars($personalInfo['email']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile No</label>
                        <input type="tel" name="phone" min="1111111111" placeholder="1234567890" max="9999999999" class="form-control" value="<?php echo $isEdit && $personalInfo ? htmlspecialchars($personalInfo['phone']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date Of Birth</label>
                        <input type="date" name="dob" class="form-control" value="<?php echo $isEdit && $personalInfo ? htmlspecialchars($personalInfo['dob']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="Male" <?php echo ($isEdit && $personalInfo && $personalInfo['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($isEdit && $personalInfo && $personalInfo['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($isEdit && $personalInfo && $personalInfo['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Religion</label>
                        <select class="form-select" name="religion">
                            <option value="Hindu" <?php echo ($isEdit && $personalInfo && $personalInfo['religion'] == 'Hindu') ? 'selected' : ''; ?>>Hindu</option>
                            <option value="Muslim" <?php echo ($isEdit && $personalInfo && $personalInfo['religion'] == 'Muslim') ? 'selected' : ''; ?>>Muslim</option>
                            <option value="Sikh" <?php echo ($isEdit && $personalInfo && $personalInfo['religion'] == 'Sikh') ? 'selected' : ''; ?>>Sikh</option>
                            <option value="Christian" <?php echo ($isEdit && $personalInfo && $personalInfo['religion'] == 'Christian') ? 'selected' : ''; ?>>Christian</option>
                            <option value="Other" <?php echo ($isEdit && $personalInfo && $personalInfo['religion'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nationality</label>
                        <select class="form-select" name="nationality">
                            <option value="Indian" <?php echo ($isEdit && $personalInfo && $personalInfo['nationality'] == 'Indian') ? 'selected' : ''; ?>>Indian</option>
                            <option value="Non Indian" <?php echo ($isEdit && $personalInfo && $personalInfo['nationality'] == 'Non Indian') ? 'selected' : ''; ?>>Non Indian</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Marital Status</label>
                        <select class="form-select" name="marital_status">
                            <option value="Married" <?php echo ($isEdit && $personalInfo && $personalInfo['marital_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                            <option value="Single" <?php echo ($isEdit && $personalInfo && $personalInfo['marital_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                            <option value="Divorced" <?php echo ($isEdit && $personalInfo && $personalInfo['marital_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                            <option value="Widowed" <?php echo ($isEdit && $personalInfo && $personalInfo['marital_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hobbies</label>
                        <input type="text" name="hobbies" placeholder="Reading Books, Watching Movies" class="form-control" value="<?php echo $isEdit && $personalInfo ? htmlspecialchars($personalInfo['hobbies']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Languages Known</label>
                        <input type="text" name="languages" placeholder="Hindi,English" class="form-control" value="<?php echo $isEdit && $personalInfo ? htmlspecialchars($personalInfo['languages']) : ''; ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="1234 Main St" value="<?php echo $isEdit && $personalInfo ? htmlspecialchars($personalInfo['address']) : ''; ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Objective</label>
                        <textarea name="objective" class="form-control" rows="3"><?php echo $isEdit && $personalInfo ? htmlspecialchars($personalInfo['objective']) : ''; ?></textarea>
                    </div>
                    
                    <hr>
                    <div class="d-flex justify-content-between">
                        <h5 class="text-secondary"><i class="bi bi-briefcase"></i> Experience</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addExperience()"><i class="bi bi-file-earmark-plus"></i> Add New</button>
                        </div>
                    </div>
                    
                    <div id="experience-container">
                        <?php if ($isEdit && !empty($experience)): ?>
                            <?php foreach ($experience as $exp): ?>
                                <div class="col-12 col-md-6 p-2 experience-item">
                                    <div class="p-2 border rounded">
                                        <div class="d-flex justify-content-between">
                                            <input type="text" name="experience[][job_title]" class="form-control mb-2" placeholder="Job Title" value="<?php echo htmlspecialchars($exp['job_title']); ?>" required>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                        <input type="text" name="experience[][employer]" class="form-control mb-2" placeholder="Employer" value="<?php echo htmlspecialchars($exp['employer']); ?>" required>
                                        <div class="row g-2 mb-2">
                                            <div class="col-md-6">
                                                <input type="text" name="experience[][start_date]" class="form-control" placeholder="Start Date (e.g. Oct 2020)" value="<?php echo htmlspecialchars($exp['start_date']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" name="experience[][end_date]" class="form-control" placeholder="End Date (e.g. Present)" value="<?php echo htmlspecialchars($exp['end_date']); ?>" required>
                                            </div>
                                        </div>
                                        <textarea name="experience[][description]" class="form-control" rows="2" placeholder="Job description"><?php echo htmlspecialchars($exp['description']); ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    <div class="d-flex justify-content-between">
                        <h5 class="text-secondary"><i class="bi bi-journal-bookmark"></i> Education</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addEducation()"><i class="bi bi-file-earmark-plus"></i> Add New</button>
                        </div>
                    </div>
                    
                    <div id="education-container">
                        <?php if ($isEdit && !empty($education)): ?>
                            <?php foreach ($education as $edu): ?>
                                <div class="col-12 col-md-6 p-2 education-item">
                                    <div class="p-2 border rounded">
                                        <div class="d-flex justify-content-between">
                                            <input type="text" name="education[][degree]" class="form-control mb-2" placeholder="Degree (e.g. B.Tech)" value="<?php echo htmlspecialchars($edu['degree']); ?>" required>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                        <input type="text" name="education[][institution]" class="form-control mb-2" placeholder="Institution" value="<?php echo htmlspecialchars($edu['institution']); ?>" required>
                                        <input type="text" name="education[][year]" class="form-control mb-2" placeholder="Year (e.g. 2020 or Currently Pursuing)" value="<?php echo htmlspecialchars($edu['completion_year']); ?>" required>
                                        <textarea name="education[][description]" class="form-control" rows="2" placeholder="Additional details"><?php echo htmlspecialchars($edu['description']); ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    <div class="d-flex justify-content-between">
                        <h5 class="text-secondary"><i class="bi bi-boxes"></i> Skills</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addSkill()"><i class="bi bi-file-earmark-plus"></i> Add New</button>
                        </div>
                    </div>
                    
                    <div id="skills-container">
                        <?php if ($isEdit && !empty($skills)): ?>
                            <?php foreach ($skills as $skill): ?>
                                <div class="col-12 p-2 skill-item">
                                    <div class="p-2 border rounded">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <input type="text" name="skills[][name]" class="form-control" placeholder="Skill (e.g. JavaScript)" value="<?php echo htmlspecialchars($skill['skill_name']); ?>" required>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-floppy"></i> Save Resume</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addExperience() {
            const container = document.getElementById('experience-container');
            const item = document.createElement('div');
            item.className = 'col-12 col-md-6 p-2 experience-item';
            item.innerHTML = `
                <div class="p-2 border rounded">
                    <div class="d-flex justify-content-between">
                        <input type="text" name="experience[][job_title]" class="form-control mb-2" placeholder="Job Title" required>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <input type="text" name="experience[][employer]" class="form-control mb-2" placeholder="Employer" required>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <input type="text" name="experience[][start_date]" class="form-control" placeholder="Start Date (e.g. Oct 2020)" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="experience[][end_date]" class="form-control" placeholder="End Date (e.g. Present)" required>
                        </div>
                    </div>
                    <textarea name="experience[][description]" class="form-control" rows="2" placeholder="Job description"></textarea>
                </div>
            `;
            container.appendChild(item);
        }
        
        function addEducation() {
            const container = document.getElementById('education-container');
            const item = document.createElement('div');
            item.className = 'col-12 col-md-6 p-2 education-item';
            item.innerHTML = `
                <div class="p-2 border rounded">
                    <div class="d-flex justify-content-between">
                        <input type="text" name="education[][degree]" class="form-control mb-2" placeholder="Degree (e.g. B.Tech)" required>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <input type="text" name="education[][institution]" class="form-control mb-2" placeholder="Institution" required>
                    <input type="text" name="education[][year]" class="form-control mb-2" placeholder="Year (e.g. 2020 or Currently Pursuing)" required>
                    <textarea name="education[][description]" class="form-control" rows="2" placeholder="Additional details"></textarea>
                </div>
            `;
            container.appendChild(item);
        }
        
        function addSkill() {
            const container = document.getElementById('skills-container');
            const item = document.createElement('div');
            item.className = 'col-12 p-2 skill-item';
            item.innerHTML = `
                <div class="p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <input type="text" name="skills[][name]" class="form-control" placeholder="Skill (e.g. JavaScript)" required>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            `;
            container.appendChild(item);
        }
        
        function removeItem(button) {
            if (confirm('Are you sure you want to remove this item?')) {
                const item = button.closest('.experience-item, .education-item, .skill-item');
                item.remove();
            }
        }
    </script>
</body>
</html>