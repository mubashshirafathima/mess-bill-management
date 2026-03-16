<?php
include('db_connect.php');

$hallTicket = '';
if (isset($_POST['ht_no'])) {
    $hallTicket = trim((string)$_POST['ht_no']);
} elseif (isset($_GET['hall_ticket'])) {
    $hallTicket = trim((string)$_GET['hall_ticket']);
}

$student = null;
$bills = [];
$dueSummary = ['total' => 0.0, 'expression' => 'No dues'];
$fallbackName = null;
$isRegistered = false;

if ($hallTicket !== '') {
    // Student identity comes from registered_students; bills come from student_bills (same source as view_bills.php).
    $stmt = mysqli_prepare($conn, "SELECT hall_ticket, student_name, branch FROM registered_students WHERE hall_ticket = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $hallTicket);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $student = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }
    $isRegistered = $student !== null;

    // Full report: all months for this hallticket (same raw data admin sees, just not month-filtered).
    $stmt2 = mysqli_prepare(
        $conn,
        "SELECT billing_month, days_attended, total_amount, payment_status
         FROM student_bills
         WHERE hall_ticket = ?
         ORDER BY billing_month DESC"
    );
    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, 's', $hallTicket);
        mysqli_stmt_execute($stmt2);
        $res2 = mysqli_stmt_get_result($stmt2);
        if ($res2) {
            while ($row = mysqli_fetch_assoc($res2)) {
                $bills[] = $row;
            }
        }
        mysqli_stmt_close($stmt2);
    }

    // If student is not registered anymore (or never registered), try to show name from bills.
    if (!$isRegistered) {
        $stmt3 = mysqli_prepare($conn, "SELECT student_name FROM student_bills WHERE hall_ticket = ? ORDER BY billing_month DESC LIMIT 1");
        if ($stmt3) {
            mysqli_stmt_bind_param($stmt3, 's', $hallTicket);
            mysqli_stmt_execute($stmt3);
            $res3 = mysqli_stmt_get_result($stmt3);
            $row3 = $res3 ? mysqli_fetch_assoc($res3) : null;
            $fallbackName = $row3 && isset($row3['student_name']) ? (string)$row3['student_name'] : null;
            mysqli_stmt_close($stmt3);
        }
    }

    // Dues are calculated from unpaid months in student_bills (same truth source admin uses).
    $dueSummary = get_total_due_summary($conn, $hallTicket);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Bills</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f6f9; }
        .wrap { max-width: 900px; margin: 0 auto; background: #fff; padding: 18px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #1565c0; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
        .num { text-align: right; }
        .pill { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: bold; }
        .paid { background: #d4edda; color: #155724; }
        .unpaid { background: #f8d7da; color: #721c24; }
        .due { margin-top: 12px; background: #fff3cd; border: 1px solid #ffeeba; padding: 10px; border-radius: 6px; }
        .links { margin-top: 12px; text-align: right; }
        .links a { text-decoration: none; color: #007bff; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="wrap">
        <h2>My Bills</h2>

        <?php if ($hallTicket === ''): ?>
            <p style="color:#b42318;">No Hall Ticket provided.</p>
        <?php else: ?>
            <?php if (!$isRegistered): ?>
                <p style="color:#b42318;"><strong>Note:</strong> This hallticket is not found in the registered students list.</p>
            <?php endif; ?>

            <p><strong>Name:</strong> <?= htmlspecialchars($student['student_name'] ?? ($fallbackName ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Hall Ticket:</strong> <?= htmlspecialchars($hallTicket, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Branch:</strong> <?= htmlspecialchars($student['branch'] ?? 'Unknown (not registered)', ENT_QUOTES, 'UTF-8') ?></p>

            <div class="due">
                <strong>Dues (Unpaid Months):</strong> Rs <?= number_format((float)$dueSummary['total'], 2) ?><br>
                <span><?= htmlspecialchars((string)$dueSummary['expression'], ENT_QUOTES, 'UTF-8') ?></span>
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
            <a href="student_login.php">Back to Login</a>
            <a href="home.php">Back to Home</a>
        </div>
    </div>
</body>
</html>
