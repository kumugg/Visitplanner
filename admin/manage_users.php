<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';
$page = "manage_users";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) ) {

    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
    
    $user_id = $_POST['user_id'];

    // Delete the user from the database
    $sql = "DELETE FROM users WHERE user_id = ?";
    $result = $conn->execute_query($sql, [$user_id]);

    if ($result) {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("User deleted successfully!"); }); </script>';
    } else {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error deleting user: ' . htmlspecialchars($conn->error) . '"); }); </script>';
    }
}

?>

<div class="container py-5">
    <a href="admin_dashboard.php" class="btn btn-<?= $theme_color ?> mt-3">&laquo; Back to Dashboard</a>
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold mt-4">Manage Users</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <a href="add_user.php" class="btn btn-<?= $theme_color ?> w-100 mb-3">Add New User</a>
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Username</th>
                                <th scope="col">User Type</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT user_id, username, user_type FROM users";
                            $result = $conn->execute_query($sql);
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['username']); ?></td>
                                    <td><?= htmlspecialchars($row['user_type']  ); ?></td>
                                    <td>
                                        <a href="edit_user.php?user_id=<?= urlencode($row['user_id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form action="#" method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['user_id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
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


<?php
include __DIR__ . '/../includes/footer.php';
?>