<?php
require_once __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admins_only.php';

$page = "manage_users";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';

    $sql = "SELECT user_id FROM users WHERE username = ?";
    $result = $conn->execute_query($sql, [$username]);
    $user_exists = !empty($result->fetch_assoc());

    if (!empty($username) && !empty($password) && !empty($user_type)) {
        
        if ($user_exists) {
            echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("User already exists."); }); </script>';
        } 
        else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password_hash, user_type) VALUES (?, ?, ?)";
            try {
            $result = $conn->execute_query($sql, [$username, $hashed_password, $user_type]);

            if ($result) {
                echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationSuccess("User added successfully!"); }); </script>';
            } 
            else {
                echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error adding user: ' . htmlspecialchars($conn->error) . '"); }); </script>';
            }

            } catch (Exception $e) {
                echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Error adding user: ' . htmlspecialchars($e->getMessage()) . '"); }); </script>';
            }
        }
    } else {
        echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("All fields are required."); }); </script>';
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center">
            <h1 class="h3 fw-bold">Add Users</h1>
        </div>
        
    <div class="row justify-content-center">
        <div class="col-md-8 col-sm-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="#" method="post" id="addUserForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="user_type" class="form-label">User Type</label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="">Select User Type</option>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100">Add User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../includes/footer.php';
?>