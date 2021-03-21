<?php
include_once("./mysql.php");

// Time in seconds for each turn
$turn_time = 60;

// Removes unnecessary attributes from pieces info
// Used before sending chessboard to client
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

// Returns the last row of the match log
function getLastMove($match_id)
{
    global $mysqli;
    $query = $mysqli->prepare(
        "SELECT * FROM `match_log` WHERE `id` = ?
        ORDER BY `timestamp` DESC LIMIT 1"
    );
    $query->bind_param("s", $match_id);
    $query->execute();
    $result = $query->get_result();
    if (!$result) return NULL;
    return $result->fetch_array();
}

// Get remaining time to do the move
// Returns a negative number if time exceeded
// Negative result is used to handle the end of the match
function getTime($timestamp)
{
    global $turn_time;
    $last_move = date_timestamp_get(DateTime::createFromFormat("Y-m-d H:i:s", $timestamp));
    $now = date_timestamp_get(date_create());
    return $last_move + $turn_time - $now;
}

// End the match and set a winner (0, 1, 2)
// 2 means that the match ended in a draw
function setWinner($match_id, $winner)
{
    global $mysqli;
    $query = $mysqli->prepare(
        "UPDATE `match_info` SET
        `status` = 0, `winner` = ?
        WHERE `id` = ?"
    );
    $query->bind_param("is", $winner, $match_id);
    $query->execute();
}

// Returns an array with match status and winner
// attributes from DataBase row
function getStatus($match_id)
{
    global $mysqli;
    $query = $mysqli->prepare(
        "SELECT `status`, `winner` 
        FROM `match_info` WHERE `id` = ?"
    );
    $query->bind_param("s", $match_id);
    $query->execute();
    $result = $query->get_result();
    if (!$result) return;
    $row = $result->fetch_array();
    return array($row["status"], $row["winner"]);
}

// Checks if the match is started
function isStarted($match_id)
{
    $status = getStatus($match_id)[0];
    if ($status === NULL)
        return 0;
    return 1;
}

// Checks if the match is ended
function isEnded($match_id)
{
    $status = getStatus($match_id)[0];
    if ($status === 0)
        return 1;
    return 0;
}

// Returns the winner of the match
function whoIsTheWinner($match_id, $player_id)
{
    if (!isEnded($match_id)) return NULL;
    $winner = getStatus($match_id)[1];
    if ($winner == 2) return "draw";
    if ($winner == $player_id) return "you";
    return "opp";
}

// If time exceeded, ends the match
function checkTimeExceeded($match_id)
{
    $last_move = getLastMove($match_id);
    $time = getTime($last_move["timestamp"]);
    // If it's all okay do nothing
    if (!isStarted($match_id) || isEnded($match_id) || $time > 0)
        return;
    // Else end the match
    $winner = ($last_move["turn"]) ? 0 : 1;
    setWinner($match_id, $winner);
}

// If there's checkmate or draw, ends the match
function checkIfCheckmateDraw($match_id, $team_id, $chessboard)
{
    $status = isCheckMate($chessboard, "opp");
    if (!$status) return;
    switch ($status) {
        case 1:
            // If checkmate
            $winner = $team_id;
            break;
        case 2:
            // If draw
            $winner = 2;
            break;
    }
    setWinner($match_id, $winner);
}

// Checks if the match is started and not ended
// (is currently active)
function isMatchActive($match_id)
{
    if (!isEnded($match_id) && isStarted($match_id))
        return true;
    return false;
}

// Calculates the index in the original chessboard
// depending on the player_id
function index($n, $player_id)
{
    // If I'm in team 0, I'll be operating
    // on the rotated chessboard
    return ($player_id) ? $n : 7 - $n;
}

// Returns the cells where there are kings in check
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

// Reads data from the DataBase and returns an object
// with the current status of the match relatively
// to the current team
function fetchMatch($match_row, $player_id)
{
    $chessboard = json_decode($match_row["chessboard"]);
    $captured = json_decode($match_row["captured"]);
    $isYourTurn = ($match_row["turn"] == $player_id) ? 1 : 0;

    $result["chessboard"] = array();
    // Custom matrix creation
    for ($i = 0; $i < 8; $i++) {
        $tmp_row = array();
        for ($j = 0; $j < 8; $j++) {
            $a = index($i, $player_id);
            $b = index($j, $player_id);
            $tmp_cell = $chessboard[$a][$b];
            // Set "you" for the pieces of the current team
            // and "opp" for opponent's pieces
            if ($tmp_cell) {
                $tmp_cell->owner = ($tmp_cell->owner == $player_id) ? "you" : "opp";
            }
            array_push($tmp_row, $tmp_cell);
        }
        array_push($result["chessboard"], $tmp_row);
    }
    // Captured pieces
    $result["captured"] = $captured;
    // Set the owner of every captured piece with "you" if captured
    // by the current team and "opp" if captured by the opponent
    for ($i = 0; $i < count($captured); $i++)
        $result["captured"][$i]->owner = ($result["captured"][$i]->owner == $player_id) ? "opp" : "you";

    $result["incheck"] = inCheck($result["chessboard"]);
    $result["yourturn"] = $isYourTurn;
    return $result;
}
