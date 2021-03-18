<?php
session_start();
include_once("./mysql.php");

$string = file_get_contents("../res/new_chessboard.json");
$json_sc = json_encode(json_decode($string, true)["scacchiera"], true);
$json_ma = json_encode(json_decode($string, true)["mangiati"], true);

//var_dump($_SESSION);
//var_dump($_POST);
//var_dump($json_sc);
if (isset($_POST["avversario"]) && isset($_SESSION["user_id"]) && $_POST["avversario"] != $_SESSION["user_id"]) {

  $me = $_SESSION["user_id"];
  $av = $_POST["avversario"];
  $query = "SELECT * FROM `match_team` M1 INNER JOIN `match_team` M2
    ON  M1.`match_id` = M2.`match_id` WHERE M1.`user` = '{$me}' 
    AND M2.`user` = '{$av}' OR M1.`user` = '{$av}' AND M2.`user` = '{$me}'";
  $result = $mysqli->query($query);
  $def = $result->fetch_array();
  if (!$def) {
    $id = uniqid();
    $query = "INSERT INTO `match_info` (`id`, `status`) VALUES ('{$id}','1')";
    $mysqli->query($query);
    $query = "INSERT INTO `match_log` (`id`, `number`, `turn`, `chessboard`,`captured`, `timestamp`)
      VALUES ('{$id}', '0', '0', '{$json_sc}', '{$json_ma}', CURRENT_TIMESTAMP)";
    $mysqli->query($query);
    $query = "INSERT INTO `match_team` VALUES ('{$id}', '0', '{$me}'), ('{$id}', '1', '{$av}')";
    $mysqli->query($query);
  } else $id = $def["match_id"];
  // Ridirigo verso la partita
  header("Location: ../match.php?id=" . $id);
} else header("Location: ../");
