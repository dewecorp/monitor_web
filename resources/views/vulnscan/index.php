<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'vuln';
require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Vulnerability Scanner</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div><p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Vuln Scan</p><h2 class="text-xl font-semibold text-slate-900">Pemindaian Kerentanan</h2></div>
</div>

<div class="grid gap-4 lg:grid-cols-3 mb-5">
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Scan Vulnerability</h3>
            <div class="flex gap-3">
                <select id="websiteSelect" class="form-select-dash text-xs flex-1">
                    <option value="">Pilih Website</option>
                    <?php foreach ($websites as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $selectedWebsiteId == $w['id'] ? 'selected' : '' ?>><?= e($w['nama_website']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button onclick="runVulnScan()" class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Scan</button>
            </div>
        </div>

        <?php if ($result): $r = $result; ?>
        <div class="grid gap-3 sm:grid-cols-4 mt-4">
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">CMS</p>
                <p class="text-sm font-semibold text-slate-800"><?= e($r['cms'] ?? 'Unknown') ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Score</p>
                <p class="text-lg font-semibold <?= $r['score'] >= 80 ? 'text-emerald-600' : ($r['score'] >= 50 ? 'text-amber-600' : 'text-rose-600') ?>"><?= $r['score'] ?>%</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Vulnerabilities</p>
                <p class="text-lg font-semibold text-slate-800"><?= count($r['vulnerabilities']) ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Severity</p>
                <p class="text-lg font-semibold <?= $r['severity'] === 'clean' ? 'text-emerald-600' : ($r['severity'] === 'medium' ? 'text-amber-600' : 'text-rose-600') ?>"><?= ucfirst($r['severity']) ?></p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mt-4">
            <div class="p-4 border-b border-slate-100"><p class="text-xs font-semibold text-slate-700">Daftar Kerentanan</p></div>
            <?php if (empty($r['vulnerabilities'])): ?>
            <div class="p-8 text-center"><p class="text-xs text-emerald-600 font-medium">Tidak ditemukan kerentanan</p></div>
            <?php else: ?>
            <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                <?php foreach ($r['vulnerabilities'] as $v): ?>
                <div class="px-4 py-3 hover:bg-slate-50">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase
                                <?= $v['severity'] === 'critical' ? 'bg-rose-50 text-rose-700' : ($v['severity'] === 'high' ? 'bg-amber-50 text-amber-700' : ($v['severity'] === 'medium' ? 'bg-orange-50 text-orange-700' : 'bg-slate-100 text-slate-600')) ?>">
                                <?= e($v['severity']) ?>
                            </span>
                            <p class="text-xs font-medium text-slate-800 mt-1"><?= e($v['description']) ?></p>
                            <p class="text-[10px] text-slate-500 font-mono"><?= e($v['detail']) ?></p>
                        </div>
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
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>WordPress version & CVE</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>Laravel debug & env exposure</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>.git / .env / composer.json exposure</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>Directory listing</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>XML-RPC, readme.html</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>Security headers check</li>
        </ul>
    </div>
</div>

<script>
document.getElementById('websiteSelect')?.addEventListener('change', function() { if (this.value) window.location = '?website_id=' + this.value; });
function runVulnScan() {
    var id = document.getElementById('websiteSelect').value;
    if (!id) { toastr.error('Pilih website dulu'); return; }
    toastr.info('Memindai kerentanan...');
    fetch(BASE_URL + 'vulnerability-scan/api-scan?website_id=' + id)
        .then(r => r.json())
        .then(d => { if (d.success) window.location = '?website_id=' + id; else toastr.error(d.error || 'Gagal'); })
        .catch(() => toastr.error('Koneksi error'));
}
</script>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
