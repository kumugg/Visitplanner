<?php
$page = "manage_users";
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';

$notification_js = '';
$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0);

if ($user_id <= 0) {
    header("Location: manage_users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $username         = trim($_POST['username'] ?? '');
    $first_name       = trim($_POST['first_name'] ?? '');
    $last_name        = trim($_POST['last_name'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type        = trim($_POST['user_type'] ?? '');

    // Protect default admin user (user_id = 1) from role demotion
    if ($user_id === 1 && $user_type !== 'admin') {
        $notification_js = 'triggerNotificationFailed(' . json_encode("The default admin user role cannot be changed.") . ');';
    } elseif (!in_array($user_type, ['admin', 'user'], true)) {
        $notification_js = 'triggerNotificationFailed(' . json_encode("Invalid user type selected.") . ');';
    } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        $notification_js = 'triggerNotificationFailed(' . json_encode("Username must be between 3 and 50 characters.") . ');';
    } elseif (mb_strlen($first_name) > 50 || mb_strlen($last_name) > 50) {
        $notification_js = 'triggerNotificationFailed(' . json_encode("First and last names cannot exceed 50 characters.") . ');';
    } else {
        // Check if duplicate username exists for another user
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ? AND user_id != ?";
        $result = $conn->execute_query($sql, [$username, $user_id]);
        $row = $result ? $result->fetch_assoc() : ['count' => 0];

        if ($row['count'] > 0) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("Username already exists. Please choose a different username.") . ');';
        } elseif (!empty($password) || !empty($confirm_password)) {
            if (strlen($password) < 6) {
                $notification_js = 'triggerNotificationFailed(' . json_encode("Password must be at least 6 characters long.") . ');';
            } elseif ($password !== $confirm_password) {
                $notification_js = 'triggerNotificationFailed(' . json_encode("Passwords do not match. Please try again.") . ');';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, first_name = ?, last_name = ?, password_hash = ?, user_type = ? WHERE user_id = ?";
                try {
                    $conn->execute_query($sql, [$username, $first_name, $last_name, $hashed_password, $user_type, $user_id]);

                    // If current logged-in user changed their own username/type, update session
                    if (isset($_SESSION['logged_in_username'])) {
                        $curr_res = $conn->execute_query("SELECT user_id FROM users WHERE username = ? LIMIT 1", [$_SESSION['logged_in_username']]);
                        $curr_u = $curr_res ? $curr_res->fetch_assoc() : null;
                        if ($curr_u && (int)$curr_u['user_id'] === $user_id) {
                            $_SESSION['logged_in_username'] = $username;
                            $_SESSION['user_type'] = $user_type;
                        }
                    }

                    $notification_js = 'triggerNotificationSuccess(' . json_encode("User details and password updated successfully!") . ');';
                } catch (Exception $e) {
                    error_log("Error updating user: " . $e->getMessage());
                    $notification_js = 'triggerNotificationFailed(' . json_encode("Database error occurred while updating user.") . ');';
                }
            }
        } elseif (!empty($username) && !empty($user_type)) {
            $sql = "UPDATE users SET username = ?, first_name = ?, last_name = ?, user_type = ? WHERE user_id = ?";
            try {
                $conn->execute_query($sql, [$username, $first_name, $last_name, $user_type, $user_id]);

                // If current logged-in user changed their own username/type, update session
                if (isset($_SESSION['logged_in_username'])) {
                    $curr_res = $conn->execute_query("SELECT user_id FROM users WHERE username = ? LIMIT 1", [$_SESSION['logged_in_username']]);
                    $curr_u = $curr_res ? $curr_res->fetch_assoc() : null;
                    if ($curr_u && (int)$curr_u['user_id'] === $user_id) {
                        $_SESSION['logged_in_username'] = $username;
                        $_SESSION['user_type'] = $user_type;
                    }
                }

                $notification_js = 'triggerNotificationSuccess(' . json_encode("User details updated successfully!") . ');';
            } catch (Exception $e) {
                error_log("Error updating user: " . $e->getMessage());
                $notification_js = 'triggerNotificationFailed(' . json_encode("Database error occurred while updating user.") . ');';
            }
        }
    }
}

$sql = "SELECT user_id, username, first_name, last_name, user_type FROM users WHERE user_id = ? LIMIT 1";
$result = $conn->execute_query($sql, [$user_id]);
$user = $result ? $result->fetch_assoc() : null;

if (!$user) {
    header("Location: manage_users.php");
    exit();
}
?>

<div class="container py-5">
    <a href="manage_users.php" class="btn btn-<?= htmlspecialchars($theme_color) ?> mt-3">&laquo; Back to Manage Users</a>
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Edit User</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-6 col-sm-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="edit_user.php?user_id=<?= (int)$user['user_id'] ?>" method="post" id="editUserForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="user_id" value="<?= (int)$user['user_id']; ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="Enter new password to change it">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" placeholder="Confirm new password">
                        </div>
                        <div class="mb-3">
                            <label for="user_type" class="form-label">User Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="user_type" name="user_type" required <?= ((int)$user['user_id'] === 1) ? 'disabled' : '' ?>>
                                <option value="user" <?= ($user['user_type'] === 'user') ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= ($user['user_type'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <?php if ((int)$user['user_id'] === 1): ?>
                                <input type="hidden" name="user_type" value="admin">
                                <small class="text-muted">The default admin role cannot be modified.</small>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-<?= htmlspecialchars($theme_color) ?> w-100">Update User</button>
                    </form>
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
