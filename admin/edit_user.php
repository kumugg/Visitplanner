<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username = ?, password_hash = ? WHERE user_id = ?";
        $stmt = $conn->execute_query($sql, [$username, $hashed_password, $user_id]);
    } else {
        $sql = "UPDATE users SET username = ? WHERE user_id = ?";
        $stmt = $conn->execute_query($sql, [$username, $user_id]);
    }

    if ($stmt) {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("User updated successfully!"); }); </script>';
    } else {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error updating user: ' . htmlspecialchars($conn->error) . '"); }); </script>';
    }
}

?>

<div class="container py-5">
    <a href="admin_dashboard.php" class="btn btn-<?= $theme_color ?> mt-3">&laquo; Back to Dashboard</a>
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Edit User</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-6 col-sm-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <?php
                    if (isset($_GET['user_id'])) {
                        $user_id = $_GET['user_id'];
                        $sql = "SELECT user_id, username FROM users WHERE user_id = ?";
                        $result = $conn->execute_query($sql, [$user_id]);
                        $user = $result->fetch_assoc();
                        if ($user) {
                            ?>
                            <form action="#" method="post" id="editUserForm">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']); ?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password if you want to change it">
                                </div>
                                <button type="submit" class="btn btn-<?= $theme_color ?> w-100">Update User</button>
                            </form>
                            <?php
                        } else {
                            echo '<p>User not found.</p>';
                        }
                    } else {
                        echo '<p>No user selected.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php
include __DIR__ . '/../includes/footer.php';
?>