<?php
session_start();
include_once("./php/mysql.php");
include_once("./php/game.php");
$team = NULL;

// Match ID must be passed with GET
// If invalid, return home
if (isset($_GET["id"])) {
    $mode = 1;
    $match_id = $_GET["id"];
} else if (isset($_GET["replay"])) {
    $mode = 0;
    $match_id = $_GET["replay"];
} else
    header("Location: ./");

if (
    !isset($_SESSION["user_id"])
    || !isMyMatch($match_id, $_SESSION["user_id"])
)
    header("Location: ./");


$team = get_my_team($match_id, $_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <link rel="icon" href="./img/icons/icon_16.png">
    <link rel="manifest" href="./res/manifest.webmanifest">
    <link rel="stylesheet" href="./css/core.css">
    <!-- Board style -->
    <link rel="stylesheet" href="./css/board.css">
    <?php if ($mode) : ?>
        <!-- Game elements -->
        <link rel="stylesheet" href="./css/game.css">
        <title>Match | Strange Chess</title>
    <?php else : ?>
        <!-- Replay -->
        <link rel="stylesheet" href="./css/replay.css">
        <title>Replay | Strange Chess</title>
    <?php endif; ?>

</head>

<body>
    <header>
        <?php
        function print_players($team)
        {
            global $mysqli, $match_id;
            $query = $mysqli->prepare(
                "SELECT `U`.`username`, `U`.`id`, `U`.`avatar`
            FROM `user` `U` INNER JOIN `match_team` `T`
            ON `T`.`user` = `U`.`id` WHERE 
            `T`.`match_id` = ? AND `T`.`team` = ?"
            );
            $query->bind_param("si", $match_id, $team);
            $query->execute();
            $result = $query->get_result();
            while ($row = $result->fetch_array()) {
                $username = ($row["username"] == $_SESSION["username"]) ? "you" : $row["username"];
                echo '<div class="player_info" id="player_' . $row["id"] . '">';
                echo '<img src="./img/avatars/' . $row["avatar"] . '.svg" alt="' . $username . '">';
                echo '<h3>' . $username . '</h3>';
                echo '</div>';
            }
        }
        ?>
        <div class="your_team">
            <?php
            print_players($team);
            ?>
        </div>
        <div class="opponent_team">
            <?php
            print_players(!$team);
            ?>
        </div>
        <h1 id="time"></h1>
    </header>

    <!-- Chess Board -->
    <div class="game">
        <div class="captured" id="captured_opp"></div>
        <div id="board">
            <table class="chessboard">
                <?php
                // Draw the chessboard
                echo "<tbody>";
                for ($i = 0; $i < 8; $i++) {
                    echo "<tr>";
                    for ($j = 0; $j < 8; $j++) {
                        // Cell ID (concat i + j)
                        echo "<td id=" . strval($i) . strval($j) . "></td>";
                    }
                    echo "</tr>";
                }
                echo "</tbody>";
                ?>
            </table>
        </div>
        <div class="captured" id="captured_you"></div>
    </div>
    <?php
    // Include the form for pawn promotion
    if ($mode) : ?>
        <!-- Pawn promotion -->
        <div id="promotion">
            <form id="promotion_form">
                <?php

                function pieceName($piece)
                {
                    global $team;
                    $color = ($team) ? "b" : "w";
                    $piece_ = strtoupper(substr($piece, 0, 1));
                    if ($piece_ == "K") $piece_ = "N";
                    return $color . $piece_;
                }
                // Set the radio input fields
                $pieces = array("queen", "knight", "rook", "bishop");
                foreach ($pieces as $piece) {
                    echo "<label>";
                    echo '<input type="radio" class="promotion_field" name="promotion" value="' . $piece . '">';
                    echo '<img src="./img/pieces/' . pieceName($piece) . '.png" alt="' . $piece . '">';
                    echo "</label>";
                }
                ?>
            </form>
        </div>

    <?php
    // Include buttons for replay
    else : ?>
        <div class="controls">
            <button id="back-btn">BACK</button>
            <button id="play-btn">PLAY</button>
            <button id="next-btn">NEXT</button>
        </div>
    <?php endif; ?>
    <!-- jQuery -->
    <script src="./js/lib/jquery-3.6.0.min.js"></script>
    <!-- Board -->
    <script src="./js/board.js"></script>
    <?php
    // Include the right JavaScript
    if ($mode) : ?>
        <!-- Game -->
        <script src="./js/game.js"></script>
    <?php else : ?>
        <!-- Replay -->
        <script src="./js/replay.js"></script>
    <?php endif; ?>
</body>

</html>