<?php
include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/admins_only.php';
$page = "manage_places";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_id'])) {

    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $place_id = $_POST['place_id'];

    // Delete the place from the database
    $sql = "DELETE FROM place WHERE place_id = ?";
    $stmt = $conn->execute_query($sql, [$place_id]);

    if ($stmt) {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("Place deleted successfully!"); }); </script>';
    } else {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error deleting place: ' . htmlspecialchars($conn->error) . '"); }); </script>';
    }
}

?>

<div class="container py-5">
    <a href="admin_dashboard.php" class="btn btn-<?= $theme_color ?> mt-3">&laquo; Back to Dashboard</a>
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Manage Places</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <a href="add_place.php" class="btn btn-<?= $theme_color ?> w-100 mb-3">Add New Place</a>
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Place Name</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT place_id, place_name FROM place ORDER BY place_name ASC";
                            $result = $conn->execute_query($sql);
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['place_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a href="edit_place.php?place_id=<?= urlencode($row['place_id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form action="#" method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="place_id" value="<?= htmlspecialchars($row['place_id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this place?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../includes/footer.php';
?>