<?php
session_start();
include("./php/mysql.php");
// If logged in, load the dashboard, else the login screen
$mode = (isset($_SESSION["user_id"])) ? 1 : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="UTF-8">
  <link rel="stylesheet" href="./css/core.css">
  <?php
  // DASHBOARD
  if ($mode) : ?>
    <title>Welcome</title>
    <link rel="stylesheet" href="./css/join.css">
  <?php
  // LOGIN SCREEN
  else : ?>
    <title>Login</title>
  <?php endif; ?>
</head>

<body>
  <?php
  // DASHBOARD
  if ($mode) : ?>
    <!--
    <header>
      <img src="./img/logo.png" id="logo">
      <form id="logout_form">
        <input type="submit" value="LOGOUT">
      </form>
    </header>-->
    <main>
      <section class="parallax">
        <div id="moon"></div>
        <img id="mountain" src="./img/parallax/mountain.svg" alt="mountain">
        <img id="ground" src="./img/parallax/ground.svg" alt="ground">
        <h2 id="logo_p">Strange Chess</h2>
      </section>
      <section class="menu">
        <button id="scroll-top">TOP</button>
        <button class="menu_btn" id="host_btn">Host new match</button>
        <button class="menu_btn" id="join_btn">Join a match</button>
        <div id="history">
          <?php
          function getHistory($user, $limit)
          {
            global $mysqli;
            $query = $mysqli->prepare(
              "SELECT DISTINCT `I`.* FROM `match_info` `I`
          INNER JOIN `match_team` `T`
          INNER JOIN `match_log` `L`
          ON `T`.`match_id` = `I`.`id`
          AND `L`.`id` = `I`.`id`
          WHERE `T`.`user` = ?
          GROUP BY `I`.`id` HAVING MAX(`L`.`number` > 0)
          ORDER BY `T`.`last_ping` DESC
          LIMIT ?"
            );
            $query->bind_param("ii", $user, $limit);
            $query->execute();
            $result = $query->get_result();
            if (!$result) return null;
            return $result->fetch_all(MYSQLI_ASSOC);
          }

          $list = getHistory($_SESSION["user_id"], 10);
          foreach ($list as $match) {
            echo '<div class="match_history" id="' . $match["id"] . '">';
            echo '<p>' . $match["id"] . '</p>';
            echo '<a href="./match.php?replay=' . $match["id"] . '">REPLAY</a>';
            echo "</div>";
          }
          ?>
        </div>
      </section>
      <div id="join_dialog" class="dialog">
        <div class="dialog_content">
          <span id="dialog_close">CLOSE</span>
          <form id="host_form">
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

            <input type="submit" value="HOST">
          </form>
          <form id="join_form" method="GET">
            <input type="text" name="join">
            <input type="submit" value="JOIN">
          </form>
          <div id="available_matches_list"></div>
        </div>
      </div>
      <div id="wait">
        <h1>Waiting...</h1>
        <div id="players_list">
        </div>
        <a id="share_whatsapp" href="#" target="_blank">Share with WhatsApp</a>
        <p id="match_id"></p>
      </div>
    <?php
  // LOGIN SCREEN
  else : ?>
      <main>
        <div id="auth_form">
          <form id="login_form">
            <input type="text" name="username">
            <input type="password" name="password">
            <input type="submit" value="SUBMIT">
          </form>
          <form id="register_form">
            <input type="text" name="username">
            <input type="password" name="password">
            <input type="text" name="avatar">
            <input type="submit" value="SUBMIT">
          </form>
          <p id="auth_error"></p>
        </div>
        <div id="register_success"></div>
      </main>
    <?php endif; ?>

    <!-- jQuery -->
    <script src="./js/lib/jquery-3.6.0.min.js"></script>
    <?php
    // DASHBOARD
    if ($mode) : ?>
      <!-- Join -->
      <script src="./js/join.js"></script>
    <?php
    // LOGIN SCREEN
    else : ?>
      <!-- Auth -->
      <script src="./js/auth.js"></script>
    <?php endif; ?>
</body>

</html>