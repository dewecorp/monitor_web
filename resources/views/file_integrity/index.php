<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'file_integrity';
require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">File Integrity Monitor</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">FIM</p>
        <h2 class="text-xl font-semibold text-slate-900">File Integrity Monitoring</h2>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-3 mb-5">
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Jalankan Scan</h3>
            <form method="POST" action="<?= url('file-integrity/scan') ?>" class="space-y-3">
                <?= csrfField() ?>
                <div class="flex gap-3">
                    <select name="website_id" class="form-select-dash text-xs flex-1">
                        <option value="">Pilih Website</option>
                        <?php foreach ($websites as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $selectedWebsiteId == $w['id'] ? 'selected' : '' ?>><?= e($w['nama_website']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="path" class="form-control-dash flex-[2] text-xs" placeholder="Masukkan path direktori website">
                </div>
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                    <svg class="h-3.5 w-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Scan Sekarang
                </button>
            </form>
            <p class="text-[10px] text-slate-400 mt-2">Scan SHA256 checksums file PHP, JS, CSS, HTML — membandingkan dengan baseline.</p>
        </div>

        <?php if ($summary): ?>
        <div class="grid gap-3 sm:grid-cols-4 mt-4">
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Total Changes</p>
                <p class="text-lg font-semibold text-slate-800"><?= $summary['total'] ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Modified</p>
                <p class="text-lg font-semibold text-amber-600"><?= $summary['modified'] ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Added</p>
                <p class="text-lg font-semibold text-emerald-600"><?= $summary['added'] ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Deleted</p>
                <p class="text-lg font-semibold text-rose-600"><?= $summary['deleted'] ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($changes)): ?>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mt-4">
            <div class="p-4 border-b border-slate-100">
                <p class="text-xs font-semibold text-slate-700">Riwayat Perubahan</p>
            </div>
            <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                <?php foreach ($changes as $c): ?>
                <div class="px-4 py-3 hover:bg-slate-50 flex items-start justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-800 truncate"><?= e($c['file_path']) ?></p>
                        <p class="text-[10px] text-slate-500">
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-semibold
                                <?= $c['change_type'] === 'modified' ? 'bg-amber-50 text-amber-700' : ($c['change_type'] === 'added' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700') ?>">
                                <?= ucfirst($c['change_type']) ?>
                            </span>
                            <?= timeAgo($c['detected_at']) ?>
                        </p>
                        <?php if ($c['diff_preview'] && $c['change_type'] === 'modified'): ?>
                        <pre class="mt-1 text-[9px] bg-slate-50 rounded p-2 overflow-x-auto max-h-20 font-mono text-slate-600"><?= e(substr($c['diff_preview'], 0, 500)) ?></pre>
                        <?php endif; ?>
                    </div>
                    <?php if (!$c['is_reviewed']): ?>
                    <button onclick="markReviewed(<?= $c['id'] ?>)" class="shrink-0 ml-2 rounded-lg border border-slate-200 px-2.5 py-1 text-[9px] text-slate-500 hover:bg-slate-100">Tandai</button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif ($selectedWebsiteId > 0): ?>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center mt-4">
            <p class="text-sm text-slate-500">Belum ada data perubahan. Jalankan scan untuk membuat baseline.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm h-fit">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Tentang FIM</h3>
        <div class="space-y-2 text-xs text-slate-600">
            <p>File Integrity Monitoring memindai dan mencatat checksum SHA256 dari setiap file.</p>
            <ul class="space-y-1.5">
                <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mt-1.5 shrink-0"></span>Scan file PHP, JS, CSS, HTML, JSON</li>
                <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>Deteksi perubahan konten file</li>
                <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>Deteksi file baru atau hilang</li>
                <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>Preview diff untuk file yang berubah</li>
            </ul>
            <p class="text-[10px] text-slate-400 mt-3">Scan pertama akan membuat baseline. Scan selanjutnya membandingkan dengan baseline.</p>
        </div>
    </div>
</div>

<script>
function markReviewed(id) {
    fetch(BASE_URL + 'file-integrity/mark-reviewed', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'id=' + id })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); });
}
</script>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
