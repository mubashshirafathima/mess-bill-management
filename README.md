
# Hostel Mess Bill Management System

A PHP + MySQL (XAMPP) web app to manage hostel mess billing with:
- student registration (with hostel category + academic year)
- month-wise bill entry and storage
- paid/unpaid tracking and dues summary
- admin controls for promotion/graduation
- printable bill export (browser “Save as PDF”)

## Tech Stack
- PHP (runs on XAMPP)
- MySQL / MariaDB (phpMyAdmin)
- HTML/CSS/JavaScript

## Main Features
### Admin
- Register students (hallticket unique)
- Choose Hostel Category: Boys / Girls
- Manual Academic Year: 1–4 (promotions via admin controls)
- Enter monthly bills in tabular format (month + year + hostel filtering)
- View bills month-wise with:
  - payment status toggle (PAID / UNPAID)
  - delete bill entry (per student per month)
  - filtering by Academic Year and Hostel Category
- View registered students with:
  - dues summary string (month-wise unpaid totals)
  - “View more” student report (all months + totals)

### Student
- Login with hallticket number
- View full report:
  - name / branch
  - all month entries saved by admin
  - paid/unpaid status per month
  - dues statement (unpaid months)

## Project Structure (Important Files)
- `home.php` – landing page (Admin / Student)
- `admin_login.php` – admin login
- `admin_section.php` – admin dashboard (hostel selection popup)
- `register_student.php` – student registration
- `index.php` – monthly bill entry (table)
- `save_batch_bill.php` – saves the monthly bill table into DB
- `view_bills.php` – admin view for saved bills (month links + filters + actions)
- `update_bill_status.php` – mark bill paid/unpaid
- `delete_bill.php` – delete a bill row (student + month)
- `view_registered_students.php` – registered students list (with dues + “view more”)
- `registered_student_details.php` – full student report (admin)
- `student_login.php` – student portal login
- `student_view.php` – student report
- `billing_settings.php` – update rate/GST/maintenance
- `academic_management.php` – manual promotion/graduation controls
- `db_connect.php` – DB connection + table migrations/helpers

## Database
Database name: `mess_bill_db`

Tables are auto-created/updated by `db_connect.php` when pages run.

### `registered_students`
Stores student profile (master data).
Key fields:
- `id` (PK)
- `hall_ticket` (UNIQUE)
- `student_name`, `branch`, `phone_number`
- `hostel_category` (`boys`/`girls`)
- `current_academic_year` (1–4)
- `joining_date` (Hostel Joining Date)
- `expected_end_date` (optional)
- `end_date` (Actual End Date set when graduating)

### `student_bills`
Stores month-wise bill rows.
Key fields:
- `hall_ticket`, `billing_month` (`YYYY-MM`) with unique constraint
- `student_id` (FK to `registered_students.id`, nullable for legacy rows)
- `days_attended`, `rate_per_day`, `gst_percent`, `maintenance_fee`, `total_amount`
- `payment_status` (`paid`/`unpaid`)
- `hostel_category` (`boys`/`girls`)

### `billing_settings`
Single-row config:
- `rate_per_day`
- `gst_percent`
- `maintenance_fee`

## Dues Logic
Dues are calculated from `student_bills`:
- Any month marked `UNPAID` contributes to the due statement.
- The UI shows a readable expression like:
  - `2026-01: Rs 2500.00 + 2026-02: Rs 2600.00 = Rs 5100.00`

## Setup (XAMPP)
1. Install XAMPP and start:
   - Apache
   - MySQL
2. Copy the project folder into:
   - `C:\xampp\htdocs\mess_bill_project\mess_bill_project`
3. Create the database:
   - Open phpMyAdmin → create DB named `mess_bill_db`
4. Open in browser:
   - `http://localhost/mess_bill_project/mess_bill_project/home.php`

## Admin Login
Default credentials are set in `admin_login.php`.
Update them there before deploying.

## Using the System (Quick Flow)
1. Admin Login
2. Register students (select Hostel Category + Branch + Academic Year)
3. Enter monthly bills:
   - pick Month + Academic Year + Hostel Category
   - enter days per student
   - Save Bill (writes to DB)
4. View Existing Bills:
   - pick Month (quick links)
   - mark PAID/UNPAID or delete entries
5. Students use hallticket to view their full bill history + dues

## Notes / Safety
- Delete actions remove rows from the MySQL tables (not just from the UI).
- This project is meant for local/LAN usage; hardening (sessions, hashing, roles) is recommended before internet deployment.

## Future Improvements (Optional)
- Authentication: sessions + password hashing for admin
- “Missing bill for this month” indicator in billing entry
- Export consolidated PDF (true server-side PDF library) if needed
- Attendance import (CSV) for faster entry at scale

## License
Choose a license (MIT recommended) or keep it private.

