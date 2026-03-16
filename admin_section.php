<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f6f9;
        }
        .card {
            width: 460px;
            background: #ffffff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }
        .actions {
            display: grid;
            gap: 12px;
        }
        .btn {
            display: block;
            text-decoration: none;
            color: #fff;
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
        }
        .btn-bills { background: #007bff; }
        .btn-registered { background: #28a745; }
        .btn-entry { background: #ff9800; }
        .btn-register { background: #6f42c1; }
        .btn-settings { background: #c0392b; }
        .btn-academic { background: #2f6f3e; }
        .btn:hover { opacity: 0.92; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Admin Dashboard</h2>
        <div class="actions">
            <a class="btn btn-bills hostel-pick" data-href="view_bills.php" href="#">View Existing Bills</a>
            <a class="btn btn-registered hostel-pick" data-href="view_registered_students.php" href="#">View Registered Students</a>
            <a class="btn btn-entry hostel-pick" data-href="index.php" href="#">Enter Students Data</a>
            <a class="btn btn-register" href="register_student.php">Register Student</a>
            <a class="btn btn-settings" href="billing_settings.php">Update Bill Settings</a>
            <a class="btn btn-academic" href="academic_management.php">Academic Year Management</a>
        </div>
    </div>

    <div id="hostelModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); align-items:center; justify-content:center; padding:18px;">
        <div style="background:#fff; width:420px; max-width:95vw; border-radius:10px; padding:18px; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 10px;">Select Hostel Category</h3>
            <p style="margin:0 0 14px; color:#555;">Please select to continue:</p>
            <div style="display:grid; gap:10px;">
                <button id="pickBoys" style="padding:12px; border:none; border-radius:8px; background:#007bff; color:#fff; font-weight:bold; cursor:pointer;">Boys Hostel</button>
                <button id="pickGirls" style="padding:12px; border:none; border-radius:8px; background:#e91e63; color:#fff; font-weight:bold; cursor:pointer;">Girls Hostel</button>
                <button id="pickAll" style="padding:12px; border:none; border-radius:8px; background:#2c3e50; color:#fff; font-weight:bold; cursor:pointer;">All</button>
                <button id="closeModal" style="padding:10px; border:1px solid #ddd; border-radius:8px; background:#fff; cursor:pointer;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('hostelModal');
            const pickBoys = document.getElementById('pickBoys');
            const pickGirls = document.getElementById('pickGirls');
            const pickAll = document.getElementById('pickAll');
            const closeModal = document.getElementById('closeModal');
            let nextHref = null;

            function openModal(href) {
                nextHref = href;
                modal.style.display = 'flex';
            }

            function close() {
                modal.style.display = 'none';
                nextHref = null;
            }

            function go(hostel) {
                if (!nextHref) return;
                const sep = nextHref.indexOf('?') >= 0 ? '&' : '?';
                window.location.href = nextHref + sep + 'hostel=' + encodeURIComponent(hostel);
            }

            document.querySelectorAll('.hostel-pick').forEach(a => {
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    const href = a.getAttribute('data-href');
                    openModal(href);
                });
            });

            pickBoys.addEventListener('click', () => go('boys'));
            pickGirls.addEventListener('click', () => go('girls'));
            pickAll.addEventListener('click', () => go('all'));
            closeModal.addEventListener('click', close);
            modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
        })();
    </script>
</body>
</html>
