    </section>
</main>

<footer class="wg-footer px-4 py-3 text-[10px] text-slate-500 flex items-center justify-between">
    <span>&copy; <?= date('Y') ?> WEBGUARDIAN — Website Monitoring & Security Center</span>
    <span>v2.0</span>
</footer>

<!-- Back to Top -->
<button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" style="display:none;position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;width:3rem;height:3rem;border-radius:9999px;background:linear-gradient(135deg,#4338ca,#047857);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 15px rgba(4,120,87,0.4);font-size:1.5rem;line-height:1;opacity:0.9;transition:all 0.2s" onmouseover="this.style.transform='scale(1.1)';this.style.opacity=1" onmouseout="this.style.transform='scale(1)';this.style.opacity=0.9">
    <svg style="width:1.25rem;height:1.25rem;margin:auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
</button>
<script>
var bt = document.getElementById('backToTop');
window.addEventListener('scroll', function() { if (bt) bt.style.display = (window.scrollY > 300) ? '' : 'none'; });

var userMenuTimer;

function showUserMenu() {
    clearTimeout(userMenuTimer);
    var menu = document.getElementById('userMenu');
    if (menu) menu.classList.remove('hidden');
}

function hideUserMenu() {
    userMenuTimer = setTimeout(function() {
        var menu = document.getElementById('userMenu');
        if (menu) menu.classList.add('hidden');
    }, 300);
}
</script>

<script>
// Force fresh load every time
window.addEventListener('pageshow', function(e) { if (e.persisted) window.location.reload(true); });
const BASE_URL = '<?= url('/') ?>';
</script>
<script src="<?= asset('js/jquery.min.js') ?>"></script>
<script src="<?= asset('js/toastr.min.js') ?>"></script>
<script src="<?= asset('js/sweetalert2.min.js') ?>"></script>
<script src="<?= asset('js/chart.min.js') ?>"></script>
<script src="<?= asset('js/dashboard.js') ?>"></script>
<script>
toastr.options = { positionClass: 'toast-bottom-right', progressBar: true, closeButton: true, timeOut: 3000 };

function showSwalLoading(title, text) {
    Swal.fire({ title: title, text: text, allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: function() { Swal.showLoading(); }, customClass: { popup: 'rounded-2xl shadow-2xl border border-slate-200 p-8 font-sans', title: 'text-sm font-semibold text-slate-900 !mt-0 !mb-1 !p-0', htmlContainer: '!text-xs !text-slate-500 !mt-0 !mb-0 !p-0' } });
}

function showSwalResult(icon, title, text) {
    Swal.fire({ icon: icon, title: title, text: text, timer: 3000, showConfirmButton: false, customClass: { popup: 'rounded-2xl shadow-2xl border border-slate-200 p-6 font-sans', title: 'text-sm font-semibold text-slate-900 !mt-0 !mb-1 !p-0', htmlContainer: '!text-xs !text-slate-500 !mt-0 !mb-0 !p-0' } });
}

function checkAllWebsites() {
    showSwalLoading('Memeriksa Semua Website', 'Proses pengecekan berlangsung...');
    fetch(BASE_URL + 'api/check-all')
        .then(r => r.json())
        .then(d => {
            if (d.success) { showSwalResult('success', 'Selesai', d.message); setTimeout(function() { location.reload(); }, 2000); }
            else { showSwalResult('error', 'Gagal', d.message || 'Gagal mengecek'); }
        })
        .catch(function() { showSwalResult('error', 'Error', 'Koneksi error'); });
}

function checkSingleWebsite(id) {
    showSwalLoading('Memeriksa Website', 'Proses pengecekan berlangsung...');
    fetch(BASE_URL + 'api/check/' + id)
        .then(r => r.json())
        .then(d => {
            if (d.success) { showSwalResult('success', 'Selesai', d.message); setTimeout(function() { location.reload(); }, 1500); }
            else { showSwalResult('error', 'Gagal', d.message || 'Gagal mengecek'); }
        })
        .catch(function() { showSwalResult('error', 'Error', 'Koneksi error'); });
}

function confirmLogout() {
    Swal.fire({
        title: 'Yakin ingin logout?',
        text: 'Anda akan kembali ke halaman login.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        customClass: {
            popup: 'rounded-2xl shadow-2xl border border-slate-200 p-8 font-sans',
            title: 'text-sm font-semibold text-slate-900 !mt-0 !mb-1 !p-0',
            htmlContainer: '!text-xs !text-slate-500 !mt-0 !mb-0 !p-0',
            confirmButton: 'btn-dash-danger text-[11px] px-4 py-2 mx-1 rounded-full font-semibold shadow-md',
            cancelButton: 'btn-dashboard-soft text-[11px] px-4 py-2 mx-1 rounded-full font-semibold'
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = BASE_URL + 'logout';
        }
    });
}

function deleteWebsite(id, name) {
    Swal.fire({
        title: 'Hapus ' + name + '?',
        text: 'Semua data monitoring website ini akan ikut terhapus!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        customClass: {
            popup: 'rounded-2xl shadow-2xl border border-slate-200 p-8 font-sans',
            title: 'text-sm font-semibold text-slate-900 !mt-0 !mb-1 !p-0',
            htmlContainer: '!text-xs !text-slate-500 !mt-0 !mb-0 !p-0',
            confirmButton: 'btn-dash-danger text-[11px] px-4 py-2 mx-1 rounded-full font-semibold shadow-md',
            cancelButton: 'btn-dashboard-soft text-[11px] px-4 py-2 mx-1 rounded-full font-semibold'
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = BASE_URL + 'websites/' + id + '/delete';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_csrf_token';
            input.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function updateClock() {
    var now = new Date();
    var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var str = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear() + ', ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
    var el = document.getElementById('clockDisplay');
    if (el) el.textContent = str;
}
setInterval(updateClock, 30000);
updateClock();

document.addEventListener('DOMContentLoaded', function() {
    // Flash messages as SweetAlert
    var flashSuccess = document.getElementById('flashSuccess');
    var flashError = document.getElementById('flashError');
    if (flashSuccess) {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: flashSuccess.getAttribute('data-message'),
            timer: 3000,
            showConfirmButton: false,
            customClass: {
                popup: 'rounded-2xl shadow-2xl border border-slate-200 p-6 font-sans',
                title: 'text-sm font-semibold text-slate-900 !mt-0 !mb-1 !p-0',
                htmlContainer: '!text-xs !text-slate-500 !mt-0 !mb-0 !p-0'
            }
        });
    }
    if (flashError) {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: flashError.getAttribute('data-message'),
            timer: 4000,
            showConfirmButton: false,
            customClass: {
                popup: 'rounded-2xl shadow-2xl border border-slate-200 p-6 font-sans',
                title: 'text-sm font-semibold text-slate-900 !mt-0 !mb-1 !p-0',
                htmlContainer: '!text-xs !text-slate-500 !mt-0 !mb-0 !p-0'
            }
        });
    }

    // Mobile sidebar toggle
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('mobileSidebar');
    var overlay = document.getElementById('mobileOverlay');
    
    function openSidebar() {
        if (sidebar) sidebar.classList.remove('-translate-x-full');
        if (sidebar) sidebar.classList.add('translate-x-0');
        if (overlay) overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.add('-translate-x-full');
        if (sidebar) sidebar.classList.remove('translate-x-0');
        if (overlay) overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }

    if (toggle && sidebar) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('-translate-x-full')) {
                openSidebar();
            } else {
                closeSidebar();
            }
        });
    }
});

function systemUpdate() {
    Swal.fire({
        title: 'Update Sistem',
        text: 'Proses update akan mengunduh versi terbaru dan mengganti file sistem. Lanjutkan?',
        icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Update', cancelButtonText: 'Batal', reverseButtons: true,
        customClass: { popup: 'rounded-2xl shadow-2xl border border-slate-200 p-8 font-sans', title: 'text-sm font-semibold text-slate-900 !mt-0 !mb-1 !p-0', htmlContainer: '!text-xs !text-slate-500 !mt-0 !mb-0 !p-0', confirmButton: 'btn-dash-primary text-[11px] px-4 py-2 mx-1 rounded-full', cancelButton: 'btn-dashboard-soft text-[11px] px-4 py-2 mx-1 rounded-full' }
    }).then(function(r) {
        if (!r.isConfirmed) return;
        showSwalLoading('Mengupdate Sistem', 'Mengunduh dan memasang pembaruan...');
        var form = document.createElement('form');
        form.method = 'POST'; form.action = BASE_URL + 'update/run';
        var input = document.createElement('input');
        input.type = 'hidden'; input.name = '_csrf_token';
        input.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(input); document.body.appendChild(form); form.submit();
    });
}

// Auto check update
if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    fetch(BASE_URL + 'update/check').then(function(r){return r.json()}).then(function(d){
        if (d.update_available) {
            var badge = document.createElement('span');
            badge.className = 'absolute -top-0.5 -right-0.5 h-2.5 w-2.5 bg-rose-500 rounded-full ring-2 ring-white';
            var btn = document.getElementById('userMenuBtn');
            if (btn && btn.parentElement) btn.parentElement.appendChild(badge);
        }
    }).catch(function(){});
}
</script>
</body>
</html>
