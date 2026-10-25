<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['logged_in_username'])) {
    header("Location: my_visit_plan.php");
    exit();
}

$notification_js = '';
$form_values = [
    'first_name' => '',
    'last_name' => '',
    'username' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
    
    // Trim input to prevent whitespace issues
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $form_values = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'username' => $username,
    ];

    if (!empty($first_name) && !empty($username) && !empty($password) && !empty($confirm_password)) {
        if (mb_strlen($first_name) > 50 || mb_strlen($last_name) > 50) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("First and last names cannot exceed 50 characters.") . ');';
        } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("Username must be between 3 and 50 characters.") . ');';
        } elseif (strlen($password) < 6) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("Password must be at least 6 characters long.") . ');';
        } elseif ($password !== $confirm_password) {
            $notification_js = 'triggerNotificationFailed(' . json_encode("Passwords do not match. Please try again.") . ');';
        } else {
            // Check if username exists
            $sql = "SELECT COUNT(*) AS count FROM users WHERE username = ?";
            $result = $conn->execute_query($sql, [$username]);
            $row = $result ? $result->fetch_assoc() : ['count' => 0];

            if ($row['count'] == 0) {
                // Hash password and insert user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $insert_sql = "INSERT INTO users (first_name, last_name, username, password_hash, user_type) VALUES (?, ?, ?, ?, 'user')";
                try {
                    $insert_result = $conn->execute_query($insert_sql, [$first_name, $last_name, $username, $password_hash]);

                    if ($insert_result) {
                        $notification_js = 'triggerNotificationSuccess(' . json_encode("Registration successful! You can now log in.") . ');';
                        // Clear form values on success
                        $form_values = ['first_name' => '', 'last_name' => '', 'username' => ''];
                    } else {
                        $notification_js = 'triggerNotificationFailed(' . json_encode("Error occurred during registration. Please try again.") . ');';
                    }
                } catch (Exception $e) {
                    error_log("Registration error: " . $e->getMessage());
                    $notification_js = 'triggerNotificationFailed(' . json_encode("Registration failed. Please try again later.") . ');';
                }
            } else {
                $notification_js = 'triggerNotificationFailed(' . json_encode("Username already exists. Please choose a different username.") . ');';
            }
        }
    } else {
        $notification_js = 'triggerNotificationFailed(' . json_encode("Please fill in all required fields.") . ');';
    }
}

$page = "register";
include __DIR__ . '/includes/header.php';
?>

<div class="container-sm">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 text-center">
            <p class="fs-2 lead" style="font-family: 'Roboto', sans-serif">Register</p>
        </div>
    </div>
    <div class="row justify-content-center mt-3">
        <form action="#" method="POST" class="col-md-4 col-sm-6" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <div class="card shadow-sm border-0 p-4">
                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control mb-3" id="first_name" name="first_name" maxlength="50" value="<?= htmlspecialchars($form_values['first_name']) ?>" required>

                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control mb-3" id="last_name" name="last_name" maxlength="50" value="<?= htmlspecialchars($form_values['last_name']) ?>">

                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" class="form-control mb-3" id="username" name="username" maxlength="50" value="<?= htmlspecialchars($form_values['username']) ?>" required>

                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control mb-3" id="password" name="password" minlength="6" required>

                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control mb-3" id="confirm_password" name="confirm_password" minlength="6" required>

                <button type="submit" class="btn btn-<?= htmlspecialchars($theme_color ?? 'secondary') ?> w-100" id="registerBtn">Register</button>
            </div>
        </form>
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

<script>
    document.getElementById('registerForm').addEventListener('submit', function(event) {
        var password = document.getElementById('password').value;
        var confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            event.preventDefault();
            if (typeof triggerNotificationFailed === "function") {
                triggerNotificationFailed('Passwords do not match. Please try again.');
            } else {
                alert('Passwords do not match. Please try again.');
            }
        }
    });
</script>
