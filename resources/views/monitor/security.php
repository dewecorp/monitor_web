<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'security'; require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Keamanan Website</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Security Monitoring</p>
        <h2 class="text-xl font-semibold text-slate-900">Skor keamanan website</h2>
    </div>
    <button onclick="checkAllWebsites()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        Scan All
    </button>
</div>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="table-wrap">
        <table class="min-w-full text-xs divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500 w-8">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-[11px] uppercase tracking-wide text-slate-500">Website</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Skor</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">SSL</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">HSTS</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">XSS</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Headers</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Last Scan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php $i = 1; foreach ($websites as $w): ?>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-3 py-3 text-center text-[11px] text-slate-400"><?= $i++ ?></td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-800"><?= e($w['nama_website']) ?></p>
                        <p class="text-[10px] text-slate-400 truncate max-w-[160px]"><?= e($w['url']) ?></p>
                    </td>
                    <td class="px-4 py-3 text-center"><?= securityBadge((int)($w['score'] ?? 0)) ?></td>
                    <td class="px-4 py-3 text-center"><?= ($w['ssl_valid'] ?? 0) ? '<span class="text-emerald-600 font-bold">&#10003;</span>' : '<span class="text-rose-600 font-bold">&#10007;</span>' ?></td>
                    <td class="px-4 py-3 text-center"><?= ($w['has_hsts'] ?? 0) ? '<span class="text-emerald-600 font-bold">&#10003;</span>' : '<span class="text-rose-600 font-bold">&#10007;</span>' ?></td>
                    <td class="px-4 py-3 text-center"><?= ($w['has_xss_protection'] ?? 0) ? '<span class="text-emerald-600 font-bold">&#10003;</span>' : '<span class="text-rose-600 font-bold">&#10007;</span>' ?></td>
                    <td class="px-4 py-3 text-center"><?= ($w['headers_secure'] ?? 0) ? '<span class="text-emerald-600 font-bold">&#10003;</span>' : '<span class="text-rose-600 font-bold">&#10007;</span>' ?></td>
                    <td class="px-4 py-3 text-right text-slate-400"><?= ($w['last_scan'] ?? '') ? timeAgo($w['last_scan']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
