<?php
session_start();
header("Content-type: application/json");
include("./mysql.php");
include("./game.php");

if (
    isset($_POST["id"]) && isset($_POST["action"])
    && isset($_SESSION["user_id"])
) {
    // Parametri sempre presenti
    $action = $_POST["action"];
    $match_id = $_POST["id"];
    $user_id = $_SESSION["user_id"];



    if (!isMyMatch($match_id, $user_id)) exit;
    $player_id = get_my_team($match_id, $user_id);
    checkTimeExceeded($match_id);

    $result = getLastMove($match_id);

    $number = $result["number"];

    // Valuto l'azione da eseguire
    switch ($action) {
        case "refresh":
            // Resetto l'impronta, cosi da forzare il refresh
            $_SESSION["h_" . $match_id] = NULL;
        case "pull":
            $match_status = fetchMatch($result, $player_id);
            clean($match_status["chessboard"]);
            $match_status["control"] = $result["control"];
            $match_status["changed"] = 1;
            // Se l'impronta e'uguale all'ultima registrata, ritorno
            // soltanto {"r":0} per indicare di non eseguire il refresh
            if (
                isset($_SESSION["h_" . $match_id]) &&
                $_SESSION["h_" . $match_id] == md5(json_encode($match_status))
            ) {
                $risp["changed"] = 0;
                $match_status = $risp;
            } else
                $_SESSION["h_" . $match_id] = md5(json_encode($match_status));
            $r_time = getTime($result["timestamp"]);
            $match_status["time"] = ($r_time > 0) ? $r_time : 0;
            if (isEnded($match_id)) {
                $match_status["status"] = 0;
                $match_status["winner"] = whoIsTheWinner($match_id, $player_id);
            } else
                $match_status["status"] = 1;
            echo json_encode($match_status);
            exit;
        case "check":
            if (!isset($_POST["source"])) exit;
            //include_once("./rules.php");
            $match_status = fetchMatch($result, $player_id);
            $allowed = allowed($match_status["chessboard"], $_POST["source"]);
            echo json_encode($allowed);
            exit;
        case "move":
            if (!isset($_POST["source"]) || !isset($_POST["destination"])) exit;
            //include_once("./rules.php");
            $match_status = fetchMatch($result, $player_id);
            $allowed = allowed($match_status["chessboard"], $_POST["source"]);
            // Verifico fattibilità nella matrice personalizzata
            // e verifico che sia il mio turno
            if (
                in_array($_POST["destination"], $allowed)
                && $match_status["yourturn"]
                && isMatchActive($match_id)
            ) {

                // Scacchiera e array di pedine mangiate
                $chessboard = json_decode($result["chessboard"]);
                $captured = json_decode($result["captured"]);

                // Calcolo gli indici sulla matrice originale
                $x_s = index(cellIndex($_POST["source"])[0], $player_id);
                $y_s = index(cellIndex($_POST["source"])[1], $player_id);
                $x_d = index(cellIndex($_POST["destination"])[0], $player_id);
                $y_d = index(cellIndex($_POST["destination"])[1], $player_id);
                $source = cellName($x_s, $y_s);
                $destination = cellName($x_d, $y_d);
                $promotion = (isset($_POST["promotion"])) ? $_POST["promotion"] : NULL;
                $aftermove = move($chessboard, $captured, $source, $destination, 0, $promotion);
                $chessboard = json_encode($aftermove[0]);
                $captured = json_encode($aftermove[1]);

                $number++;
                $turn = ($player_id + 1) % 2;
                // Aggiorno nel DataBase
                $query = $mysqli->prepare(
                    "INSERT INTO `match_log` (`id`, `number`, `turn`, `control`, 
                    `chessboard`,`captured`, `timestamp`) VALUES 
                    (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)"
                );
                $query->bind_param("siiiss", $match_id, $number, $turn, $user_id, $chessboard, $captured);
                $query->execute();
                // Checks if match has to end
                $result = getLastMove($match_id);
                $match_status = fetchMatch($result, $player_id);
                $chessboard = $match_status["chessboard"];
                checkIfCheckmateDraw($match_id, $player_id, $chessboard);
                // Se tutto ok
                // ridondante
                $match_status = fetchMatch($result, $player_id);
                $match_status["control"] = $result["control"];
                clean($match_status["chessboard"]);
                // Se l'impronta e'uguale all'ultima registrata, ritorno
                // soltanto {"r":0} per indicare di non eseguire il refresh
                if (isset($_SESSION["h_" . $match_id]) && $_SESSION["h_" . $match_id] == md5(json_encode($match_status))) {
                    $risp["changed"] = 0;
                    echo json_encode($risp);
                    exit;
                }
                // Altrimenti, faccio full-refresh e aggiorno l'impronta
                echo json_encode($match_status);
                $_SESSION["h_" . $match_id] = md5(json_encode($match_status));
            }
            exit;
    }
}
