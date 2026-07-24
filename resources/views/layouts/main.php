<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?= csrfMeta() ?>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%234f46e5'/%3E%3Cstop offset='100%25' stop-color='%2310b981'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='8' fill='url(%23g)'/%3E%3Ctext x='16' y='22' text-anchor='middle' fill='white' font-size='15' font-weight='bold' font-family='system-ui'%3EWG%3C/text%3E%3C/svg%3E" />
    <title><?= e($pageTitle ?? 'Dashboard') ?> | WEBGUARDIAN</title>
    <meta name="wg-ver" content="<?= time() ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= asset('css/toastr.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>?v=<?= time() ?>">
    <style>
        body { background: #d1fae5 !important; color: #0f172a; font-family: system-ui, -apple-system, sans-serif; }
        .wg-card { background: rgba(255,255,255,0.85); backdrop-filter: blur(8px); border: 1px solid rgba(16,185,129,0.15); border-radius: 1rem; padding: 1rem; box-shadow: 0 4px 16px rgba(16,185,129,0.06); }
        .wg-glass-sidebar { background: linear-gradient(180deg, #d1fae5 0%, #a7f3d0 60%, #6ee7b7 100%) !important; border: 1px solid rgba(16,185,129,0.3) !important; box-shadow: 4px 0 30px rgba(16,185,129,0.12); }
        .wg-glass-sidebar .nav-section { background: rgba(255,255,255,0.7); backdrop-filter: blur(8px); border: 1px solid rgba(16,185,129,0.2); border-radius: 1rem; }
        .wg-gradient-text { background: linear-gradient(135deg, #4338ca, #047857); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }
        .wg-nav { background: linear-gradient(135deg, rgba(255,255,255,0.93) 0%, rgba(167,243,208,0.93) 100%) !important; backdrop-filter: blur(16px); border-bottom: 2px solid rgba(16,185,129,0.25); box-shadow: 0 4px 24px rgba(16,185,129,0.1); }
        .wg-sidebar-item:hover { background: rgba(5,150,105,0.12) !important; }
        .wg-sidebar-item.active { background: linear-gradient(135deg, #4338ca, #047857) !important; color: #fff !important; box-shadow: 0 4px 16px rgba(4,120,87,0.35); }
        .wg-footer { background: linear-gradient(135deg, rgba(255,255,255,0.92), rgba(167,243,208,0.92)) !important; border-top: 1px solid rgba(16,185,129,0.25); backdrop-filter: blur(12px); }
        .wg-content-card { background: rgba(255,255,255,0.82); backdrop-filter: blur(8px); border: 1px solid rgba(16,185,129,0.12); border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 12px rgba(16,185,129,0.05); }
        .wg-table-header { background: rgba(16,185,129,0.06) !important; }
        .wg-table-row:hover { background: rgba(16,185,129,0.04) !important; }
        @media (min-width: 768px) { #desktopSidebar { display: block !important; } #mobileSidebar, #mobileOverlay { display: none !important; } #mainLayout { flex-direction: row !important; } }
        @media (max-width: 767px) { #desktopSidebar { display: none !important; } }
canvas { max-width: 100% !important; height: auto !important; }
        #dashboardGrid { display:block; }
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; max-width:100%; }
        #mainContent table { min-width:auto; width:100%; table-layout:fixed; } #mainContent table td, #mainContent table th { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">

<header class="wg-nav sticky top-0 z-50">
    <div class="flex items-center justify-between px-4 py-2.5 sm:px-6">
        <div class="flex items-center gap-3">
            <button id="sidebarToggle" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 wg-sidebar-item md:hidden">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 via-emerald-400 to-emerald-500 shadow-lg shadow-emerald-500/30">
                <span class="text-sm font-bold text-white">WG</span>
            </div>
            <div>
                <h1 class="wg-gradient-text text-sm font-bold tracking-tight">WEBGUARDIAN</h1>
                <p class="text-[10px] text-slate-500">Monitoring & Security Center</p>
            </div>
            <div class="hidden sm:block text-xs text-slate-600 ml-4 pl-4 border-l border-emerald-200/60 font-medium">
                <span id="clockDisplay"><?= date('d M Y, H:i') ?></span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="checkAllWebsites()" class="hidden md:inline-flex items-center gap-1.5 rounded-full border border-emerald-200/60 bg-emerald-50/80 px-3 py-1.5 text-[11px] font-medium text-emerald-700 hover:bg-emerald-100 transition-colors">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Check All
            </button>
            <div class="relative" onmouseenter="showUserMenu()" onmouseleave="hideUserMenu()">
                <button id="userMenuBtn" class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-emerald-500 text-[10px] font-bold text-white shadow-sm shadow-emerald-500/20 cursor-pointer hover:shadow-md transition-shadow">
                    <?= e(strtoupper(substr($user['nama'] ?? 'U', 0, 2))) ?>
                </button>
                <div id="userMenu" class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-2xl border border-slate-200 py-2 hidden z-50">
                    <div class="px-4 py-2 border-b border-slate-100">
                        <p class="text-xs font-semibold text-slate-800"><?= e($user['nama'] ?? 'User') ?></p>
                        <p class="text-[10px] text-slate-500"><?= e(ucfirst(str_replace('_', ' ', $user['level'] ?? 'user'))) ?></p>
                    </div>
                    <a href="<?= url('settings') ?>" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 transition-colors">Pengaturan</a>
                    <a href="#" onclick="confirmLogout()" class="flex items-center gap-2 px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

<main id="mainLayout" style="display:flex;flex-direction:column;gap:1.5rem;padding:1.25rem 1rem 2.5rem;width:100%;max-width:100vw" class="sm:px-6 lg:px-8">
    <!-- Sidebar Desktop -->
    <aside id="desktopSidebar" class="w-full md:block lg:w-64 lg:sticky lg:top-20 wg-glass-sidebar" style="flex-shrink:0;align-self:flex-start;display:none;border-radius:1rem;padding:1rem">
        <div style="display:flex;flex-direction:column;gap:0.75rem">
            <section class="nav-section p-4">
            <div class="flex items-center gap-3">
                <div class="relative h-10 w-10 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-emerald-500 shadow-lg shadow-emerald-500/30">
                    <span class="relative flex h-full w-full items-center justify-center text-sm font-semibold text-white"><?= e(strtoupper(substr($user['nama'] ?? 'U', 0, 2))) ?></span>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-slate-500">Selamat datang,</p>
                    <p class="text-sm font-semibold text-slate-900"><?= e($user['nama'] ?? 'User') ?></p>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between rounded-xl bg-emerald-50/70 px-3 py-2">
                <p class="text-[10px] uppercase tracking-wide text-slate-500">Akses</p>
                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-emerald-700"><?= e(ucfirst(str_replace('_', ' ', $user['level'] ?? 'user'))) ?></span>
            </div>
        </section>

        <nav class="nav-section p-3 text-xs text-slate-600">
            <p class="mb-1 px-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Menu</p>
            <ul class="space-y-0.5 mb-4">
                <li><a href="<?= url('/') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= ($activeMenu ?? '') === 'dashboard' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"/></svg>
                    Dashboard</a></li>
                <li><a href="<?= url('websites') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'websites' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                    Websites</a></li>
            </ul>

            <p class="mb-1 px-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Security</p>
            <ul class="space-y-0.5 mb-4">
                <li><a href="<?= url('security-scan') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'security_scan' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01"/></svg>
                    Security Scan</a></li>
                <li><a href="<?= url('file-integrity') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'file_integrity' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    File Integrity</a></li>
                <li><a href="<?= url('ai-analysis') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'ai_analysis' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                    AI Analysis</a></li>
                <li><a href="<?= url('hardening') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'hardening' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Hardening</a></li>
                <li><a href="<?= url('incident-response') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'incident' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    IR Engine</a></li>
                <li><a href="<?= url('vulnerability-scan') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'vuln' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    Vuln Scanner</a></li>
                <li><a href="<?= url('threat-detection') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'threat' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    Threat Detection</a></li>
                <li><a href="<?= url('server-scan') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'server_scan' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Server Scan</a></li>
                <li><a href="<?= url('quarantine') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'quarantine' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    Quarantine</a></li>
            </ul>

            <p class="mb-1 px-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Monitoring</p>
            <ul class="space-y-0.5 mb-4">
                <li><a href="<?= url('monitor/health') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'health' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Kesehatan</a></li>
                <li><a href="<?= url('monitor/security') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'security' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Keamanan</a></li>
                <li><a href="<?= url('monitor/traffic') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'traffic' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Traffic</a></li>
            </ul>

            <p class="mb-1 px-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Lainnya</p>
            <ul class="space-y-0.5">
                <li><a href="<?= url('reports') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'reports' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Laporan</a></li>
                <li><a href="<?= url('agent/servers') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'agent' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                    Servers</a></li>
                <li><a href="<?= url('settings') ?>" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors <?= $activeMenu === 'settings' ? 'wg-sidebar-item active' : 'text-slate-600 wg-sidebar-item' ?>">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Pengaturan</a></li>
                <li><a href="#" onclick="confirmLogout()" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition-colors">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7l-4-4m0 0L8 7m4-4v12m5 4H7a2 2 0 01-2-2v-3"/></svg>
                    Logout</a></li>
            </ul>
        </nav>
    </div>
</aside>

<!-- Mobile sidebar overlay -->
<div id="mobileOverlay" class="fixed inset-0 bg-black/60 z-40 hidden" onclick="closeSidebar()"></div>
<aside id="mobileSidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-2xl -translate-x-full transition-transform duration-300 overflow-y-auto border-r border-slate-200">
    <div class="p-4">
        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-emerald-500 text-[10px] font-bold text-white"><?= e(strtoupper(substr($user['nama'] ?? 'U', 0, 2))) ?></div>
                <div>
                    <p class="text-xs font-semibold text-slate-800"><?= e($user['nama'] ?? 'User') ?></p>
                    <p class="text-[9px] text-slate-500"><?= e(ucfirst(str_replace('_', ' ', $user['level'] ?? 'user'))) ?></p>
                </div>
            </div>
            <button onclick="closeSidebar()" class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">✕</button>
        </div>
        <nav class="text-xs text-slate-600 space-y-0.5">
            <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400 mt-1 mb-1 px-2">Menu</p>
            <a href="<?= url('/') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'dashboard' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"/></svg>
                Dashboard</a>
            <a href="<?= url('websites') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'websites' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                Websites</a>

            <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400 mt-3 mb-1 px-2">Security</p>
            <a href="<?= url('security-scan') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'security_scan' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Security Scan</a>
            <a href="<?= url('file-integrity') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'file_integrity' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">File Integrity</a>
            <a href="<?= url('ai-analysis') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'ai_analysis' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">AI Analysis</a>
            <a href="<?= url('hardening') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'hardening' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Hardening</a>
            <a href="<?= url('incident-response') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'incident' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">IR Engine</a>
            <a href="<?= url('vulnerability-scan') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'vuln' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Vuln Scanner</a>
            <a href="<?= url('threat-detection') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'threat' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Threat Detection</a>
            <a href="<?= url('server-scan') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'server_scan' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Server Scan</a>
            <a href="<?= url('quarantine') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'quarantine' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Quarantine</a>

            <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400 mt-3 mb-1 px-2">Monitoring</p>
            <a href="<?= url('monitor/health') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'health' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Kesehatan</a>
            <a href="<?= url('monitor/security') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'security' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Keamanan</a>
            <a href="<?= url('monitor/traffic') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'traffic' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Traffic</a>

            <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400 mt-3 mb-1 px-2">Lainnya</p>
            <a href="<?= url('reports') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'reports' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Laporan</a>
            <a href="<?= url('agent/servers') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'agent' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Servers</a>
            <a href="<?= url('settings') ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 <?= $activeMenu === 'settings' ? 'bg-indigo-600 text-white' : 'hover:bg-slate-100' ?>">Pengaturan</a>
            <div class="border-t border-slate-100 my-3"></div>
            <a href="#" onclick="closeSidebar(); confirmLogout()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-rose-600 hover:bg-rose-50">Logout</a>
        </nav>
    </div>
</aside>

<section id="mainContent" style="flex:1;min-width:0;display:flex;flex-direction:column;gap:1.25rem;overflow-x:hidden">

        <?php if (isset($_SESSION['success'])): ?>
        <div id="flashSuccess" class="hidden" data-message="<?= e($_SESSION['success']) ?>"></div>
        <?php unset($_SESSION['success']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
        <div id="flashError" class="hidden" data-message="<?= e($_SESSION['error']) ?>"></div>
        <?php unset($_SESSION['error']); endif; ?>
