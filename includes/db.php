<?php

$hostname = 'localhost';
$username = 'webapp';
$password = "*kmEz4ffUH!yNj1A";
$database = "visitplanner";

$conn = new mysqli($hostname, $username, $password, $database);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

$conn->set_charset('utf8mb4');
