<?php
$page = "place_details";
include __DIR__ . '/includes/header.php';

$selected_place_id = isset($_GET['place_id']) ? (int) $_GET['place_id'] : 0;
$notification_js = '';

$sql = "SELECT p.place_id, p.place_name, p.place_description, p.opening_time, p.travel_tip, p.distance_km, p.image_path, c.category_name FROM place p INNER JOIN category c ON c.category_id = p.category_id WHERE p.place_id = ? LIMIT 1";
$result = $conn->execute_query($sql, [$selected_place_id]);
$place = $result ? $result->fetch_assoc() : null;

if (!$place) {
    echo '<div class="container py-5 text-center">
            <h2 class="mb-4">Place Not Found</h2>
            <p class="text-muted">The requested place could not be found or has been removed.</p>
            <a href="index.php?category=all" class="btn btn-' . htmlspecialchars($theme_color) . '">&laquo; Back to Places</a>
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit();
}

// Fetch multiple categorized travel tips per SRS REQ-3.3
$tips_result = $conn->execute_query("SELECT tip_category, tip_text FROM travel_tip WHERE place_id = ? ORDER BY tip_id ASC", [$selected_place_id]);
$travel_tips = $tips_result ? $tips_result->fetch_all(MYSQLI_ASSOC) : [];

$place_name        = htmlspecialchars($place['place_name']);
$place_description = htmlspecialchars($place['place_description']);
$opening_time      = htmlspecialchars($place['opening_time'] ?? 'Not Applicable');
$fallback_tip      = htmlspecialchars($place['travel_tip'] ?? 'None');
$distance_km       = htmlspecialchars($place['distance_km'] ?? 'N/A');
$image_path        = $place['image_path'] ?? '';
$category_name     = htmlspecialchars($place['category_name']);

// Handle adding/removing from visit plan
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
            $_SESSION['visit_plan'] = array_values($_SESSION['visit_plan']);
            
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

$is_added_to_visitplan = in_array($selected_place_id, $_SESSION['visit_plan'], true);
?>

<div class="container-xxl">
    <div class="row">
        <a href="index.php?category=<?= urlencode(strtolower($place['category_name'])); ?>" class="btn btn-outline-<?= htmlspecialchars($theme_color); ?> btn-sm col-auto mt-4 mb-3">&laquo; Back to <?= $category_name; ?> Category </a>
    </div>
    <div class="row">
        <div class="col-12 col-md-6 my-4">
            <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $allowed_extension = ['webp', 'jpg', 'jpeg', 'png'];
                    $carousel_images = [];
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
                                $ext = strtolower($fileinfo->getExtension());
                                if (in_array($ext, $allowed_extension, true)) {
                                    $carousel_images[] = rtrim($image_path, '/') . '/' . $fileinfo->getFilename();
                                }
                            }
                        }
                        sort($carousel_images);
                    }

                    if (empty($carousel_images)) {
                        echo '<div class="carousel-item active">
                                <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 350px;">
                                    <span>No images available</span>
                                </div>
                              </div>';
                    } else {
                        foreach ($carousel_images as $idx => $img_src) {
                            $active_cls = ($idx === 0) ? ' active' : '';
                            echo '<div class="carousel-item' . $active_cls . '">
                                    <img src="' . htmlspecialchars($img_src) . '" class="d-block w-100 rounded" alt="Image of ' . $place_name . '" style="max-height: 400px; object-fit: cover;">
                                  </div>';
                        }
                    }
                    ?>
                </div>
                <?php if (count($carousel_images) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12 col-md-6 my-2">
            <p class="fs-1 fw-semibold lh-1"><?= $place_name; ?></p>
            <span class="badge bg-<?= htmlspecialchars($theme_color); ?> fs-6"><?= $category_name; ?></span>
            <p class="lead mt-4"><?= $place_description; ?></p>
            <table class="table table-borderless fs-6 mb-0">
                <tbody>
                    <tr>
                        <td class="fw-bold pb-3" style="width: 30%;">Opening Times:</td>
                        <td class="pb-3 text-secondary"><?= $opening_time; ?></td>
                    </tr>
                    <tr>
                        <td class="fw-bold pb-3" style="width: 30%;">Distance from Pugoda:</td>
                        <td class="pb-3 text-secondary"><?= $distance_km; ?> km</td>
                    </tr>
                    <tr>
                        <td class="fw-bold pb-3 align-top" style="width: 30%;">Travel Tips:</td>
                        <td class="pb-3 text-secondary">
                            <?php if (!empty($travel_tips)): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($travel_tips as $tip): 
                                        $badge_class = 'bg-secondary';
                                        if ($tip['tip_category'] === 'entry-fee status') {
                                            $badge_class = 'bg-info text-dark';
                                        } elseif ($tip['tip_category'] === 'safety/what-to-bring') {
                                            $badge_class = 'bg-warning text-dark';
                                        } elseif ($tip['tip_category'] === 'best-time-to-visit') {
                                            $badge_class = 'bg-success';
                                        }
                                    ?>
                                        <li class="mb-2">
                                            <span class="badge <?= $badge_class ?> me-1"><?= htmlspecialchars(ucwords(str_replace(['-', '/'], [' ', ' / '], $tip['tip_category']))); ?></span>
                                            <span><?= htmlspecialchars($tip['tip_text']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php elseif (!empty($fallback_tip) && $fallback_tip !== 'None'): ?>
                                <span><?= $fallback_tip; ?></span>
                            <?php else: ?>
                                <span class="text-muted">No travel tips currently available.</span>
                            <?php endif; ?>
                            <small class="text-muted d-block mt-2 fst-italic">Note: Conditions, accessibility, and entry fees may vary; please verify on-site.</small>
                        </td>
                    </tr>
                </tbody>
            </table>
            <form action="" method="post" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <?php if (!$is_added_to_visitplan): ?>
                    <button type="submit" name="action" value="add" class="btn btn-outline-<?= htmlspecialchars($theme_color); ?>">Add to visit plan</button>
                <?php else: ?>
                    <button type="submit" name="action" value="remove" class="btn btn-outline-danger">Remove from visit plan</button>
                <?php endif; ?>
                <a href="my_visit_plan.php" class="btn btn-outline-<?= htmlspecialchars($theme_color) ?> ms-3">View on Map</a>
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
