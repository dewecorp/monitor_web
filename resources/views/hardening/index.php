<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'hardening';
require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Server Hardening</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div><p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Hardening</p><h2 class="text-xl font-semibold text-slate-900">Hardening Check</h2></div>
</div>

<div class="grid gap-4 lg:grid-cols-3 mb-5">
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Run Hardening Check</h3>
            <div class="flex gap-3">
                <select id="websiteSelect" class="form-select-dash text-xs flex-1">
                    <option value="">Pilih Website</option>
                    <?php foreach ($websites as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= $selectedWebsiteId == $w['id'] ? 'selected' : '' ?>><?= e($w['nama_website']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button onclick="runCheck()" class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Check</button>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">Scan dilakukan pada direktori root website yang dipilih</p>
        </div>

        <?php if ($result): $r = $result; ?>
        <div class="grid gap-3 sm:grid-cols-3 mt-4 mb-4">
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Hardening Score</p>
                <p class="text-lg font-semibold <?= $r['score'] >= 80 ? 'text-emerald-600' : ($r['score'] >= 50 ? 'text-amber-600' : 'text-rose-600') ?>"><?= $r['score'] ?>%</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Issues</p>
                <p class="text-lg font-semibold text-slate-800"><?= count($r['issues']) ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Passed</p>
                <p class="text-lg font-semibold text-emerald-600"><?= count($r['passed']) ?></p>
            </div>
        </div>

        <?php if (!empty($r['issues'])): ?>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mt-4">
            <div class="p-4 border-b border-slate-100 bg-rose-50/50"><p class="text-xs font-semibold text-rose-700">Issues Ditemukan</p></div>
            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                <?php foreach ($r['issues'] as $iss): ?>
                <div class="px-4 py-3 hover:bg-slate-50">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase
                                <?= $iss['severity'] === 'critical' ? 'bg-rose-50 text-rose-700' : ($iss['severity'] === 'high' ? 'bg-amber-50 text-amber-700' : 'bg-orange-50 text-orange-700') ?>">
                                <?= e($iss['severity']) ?>
                            </span>
                            <p class="text-xs font-medium text-slate-800 mt-1"><?= e($iss['description']) ?></p>
                            <p class="text-[10px] text-slate-500">💡 <?= e($iss['recommendation']) ?></p>
                            <p class="text-[9px] text-slate-400 font-mono"><?= e($iss['target']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($r['passed'])): ?>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mt-4">
            <div class="p-4 border-b border-slate-100 bg-emerald-50/50"><p class="text-xs font-semibold text-emerald-700">Passed Checks</p></div>
            <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto">
                <?php foreach ($r['passed'] as $p): ?>
                <div class="px-4 py-2 text-xs text-emerald-700 flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <?= e($p) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm h-fit">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Cek Keamanan</h3>
        <ul class="space-y-2 text-xs text-slate-600">
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>File permissions</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>Dangerous PHP functions</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>PHP configuration</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>Directory listing</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>Security headers</li>
            <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>SSL/TLS config</li>
        </ul>
    </div>
</div>

<script>
document.getElementById('websiteSelect')?.addEventListener('change', function() { if (this.value) window.location = '?website_id=' + this.value; });
function runCheck() {
    var id = document.getElementById('websiteSelect').value;
    if (!id) { toastr.error('Pilih website dulu'); return; }
    window.location = '?website_id=' + id;
}
</script>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
