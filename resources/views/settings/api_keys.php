<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'settings'; require VIEW_PATH . '/layouts/main.php';
$newKey = $_SESSION['new_api_key'] ?? null;
unset($_SESSION['new_api_key']);
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <a href="<?= url('settings') ?>" class="text-slate-600 hover:text-indigo-600">Pengaturan</a> <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">API Keys</span></nav>

<div class="max-w-3xl mx-auto space-y-5">
    <?php if ($newKey): ?>
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-xs font-semibold text-emerald-800 mb-1">API Key baru berhasil dibuat!</p>
        <p class="text-[10px] text-emerald-600 mb-2">Simpan key ini. Tidak bisa dilihat lagi setelah halaman di-refresh.</p>
        <code class="block bg-white rounded-xl border border-emerald-200 px-4 py-3 text-xs font-mono text-slate-800 select-all"><?= e($newKey) ?></code>
        <button onclick="copyKey('<?= e($newKey) ?>')" class="mt-2 inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-[10px] font-semibold text-white hover:bg-emerald-700">📋 Salin Key</button>
    </div>
    <?php endif; ?>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-800">API Keys</h2>
            <button data-modal-toggle="#modalAddKey" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">+ Key Baru</button>
        </div>

        <?php if (empty($keys)): ?>
        <div class="text-center py-8"><p class="text-xs text-slate-400">Belum ada API key. Buat key baru untuk mengakses REST API.</p></div>
        <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($keys as $k): ?>
            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 px-4 py-3">
                <div>
                    <p class="text-xs font-medium text-slate-800"><?= e($k['name']) ?></p>
                    <code class="text-[10px] text-slate-400 font-mono break-all"><?= substr($k['key'], 0, 20) ?>...<?= substr($k['key'], -8) ?></code>
                    <button onclick="copyKey('<?= e($k['key']) ?>')" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-[9px] text-slate-500 hover:bg-slate-100 ml-1" title="Salin key">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        Salin
                    </button>
                    <span class="ml-2 text-[10px] text-slate-400"><?= e($k['permissions']) ?></span>
                    <span class="ml-2 text-[10px] <?= $k['is_active'] ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $k['is_active'] ? 'Aktif' : 'Dicabut' ?></span>
                </div>
                <?php if ($k['is_active']): ?>
                <form method="POST" action="<?= url('settings/api-keys/revoke') ?>" onsubmit="return confirm('Yakin mencabut API key ini?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                    <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-[10px] font-medium text-rose-600 hover:bg-rose-50">Cabut</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Cara Pakai API</h2>
        <div class="space-y-2 text-xs">
            <p class="text-slate-600">Semua endpoint menggunakan <code class="bg-slate-100 px-1.5 py-0.5 rounded text-[10px] font-mono">Authorization: Bearer {api_key}</code></p>
            <p class="text-slate-600">Base URL: <code class="bg-slate-100 px-1.5 py-0.5 rounded text-[10px] font-mono"><?= url('api') ?></code></p>
            <div class="mt-3 space-y-1">
                <p class="font-medium text-slate-700">Endpoints:</p>
                <code class="block bg-slate-50 rounded-lg px-3 py-2 text-[10px] font-mono">
GET    /api/websites              - Daftar semua website<br>
GET    /api/websites/{id}         - Detail website<br>
GET    /api/websites/{id}/monitor - Log monitoring<br>
GET    /api/websites/{id}/security- Log keamanan<br>
GET    /api/websites/{id}/ssl     - Log SSL<br>
GET    /api/websites/{id}/traffic - Log traffic<br>
GET    /api/summary               - Ringkasan monitoring
                </code>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Key -->
<div id="modalAddKey" class="modal-dashboard modal-dashboard-sm hidden">
    <div class="modal-dialog"><div class="modal-content">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-800">Buat API Key Baru</h3>
            <button type="button" class="btn-close" data-modal-hide="#modalAddKey"></button>
        </div>
        <form method="POST" action="<?= url('settings/api-keys/generate') ?>">
            <?= csrfField() ?>
            <div class="space-y-3">
                <div>
                    <label class="form-label-dash block mb-1">Nama Key</label>
                    <input type="text" name="name" required class="form-control-dash w-full" placeholder="Contoh: Production API">
                </div>
                <div>
                    <label class="form-label-dash block mb-1">Permission</label>
                    <select name="permissions" class="form-select-dash w-full">
                        <option value="read">Read Only</option>
                        <option value="read,write">Read & Write</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn-dashboard-soft text-xs" data-modal-hide="#modalAddKey">Batal</button>
                <button type="submit" class="btn-dashboard-primary text-xs">Generate</button>
            </div>
        </form>
    </div></div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>

<script>
function copyKey(key) {
    navigator.clipboard.writeText(key).then(function() {
        toastr.success('API Key disalin ke clipboard', 'Berhasil');
    }).catch(function() {
        // Fallback
        var ta = document.createElement('textarea');
        ta.value = key;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        toastr.success('API Key disalin ke clipboard', 'Berhasil');
    });
}
</script>
