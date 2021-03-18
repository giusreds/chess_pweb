<?php
session_start();
$team = null;
// Controlla se la partita esiste e se sono autorizzato a partecipare
function isMyMatch($match_id, $user_id)
{
    include_once("./php/mysql.php");
    global $team;

    $query = "SELECT * FROM `match_team` WHERE `match_id` = '{$match_id}'";
    $result = $mysqli->query($query);
    // Se la partita non esiste
    if (!$result) return false;
    while ($row = $result->fetch_array())
        if ($row["user"] == $user_id) {
            $team = $row["team"];
            return true;
        }
    return false;
}

// L'ID della partita viene passato come parametro GET
// Se non posso partecipare alla partita, reindirizza alla home
if (!isset($_GET["id"]) || !isset($_SESSION["user_id"]) || !isMyMatch($_GET["id"], $_SESSION["user_id"]))
    header("Location: ./");
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Partita</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="./css/chessboard.css">

</head>

<body>
    <header>
        <h1>Chess match</h1>
        <h2 id="turn"></h2>
    </header>

    <!-- Chess Board -->
    <div class="game">
        <div class="captured" id="captured_opp"></div>
        <div id="campo">
            <table class="scacchiera">
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
    <div id="collaborative">
        <button id="request_control">REQUEST CONTROL</button>
    </div>

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
            // Disegno i campi della form
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
    <!-- jQuery -->
    <script src="./lib/jquery-3.6.0.min.js"></script>
    <script src="./js/game.js"></script>
</body>

</html>