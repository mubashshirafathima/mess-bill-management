<?php
include('db_connect.php');

$yearFilter = (string)($_GET['year'] ?? 'all');
$yearFilter = in_array($yearFilter, ['all', '1', '2', '3', '4', 'grad'], true) ? $yearFilter : 'all';
$hostelFilter = normalize_hostel((string)($_GET['hostel'] ?? 'all'));

$baseSql = "SELECT hall_ticket, student_name, joining_date, end_date, branch, phone_number, current_academic_year
    FROM registered_students";

if ($yearFilter === 'grad') {
    // Graduated = end_date is set by Academic Year Management (no date comparisons needed).
    $baseSql .= " WHERE end_date IS NOT NULL";
} elseif ($yearFilter !== 'all') {
    $baseSql .= " WHERE current_academic_year = " . (int)$yearFilter;
}

$whereParts = [];
if (stripos($baseSql, ' WHERE ') !== false) {
    // keep existing WHERE; append into AND clause below
} else {
    // no WHERE yet; we will add if needed
}

if ($hostelFilter !== 'all') {
    $whereParts[] = "hostel_category = '" . mysqli_real_escape_string($conn, $hostelFilter) . "'";
}

if (count($whereParts) > 0) {
    if (stripos($baseSql, ' WHERE ') !== false) {
        $baseSql .= " AND " . implode(" AND ", $whereParts);
    } else {
        $baseSql .= " WHERE " . implode(" AND ", $whereParts);
    }
}

$baseSql .= " ORDER BY student_name ASC";

$studentsRes = mysqli_query($conn, $baseSql);
$students = [];
if ($studentsRes) {
    while ($row = mysqli_fetch_assoc($studentsRes)) {
        $due = get_total_due_summary($conn, (string)$row['hall_ticket']);
        $row['due_total'] = (float)$due['total'];
        $row['due_expression'] = (string)$due['expression'];
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registered Students</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f6f9; }
        .wrap { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #28a745; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .actions { margin-top: 14px; text-align: right; }
        .btn { background: #6c757d; color: white; text-decoration: none; padding: 10px 12px; border-radius: 6px; display: inline-block; }
        .btn-del { background: #c62828; color: #fff; border: none; border-radius: 4px; padding: 6px 10px; cursor: pointer; }
        .btn-more { background: #1565c0; color: #fff; border: none; border-radius: 4px; padding: 6px 10px; cursor: pointer; text-decoration: none; display: inline-block; }
        .num { text-align: right; }
        .muted { font-size: 12px; color: #555; margin-top: 4px; }
        .notice { margin-top: 10px; padding: 10px; border-radius: 6px; background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }
        .action-wrap { display: flex; gap: 6px; flex-wrap: wrap; }
    </style>
</head>
<body>
    <div class="wrap">
        <h2>Registered Students</h2>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="notice">Student deleted successfully.</div>
        <?php endif; ?>

        <form method="GET" style="margin-top:10px;">
            <label><strong>Academic Year:</strong></label>
            <select name="year">
                <option value="all" <?= $yearFilter === 'all' ? 'selected' : '' ?>>All</option>
                <option value="1" <?= $yearFilter === '1' ? 'selected' : '' ?>>1st Year</option>
                <option value="2" <?= $yearFilter === '2' ? 'selected' : '' ?>>2nd Year</option>
                <option value="3" <?= $yearFilter === '3' ? 'selected' : '' ?>>3rd Year</option>
                <option value="4" <?= $yearFilter === '4' ? 'selected' : '' ?>>4th Year</option>
                <option value="grad" <?= $yearFilter === 'grad' ? 'selected' : '' ?>>Graduated</option>
            </select>
            <input type="hidden" name="hostel" value="<?= htmlspecialchars($hostelFilter, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Filter</button>
        </form>

        <table>
            <tr>
                <th>Hallticket</th>
                <th>Name</th>
                <th>Branch</th>
                <th>Year</th>
                <th>Due (Rs)</th>
                <th>Action</th>
            </tr>
            <?php if (count($students) > 0): ?>
                <?php foreach ($students as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['hall_ticket'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['branch'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="num"><?= (int)($row['current_academic_year'] ?? 0) ?></td>
                        <td class="num">
                            <?= number_format((float)$row['due_total'], 2) ?>
                            <div class="muted"><?= htmlspecialchars($row['due_expression'], ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td>
                            <div class="action-wrap">
                                <a class="btn-more" href="registered_student_details.php?hall_ticket=<?= urlencode($row['hall_ticket']) ?>">View more</a>
                                <form action="delete_registered_student.php" method="POST" onsubmit="return confirm('Delete this student from registration list?');">
                                    <input type="hidden" name="hall_ticket" value="<?= htmlspecialchars($row['hall_ticket'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn-del" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No registered students found.</td></tr>
            <?php endif; ?>
        </table>
        <div class="actions">
            <a class="btn" href="admin_section.php">Admin Dashboard</a>
        </div>
    </div>
</body>
</html>
