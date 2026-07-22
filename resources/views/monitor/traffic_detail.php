<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'traffic'; require VIEW_PATH . '/layouts/main.php';

$chartLabels = []; $chartVisitors = []; $chartViews = [];
foreach ($chart as $c) {
    $chartLabels[] = date('d M', strtotime($c['logged_date']));
    $chartVisitors[] = $c['visitors'];
    $chartViews[] = $c['page_views'];
}
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <a href="<?= url('monitor/traffic') ?>" class="text-slate-600 hover:text-indigo-600">Traffic</a> <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium"><?= e($website['nama_website']) ?></span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <h2 class="text-lg font-semibold text-slate-900"><?= e($website['nama_website']) ?></h2>
        <p class="text-xs text-slate-400"><?= e($website['url']) ?></p>
    </div>
    <div class="flex gap-1">
        <a href="?id=<?= $website['id'] ?>&days=7" class="rounded-full <?= $days == 7 ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' ?> px-3 py-1.5 text-[11px] font-medium hover:bg-indigo-500 hover:text-white transition-colors">7 Hari</a>
        <a href="?id=<?= $website['id'] ?>&days=30" class="rounded-full <?= $days == 30 ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' ?> px-3 py-1.5 text-[11px] font-medium hover:bg-indigo-500 hover:text-white transition-colors">30 Hari</a>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-3 mb-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-2">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400 mb-3">Grafik Traffic</p>
        <?php if (!empty($chartLabels)): ?>
        <div class="relative h-64 w-full"><canvas id="trafficChart"></canvas></div>
        <?php else: ?>
        <div class="flex items-center justify-center h-56 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-xs text-slate-400">Belum ada data traffic</div>
        <?php endif; ?>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400 mb-3">Ringkasan <?= $days ?> Hari</p>
        <div class="space-y-3">
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Pengunjung</p>
                <p class="text-lg font-semibold text-slate-800"><?= number_format($summary['total_visitors'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Page Views</p>
                <p class="text-lg font-semibold text-slate-800"><?= number_format($summary['total_views'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Bandwidth</p>
                <p class="text-lg font-semibold text-slate-800"><?= ($summary['total_bandwidth'] ?? 0) >= 1024 ? round(($summary['total_bandwidth'] ?? 0)/1024,1).' GB' : round(($summary['total_bandwidth'] ?? 0),1).' MB' ?></p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Rata-rata Response</p>
                <p class="text-lg font-semibold text-slate-800"><?= round($summary['avg_response'] ?? 0) ?> ms</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($chartLabels)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('trafficChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            { label: 'Pengunjung', data: <?= json_encode($chartVisitors) ?>, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.4, pointRadius: 3 },
            { label: 'Page Views', data: <?= json_encode($chartViews) ?>, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)', fill: true, tension: 0.4, pointRadius: 3 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: true, labels: { boxWidth: 8, font: { size: 10 } } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 9 } } },
            y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.12)' }, ticks: { font: { size: 9 } } }
        }
    }
});
</script>
<?php endif; ?>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
