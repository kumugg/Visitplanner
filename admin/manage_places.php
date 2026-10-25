<?php
$page = "manage_places";
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';

$notification_js = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_id'])) {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $place_id = (int) $_POST['place_id'];

    if ($place_id > 0) {
        $sql = "DELETE FROM place WHERE place_id = ?";
        try {
            $stmt = $conn->execute_query($sql, [$place_id]);
            if ($stmt) {
                // If the deleted place is in session visit_plan, remove it
                if (!empty($_SESSION['visit_plan'])) {
                    $key = array_search($place_id, $_SESSION['visit_plan'], true);
                    if ($key !== false) {
                        unset($_SESSION['visit_plan'][$key]);
                        $_SESSION['visit_plan'] = array_values($_SESSION['visit_plan']);
                    }
                }
                $notification_js = 'triggerNotificationSuccess(' . json_encode("Place deleted successfully!") . ');';
            } else {
                $notification_js = 'triggerNotificationFailed(' . json_encode("Error deleting place.") . ');';
            }
        } catch (Exception $e) {
            error_log("Error deleting place: " . $e->getMessage());
            $notification_js = 'triggerNotificationFailed(' . json_encode("Error deleting place: " . $e->getMessage()) . ');';
        }
    }
}
?>

<div class="container py-5">
    <a href="admin_dashboard.php" class="btn btn-<?= htmlspecialchars($theme_color) ?> mt-3">&laquo; Back to Dashboard</a>
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Manage Places</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <a href="add_place.php" class="btn btn-<?= htmlspecialchars($theme_color) ?> w-100 mb-3">Add New Place</a>
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Place Name</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT place_id, place_name FROM place ORDER BY place_name ASC";
                            $result = $conn->execute_query($sql);
                            $found = false;
                            while ($row = $result->fetch_assoc()):
                                $found = true;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['place_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <a href="edit_place.php?place_id=<?= urlencode($row['place_id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form action="#" method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                            <input type="hidden" name="place_id" value="<?= (int) $row['place_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this place?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if (!$found): ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No places found.</td>
                                </tr>
                            <?php endif; ?>
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

<?php if (!empty($notification_js)): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?= $notification_js ?>
    });
</script>
<?php endif; ?>
