<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'threat';
require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Threat Detection</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Threat Engine</p>
        <h2 class="text-xl font-semibold text-slate-900">Pemindaian Ancaman</h2>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-3 mb-5">
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Scan Threat</h3>
            <div class="flex gap-3">
                <select id="websiteSelect" class="form-select-dash text-xs flex-1">
                    <option value="">Pilih Website</option>
                    <?php foreach ($websites as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $selectedWebsiteId == $w['id'] ? 'selected' : '' ?>><?= e($w['nama_website']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button onclick="runThreatScan()" class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                    <svg class="h-3.5 w-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Scan
                </button>
            </div>
        </div>

        <?php if (!empty($threats)): $t = $threats; ?>
        <div class="grid gap-3 sm:grid-cols-3 mt-4">
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Threat Score</p>
                <p class="text-lg font-semibold <?= $t['threat_score'] >= 80 ? 'text-emerald-600' : ($t['threat_score'] >= 50 ? 'text-amber-600' : 'text-rose-600') ?>"><?= $t['threat_score'] ?>%</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Ancaman</p>
                <p class="text-lg font-semibold text-slate-800"><?= $t['threat_count'] ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Severity</p>
                <p class="text-lg font-semibold <?= $t['severity'] === 'clean' ? 'text-emerald-600' : ($t['severity'] === 'medium' ? 'text-amber-600' : 'text-rose-600') ?>"><?= ucfirst($t['severity']) ?></p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mt-4">
            <div class="p-4 border-b border-slate-100">
                <p class="text-xs font-semibold text-slate-700">Detected Threats</p>
            </div>
            <?php if (empty($t['threats'])): ?>
            <div class="p-8 text-center"><p class="text-xs text-emerald-600 font-medium">Tidak ada ancaman terdeteksi</p></div>
            <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($t['threats'] as $th): ?>
                <div class="px-4 py-3 hover:bg-slate-50">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase
                                <?= $th['severity'] === 'critical' ? 'bg-rose-50 text-rose-700' : ($th['severity'] === 'high' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') ?>">
                                <?= e($th['type']) ?>
                            </span>
                            <p class="text-xs font-medium text-slate-800 mt-1"><?= e($th['description']) ?></p>
                            <p class="text-[10px] text-slate-500 font-mono"><?= e($th['detail']) ?></p>
                        </div>
                        <span class="shrink-0 text-[9px] font-semibold uppercase <?= $th['severity'] === 'critical' ? 'text-rose-600' : ($th['severity'] === 'high' ? 'text-amber-600' : 'text-slate-500') ?>"><?= e($th['severity']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm h-fit">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Deteksi</h3>
        <ul class="space-y-2 text-xs text-slate-600">
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>Defacement</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>Redirect berbahaya</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>SEO spam & cloaking</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>Hidden redirect (JS)</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>Crypto miner</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>Phishing form</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>Iframe injection</li>
        </ul>
    </div>
</div>

<script>
document.getElementById('websiteSelect')?.addEventListener('change', function() {
    if (this.value) window.location = '?website_id=' + this.value;
});

function runThreatScan() {
    var id = document.getElementById('websiteSelect').value;
    if (!id) { toastr.error('Pilih website dulu'); return; }
    showSwalLoading('Memindai Ancaman', 'Proses pemindaian berlangsung...');
    fetch(BASE_URL + 'threat-detection/api-scan?website_id=' + id)
        .then(r => r.json())
        .then(d => {
            if (d.success) { showSwalResult('success', 'Selesai', 'Pemindaian selesai'); window.location = '?website_id=' + id; }
            else { showSwalResult('error', 'Gagal', d.error || 'Gagal'); }
        })
        .catch(function() { showSwalResult('error', 'Error', 'Koneksi error'); });
}
</script>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
