<?php 
session_start();
$theme_color = "secondary";
include __DIR__ . '/db.php';
$page_name = $page ?? '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] == "POST") && isset($_GET['logout']) && $_GET['logout'] === "true") {

    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    unset($_SESSION['logged_in_username']);
    unset($_SESSION['user_type']);
    header('Location: /visitplanner/login.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="/visitplanner/css/bootstrap.min.css" rel="stylesheet">
        <link href="/visitplanner/css/leaflet.css" rel="stylesheet">
        <link href="/visitplanner/css/style.css" rel="stylesheet">
        <title>Visitplanner</title>
    </head>
    <body>
        <?php
        $logged_in_user = !empty($_SESSION['logged_in_username']);
        $hide_login = $logged_in_user ? 'd-none' : '';
        $hide_logout = $logged_in_user ? '' : 'd-none';

        $is_admin = 'd-none';
        if ($logged_in_user) {
            $username = $_SESSION['logged_in_username'];
            $sql = "SELECT user_type FROM users WHERE username = ? LIMIT 1";
            $result = $conn->execute_query($sql, [$username]);
            $row = $result->fetch_assoc();

            if ($row && $row['user_type'] === 'admin') {
                $is_admin = '';
            }
        }

        // Initialize the visit plan session variable if it doesn't exist
        if (!isset($_SESSION['visit_plan'])) {
            $_SESSION['visit_plan'] = [];
        }

        ?>
        <nav class="navbar navbar-expand-lg bg-<?= $theme_color ?>">
            <div class="container-md">
                <a href="/visitplanner/index.php?category=all" class="navbar-brand text-light fw-semibold">Visit Planner System</a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link text-light <?= ($page_name === 'places') ? 'active' : ''  ?>" href="/visitplanner/index.php?category=all">Places</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light <?= ($page_name === 'myvisitplan') ? 'active' : ''  ?>" href="/visitplanner/my_visit_plan.php">My Visit Plan</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link text-light <?= $hide_login; ?> <?= ($page_name === 'login') ? 'active' : ''  ?>" href="/visitplanner/login.php">Log In</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light <?= $hide_login; ?> <?= ($page_name === 'register') ? 'active' : ''  ?>" href="/visitplanner/register.php" >Register</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light <?= $is_admin; ?> <?= ($page_name === 'admin') ? 'active' : ''  ?>" href="/visitplanner/admin/admin_dashboard.php" > Administration</a>
                        </li>
                        <li class="nav-item">
                            <form method="post" action="?logout=true">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <button class="nav-link text-light bg-transparent border-0 <?= $hide_logout; ?>" type="submit"> Log Out </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div id="toast" class="toast-bar"></div>