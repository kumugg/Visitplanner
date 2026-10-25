<?php
$page = "places";
include __DIR__ . '/includes/header.php';

$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$selected_category = strtolower($category);
?>

<div class="container">
    <?php
    if (!empty($_SESSION['logged_in_username'])) {
        $sql = "SELECT first_name FROM users WHERE username = ? LIMIT 1";
        $user_res = $conn->execute_query($sql, [$_SESSION['logged_in_username']]);
        $user = $user_res ? $user_res->fetch_assoc() : null;
        $first_name = htmlspecialchars($user['first_name'] ?? '');
        echo "<p class=\"h1 fw-bold text-" . htmlspecialchars($theme_color) . " py-5\" style=\"font-family: 'Roboto', sans-serif;\">Hi, " . $first_name . "! The Places Google Maps Forgot - Now All in One Spot</p>";    
    } else {
        echo "<p class=\"h1 fw-bold text-" . htmlspecialchars($theme_color) . " py-5\" style=\"font-family: 'Roboto', sans-serif;\">The Places Google Maps Forgot - Now All in One Spot</p>";
    }
    ?>  

    <div class="mb-5 d-flex flex-wrap gap-2">
        <a href="?category=all" class="btn <?= ($selected_category === 'all' || $selected_category === '') ? 'btn-' . htmlspecialchars($theme_color) : 'btn-outline-' . htmlspecialchars($theme_color); ?>">All</a>

        <!-- Get Category names via mysql -->
        <?php
        $cat_result = $conn->execute_query("SELECT category_name FROM category ORDER BY category_name ASC");
        while ($row = $cat_result->fetch_assoc()):
            $cat_name = htmlspecialchars($row["category_name"]);
            $cat_param = strtolower($row["category_name"]);
            $btn_class = ($selected_category === $cat_param) ? 'btn-' . htmlspecialchars($theme_color) : 'btn-outline-' . htmlspecialchars($theme_color);
        ?>
            <a href="?category=<?= urlencode($cat_param); ?>" class="btn <?= $btn_class; ?>"> <?= $cat_name; ?> </a>
        <?php endwhile; ?>
    </div>

    <!-- Display Travel Points briefly -->
    <div class="row row-cols-1 row-cols-md-3 g-5">
        <?php
        $sql = "SELECT p.place_name, p.place_id, p.place_description, p.image_path, c.category_name FROM place p INNER JOIN category c ON c.category_id = p.category_id ";
        $params = [];
        if ($selected_category !== 'all' && $selected_category !== '') {
            $sql .= " WHERE LOWER(c.category_name) = ?";
            $params[] = $selected_category;
        }
        $sql .= " ORDER BY p.place_name ASC";
        $result = $conn->execute_query($sql, $params);
        $found_places = false;
        while ($row = $result->fetch_assoc()):
            $found_places = true;
            $place_name = htmlspecialchars($row["place_name"]);
            $place_id = (int) $row["place_id"];
            $raw_image_path = $row["image_path"] ?? '';
            $image_path = !empty($raw_image_path) ? htmlspecialchars(rtrim($raw_image_path, '/') . '/1.webp') : '/visitplanner/images/leaflet/marker-icon.png';
            $place_description = htmlspecialchars($row["place_description"]);
        ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?= $image_path; ?>" class="card-img-top" alt="Image of <?= $place_name; ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary mb-2 align-self-start"><?= htmlspecialchars($row['category_name']); ?></span>
                            <h5 class="card-title"><?= $place_name; ?> </h5>
                            <p class="card-text"><?= $place_description; ?></p>
                            <a href="place_details.php?place_id=<?= $place_id; ?>" class="btn btn-outline-<?= htmlspecialchars($theme_color); ?> mt-auto">View Details</a>
                        </div>
                    </div>
                </div>
        <?php endwhile; ?>
        <?php if (!$found_places): ?>
            <div class="col-12">
                <p class="text-muted">No places found in this category.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include __DIR__ . "/includes/footer.php";
?>
