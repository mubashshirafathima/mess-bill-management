<?php
include('db_connect.php');

$hallTicket = trim((string)($_GET['hall_ticket'] ?? ''));
if ($hallTicket === '') {
    header('Location: view_registered_students.php');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT hall_ticket, student_name, joining_date, end_date, expected_end_date, branch, phone_number FROM registered_students WHERE hall_ticket = ? LIMIT 1");
$student = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $hallTicket);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $student = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
}

$bills = [];
$totalAll = 0.0;
$totalUnpaid = 0.0;
$totalPaid = 0.0;

$stmt2 = mysqli_prepare($conn, "SELECT billing_month, days_attended, total_amount, payment_status FROM student_bills WHERE hall_ticket = ? ORDER BY billing_month DESC");
if ($stmt2) {
    mysqli_stmt_bind_param($stmt2, 's', $hallTicket);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    if ($res2) {
        while ($row = mysqli_fetch_assoc($res2)) {
            $amt = (float)$row['total_amount'];
            $totalAll += $amt;
            if (strtolower((string)$row['payment_status']) === 'paid') {
                $totalPaid += $amt;
            } else {
                $totalUnpaid += $amt;
            }
            $bills[] = $row;
        }
    }
    mysqli_stmt_close($stmt2);
}

$dueSummary = get_total_due_summary($conn, $hallTicket);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Details</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f6f9; }
        .wrap { max-width: 1000px; margin: 0 auto; background: #fff; padding: 18px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #1565c0; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
        .num { text-align: right; }
        .pill { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: bold; }
        .paid { background: #d4edda; color: #155724; }
        .unpaid { background: #f8d7da; color: #721c24; }
        .summary { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 12px; }
        .box { background: #f7f7f7; border: 1px solid #e6e6e6; border-radius: 8px; padding: 10px; }
        .links { margin-top: 12px; text-align: right; }
        .links a { text-decoration: none; color: #007bff; margin-left: 10px; }
        .due { margin-top: 12px; background: #fff3cd; border: 1px solid #ffeeba; padding: 10px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="wrap">
        <h2>Student Details</h2>

        <?php if (!$student): ?>
            <p style="color:#b42318;">Student not found in registered students.</p>
        <?php else: ?>
            <p><strong>Name:</strong> <?= htmlspecialchars($student['student_name'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Hall Ticket:</strong> <?= htmlspecialchars($student['hall_ticket'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Branch:</strong> <?= htmlspecialchars($student['branch'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Hostel Joining Date:</strong> <?= htmlspecialchars($student['joining_date'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Expected End Date:</strong> <?= htmlspecialchars((string)($student['expected_end_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Actual End Date (Graduated Date):</strong> <?= htmlspecialchars((string)($student['end_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($student['phone_number'], ENT_QUOTES, 'UTF-8') ?></p>

            <div class="due">
                <strong>Dues (Unpaid Months):</strong> Rs <?= number_format((float)$dueSummary['total'], 2) ?><br>
                <span><?= htmlspecialchars((string)$dueSummary['expression'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="summary">
                <div class="box"><strong>Total of all saved bills:</strong><br>Rs <?= number_format($totalAll, 2) ?></div>
                <div class="box"><strong>Total paid:</strong><br>Rs <?= number_format($totalPaid, 2) ?></div>
                <div class="box"><strong>Total unpaid:</strong><br>Rs <?= number_format($totalUnpaid, 2) ?></div>
            </div>

            <h3 style="margin-top:16px;">Bill Entries</h3>
            <table>
                <tr>
                    <th>Month</th>
                    <th>Days</th>
                    <th>Total Amount (Rs)</th>
                    <th>Status</th>
                </tr>
                <?php if (count($bills) === 0): ?>
                    <tr><td colspan="4">No bills found for this student.</td></tr>
                <?php else: ?>
                    <?php foreach ($bills as $b): ?>
                        <?php $st = strtolower((string)$b['payment_status']); ?>
                        <tr>
                            <td><?= htmlspecialchars($b['billing_month'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)$b['days_attended'] ?></td>
                            <td class="num"><?= number_format((float)$b['total_amount'], 2) ?></td>
                            <td><span class="pill <?= $st === 'paid' ? 'paid' : 'unpaid' ?>"><?= htmlspecialchars(strtoupper($st), ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        <?php endif; ?>

        <div class="links">
            <a href="view_registered_students.php">Back to Registered Students</a>
            <a href="admin_section.php">Admin Dashboard</a>
        </div>
    </div>
</body>
</html>
