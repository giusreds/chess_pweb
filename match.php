<?php
session_start();
include_once("./php/mysql.php");
include_once("./php/game.php");
$team = NULL;

// L'ID della partita viene passato come parametro GET
// Se non posso partecipare alla partita, reindirizza alla home
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partita</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="./css/board.css">

</head>

<body>
    <header>
        <h1>Chess match</h1>
        <h2 id="turn"></h2>
        <p id="time"></p>
    </header>

    <!-- Chess Board -->
    <div class="game">
        <div class="captured" id="captured_opp"></div>
        <div id="board">
            <table class="chessboard">
                <?php
                // Disegno la scacchiera
                echo "<tbody>";
                for ($i = 0; $i < 8; $i++) {
                    echo "<tr>";
                    for ($j = 0; $j < 8; $j++) {
                        // ID della casella (concatenazione i + j)
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
                    global $player_id;
                    $color = ($player_id) ? "b" : "w";
                    $piece_ = strtoupper(substr($piece, 0, 1));
                    if ($piece_ == "K") $piece_ = "N";
                    return $color . $piece_;
                }
                // Set the radio input fields
                $pieces = array("queen", "knight", "rook", "bishop");
                foreach ($pieces as $piece) {
                    echo "<label>";
                    echo '<input type="radio" name="promotion" value="' . $piece . '">';
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