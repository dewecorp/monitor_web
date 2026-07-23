<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Middleware\Auth;

class AuthController
{
    public function login(): string
    {
        Auth::guest();
        return view('auth.login');
    }

    public function doLogin(): void
    {
        Auth::guest();

        // Rate limit: max 5 attempts per minute per IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rateKey = 'login_' . $ip;
        $attempts = $_SESSION[$rateKey] ?? ['count' => 0, 'time' => time()];

        if ($attempts['count'] >= 5 && time() - $attempts['time'] < 60) {
            $wait = 60 - (time() - $attempts['time']);
            $_SESSION['error'] = "Terlalu banyak percobaan login. Coba lagi {$wait} detik.";
            redirect('/login');
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $_SESSION['error'] = 'Harap isi username dan password!';
            redirect('/login');
        }

        // Increment attempt counter
        if (time() - $attempts['time'] > 60) {
            $_SESSION[$rateKey] = ['count' => 1, 'time' => time()];
        } else {
            $_SESSION[$rateKey] = ['count' => $attempts['count'] + 1, 'time' => $attempts['time']];
        }

        $user = User::findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            Auth::regenerate();
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_level'] = $user['level'];

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            User::logLogin((int)$user['id'], 'success');
            User::updateLogin((int)$user['id'], $ip);
            User::logActivity((int)$user['id'], 'Login', 'User berhasil login');

            redirect('/');
        }

        if ($user) {
            User::logLogin((int)$user['id'], 'failed', 'Password salah');
        }
        $_SESSION['error'] = 'Username atau password salah!';
        redirect('/login');
    }

    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            User::logActivity((int)$userId, 'Logout', 'User logout dari sistem');
        }
        $_SESSION = [];
        session_destroy();
        redirect('/login');
    }
}
