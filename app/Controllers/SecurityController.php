<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\Website;
use App\Models\SecurityLog;
use App\Models\SslLog;

class SecurityController
{
    public function index(): string
    {
        Auth::check();
        $websites = Website::withLatestStatus();
        return view('monitor.security', ['websites' => $websites, 'pageTitle' => 'Keamanan']);
    }

    public function detail(array $params): string
    {
        Auth::check();
        $website = Website::find((int)$params['id']);
        if (!$website) abort(404);
        $security = SecurityLog::latestForWebsite((int)$params['id']);
        $ssl = SslLog::latestForWebsite((int)$params['id']);
        return view('monitor.security_detail', [
            'website' => $website,
            'security' => $security,
            'ssl' => $ssl,
            'pageTitle' => 'Keamanan - ' . $website['nama_website'],
        ]);
    }
}
