<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'websites'; $isEdit = $website !== null;
require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <a href="/websites" class="text-slate-600 hover:text-indigo-600">Websites</a> <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium"><?= $isEdit ? 'Edit' : 'Tambah' ?> Website</span></nav>

<div class="max-w-2xl mx-auto">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-800 mb-5"><?= $isEdit ? 'Edit Website' : 'Tambah Website Baru' ?></h2>

        <form method="POST" action="<?= $isEdit ? url('websites/' . $website['id'] . '/update') : url('websites/store') ?>" class="space-y-4">
            <?= csrfField() ?>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500 mb-1">Nama Website</label>
                    <input type="text" name="nama_website" value="<?= e($website['nama_website'] ?? '') ?>" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500 mb-1">URL Website</label>
                    <input type="url" name="url" value="<?= e($website['url'] ?? '') ?>" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" placeholder="https://example.com">
                </div>
                <div>
                    <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500 mb-1">Kategori</label>
                    <input type="text" name="kategori" value="<?= e($website['kategori'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" placeholder="Company Profile">
                </div>
                <div>
                    <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500 mb-1">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                        <option value="active" <?= ($website['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($website['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="maintenance" <?= ($website['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Google Analytics (GA4)</p>
                    <span class="text-[10px] text-slate-400">Opsional — untuk data pengunjung asli</span>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-500 mb-1">GA4 Property ID</label>
                    <input type="text" name="ga_property_id" id="ga_property_id" value="<?= e($website['ga_property_id'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" placeholder="Contoh: 123456789">
                    <p class="mt-1 text-[10px] text-slate-400">Angka ID properti GA4. Lihat di GA: Admin → Property Settings → Property ID.</p>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-500 mb-1">Service Account Client Email <span class="text-slate-400 font-normal">(kosongkan untuk pakai kredensial global dari Settings)</span></label>
                    <input type="text" name="ga_client_email" id="ga_client_email" value="<?= e($website['ga_client_email'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" placeholder="nama@project-id.iam.gserviceaccount.com">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-500 mb-1">Private Key (PEM) <span class="text-slate-400 font-normal">(kosongkan untuk pakai kredensial global)</span></label>
                    <textarea name="ga_private_key" id="ga_private_key" rows="3" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-mono focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"><?= e($website['ga_private_key'] ?? '') ?></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="testGaWebsite()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">Test Koneksi GA</button>
                    <span class="text-[10px] text-slate-400">Menguji kredensial yang diisi di form ini (belum perlu disimpan)</span>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-500 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"><?= e($website['deskripsi'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <a href="/websites" class="rounded-xl border border-slate-200 px-5 py-2.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">Batal</a>
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"><?= $isEdit ? 'Simpan' : 'Tambah' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function testGaWebsite() {
    toastr.info('Menguji koneksi Google Analytics...');
    var body = new URLSearchParams();
    body.append('ga_property_id', document.getElementById('ga_property_id').value.trim());
    body.append('ga_client_email', document.getElementById('ga_client_email').value.trim());
    body.append('ga_private_key', document.getElementById('ga_private_key').value.trim());
    fetch(BASE_URL + 'websites/test-ga', { method: 'POST', body: body })
        .then(r => r.json())
        .then(d => {
            if (d.success) toastr.success(d.message, 'Sukses');
            else toastr.error(d.message, 'Gagal');
        })
        .catch(() => toastr.error('Gagal menghubungi server'));
}
</script>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
