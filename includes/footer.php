<?php
$is_login_page = (basename($_SERVER['PHP_SELF'], '.php') === 'login');
if (!$is_login_page && is_logged_in()):
?>
        </div>
    </div>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
