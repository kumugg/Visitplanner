<!-- This file should include after including the header file on required file.  -->
<?php if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') : ?>
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-12">
                <h2 class="mb-5">Access Denied</h2>
                <p class="text-muted">You do not have permission to access this page.</p>
            </div>
        </div>
    </div>
    <?php include __DIR__ . "/footer.php";?>
    <?php exit(); ?>
<?php endif; ?>