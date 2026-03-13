<?php
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hallTicket = trim((string)($_POST['hall_ticket'] ?? ''));

    $stmt = mysqli_prepare($conn, "DELETE FROM registered_students WHERE hall_ticket = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $hallTicket);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header('Location: view_registered_students.php?deleted=1');
    exit;
}

header('Location: view_registered_students.php');
exit;
?>
