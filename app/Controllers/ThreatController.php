<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\User;
use App\Services\ThreatDetector;

class ThreatController
{
    public function index(): string
    {
        Auth::check();
        $db = \App\Config\Database::getConnection();
        $websites = $db->query("SELECT id, nama_website, url FROM websites WHERE status='active' ORDER BY nama_website")->fetchAll();

        $websiteId = (int)($_GET['website_id'] ?? 0);
        $threats = [];

        if ($websiteId > 0) {
            $stmt = $db->prepare("SELECT id, url FROM websites WHERE id = ?");
            $stmt->execute([$websiteId]);
            $website = $stmt->fetch();
            if ($website) {
                $detector = new ThreatDetector($website['url']);
                $threats = $detector->scan();
            }
        }

        return view('threat.index', [
            'websites' => $websites,
            'threats' => $threats,
            'selectedWebsiteId' => $websiteId,
            'pageTitle' => 'Threat Detection',
        ]);
    }

    public function apiScan(): void
    {
        Auth::check();
        $websiteId = (int)($_GET['website_id'] ?? 0);
        if ($websiteId <= 0) {
            jsonResponse(['success' => false, 'error' => 'Invalid website ID'], 400);
        }

        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("SELECT url, nama_website FROM websites WHERE id = ?");
        $stmt->execute([$websiteId]);
        $website = $stmt->fetch();

        if (!$website) {
            jsonResponse(['success' => false, 'error' => 'Website not found'], 404);
        }

        $detector = new ThreatDetector($website['url']);
        $result = $detector->scan();

        User::logActivity($_SESSION['user_id'], 'Threat Scan', "Scan {$website['nama_website']}: {$result['threat_count']} threats, score {$result['threat_score']}");

        jsonResponse(['success' => true, 'data' => $result]);
    }
}
