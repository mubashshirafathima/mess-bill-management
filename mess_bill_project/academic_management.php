<?php
include('db_connect.php');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'promote_1_2') {
        mysqli_query($conn, "UPDATE registered_students SET current_academic_year = 2 WHERE current_academic_year = 1");
        $message = 'Promoted all 1st Year students to 2nd Year.';
    } elseif ($action === 'promote_2_3') {
        mysqli_query($conn, "UPDATE registered_students SET current_academic_year = 3 WHERE current_academic_year = 2");
        $message = 'Promoted all 2nd Year students to 3rd Year.';
    } elseif ($action === 'promote_3_4') {
        mysqli_query($conn, "UPDATE registered_students SET current_academic_year = 4 WHERE current_academic_year = 3");
        $message = 'Promoted all 3rd Year students to 4th Year.';
    } elseif ($action === 'graduate_4') {
        mysqli_query($conn, "UPDATE registered_students SET end_date = CURDATE() WHERE current_academic_year = 4");
        $message = 'Graduated all 4th Year students (end_date set to today).';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Academic Year Management</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f6f9; }
        .wrap { max-width: 700px; margin: 0 auto; background: #fff; padding: 18px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .grid { display: grid; gap: 12px; margin-top: 12px; }
        .btn { width: 100%; border: none; border-radius: 8px; padding: 12px 14px; cursor: pointer; color: #fff; font-weight: bold; text-align: left; }
        .b12 { background: #1565c0; }
        .b23 { background: #2e7d32; }
        .b34 { background: #6f42c1; }
        .bgrad { background: #c62828; }
        .msg { margin-top: 12px; padding: 10px; border-radius: 6px; background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }
        a { text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
    <div class="wrap">
        <h2>Academic Year Management</h2>
        <p>This is manual. No automatic promotion is used.</p>

        <?php if ($message !== ''): ?>
            <div class="msg"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="grid">
            <form method="POST" onsubmit="return confirm('Are you sure you want to promote all First Year students to Second Year?');">
                <input type="hidden" name="action" value="promote_1_2">
                <button class="btn b12" type="submit">Promote First Year → Second Year</button>
            </form>

            <form method="POST" onsubmit="return confirm('Are you sure you want to promote all Second Year students to Third Year?');">
                <input type="hidden" name="action" value="promote_2_3">
                <button class="btn b23" type="submit">Promote Second Year → Third Year</button>
            </form>

            <form method="POST" onsubmit="return confirm('Are you sure you want to promote all Third Year students to Fourth Year?');">
                <input type="hidden" name="action" value="promote_3_4">
                <button class="btn b34" type="submit">Promote Third Year → Fourth Year</button>
            </form>

            <form method="POST" onsubmit="return confirm('Are you sure you want to graduate all Fourth Year students?');">
                <input type="hidden" name="action" value="graduate_4">
                <button class="btn bgrad" type="submit">Graduate Fourth Year Students</button>
            </form>
        </div>

        <p style="margin-top:14px;"><a href="admin_section.php">Back to Admin Dashboard</a></p>
    </div>
</body>
</html>

