<?php
session_start();
header("Content-type: application/json");
include("./mysql.php");
include("./rules.php");
// ini_set("memory_limit", "16M");

function clean(&$chessboard)
{
    for ($i = 0; $i < 8; $i++)
        for ($j = 0; $j < 8; $j++) {
            $attributes = array("firstmove", "enpassant");
            foreach ($attributes as $attribute)
                if (isset($chessboard[$i][$j]->$attribute))
                    unset($chessboard[$i][$j]->$attribute);
        }
}

// Temp
function take_control() {
    global $mysqli, $match_id, $user_id;
    exit;
}
// Calcola indice nella matrice originale
// in funzione del numero del giocatore
function index($n, $player_id)
{
    // Se sono player0, va interpretata
    // la trasposta della matrice
    return ($player_id) ? $n : 7 - $n;
}

function inCheck($chessboard)
{
    $result = array();
    $range = array("you", "opp");
    foreach ($range as $current_player) {
        if (isKingInCheck($chessboard, $current_player))
            array_push($result, findKing($chessboard, $current_player));
    }
    return $result;
}

// Organizza i dati presenti nel DataBase in un oggetto
// complesso, trasformabile in JSON e restituibile
// al chiamante
function fetchMatch($match_row, $player_id)
{
    $chessboard = json_decode($match_row["chessboard"]);
    $captured = json_decode($match_row["captured"]);
    $isYourTurn = ($match_row["turn"] == $player_id) ? 1 : 0;

    $result["changed"] = 1;
    $result["chessboard"] = array();
    // Creo la matrice personalizzata
    for ($i = 0; $i < 8; $i++) {
        $tmp_row = array();
        for ($j = 0; $j < 8; $j++) {
            $a = index($i, $player_id);
            $b = index($j, $player_id);
            $tmp_cell = $chessboard[$a][$b];
            // Modifico il proprietario con you per i miei pezzi
            if ($tmp_cell) {
                $tmp_cell->owner = ($tmp_cell->owner == $player_id) ? "you" : "opp";
            }
            array_push($tmp_row, $tmp_cell);
        }
        array_push($result["chessboard"], $tmp_row);
    }
    // Pedine mangiate
    $result["captured"] = $captured;
    // Modifico i proprietari dei pezzi mangiati
    for ($i = 0; $i < count($captured); $i++)
        $result["captured"][$i]->owner = ($result["captured"][$i]->owner == $player_id) ? "opp" : "you";

    $result["incheck"] = inCheck($result["chessboard"]);
    $result["yourturn"] = $isYourTurn;
    return $result;
}

// Esecuzione
if (
    isset($_POST["id"]) && isset($_POST["action"])
    && isset($_SESSION["user"])
) {
    // Parametri sempre presenti
    $action = $_POST["action"];
    $match_id = $_POST["id"];
    $user_id = $_SESSION["user_id"];

    $found = 0;

    $query = "SELECT * FROM `match_team` WHERE `match_id` = '{$match_id}'";
    $result = $mysqli->query($query);
    // Se la partita non esiste
    if (!$result) return false;
    while ($row = $result->fetch_array()) {
        if ($row["user"] == $user_id) {
            $player_id = $row["team"];
            $found = 1;
        }
    }
    if (!$found) exit;
    $query = "SELECT * FROM `match_log` WHERE `id` = '{$match_id}'
        ORDER BY `timestamp` DESC LIMIT 1";
    $result = $mysqli->query($query);
    $result = $result->fetch_array();

    $number = $result["number"];

    // Valuto l'azione da eseguire
    switch ($action) {
        case "refresh":
            // Resetto l'impronta, cosi da forzare il refresh
            $_SESSION[$match_id] = null;
        case "pull":
            $match_status = fetchMatch($result, $player_id);
            clean($match_status["chessboard"]);
            // Se l'impronta e'uguale all'ultima registrata, ritorno
            // soltanto {"r":0} per indicare di non eseguire il refresh
            if (
                isset($_SESSION[$match_id]) &&
                $_SESSION[$match_id] == md5(json_encode($match_status))
            ) {
                $risp["changed"] = 0;
                echo json_encode($risp);
                exit;
            }
            // Altrimenti, faccio full-refresh e aggiorno l'impronta
            echo json_encode($match_status);
            $_SESSION[$match_id] = md5(json_encode($match_status));
            exit;
        case "control":
            take_control();
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
            if (in_array($_POST["destination"], $allowed) && $match_status["yourturn"]) {

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
                $promotion = (isset($_POST["promotion"])) ? $_POST["promotion"] : null;
                $aftermove = move($chessboard, $captured, $source, $destination, 0, $promotion);
                $chessboard = json_encode($aftermove[0]);
                $captured = json_encode($aftermove[1]);

                // temporaneo
                $number++;
                $turn = ($player_id + 1) % 2;
                // Aggiorno nel DataBase
                $query = "INSERT INTO `match_log` (`id`, `number`, `turn`, `chessboard`,`captured`, `timestamp`)
                VALUES ('{$match_id}', '{$number}', '{$turn}', '{$chessboard}', '{$captured}', CURRENT_TIMESTAMP)";
                $mysqli->query($query);

                // Se tutto ok
                // ridondante
                $match_status = fetchMatch($result, $player_id);
                clean($match_status["chessboard"]);
                // Se l'impronta e'uguale all'ultima registrata, ritorno
                // soltanto {"r":0} per indicare di non eseguire il refresh
                if (isset($_SESSION[$match_id]) && $_SESSION[$match_id] == md5(json_encode($match_status))) {
                    $risp["changed"] = 0;
                    echo json_encode($risp);
                    exit;
                }
                // Altrimenti, faccio full-refresh e aggiorno l'impronta
                echo json_encode($match_status);
                $_SESSION[$match_id] = md5(json_encode($match_status));
            }
            exit;
    }
}
