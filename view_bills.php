<?php
include('db_connect.php');

$months = [];
$latestMonth = null;
$monthsRes = mysqli_query($conn, "SELECT DISTINCT billing_month FROM student_bills ORDER BY billing_month DESC");
if ($monthsRes) {
    while ($m = mysqli_fetch_assoc($monthsRes)) {
        $bm = (string)($m['billing_month'] ?? '');
        if ($bm !== '') {
            $months[] = $bm;
        }
    }
    if (count($months) > 0) {
        $latestMonth = $months[0];
    }
}

$selectedMonthParam = (string)($_GET['month'] ?? '');
$selectedMonth = $selectedMonthParam !== '' ? normalize_month($selectedMonthParam) : ($latestMonth ?? date('Y-m'));

$yearFilter = (string)($_GET['year'] ?? 'all');
$yearFilter = in_array($yearFilter, ['all', '1', '2', '3', '4'], true) ? $yearFilter : 'all';
$hostelFilter = normalize_hostel((string)($_GET['hostel'] ?? 'all'));

$sql = "SELECT
        sb.hall_ticket,
        COALESCE(rs.student_name, sb.student_name) AS student_name,
        rs.branch AS branch,
        sb.days_attended,
        sb.rate_per_day,
        sb.gst_percent,
        sb.maintenance_fee,
        sb.total_amount,
        sb.billing_month,
        sb.payment_status
    FROM student_bills sb
    LEFT JOIN registered_students rs
      ON (sb.student_id IS NOT NULL AND rs.id = sb.student_id)
      OR (sb.student_id IS NULL AND rs.hall_ticket = sb.hall_ticket)
    WHERE sb.billing_month = ?";

if ($yearFilter !== 'all') {
    $sql .= " AND rs.current_academic_year = ?";
}
if ($hostelFilter !== 'all') {
    $sql .= " AND COALESCE(rs.hostel_category, sb.hostel_category) = ?";
}

$sql .= " ORDER BY sb.hall_ticket ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if ($yearFilter !== 'all' && $hostelFilter !== 'all') {
        $yf = (int)$yearFilter;
        mysqli_stmt_bind_param($stmt, 'sis', $selectedMonth, $yf, $hostelFilter);
    } elseif ($yearFilter !== 'all') {
        $yf = (int)$yearFilter;
        mysqli_stmt_bind_param($stmt, 'si', $selectedMonth, $yf);
    } elseif ($hostelFilter !== 'all') {
        mysqli_stmt_bind_param($stmt, 'ss', $selectedMonth, $hostelFilter);
    } else {
        mysqli_stmt_bind_param($stmt, 's', $selectedMonth);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mess Bill Records</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #28a745; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .total-row { background-color: #eee; font-weight: bold; font-size: 1.1em; }
        .link { color: #007bff; text-decoration: none; }
        .notice { background: #e8f5e9; color: #1b5e20; padding: 10px; border: 1px solid #c8e6c9; border-radius: 6px; margin-top: 10px; }
        .filter { margin-top: 12px; }
        .month-links { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 8px; }
        .month-link { display: inline-block; padding: 8px 10px; border: 1px solid #ddd; border-radius: 999px; text-decoration: none; color: #333; background: #fafafa; }
        .month-link:hover { background: #f0f0f0; }
        .month-link.active { background: #1565c0; color: #fff; border-color: #1565c0; }
        .badge { padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: bold; }
        .paid { background: #d4edda; color: #155724; }
        .unpaid { background: #f8d7da; color: #721c24; }
        .btn-mini { border: none; border-radius: 4px; padding: 6px 8px; color: #fff; cursor: pointer; }
        .btn-del { background: #c62828; }
        .btn-toggle { background: #1565c0; }
        .action-wrap { display: flex; gap: 6px; }
    </style>
</head>
<body>
    <h2>Student Mess Bill Records</h2>
    <a class="link" href="index.php">Add New Bill</a>

    <?php if (count($months) > 0): ?>
        <div class="month-links">
            <?php foreach ($months as $m): ?>
                <a class="month-link <?= $m === $selectedMonth ? 'active' : '' ?>" href="view_bills.php?month=<?= urlencode($m) ?><?= $yearFilter !== 'all' ? '&year=' . urlencode($yearFilter) : '' ?><?= $hostelFilter !== 'all' ? '&hostel=' . urlencode($hostelFilter) : '' ?>">
                    <?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="filter" method="GET" action="view_bills.php">
        <label for="month"><strong>Billing Month:</strong></label>
        <input type="month" id="month" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
        <label for="year" style="margin-left:10px;"><strong>Academic Year:</strong></label>
        <select id="year" name="year">
            <option value="all" <?= $yearFilter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="1" <?= $yearFilter === '1' ? 'selected' : '' ?>>1st Year</option>
            <option value="2" <?= $yearFilter === '2' ? 'selected' : '' ?>>2nd Year</option>
            <option value="3" <?= $yearFilter === '3' ? 'selected' : '' ?>>3rd Year</option>
            <option value="4" <?= $yearFilter === '4' ? 'selected' : '' ?>>4th Year</option>
        </select>
        <input type="hidden" name="hostel" value="<?= htmlspecialchars($hostelFilter, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">View</button>
    </form>

    <?php if (isset($_GET['saved'])): ?><div class="notice">Saved/Updated <?= (int)$_GET['saved'] ?> bill row(s) for <?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>.</div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="notice">Bill row deleted successfully.</div><?php endif; ?>
    <?php if (isset($_GET['status_updated'])): ?><div class="notice">Payment status updated successfully.</div><?php endif; ?>

    <table>
        <tr>
            <th>Billing Month</th>
            <th>Hall Ticket</th>
            <th>Name</th>
            <th>Branch</th>
            <th>Days</th>
            <th>Rate</th>
            <th>GST %</th>
            <th>Maintenance</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php
        $grand_total = 0;

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $grand_total += (float)$row['total_amount'];
                $status = strtolower((string)$row['payment_status']);
                $isPaid = $status === 'paid';
                $toggleTo = $isPaid ? 'unpaid' : 'paid';
                $toggleLabel = $isPaid ? 'Mark Unpaid' : 'Mark Paid';

                echo "<tr>
                    <td>" . htmlspecialchars($row['billing_month'], ENT_QUOTES, 'UTF-8') . "</td>
                    <td>" . htmlspecialchars($row['hall_ticket'], ENT_QUOTES, 'UTF-8') . "</td>
                    <td>" . htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . "</td>
                    <td>" . htmlspecialchars((string)($row['branch'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td>
                    <td>" . (int)$row['days_attended'] . "</td>
                    <td>Rs " . number_format((float)$row['rate_per_day'], 2) . "</td>
                    <td>" . number_format((float)$row['gst_percent'], 2) . "</td>
                    <td>Rs " . number_format((float)$row['maintenance_fee'], 2) . "</td>
                    <td>Rs " . number_format((float)$row['total_amount'], 2) . "</td>
                    <td><span class='badge " . ($isPaid ? 'paid' : 'unpaid') . "'>" . strtoupper($status) . "</span></td>
                    <td>
                        <div class='action-wrap'>
                            <form method='POST' action='update_bill_status.php'>
                                <input type='hidden' name='hall_ticket' value='" . htmlspecialchars($row['hall_ticket'], ENT_QUOTES, 'UTF-8') . "'>
                                <input type='hidden' name='billing_month' value='" . htmlspecialchars($row['billing_month'], ENT_QUOTES, 'UTF-8') . "'>
                                <input type='hidden' name='status' value='" . $toggleTo . "'>
                                <input type='hidden' name='return_month' value='" . htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') . "'>
                                <button class='btn-mini btn-toggle' type='submit'>" . $toggleLabel . "</button>
                            </form>
                            <form method='POST' action='delete_bill.php' onsubmit='return confirm(\"Delete this bill row?\");'>
                                <input type='hidden' name='hall_ticket' value='" . htmlspecialchars($row['hall_ticket'], ENT_QUOTES, 'UTF-8') . "'>
                                <input type='hidden' name='billing_month' value='" . htmlspecialchars($row['billing_month'], ENT_QUOTES, 'UTF-8') . "'>
                                <input type='hidden' name='return_month' value='" . htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') . "'>
                                <button class='btn-mini btn-del' type='submit'>Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='11'>No bill records found for " . htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') . ".</td></tr>";
        }
        ?>
        <tr class="total-row">
            <td colspan="8" style="text-align:right;">Total Collection:</td>
            <td>Rs <?php echo number_format($grand_total, 2); ?></td>
            <td colspan="2"></td>
        </tr>
    </table>
    <br>
    <a class="link" href="admin_section.php">Back to Admin Dashboard</a>
</body>
</html>
<?php if ($stmt) { mysqli_stmt_close($stmt); } ?>
