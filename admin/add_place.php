<?php
$page = "add_place";
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';

$notification_js = '';
$form_values = [
    'place_name' => '',
    'place_description' => '',
    'category' => '',
    'distance_km' => '',
    'opening_time' => '',
    'tip_entry_fee' => '',
    'tip_safety' => '',
    'tip_best_time' => '',
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
    $tip_entry_fee     = trim($_POST['tip_entry_fee'] ?? '');
    $tip_safety        = trim($_POST['tip_safety'] ?? '');
    $tip_best_time     = trim($_POST['tip_best_time'] ?? '');
    $latitude_raw      = trim($_POST['latitude'] ?? '');
    $longitude_raw     = trim($_POST['longitude'] ?? '');
    $image_path        = trim($_POST['image_path'] ?? '');

    $form_values = [
        'place_name' => $place_name,
        'place_description' => $place_description,
        'category' => $category_id,
        'distance_km' => $distance_km_raw,
        'opening_time' => $opening_time,
        'tip_entry_fee' => $tip_entry_fee,
        'tip_safety' => $tip_safety,
        'tip_best_time' => $tip_best_time,
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
                // Determine primary travel tip string for backward compatibility
                $primary_tip = !empty($tip_entry_fee) ? $tip_entry_fee : (!empty($tip_safety) ? $tip_safety : $tip_best_time);
                
                $sql = "INSERT INTO place (place_name, place_description, category_id, distance_km, opening_time, travel_tip, latitude, longitude, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                try {
                    $result = $conn->execute_query($sql, [$place_name, $place_description, $category_id, $distance_km, $opening_time_val, $primary_tip, $latitude, $longitude, $image_path]);

                    if ($result) {
                        $new_place_id = $conn->insert_id;

                        // Insert multiple categorized travel tips per SRS REQ-3.3
                        if (!empty($tip_entry_fee)) {
                            $conn->execute_query("INSERT INTO travel_tip (place_id, tip_category, tip_text) VALUES (?, 'entry-fee status', ?)", [$new_place_id, $tip_entry_fee]);
                        }
                        if (!empty($tip_safety)) {
                            $conn->execute_query("INSERT INTO travel_tip (place_id, tip_category, tip_text) VALUES (?, 'safety/what-to-bring', ?)", [$new_place_id, $tip_safety]);
                        }
                        if (!empty($tip_best_time)) {
                            $conn->execute_query("INSERT INTO travel_tip (place_id, tip_category, tip_text) VALUES (?, 'best-time-to-visit', ?)", [$new_place_id, $tip_best_time]);
                        }

                        $notification_js = 'triggerNotificationSuccess(' . json_encode("Place and travel tips added successfully!") . ');';
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
    <a href="manage_places.php" class="btn btn-<?= htmlspecialchars($theme_color) ?> mt-3">&laquo; Back to Manage Places</a>
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
                            <label for="place_name" class="form-label">Place Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="place_name" name="place_name" value="<?= htmlspecialchars($form_values['place_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="place_description" class="form-label">Place Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="place_description" name="place_description" rows="3" required><?= htmlspecialchars($form_values['place_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select a category</option>
                                <?php
                                $sql = "SELECT category_id, category_name FROM category ORDER BY category_name ASC";
                                $result = $conn->execute_query($sql);
                                while ($row = $result->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['category_id']); ?>" <?= ($form_values['category'] == $row['category_id']) ? 'selected' : ''; ?>><?= htmlspecialchars($row['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="distance_km" class="form-label">Distance from Pugoda (km)</label>
                            <input type="number" step="0.01" class="form-control" id="distance_km" name="distance_km" value="<?= htmlspecialchars($form_values['distance_km']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="opening_time" class="form-label">Opening Time</label>
                            <input type="text" class="form-control" id="opening_time" name="opening_time" placeholder="e.g. 8:00 AM - 5:00 PM or Not applicable" value="<?= htmlspecialchars($form_values['opening_time']); ?>">
                        </div>

                        <!-- Categorized Travel Tips per SRS REQ-3.3 -->
                        <div class="card bg-light p-3 mb-3 border-0">
                            <h6 class="fw-bold mb-3">Travel Tips (Categorized per SRS Requirements)</h6>
                            <div class="mb-2">
                                <label for="tip_entry_fee" class="form-label small fw-semibold">1. Entry-Fee Status Tip</label>
                                <input type="text" class="form-control" id="tip_entry_fee" name="tip_entry_fee" placeholder="e.g. Free to visit / Entry fee applies" value="<?= htmlspecialchars($form_values['tip_entry_fee']); ?>">
                            </div>
                            <div class="mb-2">
                                <label for="tip_safety" class="form-label small fw-semibold">2. Safety / What-to-Bring Tip</label>
                                <input type="text" class="form-control" id="tip_safety" name="tip_safety" placeholder="e.g. Dress modestly, wear non-slip footwear, bring water" value="<?= htmlspecialchars($form_values['tip_safety']); ?>">
                            </div>
                            <div class="mb-1">
                                <label for="tip_best_time" class="form-label small fw-semibold">3. Best-Time-to-Visit Tip</label>
                                <input type="text" class="form-control" id="tip_best_time" name="tip_best_time" placeholder="e.g. Morning hours, weekday mornings" value="<?= htmlspecialchars($form_values['tip_best_time']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="latitude" class="form-label">Latitude <span class="text-danger">*</span></label>
                            <input type="number" step="any" class="form-control" id="latitude" name="latitude" value="<?= htmlspecialchars($form_values['latitude']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="longitude" class="form-label">Longitude <span class="text-danger">*</span></label>
                            <input type="number" step="any" class="form-control" id="longitude" name="longitude" value="<?= htmlspecialchars($form_values['longitude']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="image_path" class="form-label">Image URL: <small class="form-text text-muted">Relative path of images folder. Ex: images/anods/</small> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="image_path" name="image_path" value="<?= htmlspecialchars($form_values['image_path']); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-<?= htmlspecialchars($theme_color) ?> w-100">Add Place</button>
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
