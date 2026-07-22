<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\Setting;
use App\Models\Notification;
use App\Models\User;

class SettingsController
{
    public function index(): string
    {
        Auth::check();
        $settings = Setting::all();
        $settingsByKey = [];
        foreach ($settings as $s) {
            $settingsByKey[$s['key']] = $s;
        }
        return view('settings.index', [
            'settings' => $settingsByKey,
            'pageTitle' => 'Pengaturan',
        ]);
    }

    public function update(): void
    {
        Auth::check();
        $keys = [
            'telegram_enabled', 'email_enabled', 'discord_enabled',
            'notify_down_after', 'ssl_warning_days', 'domain_warning_days',
            'cpu_warning_threshold', 'ram_warning_threshold', 'disk_warning_threshold',
            'retention_days', 'check_interval',
        ];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                Setting::set($key, $_POST[$key]);
            }
        }
        User::logActivity($_SESSION['user_id'], 'Update Settings', 'Memperbarui pengaturan notifikasi');
        $_SESSION['success'] = 'Pengaturan berhasil disimpan!';
        redirect('/settings');
    }

    public function credentials(): void
    {
        Auth::check();
        $keys = ['TELEGRAM_BOT_TOKEN', 'TELEGRAM_CHAT_ID', 'DISCORD_WEBHOOK'];
        $updated = [];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                Setting::set($key, $_POST[$key]);
                $updated[] = $key;
            }
        }
        User::logActivity($_SESSION['user_id'], 'Update Credentials', 'Memperbarui kredensial notifikasi');
        $_SESSION['success'] = 'Kredensial berhasil disimpan!';
        redirect('/settings');
    }

    public function testNotification(): void
    {
        Auth::check();
        $channel = $_GET['channel'] ?? 'telegram';

        $sent = false;
        $message = match($channel) {
            'telegram' => 'Test notifikasi dari WEBGUARDIAN',
            'discord' => 'Test notifikasi dari WEBGUARDIAN',
            default => 'Test notifikasi dari WEBGUARDIAN',
        };

        if ($channel === 'telegram') {
            $sent = Notification::sendTelegram("🔔 <b>Test Notification</b>\nWEBGUARDIAN monitoring berjalan dengan baik.\nWaktu: " . date('Y-m-d H:i:s'));
        } elseif ($channel === 'discord') {
            $sent = Notification::sendDiscord("🔔 **Test Notification**\nWEBGUARDIAN monitoring berjalan dengan baik.\nWaktu: " . date('Y-m-d H:i:s'));
        }

        User::logActivity($_SESSION['user_id'], 'Test Notification', "Test notifikasi via {$channel}: " . ($sent ? 'berhasil' : 'gagal'));
        jsonResponse(['success' => $sent, 'message' => $sent ? 'Notifikasi terkirim!' : 'Gagal mengirim notifikasi. Cek kredensial.']);
    }
}
