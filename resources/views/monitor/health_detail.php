<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'health'; require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <a href="<?= url('monitor/health') ?>" class="text-slate-600 hover:text-indigo-600">Kesehatan</a> <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium"><?= e($website['nama_website']) ?></span></nav>

<div class="max-w-3xl mx-auto">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm mb-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-semibold text-slate-800"><?= e($website['nama_website']) ?></h2>
                <p class="text-[11px] text-slate-400"><?= e($website['url']) ?></p>
            </div>
            <button onclick="checkSingleWebsite(<?= $website['id'] ?>)" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition-colors">Check Now</button>
        </div>

        <?php if (!empty($history)): $latest = $history[0]; ?>
        <div class="grid gap-4 sm:grid-cols-3 mb-4">
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Status</p>
                <div class="mt-1"><?= statusBadge((bool)($latest['is_up'] ?? 0)) ?></div>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">HTTP Code</p>
                <p class="text-lg font-semibold <?= ($latest['status_code'] ?? 0) >= 200 && ($latest['status_code'] ?? 0) < 400 ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $latest['status_code'] ?? '-' ?></p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Response Time</p>
                <p class="text-lg font-semibold <?= ($latest['response_time_ms'] ?? 0) < 500 ? 'text-emerald-600' : (($latest['response_time_ms'] ?? 0) < 1000 ? 'text-amber-600' : 'text-rose-600') ?>"><?= $latest['response_time_ms'] ?? '-' ?> ms</p>
            </div>
        </div>

        <div class="mt-4">
            <p class="text-[11px] font-semibold text-slate-700 mb-2">Riwayat Pengecekan (30 terakhir)</p>
            <div class="space-y-1 max-h-80 overflow-y-auto scroll-thin">
                <?php foreach ($history as $h): ?>
                <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full <?= $h['is_up'] ? 'bg-emerald-400' : 'bg-rose-400' ?>"></span>
                        <span class="<?= $h['is_up'] ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $h['is_up'] ? 'Online' : 'Offline' ?></span>
                    </div>
                    <span class="text-slate-500"><?= $h['status_code'] ?></span>
                    <span class="text-slate-500"><?= $h['response_time_ms'] ?> ms</span>
                    <span class="text-slate-400"><?= timeAgo($h['checked_at']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-8">
            <p class="text-xs text-slate-400">Belum ada data pengecekan. Klik "Check Now" untuk memulai.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
