<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\User;
use App\Services\ServerSecurityScanner;

class ServerScanController
{
    public function index(): string
    {
        Auth::check();
        $scanner = new ServerSecurityScanner();
        $results = $scanner->scan();
        return view('server_scan.index', [
            'results' => $results,
            'pageTitle' => 'Server Scanner',
        ]);
    }

    public function harden(): void
    {
        Auth::check();
        $fixes = $_POST['fixes'] ?? [];

        if (empty($fixes)) {
            $_SESSION['error'] = 'Pilih setidaknya satu tindakan hardening';
            redirect('/server-scan');
        }

        $scanner = new ServerSecurityScanner();
        $fixResults = $scanner->autoHarden($fixes);

        User::logActivity($_SESSION['user_id'], 'Auto Hardening', 'Menjalankan ' . count($fixResults) . ' tindakan hardening');

        $successCount = 0;
        foreach ($fixResults as $key => $r) {
            if ($r['status'] === 'fixed') $successCount++;
        }

        $_SESSION['success'] = "Hardening selesai: {$successCount} dari " . count($fixResults) . " tindakan berhasil";
        redirect('/server-scan');
    }
}
