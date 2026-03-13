<!DOCTYPE html>
<html>

<head>
    <title>Student Portal</title>
</head>

<body>
    <div style="width:300px; margin:100px auto; padding:20px; border:1px solid #ccc; text-align:center;">
        <h2>Student Bill Portal</h2>
        <form action="student_view.php" method="POST">
            <input type="text" name="ht_no" placeholder="Enter Hall Ticket Number" required
                style="width:90%; padding:10px;"><br><br>
            <button type="submit" style="padding:10px 20px; background:green; color:white; border:none;">View My
                Bill</button>

        </form>
        <br>
        <button style="padding:10px 20px; background:green; color:white; border:none;" href="home.php"><a
                href="home.php" style="text-decoration:none; text-decoration-line:none; color:white;">back to
                home</a></button>

    </div>
</body>

</html>

<!-- look chat, i fixed it....btw....i have a php page adminlogin.php...after login which takes me to index.php....here i want to make change, after admin login, i want a different page to open which shows 3 buttons....one to view existing bills,view students data, enter data page..... -->