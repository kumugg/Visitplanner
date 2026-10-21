<?php
include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';
$page = "login";
$error_msg = '';

// if user is already logged in, redirect to visit_plan.php
if(isset($_SESSION['logged_in_username'])) {
    header("Location: visit_plan.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $sql = "SELECT password_hash, user_type FROM users WHERE username = ? LIMIT 1";
        $result = $conn->execute_query($sql, [$username]);

        if (!empty($row = $result->fetch_assoc())) {
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['logged_in_username'] = $username;
                $_SESSION['user_type'] = $row['user_type'];

                if ($row['user_type'] == 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: my_visitplan.php");
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
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label> <!-- Fixed typo: passoword -> password -->
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-<?= $theme_color ?> w-100" id="submitBtn">Log In</button> <!-- Added w-100 for a full-width button -->
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include __DIR__ . '/includes/footer.php';   
?>