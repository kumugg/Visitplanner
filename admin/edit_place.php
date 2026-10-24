<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $place_id = $_POST['place_id'];
    $name = $_POST['place_name'];
    $description = $_POST['place_description'];
    $category_id = $_POST['category'];
    $opening_time = $_POST['opening_time'];
    $distance_km = $_POST['distance_km'];
    $travel_tip = $_POST['travel_tip'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $image_path = $_POST['image_path'];


    $sql = "SELECT * FROM category";
    $category_result = $conn->execute_query($sql)->fetch_all(MYSQLI_ASSOC);

    if ($category_id && !in_array($category_id, array_column($category_result, 'category_id'))) {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Invalid category selected."); }); </script>';
        exit();
    }

    // Added travel_tip = ? to the SQL query
    $sql = "UPDATE place SET place_name = ?, place_description = ?, category_id = ?, distance_km = ?, opening_time = ?, travel_tip = ?, latitude = ?, longitude = ?, image_path = ? WHERE place_id = ?";
    
    // Added $travel_tip to the parameter array in the exact order as the SQL query
    $stmt = $conn->execute_query($sql, [$name, $description, $category_id, $distance_km, $opening_time, $travel_tip, $latitude, $longitude, $image_path, $place_id]);

    if ($stmt) {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("Place updated successfully!"); }); </script>';
    } else {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error updating place: ' . htmlspecialchars($conn->error) . '"); }); </script>';
    }
}

$place_id = isset($_GET['place_id']) ? intval($_GET['place_id']) : 0;
if ($place_id <= 0) {
    header("Location: manage_places.php");
    exit();
}

$sql = "SELECT * FROM place WHERE place_id = ?";
$stmt = $conn->execute_query($sql, [$place_id]);
$place = $stmt->fetch_assoc();

if (!$place) {
    header("Location: manage_places.php");
    exit();
}
?>

<div class="container py-5">
    <a href="manage_places.php" class="btn btn-<?= $theme_color ?> mt-3">&laquo; Back to Manage Places</a>
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Edit Place</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="#" method="post" id="editPlaceForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="place_id" value="<?= htmlspecialchars($place['place_id']); ?>">
                        <div class="mb-3">
                            <label for="place_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="place_name" name="place_name" value="<?= htmlspecialchars($place['place_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="place_description" class="form-label">Description</label>
                            <textarea class="form-control" id="place_description" name="place_description" rows="3" required><?= htmlspecialchars($place['place_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Select a category</option>
                                <?php
                                $sql = "SELECT category_id, category_name FROM category";
                                $result = $conn->execute_query($sql);
                                while ($row = $result->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['category_id']); ?>" <?= ($row['category_id'] == $place['category_id']) ? 'selected' : ''; ?>><?= htmlspecialchars($row['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="distance_km" class="form-label">Distance (km)</label>
                            <input type="text" class="form-control" id="distance_km" name="distance_km" value="<?= htmlspecialchars($place['distance_km']); ?>" >
                        </div>
                        <div class="mb-3">
                            <label for="travel_tip" class="form-label">Travel Tip</label>
                            <textarea class="form-control" id="travel_tip" name="travel_tip" rows="3"><?= htmlspecialchars($place['travel_tip']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="opening_time" class="form-label">Opening Time</label>
                            <input type="text" class="form-control" id="opening_time" name="opening_time" value="<?= htmlspecialchars($place['opening_time']); ?>" >
                        </div>
                        <div class="mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" value="<?= htmlspecialchars($place['latitude']); ?>" >
                        </div>
                        <div class="mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" value="<?= htmlspecialchars($place['longitude']); ?>" >
                        </div>
                        <div class="mb-3">
                            <label for="image_path" class="form-label">Image URL: <small class="form-text text-muted">Enter the relative path of the images folder in the server. Ex: images/anods/</small></label>
                            <input type="text" class="form-control" id="image_path" name="image_path" value="<?= htmlspecialchars($place['image_path']); ?>" >
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100">Update Place</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../includes/footer.php';
?>