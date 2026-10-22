<?php
require_once __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';
$page = "map";
$place_ids = array_values(array_unique(array_map('intval', $_SESSION['visit_plan'] ?? [])));        // Get the session array values by making it integer, unique and in order.
$places_by_id = [];     // 0 => ['place_id' => 42, 'place_name' => 'Sigiriya', 'latitude' => 7.95, 'longitude' => 80.75] as array

if (!empty($place_ids)) {
    $placeholders = implode(',', array_fill(0, count($place_ids), '?'));        // Using ? mark on sql command according to the value count on session array.
    $sql = "SELECT place_id, place_name, latitude, longitude FROM place WHERE place_id IN ($placeholders)";
    $result = $conn->execute_query($sql, $place_ids);

    while ($row = $result->fetch_assoc()) {
        $places_by_id[(int) $row['place_id']] = $row;
    }
}

$places = [];
foreach ($place_ids as $place_id) {
    if (isset($places_by_id[$place_id])) {
        $places[] = $places_by_id[$place_id];
    }
}

if (isset($_GET['place_id'], $_GET['remove']) && ($_GET['remove'] === "true")) {
    $pid = (int) $_GET['place_id'];

    if (!empty($_SESSION['visit_plan'])) {
        $key = array_search($pid, $_SESSION['visit_plan']);

        if ($key !== false) {
            unset($_SESSION['visit_plan'][$key]);
            $_SESSION['visit_plan'] = array_values($_SESSION['visit_plan']);
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));        // Remove the content after the ? mark on URI without manually typing the php file name.
            exit();
        }
    }
}

?>

<div class="container py-4">
    <div class="row g-4">
        <aside class="col-12 col-md-4">
            <h2 class="mb-5">Places in My Visit Plan</h2>
            <?php if (empty($places)): ?>
                <p class="text-muted">Your visit plan is empty.</p>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($places as $place): ?>
                        <li class="list-group-item">
                            <button class="btn btn-link text-decoration-none text-dark fw-medium" data-place-id="<?= (int) $place['place_id'] ?>" onclick="panToLocation(<?= $place['latitude'] ?? 'null' ?>, <?= $place['longitude'] ?? 'null' ?>)">
                                <?= htmlspecialchars($place['place_name']); ?>
                            </button>
                            <?php if ($place['latitude'] === null || $place['longitude'] === null): ?>
                                <small class="d-block text-muted">Location coordinates not available</small>
                            <?php endif; ?>
                            <a class="btn btn-sm btn-outline-danger float-end" href="?place_id=<?= htmlspecialchars($place['place_id']) ?>&remove=true">Remove</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </aside>
        <section class="col-12 col-md-8 mt-5" aria-label="Map of visit plan places">
            <div id="map" style="height: 560px; width: 100%;"></div>
            <p id="map-message" class="small text-muted mt-2 mb-0" hidden>No visit plan places have map coordinates yet.</p>
        </section>
    </div>
</div>
<script>
    let map;
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Wrap PHP output in array_values() to guarantee a valid JavaScript array
        const places = <?= json_encode(array_values(array_map(static function ($place) {
            return [
                'id' => (int) $place['place_id'],
                'name' => $place['place_name'],
                'latitude' => $place['latitude'] === null ? null : (float) $place['latitude'],
                'longitude' => $place['longitude'] === null ? null : (float) $place['longitude'],
            ];
        }, $places ?? [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        const defaultIcon = L.icon({
            iconUrl: '/visitplanner/images/leaflet/marker-icon.png',
            iconRetinaUrl: '/visitplanner/images/leaflet/marker-icon-2x.png',
            shadowUrl: '/visitplanner/images/leaflet/marker-shadow.png',

            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        map = L.map('map').setView([6.9271, 79.8612], 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // 2. Custom HTML sanitizer replacing non-existent L.Util.escapeHTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        const bounds = [];
        places.forEach(function(place) {
            // Skip places with missing/null coordinates
            if (place.latitude === null || place.longitude === null) {
                return;
            }

            const position = [place.latitude, place.longitude];
            
            L.marker(position, {icon: defaultIcon })
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
        if (map) {
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