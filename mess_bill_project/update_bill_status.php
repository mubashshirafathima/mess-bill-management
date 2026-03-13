<?php
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hallTicket = trim((string)($_POST['hall_ticket'] ?? ''));
    $billingMonth = normalize_month((string)($_POST['billing_month'] ?? date('Y-m')));
    $status = strtolower(trim((string)($_POST['status'] ?? 'unpaid')));
    $status = $status === 'paid' ? 'paid' : 'unpaid';
    $returnMonth = normalize_month((string)($_POST['return_month'] ?? $billingMonth));

    $stmt = mysqli_prepare($conn, "UPDATE student_bills SET payment_status = ? WHERE hall_ticket = ? AND billing_month = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sss', $status, $hallTicket, $billingMonth);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header('Location: view_bills.php?status_updated=1&month=' . urlencode($returnMonth));
    exit;
}

header('Location: view_bills.php');
exit;
?>
