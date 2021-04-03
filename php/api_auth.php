<?php
session_start();
header("Content-type: application/json");
include("./mysql.php");
include("./auth.php");

// Main
// If the request hasn't an action
if (!isset($_POST["action"]))
    return_failure("Invalid action");
$action = $_POST["action"];
switch ($action) {
    case "login":
        if (login($_POST["username"], $_POST["password"]))
            return_success();
        return_failure("Wrong username or password.");
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
