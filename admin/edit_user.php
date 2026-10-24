<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $user_id = $_POST['user_id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';

    // Check if duplicate username exists for another user
    $sql = "SELECT COUNT(*) as count FROM users WHERE username = ? AND user_id != ?";
    $result = $conn->execute_query($sql, [$username, $user_id]);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Username already exists. Please choose a different username."); }); </script>';
    } 
    // If password input was provided, validate matching passwords
    elseif (!empty($password) || !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Passwords do not match. Please try again."); }); </script>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, password_hash = ?, user_type = ? WHERE user_id = ?";
            $conn->execute_query($sql, [$username, $hashed_password, $user_type, $user_id]);
            echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("User and password updated successfully!"); }); </script>';
        }
    } 
    // Password fields left empty -> Update details only
    elseif (!empty($username) && !empty($user_type)) {
        $sql = "UPDATE users SET username = ?, user_type = ? WHERE user_id = ?";
        $conn->execute_query($sql, [$username, $user_type, $user_id]);
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("User details updated successfully!"); }); </script>';
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
                        $sql = "SELECT user_id, username, user_type FROM users WHERE user_id = ?";
                        $result = $conn->execute_query($sql, [$user_id]);
                        $user = $result->fetch_assoc();
                        if ($user) {
                            ?>
                            <form action="#" method="post" id="editUserForm">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']); ?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password if you want to change it">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                                </div>
                                <div class="mb-3">
                                    <label for="user_type" class="form-label">User Type</label>
                                    <select class="form-control" id="user_type" name="user_type" required>
                                        <option value="user" <?= ($user['user_type'] === 'user') ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= ($user['user_type'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                    </select>
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