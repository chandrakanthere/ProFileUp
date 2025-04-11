<?php
require_once 'config.php';

$resumeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$resume = [];
$personalInfo = [];
$education = [];
$experience = [];
$skills = [];

try {
    // Fetch resume data
    $stmt = $pdo->prepare("SELECT r.*, u.name AS user_name FROM resumes r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->execute([$resumeId]);
    $resume = $stmt->fetch();
    
    if (!$resume) {
        die("Resume not found");
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
    die("Error fetching resume data: " . $e->getMessage());
}

// Handle print request
if (isset($_GET['print'])) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="resume_'.$resumeId.'.pdf"');
    // In a real app, you would generate PDF here using a library like Dompdf or TCPDF
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="icon" href="logo.png">
    <title>Resume - <?php echo htmlspecialchars($resume['title']); ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #FAFAFA;
            font-family: 'Poppins', sans-serif;
            font-size: 12pt;
            background: rgb(249, 249, 249);
            background: radial-gradient(circle, rgba(249, 249, 249, 1) 0%, rgba(240, 232, 127, 1) 49%, rgba(246, 243, 132, 1) 100%);
        }
        * {
            margin: 0px;
            box-sizing: border-box;
            -moz-box-sizing: border-box;
        }
        .page {
            width: 21cm;
            min-height: 29.7cm;
            padding: 0.5cm;
            margin: 0.5cm auto;
            border: 1px #D3D3D3 solid;
            border-radius: 5px;
            background: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .subpage {
            /* height: 256mm; */
        }
        @page {
            size: A4;
            margin: 0;
        }
        @media print {
            .page {
                margin: 0;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                page-break-after: always;
            }
            .no-print {
                display: none !important;
            }
        }
        * {
            transition: all .2s;
        }
        table {
            border-collapse: collapse;
        }
        .pr {
            padding-right: 30px;
        }
        .pd-table td {
            padding-right: 10px;
            padding-bottom: 3px;
            padding-top: 3px;
        }
        .action-buttons {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="action-buttons no-print">
        <a href="resume.php?id=<?php echo $resumeId; ?>&print=1" class="btn btn-primary"><i class="bi bi-printer"></i> Print</a>
        <?php if (isLoggedIn() && $_SESSION['user_id'] == $resume['user_id']): ?>
            <a href="createresume.php?id=<?php echo $resumeId; ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
        <?php endif; ?>
    </div>

    <div class="page">
        <div class="subpage">
            <table class="w-100">
                <tbody>
                    <tr>
                        <td colspan="2" class="text-center fw-bold fs-4"><?php echo htmlspecialchars($resume['title']); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="personal-info zsection">
                            <?php if ($personalInfo): ?>
                                <div class="fw-bold name"><?php echo htmlspecialchars($personalInfo['full_name']); ?></div>
                                <div>Mobile : <span class="mobile"><?php echo htmlspecialchars($personalInfo['phone']); ?></span></div>
                                <div>Email : <span class="email"><?php echo htmlspecialchars($personalInfo['email']); ?></span></div>
                                <div>Address : <span class="address"><?php echo htmlspecialchars($personalInfo['address']); ?></span></div>
                                <hr>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if ($personalInfo && !empty($personalInfo['objective'])): ?>
                    <tr class="objective-section zsection">
                        <td class="fw-bold align-top text-nowrap pr title">Objective</td>
                        <td class="pb-3 objective">
                            <?php echo htmlspecialchars($personalInfo['objective']); ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($experience)): ?>
                    <tr class="experience-section zsection">
                        <td class="fw-bold align-top text-nowrap pr title">Experience</td>
                        <td class="pb-3 experiences">
                            <?php foreach ($experience as $exp): ?>
                                <div class="experience mb-2">
                                    <div class="fw-bold">- <span class="job-role"><?php echo htmlspecialchars($exp['job_title']); ?></span> (<span class="duration"><?php 
                                        $start = new DateTime($exp['start_date']);
                                        $end = $exp['end_date'] == 'Present' ? new DateTime() : new DateTime($exp['end_date']);
                                        $interval = $start->diff($end);
                                        echo $interval->y > 0 ? $interval->y . ' year' . ($interval->y > 1 ? 's' : '') : '';
                                        echo $interval->y > 0 && $interval->m > 0 ? ' ' : '';
                                        echo $interval->m > 0 ? $interval->m . ' month' . ($interval->m > 1 ? 's' : '') : '';
                                    ?></span> )</div>
                                    <div class="company"><?php echo htmlspecialchars($exp['employer']); ?></div>
                                    <div><span class="working-from"><?php echo htmlspecialchars($exp['start_date']); ?></span> – <span class="working-to"><?php echo htmlspecialchars($exp['end_date']); ?></span></div>
                                    <?php if (!empty($exp['description'])): ?>
                                    <div class="work-description"><?php echo htmlspecialchars($exp['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($education)): ?>
                    <tr class="education-section zsection">
                        <td class="fw-bold align-top text-nowrap pr title">Education</td>
                        <td class="pb-3 educations">
                            <?php foreach ($education as $edu): ?>
                                <div class="education mb-2">
                                    <div class="fw-bold">- <span class="course"><?php echo htmlspecialchars($edu['degree']); ?></span></div>
                                    <div class="institute"><?php echo htmlspecialchars($edu['institution']); ?></div>
                                    <div class="date"><?php echo htmlspecialchars($edu['completion_year']); ?></div>
                                    <?php if (!empty($edu['description'])): ?>
                                    <div><?php echo htmlspecialchars($edu['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($skills)): ?>
                    <tr class="skills-section zsection">
                        <td class="fw-bold align-top text-nowrap pr title">Skills</td>
                        <td class="pb-3 skills">
                            <?php foreach ($skills as $skill): ?>
                                <div class="skill">- <?php echo htmlspecialchars($skill['skill_name']); ?></div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if ($personalInfo): ?>
                    <tr class="personal-details-section zsection">
                        <td class="fw-bold align-top text-nowrap pr title">Personal Details</td>
                        <td class="pb-3">
                            <table class="pd-table">
                                <?php if ($personalInfo['dob']): ?>
                                <tr>
                                    <td>Date of Birth</td>
                                    <td>: <span class="date-of-birth"><?php echo date('d F Y', strtotime($personalInfo['dob'])); ?></span></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($personalInfo['gender']): ?>
                                <tr>
                                    <td>Gender</td>
                                    <td>: <span class="gender"><?php echo htmlspecialchars($personalInfo['gender']); ?></span></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($personalInfo['religion']): ?>
                                <tr>
                                    <td>Religion</td>
                                    <td>: <span class="religion"><?php echo htmlspecialchars($personalInfo['religion']); ?></span></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($personalInfo['nationality']): ?>
                                <tr>
                                    <td>Nationality</td>
                                    <td>: <span class="nationality"><?php echo htmlspecialchars($personalInfo['nationality']); ?></span></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($personalInfo['marital_status']): ?>
                                <tr>
                                    <td>Marital Status</td>
                                    <td>: <span class="marital-status"><?php echo htmlspecialchars($personalInfo['marital_status']); ?></span></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($personalInfo['hobbies']): ?>
                                <tr>
                                    <td>Hobbies</td>
                                    <td>: <span class="hobbies"><?php echo htmlspecialchars($personalInfo['hobbies']); ?></span></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if ($personalInfo && !empty($personalInfo['languages'])): ?>
                    <tr class="languages-known-section zsection">
                        <td class="fw-bold align-top text-nowrap pr title">Languages Known</td>
                        <td class="pb-3 languages">
                            <?php echo htmlspecialchars($personalInfo['languages']); ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <tr class="declaration-section zsection">
                        <td class="fw-bold align-top text-nowrap pr title">Declaration</td>
                        <td class="pb-5 declaration">
                            I hereby declare that above information is correct to the best of my
                            knowledge and can be supported by relevant documents as and when
                            required.
                        </td>
                    </tr>
                    <tr>
                        <td class="px-3">Date : </td>
                        <td class="px-3 name text-end"><?php echo htmlspecialchars($personalInfo ? $personalInfo['full_name'] : ''); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>