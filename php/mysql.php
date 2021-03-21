<?php
$mysqli = new mysqli("localhost", "root", "", "scacchi_rossi");

// Test DataBase connection
if (mysqli_connect_errno()) {
	print "Connection error: " . mysqli_connect_error();
	exit;
}
