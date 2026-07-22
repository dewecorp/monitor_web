<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'agent';
require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Server Monitoring</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Monitoring Agent</p>
        <h2 class="text-xl font-semibold text-slate-900">Server Status</h2>
    </div>
</div>

<?php if (empty($servers)): ?>
<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
    <svg class="h-16 w-16 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
    <p class="text-sm font-semibold text-slate-700">Belum ada server terdaftar</p>
    <p class="text-xs text-slate-500 mt-1">Deploy <code class="bg-slate-100 px-1.5 py-0.5 rounded text-[10px] font-mono">agent.php</code> ke server tujuan dan setup cron untuk mengirim data.</p>
    <div class="mt-4 text-left max-w-lg mx-auto bg-slate-50 rounded-xl p-4 text-[11px] font-mono text-slate-700">
        <p class="text-slate-500 mb-1"># Download & konfigurasi:</p>
        curl -o agent.php <?= url('agent.php') ?><br>
        php agent.php http://your-server/monitor_web YOUR_API_KEY server_name
    </div>
</div>
<?php else: ?>
<div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($servers as $s): ?>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-slate-800"><?= e($s['server_name']) ?></h3>
            <span class="flex items-center gap-1 rounded-full px-2.5 py-1 text-[9px] font-semibold <?= ($s['cpu'] ?? 100) < 80 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                <span class="h-1.5 w-1.5 rounded-full <?= ($s['cpu'] ?? 100) < 80 ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                <?= timeAgo($s['last_report']) ?>
            </span>
        </div>

        <div class="space-y-2">
            <div>
                <div class="flex justify-between text-[10px] text-slate-500 mb-0.5">
                    <span>CPU</span><span><?= $s['cpu'] ?? 'N/A' ?>%</span>
                </div>
                <div class="h-1.5 rounded-full bg-slate-200"><div class="h-full rounded-full <?= ($s['cpu'] ?? 0) >= 90 ? 'bg-rose-500' : (($s['cpu'] ?? 0) >= 70 ? 'bg-amber-500' : 'bg-emerald-500') ?>" style="width: <?= min($s['cpu'] ?? 0, 100) ?>%"></div></div>
            </div>
            <div>
                <div class="flex justify-between text-[10px] text-slate-500 mb-0.5">
                    <span>RAM</span><span><?= $s['memory'] ?? 'N/A' ?>%</span>
                </div>
                <div class="h-1.5 rounded-full bg-slate-200"><div class="h-full rounded-full <?= ($s['memory'] ?? 0) >= 90 ? 'bg-rose-500' : (($s['memory'] ?? 0) >= 70 ? 'bg-amber-500' : 'bg-emerald-500') ?>" style="width: <?= min($s['memory'] ?? 0, 100) ?>%"></div></div>
            </div>
            <div>
                <div class="flex justify-between text-[10px] text-slate-500 mb-0.5">
                    <span>Disk</span><span><?= $s['disk'] ?? 'N/A' ?>%</span>
                </div>
                <div class="h-1.5 rounded-full bg-slate-200"><div class="h-full rounded-full <?= ($s['disk'] ?? 0) >= 90 ? 'bg-rose-500' : (($s['disk'] ?? 0) >= 70 ? 'bg-amber-500' : 'bg-emerald-500') ?>" style="width: <?= min($s['disk'] ?? 0, 100) ?>%"></div></div>
            </div>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100 grid grid-cols-2 gap-2 text-[10px] text-slate-500">
            <span>PHP: <?= e($s['php_version'] ?? '-') ?></span>
            <span>Uptime: <?= e($s['uptime'] ?? '-') ?></span>
            <span>Reports: <?= $s['total_reports'] ?></span>
        </div>

        <div class="mt-3 pt-3 border-t border-slate-100 flex gap-2">
            <form method="POST" action="<?= url('agent/restart') ?>" class="inline">
                <?= csrfField() ?>
                <input type="hidden" name="service" value="apache">
                <button type="submit" class="rounded-lg border border-slate-200 px-2.5 py-1 text-[9px] text-slate-600 hover:bg-slate-50" onclick="return confirm('Restart Apache?')">Restart Apache</button>
            </form>
            <form method="POST" action="<?= url('agent/restart') ?>" class="inline">
                <?= csrfField() ?>
                <input type="hidden" name="service" value="mysql">
                <button type="submit" class="rounded-lg border border-slate-200 px-2.5 py-1 text-[9px] text-slate-600 hover:bg-slate-50" onclick="return confirm('Restart MySQL?')">Restart MySQL</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
