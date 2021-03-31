<?php
session_start();
header("Content-type: application/json");
include("./mysql.php");

$username_pattern = "(?:^|[^\w])(?:@)([a-z0-9_](?:(?:[a-z0-9_]|(?:\.(?!\.))){0,28}(?:[a-z0-9_]))?)";
$avatars = range(1, 4);



// If the request hasn't an action
if (!isset($_POST["action"]))
    exit;
$action = $_POST["action"];
switch ($action) {
    case "login":
        if (login($_POST["username"], $_POST["password"]))
            return_success();
        return_failure("Check your credentials");
        break;
    case "register":
        register($_POST["username"], $_POST["password"], $_POST["avatar"]);
        break;
    case "validate":
        usernameValidation($_POST["username"]);
        break;
}

// Returns feedback to the client

// If action succeeded
function return_success()
{
    $resp["error"] = 0;
    echo json_encode($resp);
    exit;
}
// If action failed
function return_failure($message = null)
{
    $resp["error"] = 1;
    if ($message)
        $resp["error_msg"] = $message;
    echo json_encode($resp);
    exit;
}


function login($username, $password)
{
    global $mysqli;
    if (
        !isset($username) ||
        empty($username) ||
        !isset($password) ||
        empty($password)
    )
        return false;
    $query = $mysqli->prepare(
        "SELECT *
        FROM `user`
        WHERE `username` = ?"
    );
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_array();

    if (password_verify($password, $row["password"])) {
        // If the two password matches
        $_SESSION["user_id"] = $row["id"];
        $_SESSION["username"] = $username;
        return true;
    }
    // If username or password are wrong
    return false;
}

// Checks if an username already exists
function isUsernameTaken($username)
{
    global $mysqli;
    $query = $mysqli->prepare(
        "SELECT COUNT(*) AS `found`
        FROM `user`
        WHERE `username` = ?"
    );
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_array();
    if ($row["found"])
        return 1;
    return 0;
}


function register($username, $password, $avatar)
{
    global $mysqli, $avatars;
    if (
        !isset($username) ||
        !isset($username) ||
        !isset($password) ||
        empty($password) ||
        !in_array($avatar, $avatars)
    )
        return_failure();
    if (isUsernameTaken($username))
        return_failure("Invalid username");
    $query = $mysqli->prepare(
        "INSERT INTO `user`
        (`username`, `password`, `avatar`)
        VALUES (?, ?, ?)"
    );
    $query->bind_param("ssi", $username, $password, $avatar);
    $query->execute();
    return_success();
}

// Checks if the username is valid
function usernameValidation($username)
{
    global $username_pattern;
    if (isUsernameTaken($username))
        return_failure("The username is already taken");
    if (!preg_match($username_pattern, $username))
        return_failure("The username doesn't match the pattern");
    return_success();
}
