<!-- =========================================================
   MODAL KONFIRMASI GLOBAL
   ========================================================= -->
<div id="confirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:var(--bg-card); border-radius:var(--radius-lg); padding:30px; max-width:420px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <h2 id="confirmModalTitle" style="margin-bottom:10px; font-size:1.2rem; color:var(--text-primary);">⚠️ Konfirmasi</h2>
        <p id="confirmModalMessage" style="color:var(--text-muted); margin-bottom:20px; line-height:1.5;">Apakah Anda yakin?</p>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button id="confirmModalCancel" style="padding:10px 24px; border-radius:var(--radius-md); background:var(--bg-soft); color:var(--text-primary); border:none; cursor:pointer; font-weight:600; transition:background 0.2s;">
                Batal
            </button>
            <button id="confirmModalConfirm" style="padding:10px 24px; border-radius:var(--radius-md); background:var(--text-danger); color:#fff; border:none; cursor:pointer; font-weight:600; transition:background 0.2s;">
                Ya, Hapus!
            </button>
        </div>
    </div>
</div>

<script>
// =========================================================
//   MODAL KONFIRMASI GLOBAL (JavaScript)
// =========================================================
(function() {
    const modal = document.getElementById('confirmModal');
    const title = document.getElementById('confirmModalTitle');
    const message = document.getElementById('confirmModalMessage');
    const cancelBtn = document.getElementById('confirmModalCancel');
    const confirmBtn = document.getElementById('confirmModalConfirm');

    let resolveCallback = null;
    let rejectCallback = null;

    window.showConfirmModal = function(titleText, messageText, confirmText = 'Ya, Hapus!') {
        return new Promise((resolve, reject) => {
            title.textContent = titleText || '⚠️ Konfirmasi';
            message.textContent = messageText || 'Apakah Anda yakin?';
            confirmBtn.textContent = confirmText;
            modal.style.display = 'flex';
            resolveCallback = resolve;
            rejectCallback = reject;
        });
    };

    function closeModal() {
        modal.style.display = 'none';
        resolveCallback = null;
        rejectCallback = null;
    }

    cancelBtn.addEventListener('click', function() {
        if (rejectCallback) rejectCallback(false);
        closeModal();
    });

    confirmBtn.addEventListener('click', function() {
        if (resolveCallback) resolveCallback(true);
        closeModal();
    });

    // Tutup modal saat klik di luar
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            if (rejectCallback) rejectCallback(false);
            closeModal();
        }
    });

    // Tutup dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            if (rejectCallback) rejectCallback(false);
            closeModal();
        }
    });
})();
</script>