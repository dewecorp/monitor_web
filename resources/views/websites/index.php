<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'websites'; require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Websites</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Manajemen</p>
        <h2 class="text-xl font-semibold text-slate-900">Daftar Website</h2>
    </div>
    <a href="<?= url('websites/create') ?>" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        Tambah Website
    </a>
</div>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500 w-8">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-[11px] uppercase tracking-wide text-slate-500">Nama</th>
                    <th class="px-4 py-3 text-left font-semibold text-[11px] uppercase tracking-wide text-slate-500">URL</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Response</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Kategori</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php $i = 1; foreach ($websites as $w): ?>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-3 py-3 text-center text-[11px] text-slate-400"><?= $i++ ?></td>
                    <td class="px-4 py-3 font-medium text-slate-800"><?= e($w['nama_website']) ?></td>
                    <td class="px-4 py-3 text-slate-400 truncate max-w-[200px]"><?= e($w['url']) ?></td>
                    <td class="px-4 py-3 text-center"><?= statusBadge($w['is_up'] ?? 0) ?></td>
                    <td class="px-4 py-3 text-center text-slate-600"><?= ($w['response_time'] ?? 0) ? $w['response_time'] . ' ms' : '-' ?></td>
                    <td class="px-4 py-3 text-center"><span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-medium text-slate-600"><?= e($w['kategori'] ?? 'Umum') ?></span></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button onclick="checkSingleWebsite(<?= $w['id'] ?>)" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-slate-50 hover:border-indigo-300 hover:text-indigo-600 transition-colors" title="Check">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                            <a href="<?= url('websites/' . $w['id'] . '/edit') ?>" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-slate-50 hover:border-amber-300 hover:text-amber-600 transition-colors" title="Edit">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button onclick="deleteWebsite(<?= $w['id'] ?>, '<?= e($w['nama_website']) ?>')" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-slate-50 hover:border-rose-300 hover:text-rose-600 transition-colors" title="Hapus">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($websites)): ?>
                <tr><td colspan="7" class="px-4 py-8 text-center text-xs text-slate-400">Belum ada website. <a href="<?= url('websites/create') ?>" class="text-indigo-600 font-medium hover:underline">Tambah sekarang</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
