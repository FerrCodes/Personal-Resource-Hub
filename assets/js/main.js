/* =========================================================
   PERSONAL RESOURCE HUB - MAIN JAVASCRIPT
   File: assets/js/main.js
   Catatan:
   - Semua fitur kecil UI disimpan di sini.
   - Script dibuat aman jika elemen tertentu tidak ada di sebuah halaman.
   ========================================================= */

/* =========================================================
   01. HELPER SELECTOR
   ========================================================= */
const $ = (selector) => document.querySelector(selector);

/* =========================================================
   02. JENIS RESOURCE CUSTOM
   Dipakai di tambah.php dan edit.php.
   ========================================================= */
function toggleJenisLainnya() {
    const selectJenis = $('#jenis_select');
    const inputLainnya = $('#jenis_lainnya');
    const container = $('#jenis_lainnya_container');

    if (!selectJenis || !inputLainnya || !container) return;

    const isCustom = selectJenis.value === 'lainnya';

    container.classList.toggle('is-visible', isCustom);
    inputLainnya.required = isCustom;

    if (!isCustom) {
        inputLainnya.value = '';
    }
}

function prepareJenis() {
    const selectJenis = $('#jenis_select');
    const inputLainnya = $('#jenis_lainnya');
    const hiddenJenis = $('#jenis_hidden');

    if (!selectJenis || !hiddenJenis) return true;

    hiddenJenis.value = selectJenis.value === 'lainnya'
        ? (inputLainnya?.value || '').trim()
        : selectJenis.value;

    return true;
}

/* =========================================================
   03. SIDEBAR COLLAPSE
   Menyimpan status sidebar ke localStorage.
   ========================================================= */
function toggleSidebar() {
    const app = $('.app-container');
    if (!app) return;

    app.classList.toggle('sidebar-collapsed');

    const isCollapsed = app.classList.contains('sidebar-collapsed');
    localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'open');
}

function initSidebarState() {
    const app = $('.app-container');
    if (!app) return;

    const savedState = localStorage.getItem('sidebarState');

    if (savedState === 'collapsed') {
        app.classList.add('sidebar-collapsed');
    }
}

/* =========================================================
   04. COPY LINK RESOURCE
   Menggunakan Clipboard API, lalu fallback untuk localhost/HTTP.
   ========================================================= */
function copyToClipboard(text, buttonElement) {
    const originalText = buttonElement?.innerHTML || '';

    function showSuccess() {
        if (!buttonElement) return;

        buttonElement.innerHTML = '✅ Link Disalin!';
        buttonElement.style.background = '#2ecc71';

        setTimeout(() => {
            buttonElement.innerHTML = originalText;
            buttonElement.style.background = '';
        }, 2000);
    }

    function fallbackCopy(textToCopy) {
        const textArea = document.createElement('textarea');
        textArea.value = textToCopy;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';

        document.body.appendChild(textArea);
        textArea.select();

        try {
            document.execCommand('copy');
            showSuccess();
        } catch (error) {
            alert('Gagal menyalin link.');
        } finally {
            document.body.removeChild(textArea);
        }
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(showSuccess).catch(() => fallbackCopy(text));
    } else {
        fallbackCopy(text);
    }
}

/* =========================================================
   05. DARK MODE
   Aman dipakai di semua halaman, meskipun tombol dark mode tidak ada.
   ========================================================= */
function updateDarkModeButton(isDark) {
    const icon = $('#darkModeIcon');
    const label = $('#darkModeLabel');

    if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    if (label) label.textContent = isDark ? '☀️ Switch Light' : '🌙 Switch Dark';
}

function initDarkMode() {
    const toggleBtn = $('#darkModeToggle');
    const isDarkSaved = localStorage.getItem('darkMode') === 'enabled';

    document.body.classList.toggle('dark-mode', isDarkSaved);
    updateDarkModeButton(isDarkSaved);

    if (!toggleBtn) return;

    toggleBtn.addEventListener('click', () => {
        const isDark = !document.body.classList.contains('dark-mode');

        document.body.classList.toggle('dark-mode', isDark);
        localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        updateDarkModeButton(isDark);
    });
}

function toggleDarkMode() {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
    updateDarkModeButton(isDark);
}

/* =========================================================
   07. USER DROPDOWN TOGGLE
   Seperti menu dropdown di Claude
   ========================================================= */
function toggleUserDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('userDropdown');
    if (!dropdown) return;

    // Tutup dropdown lain jika ada
    document.querySelectorAll('.user-dropdown.open').forEach(el => {
        if (el !== dropdown) el.classList.remove('open');
    });

    dropdown.classList.toggle('open');
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    if (!dropdown) return;

    // Jika klik di luar dropdown, tutup
    if (!dropdown.contains(event.target)) {
        dropdown.classList.remove('open');
    }
});

// Tutup dropdown dengan tombol ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) dropdown.classList.remove('open');
    }
});

function initPasswordToggle() {
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.closest('.password-wrapper').querySelector('input');
            const isPassword = input.type === 'password';
            const icon = this.querySelector('i');
            
            input.type = isPassword ? 'text' : 'password';
            
            if (icon) {
                icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
            }
        });
    });
}

/* =========================================================
   10. TOOLTIP INFO TAG - Toggle di Mobile
   ========================================================= */
function initTooltipToggle() {
    const infoIcons = document.querySelectorAll('.tag-info-icon');
    
    infoIcons.forEach(icon => {
        // Toggle class 'active' saat diklik (mobile)
        icon.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });

        // Tutup tooltip saat klik di luar
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.tag-info-icon')) {
                infoIcons.forEach(el => el.classList.remove('active'));
            }
        });

        // Tutup tooltip dengan tombol ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                infoIcons.forEach(el => el.classList.remove('active'));
            }
        });
    });
}

// Panggil di DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // ... kode yang sudah ada ...
    initTooltipToggle(); // <-- tambahkan ini
});

/* =========================================================
   11. MODAL KONFIRMASI GLOBAL
   ========================================================= */

// Pastikan csrfToken tersedia di global
function confirmDeleteResource(id, judul) {
    showConfirmModal('⚠️ Hapus Resource', `Yakin hapus resource "${judul}"? Resource ini akan dihapus permanen.`, 'Ya, Hapus!')
        .then(confirmed => {
            if (confirmed) {
                window.location.href = `hapus.php?id=${id}&token=${csrfToken}`;
            }
        })
        .catch(() => {});
}

function confirmDeleteTagFromResource(resourceId, tagId, tagName) {
    showConfirmModal('🗑️ Hapus Tag', `Hapus tag "#${tagName}" dari resource ini?`, 'Ya, Hapus!')
        .then(confirmed => {
            if (confirmed) {
                window.location.href = `edit.php?id=${resourceId}&hapus_tag=${tagId}&token=${csrfToken}`;
            }
        })
        .catch(() => {});
}

function confirmDeleteTagPermanen(tagId, tagName) {
    showConfirmModal('🗑️ Hapus Tag Permanen', `Yakin hapus tag "#${tagName}" secara permanen? Tag ini akan dihapus permanen.`, 'Ya, Hapus!')
        .then(confirmed => {
            if (confirmed) {
                window.location.href = `manage_tags.php?hapus=${tagId}&token=${csrfToken}`;
            }
        })
        .catch(() => {});
}

function confirmClearAllLogs() {
    showConfirmModal('🗑️ Hapus Semua Log', 'Yakin ingin menghapus semua log aktivitas? Semua aktivitas akan hilang.', 'Ya, Hapus!')
        .then(confirmed => {
            if (confirmed) {
                document.getElementById('clearLogsForm').submit();
            }
        })
        .catch(() => {});
}

function confirmDeleteAccount() {
    const checkbox = document.getElementById('confirm-delete');
    if (!checkbox || !checkbox.checked) {
        alert('Silakan centang "Saya yakin ingin menghapus akun ini" terlebih dahulu.');
        return;
    }
    
    const password = document.getElementById('delete-password').value;
    if (!password) {
        alert('Masukkan password terlebih dahulu.');
        return;
    }
    
    showConfirmModal('⚠️ Hapus Akun', 'Yakin ingin menghapus akun secara permanen? Semua data akan hilang!', 'Ya, Hapus Akun!')
        .then(confirmed => {
            if (confirmed) {
                document.getElementById('delete-password-hidden').value = password;
                document.getElementById('deleteAccountForm').submit();
            }
        })
        .catch(() => {});
}





/* =========================================================
   06. INIT
   Semua inisialisasi dijalankan setelah HTML selesai dimuat.
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
    initSidebarState();
    initDarkMode();
    toggleJenisLainnya();
    initPasswordToggle();
});