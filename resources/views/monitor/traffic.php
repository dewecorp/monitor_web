<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'traffic'; require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Traffic</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Traffic Monitoring</p>
        <h2 class="text-xl font-semibold text-slate-900">Analisa traffic website</h2>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Perbandingan Traffic 7 Hari</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500 w-8">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-[11px] uppercase tracking-wide text-slate-500">Website</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Pengunjung</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Page Views</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Bandwidth</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Rata-rata Response</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php $i = 1; foreach ($trafficSummary as $t): ?>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-3 py-3 text-center text-[11px] text-slate-400"><?= $i++ ?></td>
                    <td class="px-4 py-3 font-medium text-slate-800"><?= e($t['nama_website']) ?></td>
                    <td class="px-4 py-3 text-right text-slate-700"><?= number_format($t['visitors']) ?></td>
                    <td class="px-4 py-3 text-right text-slate-700"><?= number_format($t['page_views']) ?></td>
                    <td class="px-4 py-3 text-right text-slate-700"><?= ($t['bandwidth'] ?? 0) >= 1024 ? round(($t['bandwidth'] ?? 0)/1024,1).' GB' : round(($t['bandwidth'] ?? 0),1).' MB' ?></td>
                    <td class="px-4 py-3 text-right <?= ($t['avg_response'] ?? 0) < 500 ? 'text-emerald-600' : (($t['avg_response'] ?? 0) < 1000 ? 'text-amber-600' : 'text-rose-600') ?>"><?= round($t['avg_response'] ?? 0) ?> ms</td>
                    <td class="px-4 py-3 text-center">
                        <a href="<?= url('monitor/traffic/' . $t['id']) ?>" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-medium text-indigo-600 hover:bg-indigo-100 transition-colors">Detail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
