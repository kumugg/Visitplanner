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

                if($row['user_type'] == 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: my_visitplan.php");
                }

                } else {
                $error_msg = "Wrong Password...";
            }
        } else {
            $error_msg = "User is not found...";
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
                    <div>
                        <?php if(!empty($error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show py-2 mb-3 d-flex align-items-center justify-content-between" role="alert">
                                <?= htmlspecialchars($error_msg) ?>
                                <button type="button" class="btn-close m-0" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                    </div>
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