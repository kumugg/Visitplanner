<?php
require_once __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';
$page = "home";
$category = $_GET['category'] ?? null;
?>

<div class="container">
    <p class="h1 fw-bold text-<?= $theme_color ?> py-5" style="font-family: 'Roboto', sans-serif"> The Places Google Maps Forgot - Now All in One Spot</p>

    <a href="?category=all" class="btn btn-outline-<?= $theme_color; ?> mb-5">All</a>

    <!-- Get Categories names via mysql by using forloop -->
    <?php
    $result = $conn->execute_query("SELECT category_name FROM category");
    while($row = $result->fetch_assoc()):
        $category_name = htmlspecialchars($row["category_name"]);
    ?>
        <a href="?category=<?= strtolower($category_name); ?>" class="btn btn-outline-<?= $theme_color; ?> mb-5 ms-3"> <?= $category_name; ?> </a>
    <?php endwhile; ?>

    <!-- Display Travel Points briefly -->
    <div class="row row-cols-1 row-cols-md-3 g-5">
        <?php
        $sql = "SELECT p.place_name, p.place_id, p.place_description, p.image_path, c.category_name FROM place p INNER JOIN category c ON c.category_id = p.category_id ";
        $params = [];
        if ($category != "all") {
            $sql .= " WHERE c.category_name = ?";
            $params[] = $category;
        }
        $sql .= " ORDER BY p.place_name";
        $result = $conn->execute_query($sql, $params);
        while ($row = $result->fetch_assoc()):
            $place_name = htmlspecialchars($row["place_name"]);
            $place_id = htmlspecialchars($row["place_id"]);
            $image_path = htmlspecialchars($row["image_path"]);
            $place_description = htmlspecialchars($row["place_description"]);
        ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?= $image_path; ?>/1.webp" class="card-img-top" alt="image of the <?= $place_name; ?>" style="height: 200px;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?=$place_name; ?> </h5>
                            <p class="card-text"><?= $place_description; ?></p>
                            <a href="place_details.php?place_id=<?= $place_id; ?>" class="btn btn-outline-<?= $theme_color; ?> mt-auto">View Details</a>
                        </div>
                    </div>
                </div>
        <?php endwhile;?>
    </div>
</div>

<?php
include __DIR__ . "/includes/footer.php";
?>
