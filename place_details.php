<?php
require_once __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';
$page = "place_details";

$category_name     = '';
$image_path        = '';
$place_name        = '';
$place_description = '';
$opening_time      = '';
$travel_tip        = '';
$distance_km       = '';

$selected_place_id = isset($_GET['place_id']) ? (int) $_GET['place_id'] : 0;
$notification_js   = '';

$sql = "SELECT p.place_name, p.place_description, p.opening_time, p.travel_tip, p.distance_km, p.image_path, c.category_name FROM place p INNER JOIN category c ON c.category_id = p.category_id WHERE p.place_id = ? LIMIT 1";
$result = $conn->execute_query($sql, [$selected_place_id]);
$row = $result ? $result->fetch_assoc() : null;

if ($row) {
    $place_name        = htmlspecialchars($row['place_name']);
    $place_description = htmlspecialchars($row['place_description']);
    $opening_time      = htmlspecialchars($row['opening_time'] ?? 'Not Applicable');
    $travel_tip        = htmlspecialchars($row['travel_tip'] ?? 'None');
    $distance_km       = htmlspecialchars($row['distance_km'] ?? 'N/A');
    $image_path        = $row['image_path'] ?? '';
    $category_name     = htmlspecialchars($row['category_name']);
} else {
    echo '<div class="container py-5 text-center">
            <h2 class="mb-4">Place Not Found</h2>
            <p class="text-muted">The requested place could not be found or has been removed.</p>
            <a href="index.php?category=all" class="btn btn-' . htmlspecialchars($theme_color) . '">&laquo; Back to Places</a>
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit();
}

// Handle adding a place to the visit plan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? null;

    if ($action === 'add') {
        if (!in_array($selected_place_id, $_SESSION['visit_plan'], true)) {
            $_SESSION['visit_plan'][] = $selected_place_id;
            
            // Sync with DB if user is logged in
            if (!empty($_SESSION['logged_in_username'])) {
                $u_sql = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
                $u_res = $conn->execute_query($u_sql, [$_SESSION['logged_in_username']]);
                if ($u_row = $u_res->fetch_assoc()) {
                    $conn->execute_query("INSERT IGNORE INTO visit_plan (user_id, place_id) VALUES (?, ?)", [$u_row['user_id'], $selected_place_id]);
                }
            }
            $notification_js = 'triggerNotificationSuccess(' . json_encode("Place added to visit plan!") . ');';
        }
    } elseif ($action === 'remove') {
        $key = array_search($selected_place_id, $_SESSION['visit_plan'], true);
        if ($key !== false) {
            unset($_SESSION['visit_plan'][$key]);
            $_SESSION['visit_plan'] = array_values($_SESSION['visit_plan']); // Reindex the array
            
            // Sync with DB if user is logged in
            if (!empty($_SESSION['logged_in_username'])) {
                $u_sql = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
                $u_res = $conn->execute_query($u_sql, [$_SESSION['logged_in_username']]);
                if ($u_row = $u_res->fetch_assoc()) {
                    $conn->execute_query("DELETE FROM visit_plan WHERE user_id = ? AND place_id = ?", [$u_row['user_id'], $selected_place_id]);
                }
            }
            $notification_js = 'triggerNotificationSuccess(' . json_encode("Place removed from visit plan!") . ');';
        }
    }
}

$is_added_to_vistiplan = in_array($selected_place_id, $_SESSION['visit_plan'], true);
?>

<div class="container-xxl">
    <div class="row">
        <a href="index.php?category=<?= urlencode(strtolower($row['category_name'])); ?>" class="btn btn-outline-<?= $theme_color; ?> btn-sm col-auto mt-4 mb-3">&laquo; Back to <?= $category_name; ?> Category </a>
    </div>
    <div class="row">
        <div class="col-6 my-4">
            <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $allowed_extension = ['webp', 'jpg', 'jpeg', 'png'];
                    $count = 0;
                    $dir_to_check = '';

                    if (!empty($image_path)) {
                        if (is_dir($image_path)) {
                            $dir_to_check = $image_path;
                        } elseif (is_dir(__DIR__ . '/' . ltrim($image_path, '/'))) {
                            $dir_to_check = __DIR__ . '/' . ltrim($image_path, '/');
                        }
                    }

                    if (!empty($dir_to_check)) {
                        $iterator = new FilesystemIterator($dir_to_check, FilesystemIterator::SKIP_DOTS);
                        foreach ($iterator as $fileinfo) {
                            if ($fileinfo->isFile()) {
                                $filename = $fileinfo->getFilename();
                                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                                if (in_array($extension, $allowed_extension, true)) {
                                    $webpath = rtrim($image_path, '/') . '/' . $filename;
                                    $active_cls = ($count < 1) ? ' active' : '';
                                    echo '<div class="carousel-item' . $active_cls . '"> <img src="' . htmlspecialchars($webpath) . '" class="d-block w-100" alt="Image of ' . $place_name . '"> </div>';
                                    $count++;
                                }
                            }
                        }
                    }
                    if ($count === 0) {
                        echo '<div class="carousel-item active"> <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 350px;"><span>No images available</span></div> </div>';
                    }
                    ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
        <div class="col-6 my-2">
            <p class="fs-1 fw-semibold lh-1"><?= $place_name; ?></p>
            <button type="button" class="btn btn-<?= $theme_color; ?> btn-sm" disabled><?= $category_name; ?></button>
            <p class="lead mt-4"><?= $place_description; ?></p>
            <table class="table table-borderless fs-6 lh-1 mb-0">
                <tbody>
                    <tr>
                        <td class="fw-bold pb-4" style="width: 35%;">Opening Times:</td>
                        <td class="pb-4 text-secondary"><?= $opening_time; ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold pb-4">Travel Tips:</td>
                        <td class="pb-4 text-secondary"><?= $travel_tip; ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold pb-4">Distance from Pugoda:</td>
                        <td class="pb-4 text-secondary"><?= $distance_km; ?> km</td>
                    </tr>
                </tbody>
            </table>
            <form action="#" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                <?php 
                 if (!$is_added_to_vistiplan) {
                    echo '<button type="submit" name="action" value="add" class="btn btn-outline-' . $theme_color . ' ms-3 mt-4"> Add to visit plan</button>';
                 } else {
                    echo '<button type="submit" name="action" value="remove" class="btn btn-outline-' . $theme_color . ' ms-3 mt-4"> Remove from visit plan</button>';
                 }
                 ?>
                <a href="my_visit_plan.php" class="btn btn-outline-<?= $theme_color ?> ms-4 mt-4"> View on Map</a>
            </form>
        </div>
    </div>
</div>

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
