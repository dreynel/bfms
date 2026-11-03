<?php
// includes/footer.php - Global footer
if (defined('BFMS_FOOTER_LOADED')) {
    return;
}
define('BFMS_FOOTER_LOADED', true);
?>
            </div>
        </main>

        <footer class="mt-auto py-3 px-4 bg-white border-top text-center text-md-start d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted footer-content">
            <div>
                &copy; <?= date('Y') ?> <strong>Barangay Lublub (P.D. Monfort North)</strong>, Municipality of Dumangas, Province of Iloilo.
            </div>
            <div class="mt-2 mt-md-0">
                <span class="badge bg-light text-secondary border">BFMS v2.0 - Social Welfare & Profiling</span>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main App JS -->
<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>

