<?php
session_start();
header("Content-type: application/json");
include("./mysql.php");

// Regular expression for the username
$username_pattern = "(?:^|[^\w])(?:@)([a-z0-9_](?:(?:[a-z0-9_]|(?:\.(?!\.))){0,28}(?:[a-z0-9_]))?)";
// Valid avatars
$avatars = range(1, 4);


// Main
// If the request hasn't an action
if (!isset($_POST["action"]))
    return_failure("Invalid action");
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
    case "logout":
        // Remove all session variables and destroy the session
        session_unset();
        session_destroy();
        // Tell the client it's all okay
        return_success();
        break;
    case "validate":
        username_validation($_POST["username"]);
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
function is_username_taken($username)
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
        return true;
    return false;
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
    $username = strtolower($username);
    if (is_username_taken($username))
        return_failure("Invalid username");
    // Hash the password with Bcrypt algorithm
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $query = $mysqli->prepare(
        "INSERT INTO `user`
        (`username`, `password`, `avatar`)
        VALUES (?, ?, ?)"
    );
    $query->bind_param("ssi", $username, $password_hash, $avatar);
    $query->execute();
    return_success();
}

// Checks if the username is valid
function username_validation($username)
{
    global $username_pattern;
    if (is_username_taken($username))
        return_failure("The username is already taken");
    if (!preg_match($username_pattern, $username))
        return_failure("The username doesn't match the pattern");
    return_success();
}
