    </section>
</main>

<footer class="wg-footer px-4 py-3 text-[10px] text-slate-500 flex items-center justify-between">
    <span>&copy; <?= date('Y') ?> WEBGUARDIAN — Website Monitoring & Security Center</span>
    <span>v2.0</span>
</footer>

<script>
const BASE_URL = '<?= url('/') ?>';
</script>
<script src="<?= asset('js/dashboard.js') ?>"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
toastr.options = { positionClass: 'toast-bottom-right', progressBar: true, closeButton: true, timeOut: 3000 };

function checkAllWebsites() {
    toastr.info('Memulai pengecekan semua website...', 'WEBGUARDIAN');
    fetch(BASE_URL + 'api/check-all')
        .then(r => r.json())
        .then(d => {
            if (d.success) { toastr.success(d.message, 'Selesai'); setTimeout(() => location.reload(), 2000); }
            else { toastr.error(d.message || 'Gagal', 'Error'); }
        })
        .catch(() => toastr.error('Koneksi error', 'Error'));
}

function checkSingleWebsite(id) {
    toastr.info('Memeriksa website...', 'WEBGUARDIAN');
    fetch(BASE_URL + 'api/check/' + id)
        .then(r => r.json())
        .then(d => {
            if (d.success) { toastr.success(d.message, 'Selesai'); setTimeout(() => location.reload(), 1500); }
            else { toastr.error(d.message || 'Gagal', 'Error'); }
        })
        .catch(() => toastr.error('Koneksi error', 'Error'));
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

    // Mobile sidebar toggle - show/hide with slide
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            if (window.innerWidth < 768) {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('!block');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
                sidebar.classList.toggle('overflow-y-auto');
            }
        });
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 768 && !sidebar.classList.contains('hidden')) {
                if (!sidebar.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)) {
                    sidebar.classList.add('hidden');
                    sidebar.classList.remove('!block', 'fixed', 'inset-0', 'z-50', 'overflow-y-auto');
                }
            }
        });
    }
});
</script>
</body>
</html>
