<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'ai_analysis';
require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">AI Security Analysis</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div><p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">AI Engine</p><h2 class="text-xl font-semibold text-slate-900">Analisis Keamanan Otomatis</h2></div>
</div>

<div class="mb-5">
    <select onchange="if(this.value)window.location='?website_id='+this.value" class="form-select-dash text-xs w-64">
        <option value="">— Pilih Website —</option>
        <?php foreach ($websites as $w): ?>
        <option value="<?= $w['id'] ?>" <?= $selectedWebsiteId == $w['id'] ? 'selected' : '' ?>><?= e($w['nama_website']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<?php if ($report): $r = $report; ?>
<!-- Header -->
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm mb-5">
    <div class="flex items-start justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-900"><?= e($r['website']['nama_website'] ?? '') ?></h3>
            <p class="text-xs text-slate-500"><?= e($r['website']['url'] ?? '') ?></p>
        </div>
        <div class="text-right">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full text-xl font-bold <?= $r['grade'] === 'A' ? 'bg-emerald-50 text-emerald-600 ring-4 ring-emerald-500/20' : ($r['grade'] === 'B' ? 'bg-sky-50 text-sky-600 ring-4 ring-sky-500/20' : ($r['grade'] === 'C' ? 'bg-amber-50 text-amber-600 ring-4 ring-amber-500/20' : ($r['grade'] === 'D' ? 'bg-orange-50 text-orange-600 ring-4 ring-orange-500/20' : 'bg-rose-50 text-rose-600 ring-4 ring-rose-500/20'))) ?>"><?= $r['grade'] ?></div>
            <p class="text-[10px] text-slate-400 mt-1">Grade</p>
        </div>
    </div>
    <div class="mt-4 flex items-center gap-4 text-xs text-slate-500">
        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-indigo-400"></span>Score: <strong class="text-slate-800"><?= $r['score'] ?>%</strong></span>
        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-400"></span>Verdict: <strong class="text-slate-800"><?= $r['verdict'] ?></strong></span>
        <span class="text-slate-400"><?= $r['generated_at'] ?></span>
    </div>
</div>

<!-- Summary -->
<div class="rounded-2xl border border-slate-200 bg-gradient-to-r from-indigo-50/50 to-sky-50/50 p-5 shadow-sm mb-5">
    <div class="flex gap-3">
        <svg class="h-6 w-6 shrink-0 text-indigo-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
        <div>
            <p class="text-sm font-medium text-slate-800">AI Analysis</p>
            <p class="text-xs text-slate-600 mt-1"><?= e($r['summary']) ?></p>
        </div>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-2 mb-5">
    <!-- Findings -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h4 class="text-xs font-semibold text-slate-700 mb-3 flex items-center gap-2">
            <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Findings
        </h4>
        <div class="space-y-2">
            <?php foreach ($r['findings'] as $fi): ?>
            <div class="rounded-lg bg-slate-50/50 px-3 py-2 text-xs text-slate-700"><?= $fi ?></div>
            <?php endforeach; ?>
            <?php if (empty($r['findings'])): ?><p class="text-xs text-slate-400">Belum ada data</p><?php endif; ?>
        </div>
    </div>

    <!-- Trends -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h4 class="text-xs font-semibold text-slate-700 mb-3 flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Trends
        </h4>
        <div class="space-y-2">
            <?php foreach ($r['trends'] as $tr): ?>
            <div class="rounded-lg bg-slate-50/50 px-3 py-2 text-xs text-slate-700">📊 <?= $tr ?></div>
            <?php endforeach; ?>
            <?php if (empty($r['trends'])): ?><p class="text-xs text-slate-400">Data trend belum tersedia (min 2 data poin)</p><?php endif; ?>
        </div>
    </div>
</div>

<!-- Recommendations -->
<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-5">
    <h4 class="text-xs font-semibold text-slate-700 mb-3 flex items-center gap-2">
        <svg class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        Rekomendasi Prioritas
    </h4>
    <div class="space-y-2">
        <?php foreach ($r['recommendations'] as $rec): ?>
        <div class="flex items-start gap-3 rounded-xl <?= $rec['priority'] === 'KRITIS' ? 'bg-rose-50 border border-rose-200' : ($rec['priority'] === 'TINGGI' ? 'bg-amber-50 border border-amber-200' : 'bg-indigo-50 border border-indigo-200') ?> px-4 py-3">
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider shrink-0 mt-0.5
                <?= $rec['priority'] === 'KRITIS' ? 'bg-rose-200 text-rose-800' : ($rec['priority'] === 'TINGGI' ? 'bg-amber-200 text-amber-800' : 'bg-indigo-200 text-indigo-800') ?>">
                <?= $rec['priority'] ?>
            </span>
            <p class="text-xs text-slate-700"><?= e($rec['text']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Score bar -->
<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between mb-2">
        <p class="text-xs font-medium text-slate-600">Security Score</p>
        <p class="text-xs font-semibold <?= $r['score'] >= 80 ? 'text-emerald-600' : ($r['score'] >= 50 ? 'text-amber-600' : 'text-rose-600') ?>"><?= $r['score'] ?>%</p>
    </div>
    <div class="h-3 w-full rounded-full bg-slate-200 overflow-hidden">
        <div class="h-full rounded-full transition-all duration-500 <?= $r['score'] >= 80 ? 'bg-emerald-500' : ($r['score'] >= 50 ? 'bg-amber-500' : 'bg-rose-500') ?>" style="width: <?= $r['score'] ?>%"></div>
    </div>
    <div class="flex justify-between text-[10px] text-slate-400 mt-1">
        <span>0%</span><span>Grade: <?= $r['grade'] ?> — <?= $r['verdict'] ?></span><span>100%</span>
    </div>
</div>
<?php endif; ?>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
