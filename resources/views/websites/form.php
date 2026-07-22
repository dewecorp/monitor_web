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

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
