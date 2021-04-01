<?php
session_start();
header("Content-type: application/json");
include_once("./mysql.php");
include_once("./game.php");

if (
    !isset($_SESSION["user_id"])
    || !isset($_POST["id"])
    || !isset($_POST["action"])
    || !isMyMatch($_POST["id"], $_SESSION["user_id"])
)
    exit;

$match_id = $_POST["id"];
$user_id = $_SESSION["user_id"];
$action = $_POST["action"];

$query = $mysqli->prepare(
    "SELECT * FROM `match_info`
    WHERE `id` = ?"
);
$query->bind_param("s", $match_id);
$query->execute();
$result = $query->get_result();
$status = $result->fetch_array()["status"];
if ($status) exit;

$query = $mysqli->prepare(
    "SELECT MAX(`number`) AS `count`
    FROM `match_log`
    WHERE `id` = ?"
);
$query->bind_param("s", $match_id);
$query->execute();
$result = $query->get_result();
$total = $result->fetch_array()["count"];


if (!isset($_SESSION["r_" . $match_id]))
    $_SESSION["r_" . $match_id] = 0;

switch ($action) {
    case "restart":
        $_SESSION["r_" . $match_id] = 0;
        break;
    case "next":
        $_SESSION["r_" . $match_id]++;
        break;
    case "previous":
        $_SESSION["r_" . $match_id]--;
        break;
    default:
        exit;
}

$number = $_SESSION["r_" . $match_id];
if ($number < 0) {
    $_SESSION["r_" . $match_id] = 0;
    exit;
}
if ($number > $total) {
    $_SESSION["r_" . $match_id] = $total;
    exit;
}

$query = $mysqli->prepare(
    "SELECT * FROM `match_log`
    WHERE `id` = ? AND `number` = ?"
);
$query->bind_param("si", $match_id, $number);
$query->execute();
$match_row = $query->get_result()->fetch_array();

$query = $mysqli->prepare(
    "SELECT * FROM `match_team` 
    WHERE `match_id` = ?"
);
$query->bind_param("s", $match_id);
$query->execute();
$result = $query->get_result();
// Se la partita non esiste
if (!$result) return false;
while ($row = $result->fetch_array()) {
    if ($row["user"] == $user_id) {
        $player_id = $row["team"];
        $found = 1;
    }
}
if (!$found) exit;

$match_status = fetchMatch($match_row, $player_id);
clean($match_status["chessboard"]);
$match_status["actual"] = $number;
$match_status["total"] = $total;

echo json_encode($match_status);
