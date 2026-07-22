<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'reports'; require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Laporan</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Report Center</p>
        <h2 class="text-xl font-semibold text-slate-900">Laporan Monitoring</h2>
    </div>
    <div class="flex gap-1">
        <a href="?period=daily" class="rounded-full <?= $period === 'daily' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' ?> px-3 py-1.5 text-[11px] font-medium hover:bg-indigo-500 hover:text-white transition-colors">Harian</a>
        <a href="?period=weekly" class="rounded-full <?= $period === 'weekly' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' ?> px-3 py-1.5 text-[11px] font-medium">Mingguan</a>
        <a href="?period=monthly" class="rounded-full <?= $period === 'monthly' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' ?> px-3 py-1.5 text-[11px] font-medium">Bulanan</a>
    </div>
</div>

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] uppercase tracking-wide text-slate-400">Total Website</p>
        <p class="text-xl font-semibold text-slate-900 mt-1"><?= $summary['total'] ?></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] uppercase tracking-wide text-slate-400">Pengecekan</p>
        <p class="text-xl font-semibold text-slate-900 mt-1"><?= number_format($totalChecks) ?></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] uppercase tracking-wide text-slate-400">Rata-rata Response</p>
        <p class="text-xl font-semibold text-slate-900 mt-1"><?= $avgResponse ?> ms</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-[10px] uppercase tracking-wide text-slate-400">Insiden</p>
        <p class="text-xl font-semibold text-slate-900 mt-1"><?= $incidents ?></p>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-5">
    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Status Website</p>
        <a href="?export=csv&period=<?= $period ?>" class="rounded-full bg-indigo-50 px-3 py-1.5 text-[11px] font-medium text-indigo-600 hover:bg-indigo-100 transition-colors">Export CSV</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500 w-8">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-[11px] uppercase tracking-wide text-slate-500">Website</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Response</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Security</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Last Check</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php $i = 1; foreach ($websites as $w): ?>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-3 py-3 text-center text-[11px] text-slate-400"><?= $i++ ?></td>
                    <td class="px-4 py-3"><p class="font-medium text-slate-800"><?= e($w['nama_website']) ?></p><p class="text-[10px] text-slate-400"><?= e($w['url']) ?></p></td>
                    <td class="px-4 py-3 text-center"><?= statusBadge((bool)($w['is_up'] ?? 0)) ?></td>
                    <td class="px-4 py-3 text-right <?= ($w['response_time_ms'] ?? 999) < 500 ? 'text-emerald-600' : (($w['response_time_ms'] ?? 999) < 1000 ? 'text-amber-600' : 'text-rose-600') ?>"><?= $w['response_time_ms'] ?? '-' ?> ms</td>
                    <td class="px-4 py-3 text-right"><?= $w['security_score'] ?? '-' ?></td>
                    <td class="px-4 py-3 text-right text-slate-400"><?= ($w['last_check'] ?? '') ? timeAgo($w['last_check']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400 mb-3">Aktivitas Terbaru</p>
    <div class="space-y-2 max-h-64 overflow-y-auto">
        <?php foreach ($activities as $log): ?>
        <div class="flex items-start gap-2 rounded-lg bg-slate-50/50 p-2">
            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-indigo-400"></span>
            <div class="min-w-0"><p class="text-[11px] text-slate-700"><?= e($log['detail'] ?? $log['aksi']) ?></p><p class="text-[10px] text-slate-400"><?= timeAgo($log['created_at']) ?></p></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
