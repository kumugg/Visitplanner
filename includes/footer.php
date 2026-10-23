        <footer class='text-center text-muted py-5'>
                <hr class="border border-secondary opacity-60">
                <small> Visit Planner and Information System - Pugoda Area, Gampaha District - Made by E2320207 - GAMLATH D.G.M.K.K. </small>
        </footer>
        <script src="/visitplanner/js/bootstrap.bundle.min.js"></script>
        <script src="/visitplanner/js/leaflet.js"></script>

        <script>
                function triggerNotificationSuccess(message) {
                        const toast = document.getElementById('toast');
                        toast.innerHTML = message;
                        toast.classList.add('show');
                        toast.classList.add('success');
                        setTimeout(() => {
                        toast.classList.remove('show');
                        toast.classList.remove('success');
                        }, 3000);
                }

                function triggerNotificationFailed(message) {
                        const toast = document.getElementById('toast');
                        toast.innerHTML = message;
                        toast.classList.add('show');
                        toast.classList.add('error');
                        setTimeout(() => {
                        toast.classList.remove('show');
                        toast.classList.remove('error');
                        }, 3000);
                }
        </script>
    </body>
    <?php $page_name = $page ?? ''; ?>
</html>