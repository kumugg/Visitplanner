<?php
include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
$page = "manage_places";
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
                            $sql = "SELECT place_id, place_name FROM place";
                            $result = $conn->execute_query($sql);
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['place_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a href="edit_place.php?place_id=<?= urlencode($row['place_id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <a href="delete_place.php?place_id=<?= urlencode($row['place_id']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this place?');">Delete</a>
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
include __DIR__ . '/includes/footer.php';
?>