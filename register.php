<?php
require_once __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim input to prevent whitespace issues
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($username) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            // Check if username exists
            $sql = "SELECT COUNT(*) AS count FROM users WHERE username = ?";
            $result = $conn->execute_query($sql, [$username]);
            $row = $result->fetch_assoc();

            if ($row['count'] == 0) {
                // Hash password and insert user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $insert_sql = "INSERT INTO users (username, password_hash, user_type) VALUES (?, ?, 'user')";
                $insert_result = $conn->execute_query($insert_sql, [$username, $password_hash]);

                if ($insert_result) {
                    echo '<script>document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("Registration successful! You can now log in."); });</script>';
                } else {
                    echo '<script>document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error occurred during registration. Please try again."); });</script>';
                }
            } else {
                echo '<script>document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Username already exists. Please choose a different username."); });</script>';
            }
        } else {
            echo '<script>document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Passwords do not match. Please try again."); });</script>';
        }
    } else {
        echo '<script>document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Please fill in all fields."); });</script>';
    }
}
?>

<div class="container-sm">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 text-center">
            <p class="fs-2 lead" style="font-family: 'Roboto', sans-serif">Register</p>
        </div>
    </div>
    <div class="row justify-content-center mt-3">
        <form action="#" method="POST" class="col-md-4 col-sm-6">
            <div class="card shadow-sm border-0 p-4">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control mb-3" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>

                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control mb-3" id="password" name="password" required>

                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control mb-3" id="confirm_password" name="confirm_password" required>

                <button type="submit" class="btn btn-<?= htmlspecialchars($theme_color ?? 'primary') ?> w-100" id="registerBtn">Register</button>
            </div>
        </form>
    </div>
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>

<script>
    document.getElementById('registerBtn').addEventListener('click', function(event) {
        var password = document.getElementById('password').value;
        var confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            event.preventDefault(); // Prevent submission
            if (typeof triggerNotificationFailed === "function") {
                alert('Passwords do not match. Please try again.');
            } else {
                alert('Passwords do not match. Please try again.');
            }
        }
    });
</script>