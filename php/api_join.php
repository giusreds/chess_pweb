<?php
session_start();
header("Content-type: application/json");
include("./mysql.php");

$disconnected = 6;



if (!isset($_POST["action"]) || !isset($_SESSION["user_id"]))
    error("Invalid action");

$action = $_POST["action"];
switch ($action) {
    case "host":
        if (isset($_POST["num_players"]))
            echo json_encode(host());
        break;
    case "join":
        if (isset($_POST["id"]))
            echo json_encode(join_match($_POST["id"]));
        break;
    case "verify":
        if (isset($_POST["id"]))
            verify($_POST["id"]);
        break;
    case "get":
        get_available();
        break;
}

function error($message = null)
{
    $resp["error"] = 1;
    if ($message)
        $resp["error_msg"] = $message;
    echo json_encode($resp);
    exit;
}

function host()
{
    global $mysqli;
    $query = $mysqli->prepare(
        "SELECT COUNT(*) AS `count`
        FROM `match_info` `I` 
            INNER JOIN `match_team` `T`
            ON `I`.`id` = `T`.`match_id`
        WHERE `I`.`status` IS NULL AND
            `I`.`host` = ? OR `T`.`user` = ?"
    );
    $query->bind_param("ii", $user_id, $user_id);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_array();
    // If i'm already in another match
    if ($row["count"]) error();
    // Creation
    $visibility = (isset($_POST["public"])) ? 1 : 0;
    $num_players = $_POST["num_players"];
    $allowed_num_players = array(2, 4, 6);
    if (!in_array($num_players, $allowed_num_players)) error();
    $match_id = uniqid();
    $query1 = $mysqli->prepare(
        "INSERT INTO `match_info` (`id`, `host`, `visibility`, `num_players`) 
        VALUES (?, ?, ?, ?)"
    );
    $user_id = $_SESSION["user_id"];
    $query1->bind_param("siii", $match_id, $_SESSION["user_id"], $visibility, $num_players);
    $query1->execute();
    return join_match($match_id);
}


function join_match($match_id)
{
    global $mysqli;
    $query = $mysqli->prepare(
        "SELECT COUNT(*) AS `count`
        FROM `match_info` 
        WHERE `id` = ? AND `status` IS NULL"
    );
    $query->bind_param("s", $match_id);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_array();
    if (!$row["count"]) error();
    // Insert in match_team
    $query1 = $mysqli->prepare(
        "INSERT INTO `match_team` (`match_id`, `user`) 
        VALUES (?, ?)"
    );
    $query1->bind_param("si", $match_id, $_SESSION["user_id"]);
    $query1->execute();
    $res["id"] = $match_id;
    $res["error"] = 0;
    start_match($match_id);
    return $res;
}


function is_match_ready($id)
{
    global $mysqli;
    $query = "SELECT T.`match_id` AS `id`, COUNT(*) AS `actual`,
    I.`num_players` AS `total`
    FROM `match_team` T INNER JOIN `match_info` I
    ON T.`match_id` = I.`id`
    WHERE I.`status` IS NULL AND I.`id` = '{$id}' GROUP BY T.`match_id`";
    $result = $mysqli->query($query);
    if (!$result) return false;
    $row = $result->fetch_array();
    if ($row["total"] == $row["actual"])
        return true;
}

function start_match($match_id)
{
    global $mysqli;
    if (is_match_ready($match_id)) {
        $query2 = "SELECT `user` FROM `match_team` WHERE `match_id` = '{$match_id}'";
        $result = $mysqli->query($query2);
        $users = array();
        while ($row = $result->fetch_array()) {
            array_push($users, $row["user"]);
        }
        shuffle($users);
        for ($i = 0; $i < count($users); $i++) {
            $team = $i % 2;
            $query = "UPDATE `match_team`
                SET `team` = '{$team}'
                WHERE `match_id` = '{$match_id}' AND
                `user` = '{$users[$i]}'";
            $mysqli->query($query);
        }

        // Change match status
        $query1 = $mysqli->prepare(
            "UPDATE `match_info`
            SET `status` = 1
            WHERE `id` = ?"
        );
        $query1->bind_param("s", $match_id);
        $query1->execute();

        $string = file_get_contents("../res/new_chessboard.json");
        $json_sc = json_encode(json_decode($string)->chessboard);
        $json_ma = json_encode(json_decode($string)->captured);
        $query = "INSERT INTO `match_log` (`id`, `number`, `turn`, `chessboard`,`captured`, `timestamp`)
        VALUES ('{$match_id}', '0', '0', '{$json_sc}', '{$json_ma}', CURRENT_TIMESTAMP)";
        $mysqli->query($query);
    }
}

// Delete matches where host is disconnected
function delete_host()
{
    global $mysqli;
    global $disconnected;
    $query = $mysqli->prepare(
        "DELETE `I`, `T`
            FROM `match_info` `I` 
            INNER JOIN `match_team` `T`
            ON `I`.`id` = `T`.`match_id`
                INNER JOIN `match_team` `H`
                ON `H`.`user` = `I`.`host`
                AND `H`.`match_id` = `I`.`id`
        WHERE `I`.`status` IS NULL
        AND TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `H`.`last_ping`)) > ?"
    );
    $query->bind_param("i", $disconnected);
    $query->execute();
}

// Delete players from matches if they disconnect
function delete_player()
{
    global $mysqli;
    global $disconnected;
    $query = $mysqli->prepare(
        "DELETE `T` FROM `match_team` `T`
        WHERE TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `T`.`last_ping`)) > ?
        AND `T`.`match_id` IN (
            SELECT `id`
            FROM `match_info`
            WHERE `status` IS NULL
            AND `host` <> `T`.`user`
        )"
    );
    $query->bind_param("i", $disconnected);
    $query->execute();
}




function ping($match_id)
{
    global $mysqli;
    $query = $mysqli->prepare(
        "UPDATE `match_team`
        SET `last_ping` = CURRENT_TIMESTAMP
        WHERE `match_id` = ? AND
            `user` = ?"
    );
    $query->bind_param("si", $match_id, $_SESSION["user_id"]);
    $query->execute();
}

function verify($match_id)
{
    global $mysqli;
    ping($match_id);
    delete_host();
    delete_player();
    $query = "SELECT COUNT(*) AS `count` FROM `match_log` WHERE `id` = '{$match_id}'";
    $result = $mysqli->query($query);
    $row = $result->fetch_array();
    $resp["players"] = get_users($match_id);
    if ($row["count"] > 0) {
        $resp["status"] = 1;
        echo json_encode($resp);
        exit;
    }
    $resp["status"] = 0;
    echo json_encode($resp);
    exit;
}

function get_users($match_id)
{
    global $mysqli;
    $query = $mysqli->prepare(
        "SELECT `U`.`username`, `U`.`avatar`
        FROM `match_team` `M` 
            INNER JOIN `user` `U`
            ON `U`.`id` = `M`.`user`
        AND `M`.`match_id` = ?"
    );
    $query->bind_param("s", $match_id);
    $query->execute();
    $result = $query->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    return $users;
}


// Available matches
function get_available()
{
    global $mysqli;
    delete_host();
    delete_player();
    $user_id = $_SESSION["user_id"];
    $matches = array();
    include("./mysql.php");
    $query = "SELECT T.`match_id` AS `id`, COUNT(*) AS `actual`,
    I.`num_players` AS `total`, U.`username` AS `host`
    FROM `match_team` T INNER JOIN `match_info` I INNER JOIN `user` U
    ON T.`match_id` = I.`id` AND I.`host` = U.`id`
    WHERE I.`status` IS NULL AND I.`host` <> '{$user_id}' 
    AND I.`visibility` = 1 GROUP BY T.`match_id`";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_array()) {
        $tmp["id"] = $row["id"];
        $tmp["host"] = $row["host"];
        $tmp["actual"] = $row["actual"];
        $tmp["total"] = $row["total"];
        array_push($matches, $tmp);
    }
    echo json_encode($matches);
    exit;
}
