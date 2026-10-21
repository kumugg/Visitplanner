<?php
include __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/admins_only.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $place_id = $_POST['place_id'];
    $name = $_POST['place_name'];
    $description = $_POST['place_description'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    $sql = "UPDATE place SET place_name = ?, place_description = ?, latitude = ?, longitude = ? WHERE place_id = ?";
    $stmt = $conn->execute_query($sql, [$name, $description, $latitude, $longitude, $place_id]);

    if ($stmt) {
        echo '<div class="container mt-4"><p class="fs-2 fw-semibold">Place updated successfully.</p></div>';
    } else {
        echo '<div class="container mt-4"><p class="fs-2 fw-semibold">Error updating place: ' . htmlspecialchars($conn->error) . '</p></div>';
    }
}

$place_id = isset($_GET['place_id']) ? intval($_GET['place_id']) : 0;
if ($place_id <= 0) {
    header("Location: manage_places.php");
    exit();
}

$sql = "SELECT * FROM place WHERE place_id = ?";
$stmt = $conn->execute_query($sql, [$place_id]);
$place = $stmt->fetch_assoc();

if (!$place) {
    header("Location: manage_places.php");
    exit();
}
?>

<div class="container py-5">
    <a href="manage_places.php" class="btn btn-<?= $theme_color ?> mt-3">&laquo; Back to Manage Places</a>
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Edit Place</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="#" method="post" id="editPlaceForm">
                        <input type="hidden" name="place_id" value="<?= htmlspecialchars($place['place_id']); ?>">
                        <div class="mb-3">
                            <label for="place_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="place_name" name="place_name" value="<?= htmlspecialchars($place['place_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="place_description" class="form-label">Description</label>
                            <textarea class="form-control" id="place_description" name="place_description" rows="3" required><?= htmlspecialchars($place['place_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" value="<?= htmlspecialchars($place['latitude']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" value="<?= htmlspecialchars($place['longitude']); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100">Update Place</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>