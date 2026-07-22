<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'settings'; require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Notifikasi</span></nav>

<div class="flex items-center justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Notification Center</p>
        <h2 class="text-xl font-semibold text-slate-900">Riwayat Notifikasi</h2>
    </div>
    <button onclick="markAllRead()" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Tandai Dibaca</button>
</div>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <?php if (empty($notifications)): ?>
    <div class="text-center py-12"><p class="text-xs text-slate-400">Belum ada notifikasi.</p></div>
    <?php else: ?>
    <div class="divide-y divide-slate-100">
        <?php foreach ($notifications as $n): ?>
        <div class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50/50 <?= !$n['is_read'] ? 'bg-indigo-50/30' : '' ?>" data-id="<?= $n['id'] ?>">
            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full <?= $n['is_read'] ? 'bg-slate-300' : 'bg-indigo-500' ?>"></span>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-slate-800"><?= e($n['title']) ?></p>
                <p class="text-[11px] text-slate-500"><?= e($n['message']) ?></p>
                <p class="text-[10px] text-slate-400 mt-0.5">
                    <?= $n['nama_website'] ? e($n['nama_website']) . ' · ' : '' ?>
                    <?= timeAgo($n['created_at']) ?> · via <?= e($n['channel']) ?>
                </p>
            </div>
            <?php if (!$n['is_read']): ?>
            <button onclick="markRead(<?= $n['id'] ?>)" class="shrink-0 rounded-lg border border-slate-200 px-2.5 py-1 text-[10px] text-slate-500 hover:bg-slate-100">Baca</button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function markRead(id) {
    fetch(BASE_URL + 'notifications/mark-read?id=' + id)
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); });
}
function markAllRead() {
    fetch(BASE_URL + 'notifications/mark-all-read')
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); });
}
</script>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
