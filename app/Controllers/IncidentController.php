<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\User;
use App\Services\IncidentResponse;

class IncidentController
{
    public function index(): string
    {
        Auth::check();
        $db = \App\Config\Database::getConnection();
        $websites = $db->query("SELECT id, nama_website FROM websites WHERE status='active' ORDER BY nama_website")->fetchAll();
        $websiteId = (int)($_GET['website_id'] ?? 0);

        $analysis = null;
        $timeline = [];
        $websiteName = '';

        if ($websiteId > 0) {
            $stmt = $db->prepare("SELECT id, nama_website FROM websites WHERE id = ?");
            $stmt->execute([$websiteId]);
            $website = $stmt->fetch();
            if ($website) {
                $websiteName = $website['nama_website'];
                $engine = new IncidentResponse();
                $analysis = $engine->analyze($websiteId);
                $timeline = $engine->getTimeline($websiteId);
                User::logActivity($_SESSION['user_id'], 'Incident Analysis', "Analisis {$websiteName}: {$analysis['priority']}, {$analysis['total_issues']} issues");
            }
        }

        return view('incidents.index', [
            'websites' => $websites,
            'analysis' => $analysis,
            'timeline' => $timeline,
            'websiteId' => $websiteId,
            'websiteName' => $websiteName,
            'pageTitle' => 'Incident Response',
        ]);
    }
}
