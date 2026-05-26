<?php
/**
 * Urdu Books World - Admin Layout Footer
 */
?>
        </main> <!-- /admin-main -->
    </div> <!-- /admin-layout -->

    <!-- jQuery for admin panel actions -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js" crossorigin="anonymous"></script>
    <script>
        // Set standard JS references
        window.siteUrl = "<?php echo SITE_URL; ?>";
        window.csrfToken = "<?php echo e($_SESSION['csrf_token']); ?>";
    </script>
</body>
</html>
