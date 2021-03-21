<?php
session_start();
print uniqid() . "<br>";
if (!isset($_SESSION["user"])) {
  header("Location: ./");
  exit;
}
print $_SESSION["user"];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="./css/join.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
  <header>
    <img src="./img/logo.png" id="logo">
  </header>
  <main>
    <button class="menu_btn" id="host_btn">Host new match</button>
    <button class="menu_btn" id="join_btn">Join a match</button>
  </main>
  <div id="join_dialog" class="dialog">
    <div class="dialog_content">
      <span id="dialog_close">CLOSE</span>
      <form id="host">
        <h2>Host</h2>
        <input type="hidden" name="action" value="host">
        <input type="radio" name="num_players" value="2" required>
        <label for="2">2</label><br>
        <input type="radio" name="num_players" value="4">
        <label for="4">4</label><br>
        <input type="radio" name="num_players" value="6">
        <label for="6">6</label>

        <label class="switch">
          <input type="checkbox" id="public" name="public" value="1" checked>
          <span class="slider round"></span>
        </label>

        <button id="host_submit">HOST</button>
      </form>
      <form id="join">
        <input type="hidden" name="action" value="join">
        <input type="text" name="id">
        <button id="join_submit">JOIN</button>
      </form>
      <div id="available"></div>
    </div>
  </div>
  <div id="wait">
    <h1>Waiting...</h1>
    <div id="players_list">
    </div>
    <a id="share_whatsapp" href="#" target="_blank">Share with WhatsApp</a>
    <p id="match_id"></p>
  </div>
  <script src="./lib/jquery-3.6.0.min.js"></script>
  <script src="./js/join.js"></script>
</body>

</html>