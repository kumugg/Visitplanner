<?php
$page = "admin";
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';
?>

<div class="container py-5">
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Admin Dashboard</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <a href="manage_users.php" class="btn btn-<?= $theme_color ?> w-100 mb-3">Manage Users</a>
                    <a href="manage_places.php" class="btn btn-<?= $theme_color ?> w-100 mb-3">Manage Places</a>
                </div>
            </div>
        </div>
    </div>
</div>


<?php 
include __DIR__ . '/../includes/footer.php';
?>