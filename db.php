<?php
$host = "localhost";
$dbname = "electronics_store";
$user = "root";  // or your DB user
$pass = "";      // your DB password

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
