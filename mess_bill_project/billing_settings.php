<?php
include('db_connect.php');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rate = (float)($_POST['rate_per_day'] ?? 0);
    $gst = (float)($_POST['gst_percent'] ?? 0);
    $maintenance = (float)($_POST['maintenance_fee'] ?? 0);

    if ($rate < 0 || $gst < 0 || $maintenance < 0) {
        $message = 'Values cannot be negative.';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE billing_settings SET rate_per_day = ?, gst_percent = ?, maintenance_fee = ? WHERE id = 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ddd', $rate, $gst, $maintenance);
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Settings updated successfully.';
            } else {
                $message = 'Failed to update settings.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = 'Unable to prepare update query.';
        }
    }
}

$settingsRes = mysqli_query($conn, "SELECT rate_per_day, gst_percent, maintenance_fee FROM billing_settings WHERE id = 1");
$settings = $settingsRes ? mysqli_fetch_assoc($settingsRes) : null;

$rateVal = $settings ? (float)$settings['rate_per_day'] : 100.00;
$gstVal = $settings ? (float)$settings['gst_percent'] : 5.00;
$maintenanceVal = $settings ? (float)$settings['maintenance_fee'] : 1000.00;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Billing Settings</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 24px; }
        .wrap { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
        label { font-weight: bold; display: block; margin-top: 12px; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; margin-top: 6px; box-sizing: border-box; }
        .actions { margin-top: 16px; display: flex; gap: 10px; justify-content: flex-end; }
        .btn { border: none; border-radius: 6px; padding: 10px 12px; color: #fff; text-decoration: none; cursor: pointer; font-weight: bold; }
        .btn-save { background: #007bff; }
        .btn-back { background: #6c757d; }
        .msg { margin-top: 10px; font-size: 14px; color: #0f5132; }
    </style>
</head>
<body>
    <div class="wrap">
        <h2>Update Bill Settings</h2>
        <?php if ($message !== ''): ?>
            <div class="msg"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="rate_per_day">Rate Per Day (Rs)</label>
            <input type="number" step="0.01" id="rate_per_day" name="rate_per_day" value="<?= htmlspecialchars((string)$rateVal, ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="gst_percent">GST (%)</label>
            <input type="number" step="0.01" id="gst_percent" name="gst_percent" value="<?= htmlspecialchars((string)$gstVal, ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="maintenance_fee">Maintenance Fee (Rs)</label>
            <input type="number" step="0.01" id="maintenance_fee" name="maintenance_fee" value="<?= htmlspecialchars((string)$maintenanceVal, ENT_QUOTES, 'UTF-8') ?>" required>

            <div class="actions">
                <button class="btn btn-save" type="submit">Save Settings</button>
                <a class="btn btn-back" href="admin_section.php">Admin Dashboard</a>
            </div>
        </form>
    </div>
</body>
</html>
