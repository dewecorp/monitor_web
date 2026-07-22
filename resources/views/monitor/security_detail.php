<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'security'; require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <a href="<?= url('monitor/security') ?>" class="text-slate-600 hover:text-indigo-600">Keamanan</a> <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium"><?= e($website['nama_website']) ?></span></nav>

<div class="max-w-3xl mx-auto">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm mb-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-semibold text-slate-800"><?= e($website['nama_website']) ?></h2>
                <p class="text-[11px] text-slate-400"><?= e($website['url']) ?></p>
            </div>
            <button onclick="checkSingleWebsite(<?= $website['id'] ?>)" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition-colors">Scan Ulang</button>
        </div>

        <?php if ($security): $s = $security; ?>
        <div class="grid gap-4 sm:grid-cols-2 mb-4">
            <div class="rounded-xl bg-slate-50 p-4 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full text-2xl font-bold mb-2 <?= ($s['score'] ?? 0) >= 80 ? 'bg-emerald-50 text-emerald-600 ring-4 ring-emerald-500/20' : (($s['score'] ?? 0) >= 50 ? 'bg-amber-50 text-amber-600 ring-4 ring-amber-500/20' : 'bg-rose-50 text-rose-600 ring-4 ring-rose-500/20') ?>"><?= $s['score'] ?? 0 ?></div>
                <p class="text-xs font-medium text-slate-600">Skor Keamanan</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-[11px] font-medium text-slate-500 mb-2">Security Headers</p>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between"><span class="text-slate-500">XSS Protection</span><?= $s['has_xss_protection'] ? '<span class="text-emerald-600 font-semibold">Aktif</span>' : '<span class="text-rose-600 font-semibold">Tidak</span>' ?></div>
                    <div class="flex justify-between"><span class="text-slate-500">HSTS</span><?= $s['has_hsts'] ? '<span class="text-emerald-600 font-semibold">Aktif</span>' : '<span class="text-rose-600 font-semibold">Tidak</span>' ?></div>
                    <div class="flex justify-between"><span class="text-slate-500">CSP</span><?= $s['has_csp'] ? '<span class="text-emerald-600 font-semibold">Aktif</span>' : '<span class="text-rose-600 font-semibold">Tidak</span>' ?></div>
                    <div class="flex justify-between"><span class="text-slate-500">Secure Headers</span><?= $s['headers_secure'] ? '<span class="text-emerald-600 font-semibold">Aktif</span>' : '<span class="text-rose-600 font-semibold">Tidak</span>' ?></div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3 mb-4">
            <div class="rounded-xl bg-slate-50 p-3 text-xs">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 mb-1">Exposure</p>
                <div class="space-y-1">
                    <div class="flex justify-between"><span>.env</span><?= $s['env_exposed'] ? '<span class="text-rose-600 font-bold">Terbuka</span>' : '<span class="text-emerald-600">Aman</span>' ?></div>
                    <div class="flex justify-between"><span>.git</span><?= $s['git_exposed'] ? '<span class="text-rose-600 font-bold">Terbuka</span>' : '<span class="text-emerald-600">Aman</span>' ?></div>
                    <div class="flex justify-between"><span>Backup</span><?= $s['backup_exposed'] ? '<span class="text-rose-600 font-bold">Terbuka</span>' : '<span class="text-emerald-600">Aman</span>' ?></div>
                    <div class="flex justify-between"><span>Dir Listing</span><?= $s['directory_listing'] ? '<span class="text-rose-600 font-bold">Terbuka</span>' : '<span class="text-emerald-600">Aman</span>' ?></div>
                </div>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 text-xs">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 mb-1">SSL Certificate</p>
                <?php if ($ssl): ?>
                <div class="space-y-1">
                    <div class="flex justify-between"><span>Status</span><?= $ssl['ssl_valid'] ? '<span class="text-emerald-600">Valid</span>' : '<span class="text-rose-600">Invalid</span>' ?></div>
                    <div class="flex justify-between"><span>Issuer</span><span class="text-slate-600 truncate max-w-[100px]"><?= e($ssl['ssl_issuer'] ?? '-') ?></span></div>
                    <div class="flex justify-between"><span>Expires</span><span class="text-slate-600"><?= $ssl['ssl_expires'] ? date('d M Y', strtotime($ssl['ssl_expires'])) : '-' ?></span></div>
                    <div class="flex justify-between"><span>Sisa</span><span class="<?= ($ssl['ssl_remaining_days'] ?? 0) < 30 ? 'text-rose-600 font-semibold' : 'text-emerald-600' ?>"><?= $ssl['ssl_remaining_days'] ?? '-' ?> hari</span></div>
                </div>
                <?php else: ?>
                <p class="text-slate-400 py-2">Data SSL tidak tersedia</p>
                <?php endif; ?>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 text-xs">
                <p class="text-[10px] uppercase tracking-wide text-slate-400 mb-1">Policy</p>
                <div class="space-y-1">
                    <div class="flex justify-between"><span>Referrer Policy</span><?= $s['has_referrer_policy'] ? '<span class="text-emerald-600">Aktif</span>' : '<span class="text-rose-600">Tidak</span>' ?></div>
                    <div class="flex justify-between"><span>Permission Policy</span><?= $s['has_permission_policy'] ? '<span class="text-emerald-600">Aktif</span>' : '<span class="text-rose-600">Tidak</span>' ?></div>
                    <div class="flex justify-between mt-2"><span>Safe Browsing</span><?= $s['safe_browsing'] ? '<span class="text-emerald-600">Aman</span>' : '<span class="text-rose-600">Bahaya</span>' ?></div>
                    <div class="flex justify-between"><span>Blacklisted</span><?= !$s['blacklisted'] ? '<span class="text-emerald-600">Tidak</span>' : '<span class="text-rose-600">Ya</span>' ?></div>
                </div>
            </div>
        </div>

        <div class="text-[10px] text-slate-400 text-right">Last scan: <?= timeAgo($s['checked_at'] ?? '') ?></div>
        <?php else: ?>
        <div class="text-center py-8">
            <p class="text-xs text-slate-400">Belum ada data keamanan. Klik "Scan Ulang" untuk memulai.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
