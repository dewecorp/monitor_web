<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\User;
use App\Services\VulnScanner;

class VulnController
{
    public function index(): string
    {
        Auth::check();
        $db = \App\Config\Database::getConnection();
        $websites = $db->query("SELECT id, nama_website, url FROM websites WHERE status='active' ORDER BY nama_website")->fetchAll();

        $websiteId = (int)($_GET['website_id'] ?? 0);
        $result = null;

        if ($websiteId > 0) {
            $stmt = $db->prepare("SELECT id, nama_website, url FROM websites WHERE id = ?");
            $stmt->execute([$websiteId]);
            $website = $stmt->fetch();
            if ($website) {
                $scanner = new VulnScanner($website['url']);
                $result = $scanner->scan();
                $result['website_name'] = $website['nama_website'];
                User::logActivity($_SESSION['user_id'], 'Vuln Scan', "Scan {$website['nama_website']}: {$result['severity']}, {$result['score']}%");
            }
        }

        return view('vulnscan.index', [
            'websites' => $websites,
            'result' => $result,
            'selectedWebsiteId' => $websiteId,
            'pageTitle' => 'Vulnerability Scan',
        ]);
    }

    public function apiScan(): void
    {
        Auth::check();
        $websiteId = (int)($_GET['website_id'] ?? 0);
        if ($websiteId <= 0) jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400);

        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("SELECT url, nama_website FROM websites WHERE id = ?");
        $stmt->execute([$websiteId]);
        $website = $stmt->fetch();

        if (!$website) jsonResponse(['success' => false, 'error' => 'Not found'], 404);

        $scanner = new VulnScanner($website['url']);
        $result = $scanner->scan();
        jsonResponse(['success' => true, 'data' => $result]);
    }
}
