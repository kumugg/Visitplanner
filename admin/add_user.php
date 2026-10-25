<?php
$page = "manage_users";
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';

$notification_js = '';
$form_values = [
    'username' => '',
    'first_name' => '',
    'last_name' => '',
    'user_type' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $username   = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $password   = $_POST['password'] ?? '';
    $user_type  = trim($_POST['user_type'] ?? '');

    $form_values = [
        'username' => $username,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'user_type' => $user_type,
    ];

    if (!empty($username) && !empty($first_name) && !empty($password) && !empty($user_type)) {
        if (!in_array($user_type, ['admin', 'user'], true)) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("Invalid user type selected.") . ');';
        } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("Username must be between 3 and 50 characters.") . ');';
        } elseif (mb_strlen($first_name) > 50 || mb_strlen($last_name) > 50) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("First and last names cannot exceed 50 characters.") . ');';
        } elseif (strlen($password) < 6) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("Password must be at least 6 characters long.") . ');';
        } else {
            $sql = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
            $result = $conn->execute_query($sql, [$username]);
            $user_exists = ($result && $result->num_rows > 0);

            if ($user_exists) {
                $notification_js = 'triggerNotificationFailed(' . json_encode("Username already exists.") . ');';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_sql = "INSERT INTO users (username, first_name, last_name, password_hash, user_type) VALUES (?, ?, ?, ?, ?)";
                try {
                    $insert_result = $conn->execute_query($insert_sql, [$username, $first_name, $last_name, $hashed_password, $user_type]);

                    if ($insert_result) {
                        $notification_js = 'triggerNotificationSuccess(' . json_encode("User added successfully!") . ');';
                        $form_values = ['username' => '', 'first_name' => '', 'last_name' => '', 'user_type' => ''];
                    } else {
                        $notification_js = 'triggerNotificationFailed(' . json_encode("Error adding user.") . ');';
                    }
                } catch (Exception $e) {
                    error_log("Error adding user: " . $e->getMessage());
                    $notification_js = 'triggerNotificationFailed(' . json_encode("Database error occurred while adding user.") . ');';
                }
            }
        }
    } else {
        $notification_js = 'triggerNotificationFailed(' . json_encode("All required fields must be filled.") . ');';
    }
}
?>

<div class="container py-5">
    <a href="manage_users.php" class="btn btn-<?= htmlspecialchars($theme_color) ?> mt-3">&laquo; Back to Manage Users</a>
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Add User</h1>
        </div>
    </div>
        
    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="#" method="post" id="addUserForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($form_values['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($form_values['first_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($form_values['last_name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label for="user_type" class="form-label">User Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="">Select User Type</option>
                                <option value="admin" <?= ($form_values['user_type'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="user" <?= ($form_values['user_type'] === 'user') ? 'selected' : ''; ?>>User</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-<?= htmlspecialchars($theme_color) ?> w-100">Add User</button>
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
