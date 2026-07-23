<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'server_scan';
require VIEW_PATH . '/layouts/main.php';
$r = $results;
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Server Security Scanner</span></nav>

<div class="grid gap-4 lg:grid-cols-3 mb-5">
    <div class="lg:col-span-2 space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Scanner</p>
                    <h2 class="text-lg font-semibold text-slate-900">Server Security Scanner</h2>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full text-lg font-bold <?= $r['score'] >= 80 ? 'bg-emerald-50 text-emerald-600 ring-4 ring-emerald-500/20' : ($r['score'] >= 50 ? 'bg-amber-50 text-amber-600 ring-4 ring-amber-500/20' : 'bg-rose-50 text-rose-600 ring-4 ring-rose-500/20') ?>"><?= $r['score'] ?></div>
                    <p class="text-[10px] text-slate-400 mt-1">Score</p>
                </div>
            </div>
            <button onclick="location.reload()" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Scan Sekarang</button>
        </div>

        <!-- Open Ports -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-700">Open Ports (<?= count($r['ports']) ?>)</p>
                <span class="text-[10px] <?= count($r['suspicious_ports']) > 0 ? 'text-rose-600' : 'text-emerald-600' ?>"><?= count($r['suspicious_ports']) ?> mencurigakan</span>
            </div>
            <?php if (empty($r['ports'])): ?><div class="p-4 text-xs text-slate-400">Tidak ada data port</div>
            <?php else: ?>
            <div class="max-h-48 overflow-y-auto divide-y divide-slate-50">
                <?php foreach ($r['ports'] as $p): ?>
                <div class="px-4 py-2 text-xs flex justify-between">
                    <span class="font-mono text-slate-700">Port <?= $p['port'] ?></span>
                    <span class="text-slate-500"><?= e($p['service']) ?> (<?= e($p['proto']) ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Suspicious Tasks -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-700">Scheduled Tasks (<?= count($r['scheduled_tasks']) ?>)</p>
                <span class="text-[10px] <?= count($r['suspicious_tasks']) > 0 ? 'text-rose-600' : 'text-emerald-600' ?>"><?= count($r['suspicious_tasks']) ?> mencurigakan</span>
            </div>
            <?php if (count($r['suspicious_tasks']) > 0): ?>
            <div class="divide-y divide-slate-50 max-h-48 overflow-y-auto">
                <?php foreach ($r['suspicious_tasks'] as $t): ?>
                <div class="px-4 py-2.5">
                    <p class="text-xs font-medium text-rose-700"><?= e($t['name']) ?></p>
                    <p class="text-[10px] text-rose-500"><?= e($t['reason']) ?></p>
                    <code class="text-[9px] text-slate-500 font-mono block truncate mt-0.5"><?= e($t['command']) ?></code>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?><div class="p-4 text-xs text-emerald-600">Tidak ada task mencurigakan</div><?php endif; ?>
        </div>

        <!-- Admin Accounts -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-700">Admin Accounts (<?= count($r['admin_accounts']) ?>)</p>
                <span class="text-[10px] <?= count($r['unknown_accounts']) > 0 ? 'text-rose-600' : 'text-emerald-600' ?>"><?= count($r['unknown_accounts']) ?> tidak dikenal</span>
            </div>
            <?php if (count($r['unknown_accounts']) > 0): ?>
            <div class="divide-y divide-slate-50">
                <?php foreach ($r['unknown_accounts'] as $u): ?>
                <div class="px-4 py-2.5 flex items-center gap-2">
                    <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <span class="text-xs font-medium text-rose-700"><?= e($u['username']) ?></span>
                    <span class="text-[10px] text-slate-500">Anggota grup Administrator</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?><div class="p-4 text-xs text-emerald-600">Semua akun dikenal</div><?php endif; ?>
        </div>

        <!-- Heartbeat -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-700 mb-2">Agent Heartbeat</p>
            <?php $hb = $r['heartbeat']; ?>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div><span class="text-slate-500">Status:</span> <span class="font-medium <?= $hb['status'] === 'healthy' ? 'text-emerald-600' : ($hb['status'] === 'warning' ? 'text-amber-600' : 'text-rose-600') ?>"><?= ucfirst($hb['status']) ?></span></div>
                <div><span class="text-slate-500">Last Beat:</span> <span class="text-slate-700"><?= $hb['last_beat'] ? timeAgo($hb['last_beat']) : '-' ?></span></div>
                <div><span class="text-slate-500">Total Reports:</span> <span class="text-slate-700"><?= $hb['total_beats'] ?></span></div>
                <div><span class="text-slate-500">Selang:</span> <span class="text-slate-700"><?= $hb['minutes_since_last'] ?> menit</span></div>
            </div>
        </div>
    </div>

    <!-- Auto Hardening Panel -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm h-fit">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">🛡️ Auto Hardening</h3>
        <p class="text-[10px] text-slate-500 mb-4">Terapkan perbaikan keamanan otomatis ke server ini</p>

        <?php if (count($r['fixes']) > 0): ?>
        <div class="space-y-2 mb-4">
            <p class="text-[10px] font-semibold text-emerald-700">✅ Perbaikan diterapkan:</p>
            <?php foreach ($r['fixes'] as $fix): ?>
            <div class="text-[10px] text-emerald-700 bg-emerald-50 rounded-lg px-3 py-2"><?= e($fix['message']) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('server-scan/harden') ?>">
            <?= csrfField() ?>
            <div class="space-y-2 mb-4">
                <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="fixes[]" value="disable_directory_listing" checked>
                    <span class="text-xs text-slate-700">Nonaktifkan Directory Listing</span>
                </label>
                <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="fixes[]" value="protect_sensitive_files" checked>
                    <span class="text-xs text-slate-700">Lindungi file sensitif (.env, .git, .sql)</span>
                </label>
                <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="fixes[]" value="disable_php_info" checked>
                    <span class="text-xs text-slate-700">Matikan PHP display_errors & expose_php</span>
                </label>
                <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="fixes[]" value="secure_htaccess" checked>
                    <span class="text-xs text-slate-700">Tambahkan security headers (XFO, XCTO, XSS)</span>
                </label>
            </div>
            <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700 transition-colors" onclick="return confirm('Terapkan hardening ke server ini? Semua perubahan bisa dibatalkan dengan restore .htaccess backup.')">Terapkan Hardening</button>
        </form>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
