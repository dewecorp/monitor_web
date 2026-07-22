<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\User;
use App\Services\HardeningCheck;

class HardeningController
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
                $checker = new HardeningCheck('', $website['url']);
                $result = $checker->check();
                $result['website_name'] = $website['nama_website'];
                User::logActivity($_SESSION['user_id'], 'Hardening Check', "Check {$website['nama_website']}: score {$result['score']}%");
            }
        }

        return view('hardening.index', [
            'websites' => $websites,
            'result' => $result,
            'selectedWebsiteId' => $websiteId,
            'pageTitle' => 'Hardening Check',
        ]);
    }
}
