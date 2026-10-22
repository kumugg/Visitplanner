<?php
require_once __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';
$page = "place_details";

$selected_place_id = $_GET['place_id'] ?? null;
$is_added_to_vistiplan = in_array($selected_place_id, $_SESSION['visit_plan']);

$sql = "SELECT p.place_name, p.place_description, p.opening_time, p.travel_tip, p.distance_km, p.image_path, c.category_name FROM place p INNER JOIN category c ON c.category_id = p.category_id WHERE place_id = ?";
$result = $conn->execute_query($sql, [$selected_place_id]);
$row = $result->fetch_assoc();

if ($row) {
    $place_name        = htmlspecialchars($row['place_name']);
    $place_description = htmlspecialchars($row['place_description']);
    $opening_time      = htmlspecialchars($row['opening_time']);
    $travel_tip        = htmlspecialchars($row['travel_tip']);
    $distance_km       = htmlspecialchars($row['distance_km']);
    $image_path        = htmlspecialchars($row['image_path']);
    $category_name     = htmlspecialchars($row['category_name']);
    
} else {
    echo "Place not found.";
}

// Handle adding a place to the visit plan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'add') {
        if (!in_array($selected_place_id, $_SESSION['visit_plan'])) {
            $_SESSION['visit_plan'][] = $selected_place_id;
            $is_added_to_vistiplan = true;
            echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("Place added to visit plan!"); }); </script>';
        }
    } else if ($action === 'remove') {
        $key = array_search($selected_place_id, $_SESSION['visit_plan']);
        if (($key !== false)) {
            unset($_SESSION['visit_plan'][$key]);
            $_SESSION['visit_plan'] = array_values($_SESSION['visit_plan']); // Reindex the array
            $is_added_to_vistiplan = false;
            echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("Place removed from visit plan!"); }); </script>';
        }
    }
}

?>

<div class="container-xxl">
    <div class="row">
        <a href="index.php?category=<?= $category_name; ?>" class="btn btn-outline-<?= $theme_color; ?> btn-sm col-auto mt-4 mb-3">&laquo; Back to <?= $category_name; ?> Category </a>
    </div>
    <div class="row">
        <div class="col-6 my-4">
            <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $allowed_extension = ['webp'];
                    $count = 0;
                    if (is_dir($image_path)) {
                        $iterator = new FilesystemIterator($image_path, FilesystemIterator::SKIP_DOTS);
                        foreach ($iterator as $fileinfo) {
                            if ($fileinfo->isFile()) {
                                $filename = $fileinfo->getFilename();
                                $extension = strtolower(pathinfo($fileinfo, PATHINFO_EXTENSION));

                                if(in_array($extension, $allowed_extension)) {
                                    $webpath = rtrim($image_path, '/') . '/' . $filename;
                                    if ($count < 1) {
                                        echo '<div class="carousel-item active"> <img src="' . htmlspecialchars($webpath) . '" class="d-block w-100" alt="Image of '.$place_name.'"> </div>';
                                    }
                                    else {
                                        echo '<div class="carousel-item"> <img src="' . htmlspecialchars($webpath) . '" class="d-block w-100" alt="Image of '.$place_name.'"> </div>';
                                    }
                                    $count++;
                                }
                            }
                        }
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