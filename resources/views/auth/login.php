<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — WEBGUARDIAN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= asset('css/toastr.min.css') ?>">
    <link rel="stylesheet" href="<?= baseUrl() ?>/assets/css/dashboard.css?v=<?= time() ?>">
    <style>
        body { background: #0f172a !important; color: #e2e8f0; font-family: system-ui, sans-serif; }
        .wg-card { background: #1e293bb3 !important; border: 1px solid #334155; border-radius: 1rem; padding: 2rem; }
        .wg-btn { background: linear-gradient(to right, #6366f1, #8b5cf6, #10b981); color: #fff; border-radius: 0.75rem; padding: .625rem 1.25rem; font-weight: 600; border: 0; cursor: pointer; }
        .wg-input { background: #33415580; border: 1px solid #475569; border-radius: 0.75rem; padding: .625rem 1rem; color: #fff; width: 100%; }
        .wg-input:focus { outline: 0; border-color: #6366f1; box-shadow: 0 0 0 2px #6366f180; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-emerald-500 shadow-2xl shadow-indigo-500/40 mb-4 ring-1 ring-white/10">
                <span class="text-xl font-bold text-white">WG</span>
            </div>
            <h1 class="text-2xl font-semibold text-white">WEBGUARDIAN</h1>
            <p class="text-sm text-slate-400 mt-1">Website Monitoring & Security Center</p>
        </div>

        <div class="rounded-2xl border border-slate-700/50 bg-slate-800/50 backdrop-blur-xl p-6 sm:p-8 shadow-2xl">
            <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 rounded-xl bg-rose-500/10 border border-rose-500/20 px-4 py-3 text-xs text-rose-300"><?= e($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); endif; ?>

            <form method="POST" action="<?= url('login') ?>" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-400 mb-1.5">Username</label>
                    <input type="text" name="username" required class="w-full rounded-xl border border-slate-600/50 bg-slate-700/50 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" placeholder="Masukkan username" autocomplete="username">
                </div>
                <div>
                    <label class="block text-[11px] font-medium uppercase tracking-widest text-slate-400 mb-1.5">Password</label>
                    <input type="password" name="password" required class="w-full rounded-xl border border-slate-600/50 bg-slate-700/50 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" placeholder="Masukkan password" autocomplete="current-password">
                </div>
                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    Masuk ke Dashboard
                </button>
            </form>
        </div>

        <p class="text-center text-[11px] text-slate-600 mt-6">&copy; <?= date('Y') ?> WEBGUARDIAN. All rights reserved.</p>
    </div>
</body>
</html>
