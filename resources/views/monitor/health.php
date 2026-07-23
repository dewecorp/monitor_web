<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'health'; require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Kesehatan Website</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Health Monitoring</p>
        <h2 class="text-xl font-semibold text-slate-900">Status kesehatan semua website</h2>
    </div>
    <button onclick="checkAllWebsites()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        Check All
    </button>
</div>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="table-wrap">
        <table class="min-w-full text-xs divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500 w-8">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-[11px] uppercase tracking-wide text-slate-500">Website</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">HTTP Code</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Response Time</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Last Check</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php $i = 1; foreach ($websites as $w): ?>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-3 py-3 text-center text-[11px] text-slate-400"><?= $i++ ?></td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-800"><?= e($w['nama_website']) ?></p>
                        <p class="text-[10px] text-slate-400 truncate max-w-[180px]"><?= e($w['url']) ?></p>
                    </td>
                    <td class="px-4 py-3 text-center"><?= statusBadge($w['is_up'] ?? 0) ?></td>
                    <td class="px-4 py-3 text-center"><span class="font-mono font-medium <?= ($w['status_code'] ?? 0) >= 200 && ($w['status_code'] ?? 0) < 400 ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $w['status_code'] ?? '-' ?></span></td>
                    <td class="px-4 py-3 text-right font-medium <?= ($w['response_time_ms'] ?? 0) < 500 ? 'text-emerald-600' : (($w['response_time_ms'] ?? 0) < 1000 ? 'text-amber-600' : 'text-rose-600') ?>"><?= $w['response_time_ms'] ?? '-' ?> ms</td>
                    <td class="px-4 py-3 text-right text-slate-400"><?= ($w['last_check'] ?? '') ? timeAgo($w['last_check']) : '-' ?></td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="checkSingleWebsite(<?= $w['id'] ?>)" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 hover:bg-slate-50 hover:border-indigo-300 hover:text-indigo-600 transition-colors" title="Check">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
