<?php
$page = "login";
include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
$error_msg = '';

// if user is already logged in, redirect to visit_plan.php
if(isset($_SESSION['logged_in_username'])) {
    header("Location: visit_plan.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $user_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $user_token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $sql = "SELECT password_hash, user_type FROM users WHERE username = ? LIMIT 1";
        $result = $conn->execute_query($sql, [$username]);

        if (!empty($row = $result->fetch_assoc())) {
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['logged_in_username'] = $username;
                $_SESSION['user_type'] = $row['user_type'];

                $sql = "SELECT vp.place_id FROM visit_plan vp INNER JOIN users u ON vp.user_id = u.user_id WHERE u.username = ?";
                $result = $conn->execute_query($sql, [$username]);

                if ($result && $result->num_rows > 0) {
                    $rows = $result->fetch_all(MYSQLI_ASSOC);
                    $_SESSION['visit_plan'] = array_column($rows, 'place_id');
                } else {
                    $_SESSION['visit_plan'] = [];
                }

                if ($row['user_type'] == 'admin') {
                    header("Location: ./admin/admin_dashboard.php");
                } else {
                    header("Location: my_visit_plan.php");
                }

            } else {
                echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Invalid username or password."); }); </script>';
            }
        
        } else {
            echo '<script> document.addEventListener("DOMContentLoaded", function() { triggerNotificationFailed("Invalid username or password."); }); </script>';
        }
    }
}
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
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label> 
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100" id="submitBtn">Log In</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include __DIR__ . '/includes/footer.php';   
?>