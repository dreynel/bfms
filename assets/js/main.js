// assets/js/main.js - Interactive behaviors for BFMS Brgy Lublub

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Toggle for Mobile
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
    const appSidebar = document.getElementById('appSidebar');

    if (sidebarToggleBtn && appSidebar) {
        sidebarToggleBtn.addEventListener('click', function () {
            appSidebar.classList.toggle('show');
        });
    }

    if (sidebarCloseBtn && appSidebar) {
        sidebarCloseBtn.addEventListener('click', function () {
            appSidebar.classList.remove('show');
        });
    }

    // 2. Dynamic Age Calculation from Birthdate
    const birthDateInput = document.getElementById('birth_date');
    const ageInput = document.getElementById('age');

    if (birthDateInput && ageInput) {
        birthDateInput.addEventListener('change', function () {
            if (this.value) {
                const dob = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                ageInput.value = age >= 0 ? age : 0;

                // Auto flag senior citizen if >= 60
                const seniorCheckbox = document.getElementById('is_senior');
                if (seniorCheckbox) {
                    seniorCheckbox.checked = (age >= 60);
                    toggleConditionalField('is_senior', 'senior_fields');
                }

                // Auto flag youth if 15 to 30
                const youthCheckbox = document.getElementById('is_youth');
                if (youthCheckbox) {
                    youthCheckbox.checked = (age >= 15 && age <= 30);
                }
            }
        });
    }

    // 3. Conditional Form Fields Handlers (PWD, Solo Parent, Indigent, Senior, 4Ps)
    function setupConditional(checkboxId, containerId) {
        const checkbox = document.getElementById(checkboxId);
        const container = document.getElementById(containerId);
        if (checkbox && container) {
            const toggle = () => {
                if (checkbox.checked) {
                    container.classList.remove('d-none');
                } else {
                    container.classList.add('d-none');
                }
            };
            checkbox.addEventListener('change', toggle);
            toggle(); // Initial state
        }
    }

    setupConditional('is_pwd', 'pwd_details_container');
    setupConditional('is_solo_parent', 'solo_parent_details_container');
    setupConditional('is_senior', 'senior_details_container');
    setupConditional('is_4ps', 'four_ps_details_container');
    setupConditional('is_voter', 'voter_details_container');

    // 4. Live Table Filter (Instant Search for tables with class .table-filterable)
    const tableSearchInputs = document.querySelectorAll('.table-search-input');
    tableSearchInputs.forEach(input => {
        const targetTableId = input.dataset.targetTable;
        const targetTable = document.getElementById(targetTableId);
        if (targetTable) {
            input.addEventListener('keyup', function () {
                const query = this.value.toLowerCase().trim();
                const rows = targetTable.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }
    });

    // 5. Tooltips initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 6. Global Modalized Attachment Viewer
    const attachmentModalEl = document.getElementById('attachmentPreviewModal');
    let attachmentModalInstance = null;

    function openAttachmentModal(url, title, subtitle) {
        if (!url || url === '#' || url === 'javascript:void(0)') return;
        if (!attachmentModalEl) return;

        if (!attachmentModalInstance && typeof bootstrap !== 'undefined') {
            attachmentModalInstance = new bootstrap.Modal(attachmentModalEl);
        }

        const titleEl = document.getElementById('attachmentModalTitle');
        const subtitleEl = document.getElementById('attachmentModalSubtitle');
        const imgEl = document.getElementById('attachmentModalImg');
        const iframeEl = document.getElementById('attachmentModalIframe');
        const spinner = document.getElementById('attachmentLoadingSpinner');
        const errorEl = document.getElementById('attachmentModalError');
        const downloadLink = document.getElementById('attachmentDownloadLink');
        const openNewTabLink = document.getElementById('attachmentOpenNewTabLink');
        const iconEl = document.getElementById('attachmentModalIcon');

        if (titleEl) titleEl.textContent = title || 'Document Attachment Preview';
        if (subtitleEl) subtitleEl.textContent = subtitle || 'Barangay Lublub Socio-Economic Records';
        if (downloadLink) downloadLink.href = url;
        if (openNewTabLink) openNewTabLink.href = url;

        // Reset elements
        if (spinner) spinner.classList.remove('d-none');
        if (imgEl) {
            imgEl.classList.add('d-none');
            imgEl.src = '';
            imgEl.style.transform = 'scale(1)';
            imgEl.dataset.zoomed = '0';
        }
        if (iframeEl) {
            iframeEl.classList.add('d-none');
            iframeEl.src = '';
        }
        if (errorEl) errorEl.classList.add('d-none');

        // Extract extension
        const cleanPath = url.split('?')[0].split('#')[0];
        const ext = cleanPath.split('.').pop().toLowerCase();
        const isPdf = (ext === 'pdf');

        if (iconEl) {
            iconEl.className = isPdf ? 'bi bi-file-earmark-pdf fs-4 text-danger' : 'bi bi-file-earmark-image fs-4 text-warning';
        }

        if (isPdf) {
            if (iframeEl) {
                iframeEl.onload = function() {
                    if (spinner) spinner.classList.add('d-none');
                    iframeEl.classList.remove('d-none');
                };
                iframeEl.onerror = function() {
                    if (spinner) spinner.classList.add('d-none');
                    if (errorEl) errorEl.classList.remove('d-none');
                };
                iframeEl.src = url;
            }
        } else {
            if (imgEl) {
                const tempImg = new Image();
                tempImg.onload = function() {
                    imgEl.src = url;
                    if (spinner) spinner.classList.add('d-none');
                    imgEl.classList.remove('d-none');
                };
                tempImg.onerror = function() {
                    if (spinner) spinner.classList.add('d-none');
                    if (errorEl) errorEl.classList.remove('d-none');
                };
                tempImg.src = url;
            }
        }

        if (attachmentModalInstance) {
            attachmentModalInstance.show();
        }
    }

    // Intercept clicks on any attachment trigger or link pointing to uploads
    document.addEventListener('click', function (e) {
        // Direct trigger button or class
        let trigger = e.target.closest('.preview-attachment-btn, [data-preview-url], a[data-preview="true"]');
        
        // Also automatically intercept any <a> whose href contains '/uploads/' or 'uploads/'
        if (!trigger) {
            const anchor = e.target.closest('a');
            if (anchor && anchor.getAttribute('href')) {
                const href = anchor.getAttribute('href');
                if (href.includes('uploads/') && !anchor.hasAttribute('download') && !anchor.classList.contains('no-modal-preview')) {
                    trigger = anchor;
                }
            }
        }

        if (trigger) {
            e.preventDefault();
            const url = trigger.getAttribute('data-preview-url') || trigger.getAttribute('href');
            const title = trigger.getAttribute('data-preview-title') || trigger.getAttribute('title') || 'Document Attachment';
            const subtitle = trigger.getAttribute('data-preview-subtitle') || 'Barangay Lublub Socio-Economic Records';
            openAttachmentModal(url, title, subtitle);
        }
    });

    // Zoom Toggle Button & Image Click to Zoom
    const zoomBtn = document.getElementById('attachmentZoomBtn');
    const modalImg = document.getElementById('attachmentModalImg');

    function toggleZoom() {
        if (modalImg && !modalImg.classList.contains('d-none')) {
            const isZoomed = modalImg.dataset.zoomed === '1';
            modalImg.style.transform = isZoomed ? 'scale(1)' : 'scale(1.6)';
            modalImg.style.cursor = isZoomed ? 'zoom-in' : 'zoom-out';
            modalImg.dataset.zoomed = isZoomed ? '0' : '1';
            if (zoomBtn) {
                zoomBtn.innerHTML = isZoomed ? '<i class="bi bi-zoom-in me-1"></i>Zoom' : '<i class="bi bi-zoom-out me-1"></i>Reset';
            }
        }
    }

    if (zoomBtn) {
        zoomBtn.addEventListener('click', toggleZoom);
    }
    if (modalImg) {
        modalImg.addEventListener('click', toggleZoom);
    }

    // Reset iframe and image when modal closes to prevent background loading/media
    if (attachmentModalEl) {
        attachmentModalEl.addEventListener('hidden.bs.modal', function () {
            const iframe = document.getElementById('attachmentModalIframe');
            const img = document.getElementById('attachmentModalImg');
            if (iframe) iframe.src = '';
            if (img) {
                img.src = '';
                img.style.transform = 'scale(1)';
                img.dataset.zoomed = '0';
            }
            if (zoomBtn) {
                zoomBtn.innerHTML = '<i class="bi bi-zoom-in me-1"></i>Zoom';
            }
        });
    }
});

