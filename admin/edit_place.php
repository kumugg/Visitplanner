<?php
$page = "edit_place";
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';

$notification_js = '';
$place_id = isset($_GET['place_id']) ? (int) $_GET['place_id'] : (isset($_POST['place_id']) ? (int) $_POST['place_id'] : 0);

if ($place_id <= 0) {
    header("Location: manage_places.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $name              = trim($_POST['place_name'] ?? '');
    $description       = trim($_POST['place_description'] ?? '');
    $category_id       = isset($_POST['category']) ? (int)$_POST['category'] : 0;
    $opening_time      = trim($_POST['opening_time'] ?? '');
    $distance_km_raw   = trim($_POST['distance_km'] ?? '');
    $tip_entry_fee     = trim($_POST['tip_entry_fee'] ?? '');
    $tip_safety        = trim($_POST['tip_safety'] ?? '');
    $tip_best_time     = trim($_POST['tip_best_time'] ?? '');
    $latitude_raw      = trim($_POST['latitude'] ?? '');
    $longitude_raw     = trim($_POST['longitude'] ?? '');
    $image_path        = trim($_POST['image_path'] ?? '');

    $cat_result = $conn->execute_query("SELECT category_id FROM category");
    $valid_categories = $cat_result ? array_column($cat_result->fetch_all(MYSQLI_ASSOC), 'category_id') : [];

    if ($category_id <= 0 || !in_array($category_id, $valid_categories)) {
        $notification_js = 'triggerNotificationFailed(' . json_encode("Invalid category selected.") . ');';
    } elseif (empty($name) || empty($description)) {
        $notification_js = 'triggerNotificationFailed(' . json_encode("Place name and description cannot be empty.") . ');';
    } elseif ($latitude_raw !== '' && !is_numeric($latitude_raw)) {
        $notification_js = 'triggerNotificationFailed(' . json_encode("Latitude must be a valid number.") . ');';
    } elseif ($longitude_raw !== '' && !is_numeric($longitude_raw)) {
        $notification_js = 'triggerNotificationFailed(' . json_encode("Longitude must be a valid number.") . ');';
    } elseif ($distance_km_raw !== '' && !is_numeric($distance_km_raw)) {
        $notification_js = 'triggerNotificationFailed(' . json_encode("Distance must be a valid number.") . ');';
    } else {
        $distance_km = ($distance_km_raw !== '') ? (float) $distance_km_raw : null;
        $latitude    = ($latitude_raw !== '') ? (float) $latitude_raw : null;
        $longitude   = ($longitude_raw !== '') ? (float) $longitude_raw : null;
        $opening_time_val = !empty($opening_time) ? $opening_time : 'Not Applicable';

        $primary_tip = !empty($tip_entry_fee) ? $tip_entry_fee : (!empty($tip_safety) ? $tip_safety : $tip_best_time);

        $conn->begin_transaction();
        try {
            $sql = "UPDATE place SET place_name = ?, place_description = ?, category_id = ?, distance_km = ?, opening_time = ?, travel_tip = ?, latitude = ?, longitude = ?, image_path = ? WHERE place_id = ?";
            $conn->execute_query($sql, [$name, $description, $category_id, $distance_km, $opening_time_val, $primary_tip, $latitude, $longitude, $image_path, $place_id]);

            // Update multiple categorized travel tips in travel_tip table per SRS REQ-3.3
            $conn->execute_query("DELETE FROM travel_tip WHERE place_id = ?", [$place_id]);
            if (!empty($tip_entry_fee)) {
                $conn->execute_query("INSERT INTO travel_tip (place_id, tip_category, tip_text) VALUES (?, 'entry-fee status', ?)", [$place_id, $tip_entry_fee]);
            }
            if (!empty($tip_safety)) {
                $conn->execute_query("INSERT INTO travel_tip (place_id, tip_category, tip_text) VALUES (?, 'safety/what-to-bring', ?)", [$place_id, $tip_safety]);
            }
            if (!empty($tip_best_time)) {
                $conn->execute_query("INSERT INTO travel_tip (place_id, tip_category, tip_text) VALUES (?, 'best-time-to-visit', ?)", [$place_id, $tip_best_time]);
            }

            $conn->commit();
            $notification_js = 'triggerNotificationSuccess(' . json_encode("Place and travel tips updated successfully!") . ');';
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error updating place: " . $e->getMessage());
            $notification_js = 'triggerNotificationFailed(' . json_encode("Database error occurred while updating place.") . ');';
        }
    }
}

$sql = "SELECT * FROM place WHERE place_id = ? LIMIT 1";
$stmt = $conn->execute_query($sql, [$place_id]);
$place = $stmt ? $stmt->fetch_assoc() : null;

if (!$place) {
    header("Location: manage_places.php");
    exit();
}

// Load existing travel tips for this place
$existing_tips_res = $conn->execute_query("SELECT tip_category, tip_text FROM travel_tip WHERE place_id = ?", [$place_id]);
$place_tips = [
    'entry-fee status' => '',
    'safety/what-to-bring' => '',
    'best-time-to-visit' => '',
];
if ($existing_tips_res) {
    while ($t_row = $existing_tips_res->fetch_assoc()) {
        $cat_key = $t_row['tip_category'];
        if (array_key_exists($cat_key, $place_tips)) {
            $place_tips[$cat_key] = $t_row['tip_text'];
        }
    }
}
// If no categorized tips exist yet, fallback to single tip from place table
if (empty($place_tips['entry-fee status']) && empty($place_tips['safety/what-to-bring']) && empty($place_tips['best-time-to-visit'])) {
    $place_tips['entry-fee status'] = $place['travel_tip'] ?? '';
}
?>

<div class="container py-5">
    <a href="manage_places.php" class="btn btn-<?= htmlspecialchars($theme_color) ?> mt-3">&laquo; Back to Manage Places</a>
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Edit Place</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="edit_place.php?place_id=<?= (int)$place['place_id'] ?>" method="post" id="editPlaceForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="place_id" value="<?= (int) $place['place_id']; ?>">
                        <div class="mb-3">
                            <label for="place_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="place_name" name="place_name" value="<?= htmlspecialchars($place['place_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="place_description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="place_description" name="place_description" rows="3" required><?= htmlspecialchars($place['place_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select a category</option>
                                <?php
                                $cat_sql = "SELECT category_id, category_name FROM category ORDER BY category_name ASC";
                                $result = $conn->execute_query($cat_sql);
                                while ($row = $result->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['category_id']); ?>" <?= ($row['category_id'] == $place['category_id']) ? 'selected' : ''; ?>><?= htmlspecialchars($row['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="distance_km" class="form-label">Distance from Pugoda (km)</label>
                            <input type="number" step="0.01" class="form-control" id="distance_km" name="distance_km" value="<?= htmlspecialchars($place['distance_km'] ?? ''); ?>" >
                        </div>
                        <div class="mb-3">
                            <label for="opening_time" class="form-label">Opening Time</label>
                            <input type="text" class="form-control" id="opening_time" name="opening_time" value="<?= htmlspecialchars($place['opening_time'] ?? ''); ?>" >
                        </div>

                        <!-- Categorized Travel Tips per SRS REQ-3.3 -->
                        <div class="card bg-light p-3 mb-3 border-0">
                            <h6 class="fw-bold mb-3">Travel Tips (Categorized per SRS Requirements)</h6>
                            <div class="mb-2">
                                <label for="tip_entry_fee" class="form-label small fw-semibold">1. Entry-Fee Status Tip</label>
                                <input type="text" class="form-control" id="tip_entry_fee" name="tip_entry_fee" placeholder="e.g. Free to visit / Entry fee applies" value="<?= htmlspecialchars($place_tips['entry-fee status']); ?>">
                            </div>
                            <div class="mb-2">
                                <label for="tip_safety" class="form-label small fw-semibold">2. Safety / What-to-Bring Tip</label>
                                <input type="text" class="form-control" id="tip_safety" name="tip_safety" placeholder="e.g. Dress modestly, wear non-slip footwear, bring water" value="<?= htmlspecialchars($place_tips['safety/what-to-bring']); ?>">
                            </div>
                            <div class="mb-1">
                                <label for="tip_best_time" class="form-label small fw-semibold">3. Best-Time-to-Visit Tip</label>
                                <input type="text" class="form-control" id="tip_best_time" name="tip_best_time" placeholder="e.g. Morning hours, weekday mornings" value="<?= htmlspecialchars($place_tips['best-time-to-visit']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="number" step="any" class="form-control" id="latitude" name="latitude" value="<?= htmlspecialchars($place['latitude'] ?? ''); ?>" >
                        </div>
                        <div class="mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="number" step="any" class="form-control" id="longitude" name="longitude" value="<?= htmlspecialchars($place['longitude'] ?? ''); ?>" >
                        </div>
                        <div class="mb-3">
                            <label for="image_path" class="form-label">Image URL: <small class="form-text text-muted">Relative path of images folder. Ex: images/anods/</small></label>
                            <input type="text" class="form-control" id="image_path" name="image_path" value="<?= htmlspecialchars($place['image_path'] ?? ''); ?>" >
                        </div>
                        <button type="submit" class="btn btn-<?= htmlspecialchars($theme_color) ?> w-100">Update Place</button>
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
