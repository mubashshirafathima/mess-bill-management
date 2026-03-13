<?php
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$hallTickets = $_POST['hall_ticket'] ?? [];
$studentIds = $_POST['student_id'] ?? [];
$names = $_POST['student_name'] ?? [];
$daysList = $_POST['days'] ?? [];
$rate = (float)($_POST['rate'] ?? 0);
$gst = (float)($_POST['gst'] ?? 0);
$maintenance = (float)($_POST['maintenance_fee'] ?? 0);
$billingMonth = normalize_month((string)($_POST['billing_month'] ?? date('Y-m')));
$hostelFromForm = normalize_hostel((string)($_POST['hostel'] ?? 'all'));

$idMap = [];
$hostelMap = [];
$mapRes = mysqli_query($conn, "SELECT id, hall_ticket, hostel_category FROM registered_students");
if ($mapRes) {
    while ($r = mysqli_fetch_assoc($mapRes)) {
        $idMap[(string)$r['hall_ticket']] = (int)$r['id'];
        $hostelMap[(string)$r['hall_ticket']] = (string)$r['hostel_category'];
    }
}

$upsertSql = "INSERT INTO student_bills (student_id, hall_ticket, student_name, days_attended, rate_per_day, gst_percent, maintenance_fee, total_amount, billing_month, payment_status, hostel_category)
              VALUES (NULLIF(?, 0), ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', ?)
              ON DUPLICATE KEY UPDATE
                student_id = VALUES(student_id),
                student_name = VALUES(student_name),
                days_attended = VALUES(days_attended),
                rate_per_day = VALUES(rate_per_day),
                gst_percent = VALUES(gst_percent),
                maintenance_fee = VALUES(maintenance_fee),
                total_amount = VALUES(total_amount),
                hostel_category = VALUES(hostel_category)";

$upsertStmt = mysqli_prepare($conn, $upsertSql);
$savedCount = 0;

if ($upsertStmt) {
    for ($i = 0; $i < count($hallTickets); $i++) {
        $ht = trim((string)($hallTickets[$i] ?? ''));
        $sidRaw = $studentIds[$i] ?? null;
        $sid = is_numeric($sidRaw) ? (int)$sidRaw : ($idMap[$ht] ?? null);
        $name = trim((string)($names[$i] ?? ''));
        $days = (int)($daysList[$i] ?? 0);

        if ($ht === '' || $name === '') {
            continue;
        }

        $subtotal = $days * $rate;
        $gstAmount = ($subtotal * $gst) / 100;
        $total = $subtotal + $gstAmount + $maintenance;

        $sid = $sid === null ? 0 : (int)$sid;
        $hc = $hostelMap[$ht] ?? ($hostelFromForm !== 'all' ? $hostelFromForm : 'boys');
        mysqli_stmt_bind_param($upsertStmt, 'issiddddss', $sid, $ht, $name, $days, $rate, $gst, $maintenance, $total, $billingMonth, $hc);

        if (mysqli_stmt_execute($upsertStmt)) {
            $savedCount++;
        }
    }
    mysqli_stmt_close($upsertStmt);
}

header('Location: view_bills.php?saved=' . $savedCount . '&month=' . urlencode($billingMonth));
exit;
?>
