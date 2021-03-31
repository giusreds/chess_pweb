<?php
session_start();
if(isset($_SESSION["user_id"]))
header("Location: ./dashboard.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>Welcome</title>
  <meta charset="UTF-8">
</head>

<body>
  <form id="login_form">
    <input type="text" name="username">
    </input>
    <input type="text" name="password">
    </input>
    <input type="submit" value="SUBMIT">
  </form>
  <form id="register_form">
  </form>
  <p id="error"></p>

  <!-- jQuery -->
  <script src="./js/lib/jquery-3.6.0.min.js"></script>
  <!-- Auth -->
  <script src="./js/auth.js"></script>
</body>

</html>