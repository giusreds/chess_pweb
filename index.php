<?php
session_start();
include("./php/mysql.php");
if (isset($_SESSION["user"]))
  header("Location: ./dashboard.php");
// Se sto provando ad accedere (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST["username"];
  $password = $_POST["password"];

  $query = "SELECT * FROM user WHERE `username` = '{$username}'";
  $result = $mysqli->query($query);

  $password_hash = password_hash($password, PASSWORD_BCRYPT);

  if (password_verify($password, $result->fetch_array()["password"])) {
    $query = "SELECT * FROM user WHERE `username` = '{$username}'";
    $result = $mysqli->query($query);
    $_SESSION["user_id"] = $result->fetch_array()["id"];
    $_SESSION["user"] = $username;
    header("Location: ./dashboard.php");
  }
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
  <title>Welcome</title>
  <meta charset="UTF-8">
</head>

<body>
  <form action="./" method="POST">
    <input type="text" name="username">
    </input>
    <input type="text" name="password">
    </input>
    <input type="submit" value="SUBMIT">
  </form>
</body>

</html>