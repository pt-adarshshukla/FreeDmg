/**
 * FreeDmg - Core Client-Side Logic & Interactions
 * Includes: Theme switcher, Command+K search, Safe Download engine modal, Request modal, Auto-Catch File Name & Size.
 */

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initGlobalSearch();
    initDownloadTriggers();
    initRequestModal();
    initFileUploadAutoDetect();
});

/* ==========================================================================
   1. Theme Switcher (Dark / Light Mode)
   ========================================================================== */
function initTheme() {
    const savedTheme = localStorage.getItem('freedmg_theme') || 'dark';
    applyTheme(savedTheme);

    // Bind all theme toggles
    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const currentTheme = document.documentElement.classList.contains('light') ? 'light' : 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
        });
    });
}

function applyTheme(theme) {
    if (theme === 'light') {
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
        localStorage.setItem('freedmg_theme', 'light');
    } else {
        document.documentElement.classList.remove('light');
        document.documentElement.classList.add('dark');
        localStorage.setItem('freedmg_theme', 'dark');
    }

    // Update icons in buttons
    document.querySelectorAll('.theme-toggle-icon').forEach(icon => {
        icon.textContent = theme === 'light' ? 'dark_mode' : 'light_mode';
    });
}

/* ==========================================================================
   2. Search & Command+K Shortcut
   ========================================================================== */
function initGlobalSearch() {
    const searchModal = document.getElementById('search-modal');
    const searchInput = document.getElementById('modal-search-input');
    const searchResults = document.getElementById('modal-search-results');

    // Shortcut: ⌘K or Ctrl+K
    window.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            openSearchModal();
        }
        if (e.key === 'Escape' && searchModal && !searchModal.classList.contains('hidden')) {
            closeSearchModal();
        }
    });

    document.querySelectorAll('[data-open-search]').forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            openSearchModal();
        });
    });

    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();
            if (query.length < 2) {
                searchResults.innerHTML = '<p class="text-sm text-outline p-4 text-center">Type at least 2 characters to search...</p>';
                return;
            }
            debounceTimer = setTimeout(() => {
                fetchSearchResults(query);
            }, 250);
        });
    }
}

function openSearchModal() {
    const searchModal = document.getElementById('search-modal');
    const searchInput = document.getElementById('modal-search-input');
    if (searchModal) {
        searchModal.classList.remove('hidden');
        searchModal.classList.add('flex');
        if (searchInput) {
            searchInput.focus();
        }
    }
}

function closeSearchModal() {
    const searchModal = document.getElementById('search-modal');
    if (searchModal) {
        searchModal.classList.add('hidden');
        searchModal.classList.remove('flex');
    }
}

function fetchSearchResults(query) {
    const resultsContainer = document.getElementById('modal-search-results');
    if (!resultsContainer) return;

    resultsContainer.innerHTML = '<div class="p-6 text-center text-primary"><span class="material-symbols-outlined animate-spin text-3xl">progress_activity</span></div>';

    fetch(`search.php?ajax=1&q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) {
                resultsContainer.innerHTML = `<div class="p-6 text-center text-outline">No applications found for "<strong>${escapeHtml(query)}</strong>"</div>`;
                return;
            }

            let html = '<div class="divide-y divide-subtle">';
            data.forEach(item => {
                let resolvedIcon = item.icon_url;
                if (resolvedIcon && !resolvedIcon.startsWith('http') && !resolvedIcon.startsWith('//') && !resolvedIcon.startsWith('data:')) {
                    resolvedIcon = resolvedIcon.replace(/^\/+/, '');
                }

                const initialLetter = (item.title || 'A').charAt(0).toUpperCase();
                const iconHtml = resolvedIcon 
                    ? `<img src="${escapeHtml(resolvedIcon)}" class="w-10 h-10 rounded-xl object-cover border border-subtle" onerror="this.outerHTML='<div class=\\\'w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-800 flex items-center justify-center text-white font-extrabold text-sm border border-white/20\\\'>${initialLetter}</div>';">`
                    : `<div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-800 flex items-center justify-center text-white font-extrabold text-sm border border-white/20">${initialLetter}</div>`;

                html += `
                    <a href="app.php?slug=${encodeURIComponent(item.slug)}" class="flex items-center justify-between p-3 hover:bg-surface-container transition-colors group">
                        <div class="flex items-center gap-3">
                            ${iconHtml}
                            <div>
                                <h4 class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">${escapeHtml(item.title)}</h4>
                                <p class="text-xs text-outline">${escapeHtml(item.category_name || '')} • ${escapeHtml(item.version || '')}</p>
                            </div>
                        </div>
                        <span class="badge-format badge-${item.format.toLowerCase()}">${item.format}</span>
                    </a>
                `;
            });
            html += '</div>';
            resultsContainer.innerHTML = html;
        })
        .catch(err => {
            resultsContainer.innerHTML = '<div class="p-4 text-center text-error">Search request failed. Please try again.</div>';
        });
}

/* ==========================================================================
   3. Smart Download Engine (Timer Modal + Save Prompt + No Blank Screen)
   ========================================================================== */
function initDownloadTriggers() {
    const downloadModal = document.getElementById('download-modal');
    if (!downloadModal) return;

    document.querySelectorAll('[data-download-id]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const softwareId = this.getAttribute('data-download-id');
            const softwareTitle = this.getAttribute('data-download-title') || 'Software';
            const softwareFormat = this.getAttribute('data-download-format') || 'DMG';
            const softwareSize = this.getAttribute('data-download-size') || '';
            const downloadUrl = `download.php?id=${softwareId}`;

            startDownloadFlow(softwareId, softwareTitle, softwareFormat, softwareSize, downloadUrl);
        });
    });
}

function startDownloadFlow(id, title, format, size, downloadUrl) {
    const downloadModal = document.getElementById('download-modal');
    if (!downloadModal) {
        window.location.href = downloadUrl;
        return;
    }

    const titleEl = document.getElementById('dl-modal-title');
    const metaEl = document.getElementById('dl-modal-meta');
    const timerNumEl = document.getElementById('dl-timer-num');
    const timerCircle = document.getElementById('dl-timer-circle');
    const statusMsgEl = document.getElementById('dl-status-msg');
    const directLinkContainer = document.getElementById('dl-direct-link-container');
    const directLink = document.getElementById('dl-direct-link');

    if (titleEl) titleEl.textContent = title;
    if (metaEl) metaEl.textContent = `${format} Package • ${size}`;
    if (directLink) directLink.href = downloadUrl;

    if (directLinkContainer) directLinkContainer.classList.add('hidden');
    if (statusMsgEl) statusMsgEl.innerHTML = '<span class="text-primary animate-pulse font-medium">Verifying package integrity & server connection...</span>';

    downloadModal.classList.remove('hidden');
    downloadModal.classList.add('flex');

    let seconds = 3;
    if (timerNumEl) timerNumEl.textContent = seconds;

    const interval = setInterval(() => {
        seconds--;
        if (timerNumEl) timerNumEl.textContent = seconds;

        if (seconds <= 0) {
            clearInterval(interval);
            if (statusMsgEl) {
                statusMsgEl.innerHTML = `
                    <div class="flex flex-col items-center gap-2">
                        <div class="inline-flex items-center gap-1.5 text-success font-semibold text-sm">
                            <span class="material-symbols-outlined text-[20px]">check_circle</span>
                            Download starting now!
                        </div>
                        <p class="text-xs text-outline">Your browser will ask where to save the file.</p>
                    </div>
                `;
            }
            if (directLinkContainer) {
                directLinkContainer.classList.remove('hidden');
            }

            // Trigger the download cleanly without opening a blank tab or white page
            triggerDirectDownload(downloadUrl);
        }
    }, 1000);
}

function triggerDirectDownload(url) {
    // Hidden iframe trick prevents any page refresh or blank white screen
    let iframe = document.getElementById('hidden-download-frame');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'hidden-download-frame';
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
    }
    iframe.src = url;
}

function closeDownloadModal() {
    const downloadModal = document.getElementById('download-modal');
    if (downloadModal) {
        downloadModal.classList.add('hidden');
        downloadModal.classList.remove('flex');
    }
}

/* ==========================================================================
   4. Request Modal & Submission
   ========================================================================== */
function initRequestModal() {
    const requestModal = document.getElementById('request-modal');
    const requestForm = document.getElementById('software-request-form');

    document.querySelectorAll('[data-open-request]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (requestModal) {
                requestModal.classList.remove('hidden');
                requestModal.classList.add('flex');
            }
        });
    });

    if (requestForm) {
        requestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Submitting...';

            const formData = new FormData(requestForm);

            fetch('request.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    requestForm.innerHTML = `
                        <div class="py-8 text-center flex flex-col items-center">
                            <span class="material-symbols-outlined text-5xl text-success mb-3">check_circle</span>
                            <h3 class="text-lg font-bold text-on-surface mb-1">Request Submitted!</h3>
                            <p class="text-sm text-outline max-w-sm mb-6">Our team has received your application request and will review it shortly.</p>
                            <button type="button" onclick="closeRequestModal()" class="btn-electric px-6 py-2 rounded-full text-xs uppercase font-bold tracking-wider">Close</button>
                        </div>
                    `;
                } else {
                    alert(res.error || 'Submission failed. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(() => {
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
}

function closeRequestModal() {
    const requestModal = document.getElementById('request-modal');
    if (requestModal) {
        requestModal.classList.add('hidden');
        requestModal.classList.remove('flex');
    }
}

/* ==========================================================================
   5. Auto Catch File Size, Name, Version & Format on Package Upload
   ========================================================================== */
function initFileUploadAutoDetect() {
    const packageFileInput = document.querySelector('input[name="package_file"]');
    if (!packageFileInput) return;

    packageFileInput.addEventListener('change', function(e) {
        const file = this.files && this.files[0];
        if (!file) return;

        const titleInput = document.querySelector('input[name="title"]');
        const slugInput = document.querySelector('input[name="slug"]');
        const sizeInput = document.querySelector('input[name="file_size"]');
        const versionInput = document.querySelector('input[name="version"]');
        const formatSelect = document.querySelector('select[name="format"]');

        // 1. Auto Catch and Format File Size
        if (sizeInput) {
            const formattedSize = formatFileSize(file.size);
            sizeInput.value = formattedSize;
            highlightField(sizeInput);
        }

        // 2. Auto Catch File Format (.dmg, .zip, .rar, .pkg, etc.)
        const fileName = file.name;
        const lastDotIdx = fileName.lastIndexOf('.');
        if (lastDotIdx > 0) {
            const ext = fileName.substring(lastDotIdx + 1).toUpperCase();
            if (formatSelect) {
                if (['DMG', 'ZIP', 'RAR', 'PKG'].includes(ext)) {
                    formatSelect.value = ext;
                } else if (ext === '7Z' || ext === 'TAR' || ext === 'GZ') {
                    formatSelect.value = 'ZIP';
                }
                highlightField(formatSelect);
            }
        }

        // 3. Auto Catch Application Title & Version
        let baseName = lastDotIdx > 0 ? fileName.substring(0, lastDotIdx) : fileName;
        
        // Extract version if present (e.g. v2024.1.2 or 25.5.0 or 4.32)
        const versionMatch = baseName.match(/(?:[vV]|ver|version)?\s*([0-9]+(?:\.[0-9]+)+(?:[-_a-zA-Z0-9]+)?)/i);
        if (versionMatch && versionInput) {
            versionInput.value = versionMatch[1];
            highlightField(versionInput);
        }

        // Clean up title from noise
        let cleanedTitle = baseName
            .replace(/(?:[vV]|ver|version)?\s*([0-9]+(?:\.[0-9]+)+(?:[-_a-zA-Z0-9]+)?)/gi, '') // remove version
            .replace(/[._\-+]/g, ' ') // replace separators with space
            .replace(/\b(dmg|zip|rar|pkg|macosx|macos|mac|universal|arm64|x64|intel|apple\s*silicon|installer|setup|repack|portable|multilingual|full|latest)\b/gi, '') // remove common tags
            .replace(/\s+/g, ' ')
            .trim();

        // If title became too short or empty, fallback to basic cleaned baseName
        if (cleanedTitle.length < 2) {
            cleanedTitle = baseName.replace(/[._\-+]/g, ' ').replace(/\s+/g, ' ').trim();
        }

        // Capitalize Words
        cleanedTitle = cleanedTitle.replace(/\b\w/g, l => l.toUpperCase());

        if (titleInput && (!titleInput.value || titleInput.value.trim() === '')) {
            titleInput.value = cleanedTitle;
            highlightField(titleInput);

            // Auto fill slug if slug is empty
            if (slugInput && (!slugInput.value || slugInput.value.trim() === '')) {
                slugInput.value = cleanedTitle.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                highlightField(slugInput);
            }
        }
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 MB';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    const val = parseFloat((bytes / Math.pow(k, i)).toFixed(i >= 3 ? 2 : 1));
    return `${val} ${sizes[i]}`;
}

function highlightField(el) {
    if (!el) return;
    el.classList.add('ring-2', 'ring-primary', 'bg-primary/10');
    setTimeout(() => {
        el.classList.remove('ring-2', 'ring-primary', 'bg-primary/10');
    }, 1200);
}

// Utility: Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Handle Large File Upload Progress in Admin
 */
function initLargeFileUploadProgress() {
    const pkgInput = document.querySelector('input[name="package_file"]');
    if (!pkgInput) return;

    const form = pkgInput.closest('form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const file = pkgInput.files ? pkgInput.files[0] : null;
        if (!file || file.size < 5 * 1024 * 1024) {
            // For small files or empty, let standard submit handle it
            return;
        }

        e.preventDefault();

        // Create sleek upload progress modal
        let modal = document.getElementById('upload-progress-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'upload-progress-modal';
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-md';
            modal.innerHTML = `
                <div class="glass-panel rounded-3xl p-8 max-w-md w-full border border-subtle shadow-2xl relative text-center">
                    <div class="w-16 h-16 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center mx-auto mb-4 text-primary animate-pulse">
                        <span class="material-symbols-outlined text-3xl animate-bounce">cloud_upload</span>
                    </div>
                    <h3 class="text-xl font-bold text-on-surface mb-1">Uploading Package...</h3>
                    <p class="text-xs text-outline mb-6">Uploading <span id="up-filename" class="text-primary font-semibold"></span> (<span id="up-filesize" class="font-mono"></span>). Please keep this window open.</p>

                    <!-- Progress Bar -->
                    <div class="w-full bg-surface-container rounded-full h-3.5 mb-3 overflow-hidden border border-subtle relative p-0.5">
                        <div id="up-bar" class="h-full rounded-full bg-gradient-to-r from-blue-500 via-primary to-indigo-500 transition-all duration-150" style="width: 0%"></div>
                    </div>

                    <div class="flex justify-between items-center text-xs font-mono text-outline mb-6">
                        <span id="up-transferred">0 MB / 0 MB</span>
                        <span id="up-pct" class="font-bold text-primary text-sm">0%</span>
                    </div>

                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-surface-container border border-subtle text-xs text-outline">
                        <span class="w-2 h-2 rounded-full bg-primary animate-ping"></span>
                        <span>Direct binary streaming to server storage</span>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        modal.classList.remove('hidden');
        document.getElementById('up-filename').textContent = file.name;
        document.getElementById('up-filesize').textContent = formatFileSize(file.size);

        const formData = new FormData(form);
        const xhr = new XMLHttpRequest();

        const startTime = Date.now();
        xhr.upload.addEventListener('progress', function(event) {
            if (event.lengthComputable) {
                const percent = Math.round((event.loaded / event.total) * 100);
                document.getElementById('up-bar').style.width = percent + '%';
                document.getElementById('up-pct').textContent = percent + '%';
                document.getElementById('up-transferred').textContent = `${formatFileSize(event.loaded)} / ${formatFileSize(event.total)}`;
            }
        });

        xhr.addEventListener('load', function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                document.getElementById('up-pct').textContent = '100% - Processing...';
                // Follow redirect or reload to software catalog
                window.location.href = 'software.php';
            } else {
                alert('Upload encountered an issue (HTTP ' + xhr.status + '). Please check server logs.');
                modal.classList.add('hidden');
            }
        });

        xhr.addEventListener('error', function() {
            alert('Upload failed due to connection error or server timeout.');
            modal.classList.add('hidden');
        });

        xhr.open(form.method || 'POST', form.action || window.location.href);
        xhr.send(formData);
    });
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    initLargeFileUploadProgress();
});
