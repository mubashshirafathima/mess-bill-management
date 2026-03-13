<?php
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hallTicket = trim((string)($_POST['hall_ticket'] ?? ''));
    $billingMonth = normalize_month((string)($_POST['billing_month'] ?? date('Y-m')));
    $returnMonth = normalize_month((string)($_POST['return_month'] ?? $billingMonth));

    $stmt = mysqli_prepare($conn, "DELETE FROM student_bills WHERE hall_ticket = ? AND billing_month = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $hallTicket, $billingMonth);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header('Location: view_bills.php?deleted=1&month=' . urlencode($returnMonth));
    exit;
}

header('Location: view_bills.php');
exit;
?>
