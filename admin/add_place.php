<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';
$page = "add_place";

$notification_js = '';
$form_values = [
    'place_name' => '',
    'place_description' => '',
    'category' => '',
    'distance_km' => '',
    'opening_time' => '',
    'travel_tip' => '',
    'latitude' => '',
    'longitude' => '',
    'image_path' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $place_name        = trim($_POST['place_name'] ?? '');
    $place_description = trim($_POST['place_description'] ?? '');
    $category_id       = isset($_POST['category']) ? (int)$_POST['category'] : 0;
    $distance_km_raw   = trim($_POST['distance_km'] ?? '');
    $opening_time      = trim($_POST['opening_time'] ?? '');
    $travel_tip        = trim($_POST['travel_tip'] ?? '');
    $latitude_raw      = trim($_POST['latitude'] ?? '');
    $longitude_raw     = trim($_POST['longitude'] ?? '');
    $image_path        = trim($_POST['image_path'] ?? '');

    $form_values = [
        'place_name' => $place_name,
        'place_description' => $place_description,
        'category' => $category_id,
        'distance_km' => $distance_km_raw,
        'opening_time' => $opening_time,
        'travel_tip' => $travel_tip,
        'latitude' => $latitude_raw,
        'longitude' => $longitude_raw,
        'image_path' => $image_path,
    ];

    if (!empty($place_name) && !empty($place_description) && $category_id > 0 && !empty($latitude_raw) && !empty($longitude_raw) && !empty($image_path)) {
        if (!is_numeric($latitude_raw) || !is_numeric($longitude_raw)) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("Latitude and Longitude must be valid numbers.") . ');';
        } elseif ($distance_km_raw !== '' && !is_numeric($distance_km_raw)) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("Distance must be a valid number.") . ');';
        } else {
            $distance_km = ($distance_km_raw !== '') ? (float) $distance_km_raw : null;
            $latitude = (float) $latitude_raw;
            $longitude = (float) $longitude_raw;
            $opening_time_val = !empty($opening_time) ? $opening_time : 'Not Applicable';

            // Verify category exists
            $cat_check = $conn->execute_query("SELECT category_id FROM category WHERE category_id = ? LIMIT 1", [$category_id]);
            if ($cat_check && $cat_check->num_rows > 0) {
                $sql = "INSERT INTO place (place_name, place_description, category_id, distance_km, opening_time, travel_tip, latitude, longitude, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                try {
                    $result = $conn->execute_query($sql, [$place_name, $place_description, $category_id, $distance_km, $opening_time_val, $travel_tip, $latitude, $longitude, $image_path]);

                    if ($result) {
                        $notification_js = 'triggerNotificationSuccess(' . json_encode("Place added successfully!") . ');';
                        $form_values = array_fill_keys(array_keys($form_values), '');
                    } else {
                        $notification_js = 'triggerNotificationFailed(' . json_encode("Error adding place.") . ');';
                    }
                } catch (Exception $e) {
                    error_log("Error adding place: " . $e->getMessage());
                    $notification_js = 'triggerNotificationFailed(' . json_encode("Database error occurred while adding place.") . ');';
                }
            } else {
                $notification_js = 'triggerNotificationFailed(' . json_encode("Invalid category selected.") . ');';
            }
        }
    } else {
        $notification_js = 'triggerNotificationFailed(' . json_encode("All required fields must be filled.") . ');';
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
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <div class="mb-3">
                            <label for="place_name" class="form-label">Place Name</label>
                            <input type="text" class="form-control" id="place_name" name="place_name" value="<?= htmlspecialchars($form_values['place_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="place_description" class="form-label">Place Description</label>
                            <textarea class="form-control" id="place_description" name="place_description" rows="3" required><?= htmlspecialchars($form_values['place_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Select a category</option>
                                <?php
                                $sql = "SELECT category_id, category_name FROM category";
                                $result = $conn->execute_query($sql);
                                while ($row = $result->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['category_id']); ?>" <?= ($form_values['category'] == $row['category_id']) ? 'selected' : ''; ?>><?= htmlspecialchars($row['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="distance_km" class="form-label">Distance (km)</label>
                            <input type="text" class="form-control" id="distance_km" name="distance_km" value="<?= htmlspecialchars($form_values['distance_km']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="travel_tip" class="form-label">Travel Tip</label>
                            <textarea class="form-control" id="travel_tip" name="travel_tip" rows="3"><?= htmlspecialchars($form_values['travel_tip']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="opening_time" class="form-label">Opening Time</label>
                            <input type="text" class="form-control" id="opening_time" name="opening_time" value="<?= htmlspecialchars($form_values['opening_time']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" value="<?= htmlspecialchars($form_values['latitude']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" value="<?= htmlspecialchars($form_values['longitude']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="image_path" class="form-label">Image URL: <small class="form-text text-muted">Enter the relative path of the images folder in the server. Ex: images/anods/</small></label>
                            <input type="text" class="form-control" id="image_path" name="image_path" value="<?= htmlspecialchars($form_values['image_path']); ?>" required>
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

<?php if (!empty($notification_js)): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?= $notification_js ?>
    });
</script>
<?php endif; ?>
