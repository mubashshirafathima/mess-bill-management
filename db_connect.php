<?php
$conn = mysqli_connect("localhost", "root", "", "mess_bill_db");

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

function ensure_column(mysqli $conn, string $table, string $column, string $definition): void {
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $check = mysqli_query($conn, "SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'");

    if ($check && mysqli_num_rows($check) === 0) {
        mysqli_query($conn, "ALTER TABLE `{$tableEsc}` ADD COLUMN `{$columnEsc}` {$definition}");
    }
}

function normalize_month(string $month): string {
    return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
}

function normalize_hostel(string $hostel): string {
    $hostel = strtolower(trim($hostel));
    return ($hostel === 'girls' || $hostel === 'boys') ? $hostel : 'all';
}

function previous_month(string $month): string {
    $date = DateTime::createFromFormat('Y-m-d', $month . '-01');
    if (!$date) {
        return date('Y-m');
    }
    $date->modify('-1 month');
    return $date->format('Y-m');
}

function ensure_composite_month_unique(mysqli $conn): void {
    $table = 'student_bills';

    ensure_column($conn, $table, 'billing_month', "VARCHAR(7) NOT NULL DEFAULT '" . date('Y-m') . "'");
    ensure_column($conn, $table, 'payment_status', "ENUM('paid','unpaid') NOT NULL DEFAULT 'unpaid'");
    ensure_column($conn, $table, 'student_id', "INT NULL");

    mysqli_query($conn, "UPDATE student_bills SET billing_month = DATE_FORMAT(created_at, '%Y-%m') WHERE billing_month = '' OR billing_month IS NULL");
    mysqli_query($conn, "UPDATE student_bills SET payment_status = 'unpaid' WHERE payment_status IS NULL OR payment_status = ''");

    $oldUniqueRes = mysqli_query($conn, "SHOW INDEX FROM student_bills WHERE Key_name = 'hall-ticket numbers'");
    if ($oldUniqueRes && mysqli_num_rows($oldUniqueRes) > 0) {
        mysqli_query($conn, "ALTER TABLE student_bills DROP INDEX `hall-ticket numbers`");
    }

    $newUniqueRes = mysqli_query($conn, "SHOW INDEX FROM student_bills WHERE Key_name = 'uniq_hall_month'");
    if ($newUniqueRes && mysqli_num_rows($newUniqueRes) === 0) {
        mysqli_query($conn, "ALTER TABLE student_bills ADD UNIQUE KEY uniq_hall_month (hall_ticket, billing_month)");
    }

    // Add (student_id, billing_month) uniqueness for normalized linking.
    $newUniqueRes2 = mysqli_query($conn, "SHOW INDEX FROM student_bills WHERE Key_name = 'uniq_student_month'");
    if ($newUniqueRes2 && mysqli_num_rows($newUniqueRes2) === 0) {
        // Note: MySQL allows multiple NULLs in UNIQUE keys; this keeps legacy rows valid.
        mysqli_query($conn, "ALTER TABLE student_bills ADD UNIQUE KEY uniq_student_month (student_id, billing_month)");
    }
}

function get_consecutive_due(mysqli $conn, string $hallTicket, ?string $beforeMonth = null): float {
    $hallTicket = trim($hallTicket);
    if ($hallTicket === '') {
        return 0.0;
    }

    $beforeMonth = $beforeMonth !== null ? normalize_month($beforeMonth) : date('Y-m');
    $expectedMonth = previous_month($beforeMonth);

    $sql = "SELECT billing_month, total_amount, payment_status
            FROM student_bills
            WHERE hall_ticket = ? AND billing_month < ?
            ORDER BY billing_month DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return 0.0;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $hallTicket, $beforeMonth);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $streak = 0;
    $due = 0.0;

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rowMonth = (string)$row['billing_month'];
            $status = strtolower((string)$row['payment_status']);

            // Dues only when consecutive monthly unpaid records exist.
            if ($rowMonth !== $expectedMonth) {
                break;
            }
            if ($status !== 'unpaid') {
                break;
            }

            $streak++;
            $due += (float)$row['total_amount'];
            $expectedMonth = previous_month($expectedMonth);
        }
    }

    mysqli_stmt_close($stmt);
    return $streak >= 2 ? $due : 0.0;
}


function get_total_due_summary(mysqli $conn, string $hallTicket): array {
    $hallTicket = trim($hallTicket);
    if ($hallTicket === '') {
        return ['total' => 0.0, 'expression' => ''];
    }

    $sql = "SELECT billing_month, total_amount
            FROM student_bills
            WHERE hall_ticket = ? AND payment_status = 'unpaid'
            ORDER BY billing_month ASC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return ['total' => 0.0, 'expression' => ''];
    }

    mysqli_stmt_bind_param($stmt, 's', $hallTicket);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $parts = [];
    $total = 0.0;
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $month = (string)$row['billing_month'];
            $amt = (float)$row['total_amount'];
            $total += $amt;
            $parts[] = $month . ": Rs " . number_format($amt, 2);
        }
    }

    mysqli_stmt_close($stmt);

    if (count($parts) === 0) {
        return ['total' => 0.0, 'expression' => 'No dues'];
    }

    $expression = implode(" + ", $parts) . " = Rs " . number_format($total, 2);
    return ['total' => $total, 'expression' => $expression];
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS registered_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hall_ticket VARCHAR(50) NOT NULL UNIQUE,
    student_name VARCHAR(150) NOT NULL,
    joining_date DATE NOT NULL,
    end_date DATE NULL,
    expected_end_date DATE NULL,
    branch VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    current_academic_year INT NOT NULL DEFAULT 1,
    hostel_category ENUM('boys','girls') NOT NULL DEFAULT 'boys',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

ensure_column($conn, 'registered_students', 'current_academic_year', 'INT NOT NULL DEFAULT 1');
mysqli_query($conn, "UPDATE registered_students SET current_academic_year = 1 WHERE current_academic_year IS NULL OR current_academic_year NOT BETWEEN 1 AND 4");
ensure_column($conn, 'registered_students', 'hostel_category', "ENUM('boys','girls') NOT NULL DEFAULT 'boys'");
mysqli_query($conn, "UPDATE registered_students SET hostel_category = 'boys' WHERE hostel_category IS NULL OR hostel_category NOT IN ('boys','girls')");
ensure_column($conn, 'registered_students', 'expected_end_date', 'DATE NULL');

// Make end_date nullable (older schema had NOT NULL).
$endCol = mysqli_query($conn, "SHOW COLUMNS FROM registered_students LIKE 'end_date'");
if ($endCol && mysqli_num_rows($endCol) > 0) {
    $endInfo = mysqli_fetch_assoc($endCol);
    if (isset($endInfo['Null']) && strtoupper((string)$endInfo['Null']) === 'NO') {
        mysqli_query($conn, "ALTER TABLE registered_students MODIFY end_date DATE NULL");
    }
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS student_bills (
    hall_ticket VARCHAR(50) NOT NULL,
    student_id INT NULL,
    student_name VARCHAR(150) NOT NULL,
    days_attended INT NOT NULL,
    rate_per_day DECIMAL(10,2) NOT NULL,
    gst_percent DECIMAL(5,2) NOT NULL,
    maintenance_fee DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
    total_amount DECIMAL(12,2) NOT NULL,
    billing_month VARCHAR(7) NOT NULL,
    payment_status ENUM('paid','unpaid') NOT NULL DEFAULT 'unpaid',
    hostel_category ENUM('boys','girls') NOT NULL DEFAULT 'boys',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_hall_month (hall_ticket, billing_month)
)");

ensure_column($conn, 'student_bills', 'maintenance_fee', 'DECIMAL(10,2) NOT NULL DEFAULT 1000.00');
ensure_column($conn, 'student_bills', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
ensure_column($conn, 'student_bills', 'student_id', 'INT NULL');
ensure_column($conn, 'student_bills', 'hostel_category', "ENUM('boys','girls') NOT NULL DEFAULT 'boys'");
ensure_composite_month_unique($conn);

// Backfill student_id in bills by hall_ticket match.
mysqli_query($conn, "UPDATE student_bills sb JOIN registered_students rs ON sb.hall_ticket = rs.hall_ticket SET sb.student_id = rs.id WHERE sb.student_id IS NULL");
// Backfill/sync hostel_category in bills from registered students where possible.
// This is important because existing rows got the default ('boys') when the column was added.
mysqli_query($conn, "UPDATE student_bills sb
    JOIN registered_students rs ON sb.hall_ticket = rs.hall_ticket
    SET sb.hostel_category = rs.hostel_category
    WHERE sb.hostel_category <> rs.hostel_category OR sb.hostel_category IS NULL");

// Ensure FK exists (idempotent-ish: only add if missing).
$fkRes = mysqli_query($conn, "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'student_bills'
      AND COLUMN_NAME = 'student_id'
      AND REFERENCED_TABLE_NAME = 'registered_students'
    LIMIT 1");
if ($fkRes && mysqli_num_rows($fkRes) === 0) {
    mysqli_query($conn, "ALTER TABLE student_bills
        ADD CONSTRAINT fk_student_bills_student
        FOREIGN KEY (student_id) REFERENCES registered_students(id)
        ON DELETE SET NULL ON UPDATE CASCADE");
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS billing_settings (
    id TINYINT PRIMARY KEY,
    rate_per_day DECIMAL(10,2) NOT NULL DEFAULT 100.00,
    gst_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    maintenance_fee DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

mysqli_query($conn, "INSERT INTO billing_settings (id, rate_per_day, gst_percent, maintenance_fee)
    SELECT 1, 100.00, 5.00, 1000.00
    WHERE NOT EXISTS (SELECT 1 FROM billing_settings WHERE id = 1)");
?>

