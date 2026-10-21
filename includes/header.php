<?php 
$theme_color = "secondary";
include __DIR__ . '/db.php';
session_start();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="/visitplanner/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link href="/visitplanner/css/leaflet.css" rel="stylesheet" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
        <link href="/visitplanner/css/style.css" rel="stylesheet">
        <title>Visitplanner</title>
    </head>
    <body>
        <?php
        $logged_in_user = !empty($_SESSION['logged_in_username']);
        $hide_login = $logged_in_user ? 'hidden' : '';
        $hide_logout = $logged_in_user ? '' : 'hidden';

        $is_admin = 'hidden';
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
                <!-- header logo text -->
                <a href="index.php?category=all" class="navbar-brand text-light fw-semibold">Visit Planner System</a>
                <!-- navbar mobile collapse button -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <!-- collapse navbar -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <!-- navbar tabs -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($page == 'places') ? 'active' : ''  ?> text-light" href="index.php?category=all">Places</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($page == 'myvisitplan') ? 'active' : ''  ?> text-light" href="my_visit_plan.php">My Visit Plan</a>
                        </li>
                        <div class="ms-5 d-flex">
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($page == 'admin') ? 'active' : ''  ?> text-light" href="login.php" <?= $hide_login; ?>>Log In</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($page == 'admin') ? 'active' : ''  ?> text-light" href="admin_dashboard.php" <?= $is_admin; ?>> Administration</a>
                            </li>
                            <li class="nav-item">
                                <form action="logout.php" method="POST" class="d-inline">
                                    <button type="submit" class="nav-link text-light bg-transparent border-0" <?= $hide_logout; ?>> Log Out </button>
                                </form>
                            </li>
                        </div>
                    </ul>
                </div>
            </div>
        </nav>