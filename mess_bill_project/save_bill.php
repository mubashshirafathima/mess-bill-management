<?php
include('db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ht = trim($_POST['hall_ticket'] ?? '');
    $name = trim($_POST['student_name'] ?? '');
    $days = (int)($_POST['days'] ?? 0);

    $settingsRes = mysqli_query($conn, "SELECT rate_per_day, gst_percent, maintenance_fee FROM billing_settings WHERE id = 1");
    $settings = $settingsRes ? mysqli_fetch_assoc($settingsRes) : null;

    $rate = $settings ? (float)$settings['rate_per_day'] : (float)($_POST['rate'] ?? 100);
    $gst = $settings ? (float)$settings['gst_percent'] : (float)($_POST['gst'] ?? 5);
    $maintenance = $settings ? (float)$settings['maintenance_fee'] : 1000.00;

    $subtotal = $days * $rate;
    $gstAmount = ($subtotal * $gst) / 100;
    $total = $subtotal + $gstAmount + $maintenance;

    $sql = "INSERT INTO student_bills (hall_ticket, student_name, days_attended, rate_per_day, gst_percent, maintenance_fee, total_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssidddd', $ht, $name, $days, $rate, $gst, $maintenance, $total);

        if (mysqli_stmt_execute($stmt)) {
            echo "<div style='padding:20px; font-family:sans-serif;'>";
            echo "<h2>Bill Saved Successfully!</h2>";
            echo "Student: " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "<br>";
            echo "Total Amount: <b>Rs " . number_format($total, 2) . "</b>";
            echo "<br><br><a href='index.php'>Back to Entry Form</a>";
            echo "<br><a href='view_bills.php' style='color:blue; text-decoration:none;'>View all records</a>";
            echo "</div>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error: Unable to prepare statement.";
    }
}
?>
