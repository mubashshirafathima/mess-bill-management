<?php
include('db_connect.php');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['student_name'] ?? '');
    $hallTicket = trim($_POST['hall_ticket'] ?? '');
    $joiningDate = trim($_POST['joining_date'] ?? '');
    $expectedEndDate = trim($_POST['expected_end_date'] ?? '');
    $branch = trim($_POST['branch'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $currentAcademicYear = (int)($_POST['current_academic_year'] ?? 1);
    if ($currentAcademicYear < 1 || $currentAcademicYear > 4) {
        $currentAcademicYear = 1;
    }
    $hostelCategory = strtolower(trim((string)($_POST['hostel_category'] ?? 'boys')));
    if ($hostelCategory !== 'boys' && $hostelCategory !== 'girls') {
        $hostelCategory = 'boys';
    }

    $allowedBranches = ['CSE', 'CSM', 'CIVIL', 'ECE', 'MECH'];
    if ($branch !== '') {
        $branch = strtoupper($branch);
    }

    if ($name === '' || $hallTicket === '' || $joiningDate === '' || $branch === '' || $phone === '') {
        $message = 'All fields are required.';
        $messageType = 'error';
    } elseif (!in_array($branch, $allowedBranches, true)) {
        $message = 'Please select a valid branch.';
        $messageType = 'error';
    } else {
        // end_date is the actual exit date and is set when Admin graduates the student.
        $insertSql = "INSERT INTO registered_students (hall_ticket, student_name, joining_date, end_date, expected_end_date, branch, phone_number, current_academic_year, hostel_category)
                      VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertSql);

        if ($insertStmt) {
            $expectedEndDateParam = ($expectedEndDate === '') ? null : $expectedEndDate;
            mysqli_stmt_bind_param($insertStmt, 'ssssssis', $hallTicket, $name, $joiningDate, $expectedEndDateParam, $branch, $phone, $currentAcademicYear, $hostelCategory);

            if (mysqli_stmt_execute($insertStmt)) {
                $message = 'Student registered successfully.';
                $messageType = 'success';
            } else {
                if (mysqli_errno($conn) === 1062) {
                    $message = 'This hallticket is already registered.';
                } else {
                    $message = 'Registration failed: ' . mysqli_error($conn);
                }
                $messageType = 'error';
            }
            mysqli_stmt_close($insertStmt);
        } else {
            $message = 'Unable to save student data right now.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Student</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 24px; }
        .wrap { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 10px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08); padding: 24px; }
        h2 { margin-top: 0; }
        .grid { display: grid; gap: 12px; }
        label { font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .msg { padding: 10px; border-radius: 6px; margin-bottom: 14px; font-size: 14px; }
        .msg.success { background: #eaf8ee; color: #1f7a37; border: 1px solid #bfe5ca; }
        .msg.error { background: #fdeeee; color: #b42318; border: 1px solid #f5c2c7; }
        .btn { border: none; border-radius: 6px; padding: 10px 14px; cursor: pointer; text-decoration: none; color: #fff; font-weight: bold; display: inline-block; }
        .btn-save { background: #007bff; }
        .btn-secondary { background: #6c757d; margin-left: 8px; }
        .bottom-right { display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px; }
    </style>
</head>
<body>
    <div class="wrap">
        <h2>Register Student</h2>

        <?php if ($message !== ''): ?>
            <div class="msg <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="grid">
            <div><label for="student_name">Student Name</label><input type="text" id="student_name" name="student_name" required></div>
            <div><label for="hall_ticket">Hallticket Number</label><input type="text" id="hall_ticket" name="hall_ticket" required></div>
            <div><label for="joining_date">Hostel Joining Date</label><input type="date" id="joining_date" name="joining_date" required></div>
            <div><label for="expected_end_date">Expected End Date</label><input type="date" id="expected_end_date" name="expected_end_date"></div>
            <div>
                <label for="branch">Branch</label>
                <select id="branch" name="branch" required>
                    <option value="">Select branch</option>
                    <option value="CSE">CSE</option>
                    <option value="CSM">CSM</option>
                    <option value="CIVIL">CIVIL</option>
                    <option value="ECE">ECE</option>
                    <option value="MECH">MECH</option>
                </select>
            </div>
            <div><label for="phone_number">Phone Number</label><input type="text" id="phone_number" name="phone_number" required></div>
            <div>
                <label for="hostel_category">Select Hostel Category</label>
                <select id="hostel_category" name="hostel_category" required>
                    <option value="boys">Boys Hostel</option>
                    <option value="girls">Girls Hostel</option>
                </select>
            </div>
            <div>
                <label for="current_academic_year">Academic Year</label>
                <select id="current_academic_year" name="current_academic_year" required>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
            </div>
            <div><button class="btn btn-save" type="submit">Save</button></div>
        </form>

        <div class="bottom-right">
            <a class="btn btn-secondary hostel-pick" data-href="view_registered_students.php" href="#">View Registered Students</a>
            <a class="btn btn-secondary" href="admin_section.php">Admin Dashboard</a>
        </div>
    </div>

    <div id="hostelModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); align-items:center; justify-content:center; padding:18px;">
        <div style="background:#fff; width:420px; max-width:95vw; border-radius:10px; padding:18px; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 10px;">Select Hostel Category</h3>
            <p style="margin:0 0 14px; color:#555;">Please select to continue:</p>
            <div style="display:grid; gap:10px;">
                <button id="pickBoys" style="padding:12px; border:none; border-radius:8px; background:#007bff; color:#fff; font-weight:bold; cursor:pointer;">Boys Hostel</button>
                <button id="pickGirls" style="padding:12px; border:none; border-radius:8px; background:#e91e63; color:#fff; font-weight:bold; cursor:pointer;">Girls Hostel</button>
                <button id="pickAll" style="padding:12px; border:none; border-radius:8px; background:#2c3e50; color:#fff; font-weight:bold; cursor:pointer;">All</button>
                <button id="closeModal" style="padding:10px; border:1px solid #ddd; border-radius:8px; background:#fff; cursor:pointer;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('hostelModal');
            const pickBoys = document.getElementById('pickBoys');
            const pickGirls = document.getElementById('pickGirls');
            const pickAll = document.getElementById('pickAll');
            const closeModal = document.getElementById('closeModal');
            let nextHref = null;

            function openModal(href) {
                nextHref = href;
                modal.style.display = 'flex';
            }

            function close() {
                modal.style.display = 'none';
                nextHref = null;
            }

            function go(hostel) {
                if (!nextHref) return;
                const sep = nextHref.indexOf('?') >= 0 ? '&' : '?';
                window.location.href = nextHref + sep + 'hostel=' + encodeURIComponent(hostel);
            }

            document.querySelectorAll('.hostel-pick').forEach(a => {
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    openModal(a.getAttribute('data-href'));
                });
            });

            pickBoys.addEventListener('click', () => go('boys'));
            pickGirls.addEventListener('click', () => go('girls'));
            pickAll.addEventListener('click', () => go('all'));
            closeModal.addEventListener('click', close);
            modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
        })();
    </script>
</body>
</html>
