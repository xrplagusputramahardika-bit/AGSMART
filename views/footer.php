<?php
/**
 * Layout Footer - Modern Retaillet Design
 * AGSmart POS System
 */
?>

        <!-- Footer Info -->
        <footer class="footer mt-auto py-3 border-top bg-white text-muted small">
            <div class="container-fluid px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span>&copy; <?= date('Y') ?> <b><?= htmlspecialchars($pengaturan['nama_toko']) ?></b> &bull; AGSMART POS System</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="d-none d-md-inline text-secondary">Shortcuts: 
                        <kbd class="bg-light border text-dark px-1">F2</kbd> Cari
                        <kbd class="bg-light border text-dark px-1">F3</kbd> Tap Member
                        <kbd class="bg-light border text-dark px-1">F4</kbd> Bayar
                    </span>
                    <a href="pos.php" class="text-secondary text-decoration-none fw-bold" style="color: #ff7849 !important;">
                        <i class="bi bi-calculator me-1"></i> Buka Kasir
                    </a>
                    <a href="backup_restore.php" class="text-secondary text-decoration-none">
                        <i class="bi bi-shield-check me-1"></i> Backup
                    </a>
                </div>
            </div>
        </footer>

    </div> <!-- Close .app-main -->
</div> <!-- Close .app-wrapper -->

<!-- Toast Container for Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="appToast" class="toast align-items-center text-white bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="toastMessage">
                <i class="bi bi-info-circle-fill text-primary fs-5" id="toastIcon"></i>
                <span id="toastText">Notifikasi</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
    // Fullscreen Toggle
    function toggleFullScreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(() => {});
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }
</script>
</body>
</html>
