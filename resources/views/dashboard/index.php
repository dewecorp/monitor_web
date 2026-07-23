<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'dashboard';
require VIEW_PATH . '/layouts/main.php';

$s = $summary;
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Ringkasan</span></nav>

<div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Overview</p>
        <h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">Status Monitoring</h2>
    </div>
    <div class="flex items-center gap-2 text-[11px] text-slate-500">
        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-600 ring-1 ring-emerald-500/20"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>Live</span>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm w-full" style="width:100%;max-width:100%">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Website Online</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900"><?= $s['online'] ?>/<?= $s['total'] ?></p>
            </div>
            <div class="rounded-full bg-emerald-50 p-2.5">
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
        </div>
        <div class="mt-3 flex items-center justify-between text-[11px]">
            <span class="text-emerald-600 font-medium"><?= $s['online'] ?> aktif</span>
            <span class="text-slate-400">Total: <?= $s['total'] ?></span>
        </div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm w-full" style="width:100%;max-width:100%">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Skor Keamanan</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900"><?= $s['avg_security'] ?>%</p>
            </div>
            <div class="rounded-full bg-amber-50 p-2.5">
                <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
        </div>
        <div class="mt-3 flex items-center justify-between text-[11px]">
            <span class="font-medium text-slate-500">Rata-rata semua website</span>
            <span class="text-slate-400">Skor 0-100</span>
        </div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm w-full" style="width:100%;max-width:100%">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Response Time</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900"><?= $s['avg_response'] ?> ms</p>
            </div>
            <div class="rounded-full bg-sky-50 p-2.5">
                <svg class="h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <div class="mt-3 flex items-center justify-between text-[11px]">
            <span class="font-medium <?= $s['avg_response'] < 500 ? 'text-emerald-600' : ($s['avg_response'] < 1000 ? 'text-amber-600' : 'text-rose-600') ?>">24 jam terakhir</span>
            <span class="text-slate-400">Rata-rata</span>
        </div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm w-full" style="width:100%;max-width:100%">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Website Offline</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900"><?= $s['offline'] ?></p>
            </div>
            <div class="rounded-full bg-rose-50 p-2.5">
                <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <div class="mt-3 flex items-center justify-between text-[11px]">
            <span class="text-rose-600 font-medium">Butuh perhatian</span>
            <span class="text-slate-400">Periksa segera</span>
        </div>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-3 mb-6">
    <!-- Website Status Table -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-2" style="width:100%;max-width:100%">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Status Website</p>
                <p class="text-sm font-semibold text-slate-800">Monitoring real-time</p>
            </div>
            <button onclick="checkAllWebsites()" class="rounded-full bg-indigo-50 px-3 py-1.5 text-[11px] font-medium text-indigo-600 hover:bg-indigo-100 transition-colors">Refresh</button>
        </div>
        <div class="table-wrap">
            <table style="width:100%">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-2 py-2.5 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500 w-6">#</th><th class="px-3 py-2.5 text-left font-semibold text-[11px] uppercase tracking-wide text-slate-500">Website</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-3 py-2.5 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Response</th>
                        <th class="px-3 py-2.5 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Last Check</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php $i1 = 1; foreach ($websites as $w): $lastCheck = $w['last_check'] ?? null; ?>
                    <tr class="hover:bg-slate-50/50"><td class="px-2 py-2.5 text-center text-[11px] text-slate-400"><?= $i1++ ?></td>
                        <td class="px-3 py-2.5">
                            <p class="text-xs font-medium text-slate-800"><?= e($w['nama_website']) ?></p>
                            <p class="text-[10px] text-slate-400 truncate max-w-[160px]"><?= e($w['url']) ?></p>
                        </td>
                        <td class="px-3 py-2.5 text-center"><?= statusBadge((bool)($w['is_up'] ?? 0)) ?></td>
                        <td class="px-3 py-2.5 text-right text-xs font-medium <?= ($w['response_time_ms'] ?? 999) < 500 ? 'text-emerald-600' : (($w['response_time_ms'] ?? 999) < 1000 ? 'text-amber-600' : 'text-rose-600') ?>"><?= $w['response_time_ms'] ?? '-' ?> ms</td>
                        <td class="px-3 py-2.5 text-right text-[11px] text-slate-400"><?= $lastCheck ? timeAgo($lastCheck) : 'Belum diperiksa' ?></td>
                        <td class="px-3 py-2.5 text-center">
                            <button onclick="checkSingleWebsite(<?= $w['id'] ?>)" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-slate-50 hover:border-indigo-300 hover:text-indigo-600 transition-colors" title="Check">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($websites)): ?>
                    <tr><td colspan="6" class="px-3 py-8 text-center text-xs text-slate-400">Belum ada website. <a href="<?= url('websites') ?>" class="text-indigo-600 font-medium hover:underline">Tambah sekarang</a></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm w-full" style="width:100%;max-width:100%">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Aktivitas</p>
                <p class="text-sm font-semibold text-slate-800">Log terbaru</p>
            </div>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500"><?= count($activities) ?></span>
        </div>
        <div class="space-y-2 max-h-[360px] overflow-y-auto scroll-thin">
            <?php foreach ($activities as $log): ?>
            <div class="flex items-start gap-2.5 rounded-lg bg-slate-50/50 p-2.5">
                <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-indigo-400"></span>
                <div class="min-w-0">
                    <p class="text-[11px] text-slate-700"><?= e($log['detail'] ?? $log['aksi']) ?></p>
                    <p class="text-[10px] text-slate-400"><?= timeAgo($log['created_at']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($activities)): ?>
            <p class="text-[11px] text-slate-400 text-center py-4">Belum ada aktivitas</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Status Boxes -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4" style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem">
    <div class="flex items-center gap-1.5 rounded-lg bg-emerald-50 border border-emerald-200 px-2 py-1.5 text-[10px] truncate">
        <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span>
        <span class="font-semibold text-emerald-700">Online: <?= $summary['online'] ?></span>
    </div>
    <div class="flex items-center gap-1.5 rounded-lg bg-rose-50 border border-rose-200 px-2 py-1.5 text-[10px] truncate">
        <span class="h-2 w-2 rounded-full bg-rose-500 shrink-0"></span>
        <span class="font-semibold text-rose-700">Offline: <?= $summary['offline'] ?></span>
    </div>
    <div class="flex items-center gap-1.5 rounded-lg bg-sky-50 border border-sky-200 px-2 py-1.5 text-[10px] truncate">
        <span class="font-semibold text-sky-700">Response: <?= $summary['avg_response'] ?> ms</span>
    </div>
    <div class="flex items-center gap-1.5 rounded-lg bg-indigo-50 border border-indigo-200 px-2 py-1.5 text-[10px] truncate">
        <span class="font-semibold text-indigo-700">Keamanan: <?= $summary['avg_security'] ?>%</span>
    </div>
</div>

<!-- Chart Widgets -->

<!-- Chart Widgets -->
<div class="grid gap-4 lg:grid-cols-2 mb-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm w-full" style="width:100%;max-width:100%">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Uptime 7 Hari</p>
            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-600">Rata-rata</span>
        </div>
        <div class="relative h-48"><canvas id="uptimeChart"></canvas></div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm w-full" style="width:100%;max-width:100%">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Response Time 24 Jam</p>
            <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-600">ms</span>
        </div>
        <div class="relative h-48"><canvas id="responseChart"></canvas></div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm w-full" style="width:100%;max-width:100%">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Skor Keamanan</p>
            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-600">0-100</span>
        </div>
        <div class="relative h-48"><canvas id="securityChart"></canvas></div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm w-full" style="width:100%;max-width:100%">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Aktivitas 7 Hari</p>
            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-medium text-indigo-600">Log</span>
        </div>
        <div class="relative h-48"><canvas id="activityChart"></canvas></div>
    </div>
</div>

<!-- Traffic Chart Real-time -->
<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm mb-6" style="width:100%;max-width:100%">
    <div class="flex items-center justify-between mb-3">
        <div>
            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Real-time Traffic</p>
            <p class="text-sm font-semibold text-slate-800">Traffic 7 hari terakhir</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span class="text-[10px] text-slate-400">Live</span>
            <button onclick="refreshTrafficChart()" class="rounded-full bg-indigo-50 px-2.5 py-1 text-[9px] font-medium text-indigo-600 hover:bg-indigo-100 ml-2">Refresh</button>
        </div>
    </div>
    <div class="relative h-64">
        <canvas id="trafficRealtimeChart"></canvas>
    </div>
</div>

<!-- Traffic Section -->
<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm mb-6" style="width:100%;max-width:100%">
    <div class="flex items-center justify-between mb-3">
        <div>
            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Traffic</p>
            <p class="text-sm font-semibold text-slate-800">Perbandingan traffic 7 hari</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="min-w-full text-xs divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-2 py-2.5 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500 w-8">#</th><th class="px-3 py-2 text-left font-semibold text-[11px] uppercase tracking-wide text-slate-500">Website</th>
                    <th class="px-3 py-2 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Pengunjung</th>
                    <th class="px-3 py-2 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Page Views</th>
                    <th class="px-3 py-2 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Bandwidth</th>
                    <th class="px-3 py-2 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Rata-rata Response</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php $i = 1; foreach ($trafficData as $t): ?>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-2 py-2 text-center text-[11px] text-slate-400"><?= $i++ ?></td>
                    <td class="px-3 py-2 font-medium text-slate-800"><?= e($t['nama_website']) ?></td>
                    <td class="px-3 py-2 text-right text-slate-700"><?= number_format($t['visitors']) ?></td>
                    <td class="px-3 py-2 text-right text-slate-700"><?= number_format($t['page_views']) ?></td>
                    <td class="px-3 py-2 text-right text-slate-700"><?= ($t['bandwidth'] ?? 0) >= 1024 ? round(($t['bandwidth'] ?? 0)/1024,1).' GB' : round(($t['bandwidth'] ?? 0),1).' MB' ?></td>
                    <td class="px-3 py-2 text-right <?= ($t['avg_response'] ?? 0) < 500 ? 'text-emerald-600' : (($t['avg_response'] ?? 0) < 1000 ? 'text-amber-600' : 'text-rose-600') ?>"><?= round($t['avg_response'] ?? 0) ?> ms</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch(BASE_URL + 'api/chart-data')
        .then(r => r.json())
        .then(d => {
            // Uptime Chart
            new Chart(document.getElementById('uptimeChart'), {
                type: 'line',
                data: {
                    labels: d.uptime.map(function(u) { return u.date; }),
                    datasets: [{
                        label: 'Uptime %',
                        data: d.uptime.map(function(u) { return u.uptime; }),
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34,197,94,0.15)',
                        fill: true, tension: 0.4, pointRadius: 3,
                        pointBackgroundColor: '#22c55e'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                        y: { min: 95, max: 100, grid: { color: 'rgba(148,163,184,0.12)' }, ticks: { font: { size: 9 }, callback: function(v) { return v + '%'; } } }
                    }
                }
            });

            // Response Time Chart
            var respLabels = d.response_time.map(function(r) { return r.hour; });
            var respData = d.response_time.map(function(r) { return Math.round(r.avg_resp); });
            new Chart(document.getElementById('responseChart'), {
                type: 'bar',
                data: {
                    labels: respLabels,
                    datasets: [{
                        label: 'ms',
                        data: respData,
                        backgroundColor: respData.map(function(v) { return v < 500 ? 'rgba(34,197,94,0.6)' : v < 1000 ? 'rgba(245,158,11,0.6)' : 'rgba(239,68,68,0.6)'; }),
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 8 }, maxTicksLimit: 12 } },
                        y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.12)' }, ticks: { font: { size: 9 } } }
                    }
                }
            });

            // Security Score Chart
            var secLabels = d.security.map(function(s) { return s.nama_website.length > 10 ? s.nama_website.substring(0,10)+'..' : s.nama_website; });
            var secScores = d.security.map(function(s) { return s.score; });
            new Chart(document.getElementById('securityChart'), {
                type: 'bar',
                data: {
                    labels: secLabels,
                    datasets: [{
                        label: 'Skor',
                        data: secScores,
                        backgroundColor: secScores.map(function(v) { return v >= 80 ? 'rgba(34,197,94,0.7)' : v >= 50 ? 'rgba(245,158,11,0.7)' : 'rgba(239,68,68,0.7)'; }),
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { min: 0, max: 100, grid: { color: 'rgba(148,163,184,0.12)' }, ticks: { font: { size: 9 } } },
                        y: { grid: { display: false }, ticks: { font: { size: 9 } } }
                    }
                }
            });

            // Activity Chart
            new Chart(document.getElementById('activityChart'), {
                type: 'line',
                data: {
                    labels: d.activity.map(function(a) { return a.date ? a.date.substring(5) : ''; }),
                    datasets: [{
                        label: 'Aktivitas',
                        data: d.activity.map(function(a) { return a.count; }),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.15)',
                        fill: true, tension: 0.4, pointRadius: 3
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                        y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.12)' }, ticks: { font: { size: 9 } } }
                    }
                }
            });
        });

        // Traffic Realtime Chart (static, manual refresh)
        var trafficData = <?= json_encode($trafficData) ?>;
        var trafficChart = new Chart(document.getElementById('trafficRealtimeChart'), {
            type: 'bar',
            data: {
                labels: trafficData.map(function(t) { return t.nama_website ? t.nama_website.substring(0,12) : ''; }),
                datasets: [
                    {
                        label: 'Pengunjung',
                        data: trafficData.map(function(t) { return t.visitors || 0; }),
                        backgroundColor: 'rgba(99,102,241,0.6)',
                        borderRadius: 4,
                        order: 2
                    },
                    {
                        label: 'Page Views',
                        data: trafficData.map(function(t) { return t.page_views || 0; }),
                        backgroundColor: 'rgba(34,197,94,0.6)',
                        borderRadius: 4,
                        order: 2
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { boxWidth: 8, font: { size: 10 } } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.12)' }, ticks: { font: { size: 9 } } }
                }
            }
        });
});

function refreshTrafficChart() {
    location.reload();
}
</script>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
