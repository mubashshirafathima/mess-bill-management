<?php
include('db_connect.php');

$selectedMonth = normalize_month((string)($_GET['month'] ?? date('Y-m')));
$yearFilter = (string)($_GET['year'] ?? 'all');
$yearFilter = in_array($yearFilter, ['all', '1', '2', '3', '4'], true) ? $yearFilter : 'all';
$hostelFilter = normalize_hostel((string)($_GET['hostel'] ?? 'all'));

$studentsSql = "SELECT id, hall_ticket, student_name, branch, current_academic_year
    FROM registered_students";
$whereParts = [];
if ($yearFilter !== 'all') {
    $whereParts[] = "current_academic_year = " . (int)$yearFilter;
}
if ($hostelFilter !== 'all') {
    $whereParts[] = "hostel_category = '" . mysqli_real_escape_string($conn, $hostelFilter) . "'";
}
if (count($whereParts) > 0) {
    $studentsSql .= " WHERE " . implode(" AND ", $whereParts);
}
if ($yearFilter === 'all') {
    $studentsSql .= " ORDER BY
        FIELD(current_academic_year, 4, 3, 2, 1),
        FIELD(LOWER(branch), 'cse', 'csm', 'civil', 'ece', 'mech'),
        student_name ASC";
} else {
    $studentsSql .= " ORDER BY
        FIELD(LOWER(branch), 'cse', 'csm', 'civil', 'ece', 'mech'),
        student_name ASC";
}

$studentsRes = mysqli_query($conn, $studentsSql);
$students = [];
if ($studentsRes) {
    while ($row = mysqli_fetch_assoc($studentsRes)) {
        $row['due_amount'] = get_consecutive_due($conn, (string)$row['hall_ticket'], $selectedMonth);
        $students[] = $row;
    }
}

$settingsRes = mysqli_query($conn, "SELECT rate_per_day, gst_percent, maintenance_fee FROM billing_settings WHERE id = 1");
$settings = $settingsRes ? mysqli_fetch_assoc($settingsRes) : null;

$rate = $settings ? (float)$settings['rate_per_day'] : 100.00;
$gst = $settings ? (float)$settings['gst_percent'] : 5.00;
$maintenance = $settings ? (float)$settings['maintenance_fee'] : 1000.00;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mess Bill Entry</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f4f6f9; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 14px rgba(0,0,0,0.08); }
        .month-row { display: flex; justify-content: flex-start; margin-bottom: 10px; gap: 10px; align-items: center; }
        .month-row label { font-weight: bold; }
        .month-row input { padding: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007bff; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
        input.days { width: 90px; padding: 6px; }
        .num { text-align: right; }
        .footer-actions { margin-top: 14px; display: flex; justify-content: flex-end; gap: 10px; }
        .btn { border: none; border-radius: 6px; padding: 10px 14px; color: #fff; text-decoration: none; cursor: pointer; font-weight: bold; }
        .btn-save { background: #17a2b8; }
        .btn-download { background: #28a745; }
        .btn-admin { background: #6c757d; }
        .btn-filter { background: #333; }
        .empty { margin-top: 10px; color: #a94442; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Student Mess Bill Table</h2>
        <p>Fixed values from settings: Rate/Day = Rs <strong><?= number_format($rate, 2) ?></strong>, GST = <strong><?= number_format($gst, 2) ?>%</strong>, Maintenance = Rs <strong><?= number_format($maintenance, 2) ?></strong></p>

        <?php if (count($students) > 0): ?>
        <form action="download_bill.php" method="POST" id="billForm">
            <div class="month-row">
                <label for="billing_month">Billing Month:</label>
                <input type="month" id="billing_month" name="billing_month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>" required>
                <label for="year" style="font-weight:bold;">Year:</label>
                <select id="year" name="year_filter" style="padding:6px;">
                    <option value="all" <?= $yearFilter === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="1" <?= $yearFilter === '1' ? 'selected' : '' ?>>1st</option>
                    <option value="2" <?= $yearFilter === '2' ? 'selected' : '' ?>>2nd</option>
                    <option value="3" <?= $yearFilter === '3' ? 'selected' : '' ?>>3rd</option>
                    <option value="4" <?= $yearFilter === '4' ? 'selected' : '' ?>>4th</option>
                </select>
                <button class="btn btn-filter" type="button" onclick="window.location='index.php?month=' + document.getElementById('billing_month').value + '&year=' + document.getElementById('year').value + '&hostel=<?= htmlspecialchars($hostelFilter, ENT_QUOTES, 'UTF-8') ?>'">Load</button>
            </div>

            <table>
                <tr>
                    <th>Hallticket</th>
                    <th>Student Name</th>
                    <th>Branch</th>
                    <th>Year</th>
                    <th>Days</th>
                    <th>Rate/Day (Rs)</th>
                    <th>GST (%)</th>
                    <th>Maintenance (Rs)</th>
                    <th>Current Bill (Rs)</th>
                    <th>Due (Rs)</th>
                    <th>Total Payable (Rs)</th>
                </tr>
                <?php foreach ($students as $row): ?>
                    <?php $due = (float)$row['due_amount']; ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($row['hall_ticket'], ENT_QUOTES, 'UTF-8') ?>
                            <input type="hidden" name="hall_ticket[]" value="<?= htmlspecialchars($row['hall_ticket'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="student_id[]" value="<?= (int)$row['id'] ?>">
                        </td>
                        <td>
                            <?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?>
                            <input type="hidden" name="student_name[]" value="<?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?>">
                        </td>
                        <td><?= htmlspecialchars((string)($row['branch'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="num"><?= (int)($row['current_academic_year'] ?? 0) ?></td>
                        <td><input class="days" type="number" min="0" name="days[]" value="0" required></td>
                        <td class="num"><?= number_format($rate, 2) ?></td>
                        <td class="num"><?= number_format($gst, 2) ?></td>
                        <td class="num"><?= number_format($maintenance, 2) ?></td>
                        <td class="num current-total-cell">0.00</td>
                        <td class="num due-cell"><?= number_format($due, 2) ?></td>
                        <td class="num payable-total-cell">0.00</td>
                        <input type="hidden" class="due-input" name="due_amount[]" value="<?= htmlspecialchars((string)$due, ENT_QUOTES, 'UTF-8') ?>">
                    </tr>
                <?php endforeach; ?>
            </table>

            <input type="hidden" name="rate" value="<?= htmlspecialchars((string)$rate, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="gst" value="<?= htmlspecialchars((string)$gst, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="maintenance_fee" value="<?= htmlspecialchars((string)$maintenance, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="hostel" value="<?= htmlspecialchars($hostelFilter, ENT_QUOTES, 'UTF-8') ?>">

            <div class="footer-actions">
                <a class="btn btn-admin" href="admin_section.php">Admin Dashboard</a>
                <button class="btn btn-save" type="submit" formaction="save_batch_bill.php">Save Bill</button>
                <button class="btn btn-download" type="submit">Download Bill (PDF)</button>
            </div>
        </form>
        <?php else: ?>
            <p class="empty">No registered students found. Please register students first.</p>
            <div class="footer-actions"><a class="btn btn-admin" href="admin_section.php">Admin Dashboard</a></div>
        <?php endif; ?>
    </div>

    <script>
        (function () {
            const rate = <?= json_encode($rate) ?>;
            const gst = <?= json_encode($gst) ?>;
            const maintenance = <?= json_encode($maintenance) ?>;

            function computeCurrent(days) {
                const subtotal = days * rate;
                const gstAmount = subtotal * (gst / 100);
                return subtotal + gstAmount + maintenance;
            }

            const rows = document.querySelectorAll('#billForm table tr');
            rows.forEach((row, index) => {
                if (index === 0) return;
                const dayInput = row.querySelector('input.days');
                const dueInput = row.querySelector('input.due-input');
                const currentCell = row.querySelector('.current-total-cell');
                const payableCell = row.querySelector('.payable-total-cell');
                if (!dayInput || !dueInput || !currentCell || !payableCell) return;

                const recalc = () => {
                    const days = Math.max(0, parseFloat(dayInput.value || '0'));
                    const due = Math.max(0, parseFloat(dueInput.value || '0'));
                    const current = computeCurrent(days);
                    const payable = current + due;
                    currentCell.textContent = current.toFixed(2);
                    payableCell.textContent = payable.toFixed(2);
                };

                dayInput.addEventListener('input', recalc);
                recalc();
            });
        })();
    </script>
</body>
</html>
