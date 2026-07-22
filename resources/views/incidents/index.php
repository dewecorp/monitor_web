<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'incident';
require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Incident Response</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div><p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">IR Engine</p><h2 class="text-xl font-semibold text-slate-900">Analisis & Response Insiden</h2></div>
</div>

<div class="mb-5">
    <label class="text-xs font-medium text-slate-600 mr-2">Pilih Website:</label>
    <select onchange="if(this.value)window.location='?website_id='+this.value" class="form-select-dash text-xs w-64">
        <option value="">— Pilih —</option>
        <?php foreach ($websites as $w): ?>
        <option value="<?= $w['id'] ?>" <?= $websiteId == $w['id'] ? 'selected' : '' ?>><?= e($w['nama_website']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<?php if ($analysis): $a = $analysis; ?>
<div class="grid gap-4 lg:grid-cols-4 mb-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] uppercase tracking-wide text-slate-400">Status</p>
        <p class="text-lg font-semibold mt-1 <?= $a['status'] === 'healthy' ? 'text-emerald-600' : ($a['status'] === 'danger' ? 'text-rose-600' : 'text-amber-600') ?>"><?= ucfirst($a['status']) ?></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] uppercase tracking-wide text-slate-400">Priority</p>
        <p class="text-lg font-semibold mt-1 <?= $a['priority'] === 'critical' ? 'text-rose-600' : ($a['priority'] === 'high' ? 'text-amber-600' : 'text-emerald-600') ?>"><?= ucfirst($a['priority']) ?></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] uppercase tracking-wide text-slate-400">Total Issues</p>
        <p class="text-lg font-semibold mt-1 text-slate-800"><?= $a['total_issues'] ?></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] uppercase tracking-wide text-slate-400">Kritis / Tinggi</p>
        <p class="text-lg font-semibold mt-1 text-slate-800"><?= $a['critical'] ?> / <?= $a['high'] ?></p>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm mb-5">
    <p class="text-sm font-semibold text-slate-800"><?= e($a['summary']) ?></p>
</div>

<?php if (!empty($a['recommendations'])): ?>
<div class="space-y-3 mb-5">
    <?php foreach ($a['recommendations'] as $r): ?>
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase
                    <?= $r['severity'] === 'critical' ? 'bg-rose-50 text-rose-700' : ($r['severity'] === 'high' ? 'bg-amber-50 text-amber-700' : 'bg-sky-50 text-sky-700') ?>">
                    <?= e($r['severity']) ?>
                </span>
                    <span class="text-xs font-semibold text-slate-800"><?= e($r['title']) ?></span>
                </div>
            </div>
            <div class="p-4">
                <div class="rounded-lg bg-amber-50/50 border border-amber-200 p-3 mb-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-700 mb-1">🔍 Yang Terdeteksi</p>
                    <p class="text-xs text-amber-800"><?= e($r['check']) ?></p>
                </div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 mb-2">Kemungkinan Penyebab</p>
            <ul class="space-y-1 mb-4">
                <?php foreach ($r['causes'] as $c): ?>
                <li class="flex gap-2 text-xs text-slate-600"><span class="h-1.5 w-1.5 rounded-full bg-slate-400 mt-1 shrink-0"></span><?= e($c) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 mb-2">Rekomendasi Tindakan</p>
            <ol class="space-y-1">
                <?php foreach ($r['actions'] as $ac): ?>
                <li class="flex gap-2 text-xs text-slate-700"><span class="flex h-4 w-4 items-center justify-center rounded-full bg-indigo-100 text-[9px] font-semibold text-indigo-700 shrink-0 mt-0.5"><?= $ac['p'] ?></span><?= e($ac['a']) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php elseif ($websiteId > 0): ?>
<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
    <p class="text-xs text-slate-400">Belum cukup data untuk analisis. Jalankan monitoring dan scan terlebih dahulu.</p>
</div>
<?php endif; ?>

<?php if (!empty($timeline)): ?>
<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400 mb-3">Timeline Kejadian</p>
    <div class="space-y-2 max-h-64 overflow-y-auto">
        <?php foreach ($timeline as $e): ?>
        <div class="flex items-start gap-2.5 rounded-lg bg-slate-50/50 p-2.5">
            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full <?= $e['source'] === 'incident' ? 'bg-rose-400' : ($e['source'] === 'health' ? 'bg-emerald-400' : ($e['source'] === 'security' ? 'bg-indigo-400' : 'bg-amber-400')) ?>"></span>
            <div class="min-w-0">
                <p class="text-[11px] text-slate-700"><?= e($e['description'] ?? '') ?></p>
                <p class="text-[10px] text-slate-400"><?= e($e['severity'] ?? '') ?> · <?= timeAgo($e['time'] ?? '') ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
