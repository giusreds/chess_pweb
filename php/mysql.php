<?php
$mysqli = new mysqli("localhost", "root", "", "scacchi_rossi");

// Verifico connessione DataBase
if (mysqli_connect_errno()) {
	print "Connessione fallita: " . mysqli_connect_error();
	exit;
}
