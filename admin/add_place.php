<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';
$page = "add_place";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $place_name = $_POST['place_name'] ?? '';
    $place_description = $_POST['place_description'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';

    if (!empty($place_name) && !empty($place_description) && !empty($latitude) && !empty($longitude)) {
        $sql = "INSERT INTO place (place_name, place_description, latitude, longitude) VALUES (?, ?, ?, ?)";
        try {
            $result = $conn->execute_query($sql, [$place_name, $place_description, $latitude, $longitude]);

            if ($result) {
                echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("Place added successfully!"); }); </script>';
            } else {
                echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error adding place: ' . htmlspecialchars($conn->error) . '"); }); </script>';
            }
        } catch (Exception $e) {
            echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error adding place: ' . htmlspecialchars($e->getMessage()) . '"); }); </script>';
        }
    } else {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("All fields are required."); }); </script>';
    }
}

?>

<div class="container py-5">
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Add Place</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="#" method="post" id="addPlaceForm">
                        <div class="mb-3">
                            <label for="place_name" class="form-label">Place Name</label>
                            <input type="text" class="form-control" id="place_name" name="place_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="place_description" class="form-label">Place Description</label>
                            <textarea class="form-control" id="place_description" name="place_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" required>
                        </div>
                        <div class="mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" required>
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100">Add Place</button>
                    </form>
                </div>
            </div>
        </div>
    </div>