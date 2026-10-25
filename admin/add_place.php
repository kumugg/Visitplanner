<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';
$page = "add_place";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $place_name = $_POST['place_name'] ?? '';
    $place_description = $_POST['place_description'] ?? '';
    $category_id = $_POST['category'] ?? '';
    $distance_km = $_POST['distance_km'] ?? '';
    $opening_time = $_POST['opening_time'] ?? '';
    $travel_tip = $_POST['travel_tip'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $image_path = $_POST['image_path'] ?? '';

    // Main validation check (kept the same as your original: name, description, lat, and long required)
    if (!empty($place_name) && !empty($place_description) && !empty($category_id) && !empty($latitude) && !empty($longitude) && !empty($image_path)) {
        $sql = "INSERT INTO place (place_name, place_description, category_id, distance_km, opening_time, travel_tip, latitude, longitude, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $result = $conn->execute_query($sql, [$place_name, $place_description, $category_id, $distance_km, $opening_time, $travel_tip, $latitude, $longitude, $image_path]);

            if ($result) {
                echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("Place added successfully!"); }); </script>';
            } else {
                echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error adding place: ' . htmlspecialchars($conn->error) . '"); }); </script>';
            }
        } catch (Exception $e) {
            echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error adding place: ' . htmlspecialchars($e->getMessage()) . '"); }); </script>';
        }
    } else {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("All required fields must be filled."); }); </script>';
    }
}

?>

<div class="container py-5">
    <a href="manage_places.php" class="btn btn-<?= $theme_color ?> mt-3">&laquo; Back to Manage Places</a>
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
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label for="place_name" class="form-label">Place Name</label>
                            <input type="text" class="form-control" id="place_name" name="place_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="place_description" class="form-label">Place Description</label>
                            <textarea class="form-control" id="place_description" name="place_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Select a category</option>
                                <?php
                                $sql = "SELECT category_id, category_name FROM category";
                                $result = $conn->execute_query($sql);
                                while ($row = $result->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['category_id']); ?>"><?= htmlspecialchars($row['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="distance_km" class="form-label">Distance (km)</label>
                            <input type="text" class="form-control" id="distance_km" name="distance_km">
                        </div>
                        <div class="mb-3">
                            <label for="travel_tip" class="form-label">Travel Tip</label>
                            <textarea class="form-control" id="travel_tip" name="travel_tip" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="opening_time" class="form-label">Opening Time</label>
                            <input type="text" class="form-control" id="opening_time" name="opening_time">
                        </div>
                        <div class="mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" required>
                        </div>
                        <div class="mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" required>
                        </div>
                        <div class="mb-3">
                            <label for="image_path" class="form-label">Image URL: <small class="form-text text-muted">Enter the relative path of the images folder in the server. Ex: images/anods/</small></label>
                            <input type="text" class="form-control" id="image_path" name="image_path" required>
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100">Add Place</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../includes/footer.php';
?>