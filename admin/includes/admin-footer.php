        </div>
        <!-- Content Container Ends -->
    </div>
    <!-- Admin Main Wrapper Ends -->

    <!-- Global Media Library Modal Component -->
    <?php require_once __DIR__ . '/media-modal.php'; ?>

    <!-- Admin JavaScript File (with auto cache-buster) -->
    <script src="<?= BASE_URL ?>/assets/js/admin.js?v=<?= filemtime(__DIR__ . '/../../assets/js/admin.js') ?>"></script>
</body>
</html>
