<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    unset($_SESSION['logged_in_username']);
    header('Location: login.php');
    exit();
}
?>