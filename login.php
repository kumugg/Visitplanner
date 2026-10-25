<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';

// If user is already logged in, redirect to my_visit_plan.php
if (isset($_SESSION['logged_in_username'])) {
    if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        header("Location: ./admin/admin_dashboard.php");
    } else {
        header("Location: my_visit_plan.php");
    }
    exit();
}

$notification_js = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $sql = "SELECT user_id, password_hash, user_type FROM users WHERE username = ? LIMIT 1";
        $result = $conn->execute_query($sql, [$username]);
        $row = $result ? $result->fetch_assoc() : null;

        if ($row && password_verify($password, $row['password_hash'])) {
            // Prevent session fixation
            session_regenerate_id(true);

            $_SESSION['logged_in_username'] = $username;
            $_SESSION['user_type'] = $row['user_type'];

            $vp_sql = "SELECT place_id FROM visit_plan WHERE user_id = ?";
            $vp_result = $conn->execute_query($vp_sql, [$row['user_id']]);

            if ($vp_result && $vp_result->num_rows > 0) {
                $rows = $vp_result->fetch_all(MYSQLI_ASSOC);
                $_SESSION['visit_plan'] = array_map('intval', array_column($rows, 'place_id'));
            } else {
                $_SESSION['visit_plan'] = [];
            }

            if ($row['user_type'] === 'admin') {
                header("Location: ./admin/admin_dashboard.php");
            } else {
                header("Location: my_visit_plan.php");
            }
            exit();
        } else {
            $notification_js = 'triggerNotificationFailed(' . json_encode("Invalid username or password.") . ');';
        }
    } else {
        $notification_js = 'triggerNotificationFailed(' . json_encode("Please enter both username and password.") . ');';
    }
}

$page = "login";
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Login</h1>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="" method="post" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label> 
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-<?= htmlspecialchars($theme_color) ?> w-100" id="submitBtn">Log In</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include __DIR__ . '/includes/footer.php';   
?>

<?php if (!empty($notification_js)): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?= $notification_js ?>
    });
</script>
<?php endif; ?>
