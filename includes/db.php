<?php

$hostname = 'localhost';
$username = 'webapp';
$password = "*kmEz4ffUH!yNj1A";
$database = "visitplanner";

$conn = new mysqli($hostname, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>