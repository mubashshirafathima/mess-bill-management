<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Bill Management System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: radial-gradient(1200px 600px at 20% 10%, #e6f7ff 0%, #f4f7f6 35%, #f4f7f6 100%);
            color: #333;
        }
        header {
            background: linear-gradient(135deg, #2c3e50 0%, #1f2f3f 100%);
            color: #fff;
            padding: 60px 20px;
            text-align: center;
        }
        .hero {
            padding: 28px 20px 10px;
            text-align: center;
            max-width: 900px;
            margin: 0 auto;
        }
        .container {
            display: flex;
            justify-content: center;
            gap: 22px;
            padding: 28px 20px 50px;
            flex-wrap: wrap;
            max-width: 1000px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            padding: 26px;
            border-radius: 14px;
            width: 320px;
            box-shadow: 0 10px 24px rgba(0,0,0,0.10);
            text-align: left;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(0,0,0,0.06);
        }
        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 28px rgba(0,0,0,0.12);
        }
        .card h3 {
            color: #2c3e50;
            margin: 0 0 10px;
            font-size: 1.25rem;
        }
        .card p {
            font-size: 0.95em;
            color: #666;
            line-height: 1.6;
            margin: 0 0 18px;
        }
        .btn {
            display: inline-block;
            padding: 12px 16px;
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            letter-spacing: 0.2px;
            transition: filter 0.2s, transform 0.2s;
        }
        .btn:active { transform: translateY(1px); }
        .btn-admin { background: #1abc9c; }
        .btn-student { background: #007bff; }
        .btn:hover { filter: brightness(0.95); }
        footer {
            padding: 24px 16px;
            background: #2c3e50;
            color: #fff;
            text-align: center;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

<header>
    <h1>Mess Bill Management System</h1>
    
</header>

<div class="hero">
    <h2>Welcome</h2>
    <p>Admin manages registrations, billing, and settings. Students can view their saved bills.</p>
</div>

<div class="container">
    <div class="card">
        <h3>Admin Login</h3>
        <p>Register students, generate monthly bills, update rates/GST/maintenance, and manage records.</p>
        <a href="admin_login.php" class="btn btn-admin">Continue as Admin</a>
    </div>

    <div class="card">
        <h3>Student Login</h3>
        <p>View your latest saved mess bill using your hallticket number.</p>
        <a href="student_login.php" class="btn btn-student">Continue as Student</a>
    </div>
</div>

<footer>
    (c) 2026 Mess Bill Management System
</footer>

</body>
</html>
