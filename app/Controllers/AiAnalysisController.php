<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Services\AiAnalysis;

class AiAnalysisController
{
    public function index(): string
    {
        Auth::check();
        $db = \App\Config\Database::getConnection();
        $websites = $db->query("SELECT id, nama_website, url FROM websites WHERE status='active' ORDER BY nama_website")->fetchAll();

        $websiteId = (int)($_GET['website_id'] ?? 0);
        $report = null;

        if ($websiteId > 0) {
            $engine = new AiAnalysis($websiteId);
            $report = $engine->analyze();
        }

        return view('ai_analysis.index', [
            'websites' => $websites,
            'report' => $report,
            'selectedWebsiteId' => $websiteId,
            'pageTitle' => 'Security Analysis',
        ]);
    }
}
