<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'quarantine';
require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Karantina</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">File Quarantine</p>
        <h2 class="text-xl font-semibold text-slate-900">Manajemen Karantina</h2>
    </div>
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1.5 font-semibold text-rose-700">Terkarantina: <?= $activeCount ?></span>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <?php if (empty($items)): ?>
    <div class="text-center py-12">
        <svg class="h-16 w-16 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01"/></svg>
        <p class="text-sm font-semibold text-slate-700">Tidak ada file terkarantina</p>
        <p class="text-xs text-slate-500 mt-1">File yang mencurigakan akan muncul di sini setelah dipindahkan ke karantina.</p>
    </div>
    <?php else: ?>
    <div class="divide-y divide-slate-100">
        <?php foreach ($items as $q): ?>
        <div class="px-4 py-3 hover:bg-slate-50">
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase
                            <?= $q['severity'] === 'critical' ? 'bg-rose-50 text-rose-700' : ($q['severity'] === 'high' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') ?>">
                            <?= e($q['severity']) ?>
                        </span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold
                            <?= $q['status'] === 'quarantined' ? 'bg-rose-50 text-rose-700' : ($q['status'] === 'restored' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500') ?>">
                            <?= ucfirst($q['status']) ?>
                        </span>
                        <?php if ($q['auto_quarantine']): ?>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold bg-indigo-50 text-indigo-700">Otomatis</span>
                        <?php endif; ?>
                        <?php if ($q['nama_website']): ?>
                        <span class="text-[10px] text-slate-400"><?= e($q['nama_website']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs font-medium text-slate-800 mt-1 font-mono"><?= e(basename($q['file_name'])) ?></p>
                    <p class="text-[10px] text-slate-500 truncate max-w-lg"><?= e($q['original_path']) ?></p>
                    <p class="text-[10px] text-slate-600 mt-0.5"><?= e($q['reason']) ?></p>
                    <div class="flex gap-3 mt-1 text-[9px] text-slate-400">
                        <span>Size: <?= $q['file_size'] > 1024 ? round($q['file_size']/1024,1).' KB' : $q['file_size'].' B' ?></span>
                        <span>Hash: <?= $q['sha256'] ? substr($q['sha256'], 0, 16).'...' : '-' ?></span>
                        <span>Oleh: <?= e($q['user_name'] ?? 'System') ?></span>
                        <span><?= timeAgo($q['quarantined_at']) ?></span>
                    </div>
                </div>
                <?php if ($q['status'] === 'quarantined'): ?>
                <div class="flex gap-1 shrink-0 ml-2">
                    <form method="POST" action="<?= url('quarantine/restore') ?>" class="inline" onsubmit="return confirm('Kembalikan file ke lokasi asal?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $q['id'] ?>">
                        <button type="submit" class="rounded-lg border border-emerald-200 px-2.5 py-1 text-[10px] font-medium text-emerald-700 hover:bg-emerald-50">Restore</button>
                    </form>
                    <form method="POST" action="<?= url('quarantine/delete') ?>" class="inline" onsubmit="return confirm('Hapus permanen file ini? Tindakan ini tidak bisa dibatalkan!')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $q['id'] ?>">
                        <button type="submit" class="rounded-lg border border-rose-200 px-2.5 py-1 text-[10px] font-medium text-rose-700 hover:bg-rose-50">Hapus</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
