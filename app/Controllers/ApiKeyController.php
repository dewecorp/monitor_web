<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\ApiKey;
use App\Models\User;

class ApiKeyController
{
    public function index(): string
    {
        Auth::check();
        $keys = ApiKey::forUser((int)$_SESSION['user_id']);
        return view('settings.api_keys', [
            'keys' => $keys,
            'pageTitle' => 'API Keys',
        ]);
    }

    public function generate(): void
    {
        Auth::check();
        $name = $_POST['name'] ?? 'API Key';
        $perms = $_POST['permissions'] ?? 'read';
        $key = ApiKey::generate((int)$_SESSION['user_id'], $name, $perms);
        User::logActivity($_SESSION['user_id'], 'Generate API Key', "Membuat API key: {$name}");
        $_SESSION['success'] = 'API Key berhasil dibuat!';
        $_SESSION['new_api_key'] = $key;
        redirect('/settings/api-keys');
    }

    public function revoke(): void
    {
        Auth::check();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            ApiKey::revoke($id, (int)$_SESSION['user_id']);
            User::logActivity($_SESSION['user_id'], 'Revoke API Key', "Mencabut API key ID: {$id}");
            $_SESSION['success'] = 'API Key dicabut!';
        }
        redirect('/settings/api-keys');
    }
}
