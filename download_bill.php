<?php
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$hallTickets = $_POST['hall_ticket'] ?? [];
$names = $_POST['student_name'] ?? [];
$daysList = $_POST['days'] ?? [];
$dueAmounts = $_POST['due_amount'] ?? [];
$rate = (float)($_POST['rate'] ?? 0);
$gst = (float)($_POST['gst'] ?? 0);
$maintenance = (float)($_POST['maintenance_fee'] ?? 0);
$billingMonth = normalize_month((string)($_POST['billing_month'] ?? date('Y-m')));
$hostelFilter = normalize_hostel((string)($_POST['hostel'] ?? 'all'));

$rows = [];
for ($i = 0; $i < count($hallTickets); $i++) {
    $ht = trim((string)($hallTickets[$i] ?? ''));
    $name = trim((string)($names[$i] ?? ''));
    $days = (int)($daysList[$i] ?? 0);
    $due = (float)($dueAmounts[$i] ?? 0);

    if ($ht === '' || $name === '') {
        continue;
    }

    $subtotal = $days * $rate;
    $gstAmount = ($subtotal * $gst) / 100;
    $currentBill = $subtotal + $gstAmount + $maintenance;
    $payableTotal = $currentBill + $due;

    $rows[] = [
        'hall_ticket' => $ht,
        'student_name' => $name,
        'days' => $days,
        'rate' => $rate,
        'gst' => $gst,
        'maintenance' => $maintenance,
        'subtotal' => $subtotal,
        'gst_amount' => $gstAmount,
        'current_bill' => $currentBill,
        'due' => $due,
        'payable_total' => $payableTotal
    ];
}

$generatedOn = date('d-m-Y h:i A');
$grandCurrent = 0;
$grandDue = 0;
$grandPayable = 0;
foreach ($rows as $r) {
    $grandCurrent += $r['current_bill'];
    $grandDue += $r['due'];
    $grandPayable += $r['payable_total'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mess Bill PDF</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #222; background: #f7f7f7; }
        .toolbar { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 15px; }
        .btn { border: none; border-radius: 6px; padding: 10px 14px; color: #fff; text-decoration: none; cursor: pointer; font-weight: bold; }
        .btn-print { background: #007bff; }
        .btn-back { background: #6c757d; }
        .sheet { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 12px; }
        .title { margin: 0; font-size: 22px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .num { text-align: right; }
        .grand { margin-top: 12px; text-align: right; font-size: 17px; font-weight: bold; }
        @media print {
            body { background: #fff; margin: 0; }
            .toolbar { display: none; }
            .sheet { border: none; border-radius: 0; margin: 0; padding: 12mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="btn btn-back" href="index.php?month=<?= urlencode($billingMonth) ?>">Back</a>
        <button class="btn btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="sheet">
        <div class="header">
            <h1 class="title">Mess Bill Summary (<?= htmlspecialchars($billingMonth, ENT_QUOTES, 'UTF-8') ?>)<?= $hostelFilter !== 'all' ? ' - ' . strtoupper($hostelFilter) . ' HOSTEL' : '' ?></h1>
            <div>Generated: <?= htmlspecialchars($generatedOn, ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <?php if (count($rows) === 0): ?>
            <p>No bill data available. Please go back and enter days for students.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Hallticket</th>
                    <th>Name</th>
                    <th>Days</th>
                    <th>Current Bill (Rs)</th>
                    <th>Due (Rs)</th>
                    <th>Total Payable (Rs)</th>
                </tr>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['hall_ticket'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['student_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$r['days'] ?></td>
                        <td class="num"><?= number_format((float)$r['current_bill'], 2) ?></td>
                        <td class="num"><?= number_format((float)$r['due'], 2) ?></td>
                        <td class="num"><strong><?= number_format((float)$r['payable_total'], 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <div class="grand">Grand Current Bill: Rs <?= number_format($grandCurrent, 2) ?></div>
            <div class="grand">Grand Due: Rs <?= number_format($grandDue, 2) ?></div>
            <div class="grand">Grand Payable: Rs <?= number_format($grandPayable, 2) ?></div>
        <?php endif; ?>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 250);
        });
    </script>
</body>
</html>
