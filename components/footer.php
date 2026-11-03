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

<!-- Global Attachment & Document Preview Modal -->
<div class="modal fade" id="attachmentPreviewModal" tabindex="-1" aria-labelledby="attachmentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4 flex-shrink-0">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <i class="bi bi-file-earmark-image fs-4 text-warning" id="attachmentModalIcon"></i>
                    <div class="text-start">
                        <h6 class="modal-title fw-bold mb-0 text-truncate text-white" id="attachmentModalTitle">Document Attachment Preview</h6>
                        <small class="text-white-50" id="attachmentModalSubtitle">Barangay Lublub Socio-Economic Records</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-2 py-1" id="attachmentZoomBtn" title="Toggle Zoom In/Out" style="font-size: 0.75rem;">
                        <i class="bi bi-zoom-in me-1"></i>Zoom
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3 bg-light text-center position-relative d-flex align-items-center justify-content-center" style="min-height: 380px; max-height: calc(82vh - 120px); overflow: auto; -webkit-overflow-scrolling: touch; overscroll-behavior: contain;" id="attachmentModalBody">
                <div id="attachmentLoadingSpinner" class="spinner-border text-success my-5" role="status">
                    <span class="visually-hidden">Loading document...</span>
                </div>
                <img id="attachmentModalImg" src="" alt="Attachment Preview" class="img-fluid rounded-3 shadow-sm d-none" style="max-height: 72vh; max-width: 100%; object-fit: contain; transition: transform 0.2s ease; cursor: zoom-in;">
                <iframe id="attachmentModalIframe" src="" class="w-100 rounded-3 shadow-sm d-none" style="height: 72vh; border: none;"></iframe>
                <div id="attachmentModalError" class="d-none py-5 text-muted">
                    <i class="bi bi-exclamation-triangle display-4 text-warning mb-2 d-block"></i>
                    <p class="mb-0 fw-semibold">Unable to load the file preview.</p>
                    <small class="text-muted">The file might be missing or an unsupported format.</small>
                </div>
            </div>
            <div class="modal-footer bg-white py-2 px-4 justify-content-between flex-wrap gap-2 flex-shrink-0">
                <div class="text-muted small" id="attachmentModalMeta">
                    <i class="bi bi-shield-check text-success me-1"></i>Verified Barangay Profile Attachment
                </div>
                <div class="d-flex gap-2">
                    <a href="#" id="attachmentDownloadLink" class="btn btn-sm btn-outline-primary" target="_blank" download>
                        <i class="bi bi-download me-1"></i> Download
                    </a>
                    <a href="#" id="attachmentOpenNewTabLink" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Full Tab
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main App JS -->
<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>

