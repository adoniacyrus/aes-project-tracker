<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>

<h1>Welcome <?php echo $_SESSION['user_name']; ?></h1>

<p>Role: <?php echo $_SESSION['user_role']; ?></p>

<a href="?page=logout">Logout</a>
</body>
</html>