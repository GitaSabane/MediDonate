<?php if (isset($_SESSION['success']) || isset($_SESSION['error']) || isset($_SESSION['info']) || isset($_SESSION['warning'])): ?>
    <div class="container mt-3">
        <?php echo displayAlerts(); ?>
    </div>
<?php endif; ?>