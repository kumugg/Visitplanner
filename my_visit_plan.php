<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['visit_plan']) || !is_array($_SESSION['visit_plan'])) {
    $_SESSION['visit_plan'] = [];
}

$notification_js = '';

// Handle removing a place from visit plan
if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_GET['remove']) && ($_GET['remove'] === "true")) {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $pid = isset($_GET['place_id']) ? (int) $_GET['place_id'] : 0;

    if (!empty($_SESSION['visit_plan'])) {
        $key = array_search($pid, $_SESSION['visit_plan'], true);
        if ($key === false) {
            // Also try loose comparison in case of string ID in legacy session
            $key = array_search($pid, array_map('intval', $_SESSION['visit_plan']), true);
        }

        if ($key !== false) {
            unset($_SESSION['visit_plan'][$key]);
            $_SESSION['visit_plan'] = array_values($_SESSION['visit_plan']);

            // Sync with DB if logged in
            if (isset($_SESSION['logged_in_username'])) {
                $u_sql = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
                $u_res = $conn->execute_query($u_sql, [$_SESSION['logged_in_username']]);
                if ($u_row = $u_res->fetch_assoc()) {
                    $conn->execute_query("DELETE FROM visit_plan WHERE user_id = ? AND place_id = ?", [$u_row['user_id'], $pid]);
                }
            }

            header("Location: my_visit_plan.php");
            exit();
        }
    }
}

// Save user plan in database for cross-device access
if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_GET['saveplan']) && ($_GET['saveplan'] === "true")) {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    if (isset($_SESSION['logged_in_username'])) {
        $sql = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
        $u_result = $conn->execute_query($sql, [$_SESSION["logged_in_username"]]);
        $row = $u_result ? $u_result->fetch_assoc() : null;

        if ($row) {
            $user_id = (int) $row['user_id'];
            $place_ids = array_values(array_unique(array_map('intval', $_SESSION['visit_plan'] ?? [])));

            $conn->begin_transaction();
            try {
                $del_sql = 'DELETE FROM visit_plan WHERE user_id = ?';
                $conn->execute_query($del_sql, [$user_id]);

                if (!empty($place_ids)) {
                    $placeholders = implode(',', array_fill(0, count($place_ids), '(?, ?)'));
                    $insert_sql = "INSERT IGNORE INTO visit_plan (user_id, place_id) VALUES {$placeholders}";

                    $params = [];
                    foreach ($place_ids as $p_id) {
                        $params[] = $user_id;
                        $params[] = $p_id;
                    }

                    $conn->execute_query($insert_sql, $params);
                }

                $conn->commit();
                $notification_js = 'triggerNotificationSuccess(' . json_encode("Visit plan saved successfully!") . ');';
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Error saving visit plan: " . $e->getMessage());
                $notification_js = 'triggerNotificationFailed(' . json_encode("Error saving visit plan. Please try again.") . ');';
            }
        }
    }
}

$page = "myvisitplan";
include __DIR__ . '/includes/header.php';

$place_ids = array_values(array_unique(array_map('intval', $_SESSION['visit_plan'] ?? [])));
$places_by_id = [];

if (!empty($place_ids)) {
    $placeholders = implode(',', array_fill(0, count($place_ids), '?'));
    $sql = "SELECT place_id, place_name, latitude, longitude FROM place WHERE place_id IN ($placeholders)";
    $result = $conn->execute_query($sql, $place_ids);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $places_by_id[(int) $row['place_id']] = $row;
        }
    }
}

$places = [];
foreach ($place_ids as $place_id) {
    if (isset($places_by_id[$place_id])) {
        $places[] = $places_by_id[$place_id];
    }
}
?>

<div class="container py-4">
    <div class="row g-4">
        <aside class="col-12 col-md-4">
            <h2 class="mb-4">Places in My Visit Plan</h2>
            <?php if (empty($places)): ?>
                <p class="text-muted">Your visit plan is empty.</p>
                <a href="index.php?category=all" class="btn btn-sm btn-outline-<?= htmlspecialchars($theme_color) ?>">Browse Places</a>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($places as $place): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-link text-decoration-none text-dark fw-medium p-0 text-start" data-place-id="<?= (int) $place['place_id'] ?>" onclick="panToLocation(<?= $place['latitude'] !== null ? (float)$place['latitude'] : 'null' ?>, <?= $place['longitude'] !== null ? (float)$place['longitude'] : 'null' ?>)">
                                    <?= htmlspecialchars($place['place_name']); ?>
                                </button>
                                <?php if ($place['latitude'] === null || $place['longitude'] === null): ?>
                                    <small class="d-block text-muted">Location coordinates not available</small>
                                <?php endif; ?>
                            </div>
                            <form method="post" action="?place_id=<?= (int)$place['place_id'] ?>&amp;remove=true" class="d-inline ms-2">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if (isset($_SESSION['logged_in_username'])): ?>
                <form method="post" action="?saveplan=true" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <button type="submit" class="btn btn-md btn-outline-<?= htmlspecialchars($theme_color) ?>">Save Plan</button>
                </form>
            <?php endif; ?> 
        </aside>
        <section class="col-12 col-md-8 mt-4 mt-md-0" aria-label="Map of visit plan places">
            <?php if (!isset($_SESSION['logged_in_username'])): ?>
                <p class="small text-muted mb-2">Tip: <a href="login.php">Log in</a> or <a href="register.php">register</a> to save your visit plan for access across devices.</p>
            <?php endif; ?>
            <div id="map" style="height: 560px; width: 100%; border-radius: 8px;"></div>
            <p id="map-message" class="small text-muted mt-2 mb-0" hidden>No visit plan places have map coordinates yet.</p>
        </section>
    </div>
</div>

<script>
    let map;
    document.addEventListener("DOMContentLoaded", function() {
        const places = <?= json_encode(array_values(array_map(static function ($place) {
            return [
                'id' => (int) $place['place_id'],
                'name' => $place['place_name'],
                'latitude' => $place['latitude'] === null ? null : (float) $place['latitude'],
                'longitude' => $place['longitude'] === null ? null : (float) $place['longitude'],
            ];
        }, $places)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        const defaultIcon = L.icon({
            iconUrl: '/visitplanner/images/leaflet/marker-icon.png',
            iconRetinaUrl: '/visitplanner/images/leaflet/marker-icon-2x.png',
            shadowUrl: '/visitplanner/images/leaflet/marker-shadow.png',

            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        map = L.map('map').setView([6.9271, 79.8612], 9);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        const bounds = [];
        places.forEach(function(place) {
            if (place.latitude === null || place.longitude === null) {
                return;
            }

            const position = [place.latitude, place.longitude];
            
            L.marker(position, { icon: defaultIcon })
                .addTo(map)
                .bindPopup('<a href="place_details.php?place_id=' + place.id + '">' +
                    escapeHtml(place.name) + '</a>');
                    
            bounds.push(position);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [24, 24], maxZoom: 14 });
        } else {
            const mapMsg = document.getElementById('map-message');
            if (mapMsg) mapMsg.hidden = false;
        }
    });

    function panToLocation(lat, lng) {
        if (map && lat !== null && lng !== null) {
            map.flyTo([lat, lng], 14, {
                animate: true,
                duration: 1.5
            });
        }
    }
</script>

<?php 
include __DIR__ . '/includes/footer.php';
?>

<?php if (!empty($notification_js)): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?= $notification_js ?>
    });
</script>
<?php endif; ?>
