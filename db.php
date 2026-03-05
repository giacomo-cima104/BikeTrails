<?php
$host = "localhost";
$user = "root";
$pass = ""; // Di default vuota su XAMPP
$db   = "biketrails_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
?>