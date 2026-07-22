<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'settings'; require VIEW_PATH . '/layouts/main.php';
$s = $settings;
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Pengaturan</span></nav>

<div class="max-w-3xl mx-auto space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Notifikasi</h2>
        <form method="POST" action="<?= url('settings/update') ?>" class="space-y-4">
            <?= csrfField() ?>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 cursor-pointer">
                    <input type="hidden" name="telegram_enabled" value="0">
                    <input type="checkbox" name="telegram_enabled" value="1" <?= ($s['telegram_enabled']['value'] ?? '0') === '1' ? 'checked' : '' ?> class="rounded text-indigo-600">
                    <div><p class="text-xs font-medium text-slate-700">Telegram</p><p class="text-[10px] text-slate-400">Bot notification</p></div>
                </label>
                <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 cursor-pointer">
                    <input type="hidden" name="email_enabled" value="0">
                    <input type="checkbox" name="email_enabled" value="1" <?= ($s['email_enabled']['value'] ?? '0') === '1' ? 'checked' : '' ?> class="rounded text-indigo-600">
                    <div><p class="text-xs font-medium text-slate-700">Email</p><p class="text-[10px] text-slate-400">SMTP mail</p></div>
                </label>
                <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 cursor-pointer">
                    <input type="hidden" name="discord_enabled" value="0">
                    <input type="checkbox" name="discord_enabled" value="1" <?= ($s['discord_enabled']['value'] ?? '0') === '1' ? 'checked' : '' ?> class="rounded text-indigo-600">
                    <div><p class="text-xs font-medium text-slate-700">Discord</p><p class="text-[10px] text-slate-400">Webhook</p></div>
                </label>
            </div>
            <h3 class="text-xs font-semibold text-slate-700 pt-2">Ambang Batas</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                <div><label class="text-[10px] font-medium text-slate-500">Notify Down After</label><input type="number" name="notify_down_after" value="<?= e($s['notify_down_after']['value'] ?? '2') ?>" class="form-control-dash w-full text-xs mt-1"></div>
                <div><label class="text-[10px] font-medium text-slate-500">SSL Warning (hari)</label><input type="number" name="ssl_warning_days" value="<?= e($s['ssl_warning_days']['value'] ?? '30') ?>" class="form-control-dash w-full text-xs mt-1"></div>
                <div><label class="text-[10px] font-medium text-slate-500">Domain Warning (hari)</label><input type="number" name="domain_warning_days" value="<?= e($s['domain_warning_days']['value'] ?? '30') ?>" class="form-control-dash w-full text-xs mt-1"></div>
                <div><label class="text-[10px] font-medium text-slate-500">CPU Threshold (%)</label><input type="number" name="cpu_warning_threshold" value="<?= e($s['cpu_warning_threshold']['value'] ?? '90') ?>" class="form-control-dash w-full text-xs mt-1"></div>
                <div><label class="text-[10px] font-medium text-slate-500">RAM Threshold (%)</label><input type="number" name="ram_warning_threshold" value="<?= e($s['ram_warning_threshold']['value'] ?? '90') ?>" class="form-control-dash w-full text-xs mt-1"></div>
                <div><label class="text-[10px] font-medium text-slate-500">Disk Threshold (%)</label><input type="number" name="disk_warning_threshold" value="<?= e($s['disk_warning_threshold']['value'] ?? '90') ?>" class="form-control-dash w-full text-xs mt-1"></div>
                <div><label class="text-[10px] font-medium text-slate-500">Check Interval (menit)</label><input type="number" name="check_interval" value="<?= e($s['check_interval']['value'] ?? '1') ?>" class="form-control-dash w-full text-xs mt-1"></div>
                <div><label class="text-[10px] font-medium text-slate-500">Retensi Data (hari)</label><input type="number" name="retention_days" value="<?= e($s['retention_days']['value'] ?? '90') ?>" class="form-control-dash w-full text-xs mt-1"></div>
            </div>
            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Simpan Pengaturan</button>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800 mb-4">Kredensial Notifikasi</h2>
        <form method="POST" action="<?= url('settings/credentials') ?>" class="space-y-3">
            <?= csrfField() ?>
            <div><label class="text-[10px] font-medium text-slate-500">Telegram Bot Token</label><input type="text" name="TELEGRAM_BOT_TOKEN" value="<?= e($s['TELEGRAM_BOT_TOKEN']['value'] ?? '') ?>" class="form-control-dash w-full text-xs mt-1" placeholder="123456:ABC-DEF..."></div>
            <div><label class="text-[10px] font-medium text-slate-500">Telegram Chat ID</label><input type="text" name="TELEGRAM_CHAT_ID" value="<?= e($s['TELEGRAM_CHAT_ID']['value'] ?? '') ?>" class="form-control-dash w-full text-xs mt-1"></div>
            <div><label class="text-[10px] font-medium text-slate-500">Discord Webhook URL</label><input type="url" name="DISCORD_WEBHOOK" value="<?= e($s['DISCORD_WEBHOOK']['value'] ?? '') ?>" class="form-control-dash w-full text-xs mt-1" placeholder="https://discord.com/api/webhooks/..."></div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Simpan Kredensial</button>
                <button type="button" onclick="testNotif('telegram')" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">Test Telegram</button>
                <button type="button" onclick="testNotif('discord')" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">Test Discord</button>
            </div>
        </form>
    </div>
</div>

<script>
function testNotif(channel) {
    toastr.info('Mengirim test notifikasi...');
    fetch(BASE_URL + 'settings/test-notification?channel=' + channel)
        .then(r => r.json())
        .then(d => {
            if (d.success) toastr.success(d.message, 'Sukses');
            else toastr.error(d.message, 'Gagal');
        })
        .catch(() => toastr.error('Gagal menghubungi server'));
}
</script>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">API Keys</h2>
                <p class="text-xs text-slate-500 mt-1">Kelola API key untuk akses REST API</p>
            </div>
            <a href="<?= url('settings/api-keys') ?>" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Kelola</a>
        </div>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
