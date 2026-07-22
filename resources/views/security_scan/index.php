<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'security_scan';
require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Security Scan</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Backdoor & Malware Scanner</p>
        <h2 class="text-xl font-semibold text-slate-900">Pemindaian Keamanan Source Code</h2>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-3 mb-5">
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Jalankan Scan</h3>
            <form method="POST" action="<?= url('security-scan/run') ?>" class="flex gap-3">
                <?= csrfField() ?>
                <input type="text" name="path" placeholder="Masukkan path direktori website yang akan dipindai" class="form-control-dash flex-1 text-xs">
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 shrink-0">
                    <svg class="h-3.5 w-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Scan Sekarang
                </button>
            </form>
            <p class="text-[10px] text-slate-400 mt-2">Masukkan path direktori website yang ingin diperiksa.</p>
        </div>

        <?php if ($results): $r = $results; ?>
        <!-- Results Summary -->
        <div class="grid gap-3 sm:grid-cols-4 mt-4 mb-4">
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Total File</p>
                <p class="text-lg font-semibold text-slate-800"><?= number_format($r['total_files']) ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">PHP Scanned</p>
                <p class="text-lg font-semibold text-slate-800"><?= number_format($r['scanned_files']) ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Malware Score</p>
                <p class="text-lg font-semibold <?= $r['malware_score'] >= 80 ? 'text-emerald-600' : ($r['malware_score'] >= 50 ? 'text-amber-600' : 'text-rose-600') ?>"><?= $r['malware_score'] ?>%</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3 text-center">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Severity</p>
                <p class="text-lg font-semibold <?= $r['severity'] === 'clean' ? 'text-emerald-600' : ($r['severity'] === 'medium' ? 'text-amber-600' : 'text-rose-600') ?>"><?= ucfirst($r['severity']) ?></p>
            </div>
        </div>

        <!-- Dangerous Functions -->
        <?php if (!empty($r['dangerous_functions'])): ?>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mt-4">
            <div class="p-4 border-b border-slate-100 bg-rose-50/50">
                <p class="text-xs font-semibold text-rose-700">Dangerous Functions — <?= count($r['dangerous_functions']) ?> ditemukan</p>
            </div>
            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                <?php foreach ($r['dangerous_functions'] as $f): ?>
                <div class="px-4 py-3 hover:bg-slate-50">
                    <div class="flex items-start justify-between">
                        <div>
                            <code class="text-xs font-mono font-semibold <?= $f['severity'] === 'critical' ? 'text-rose-600' : ($f['severity'] === 'high' ? 'text-amber-600' : 'text-slate-700') ?>"><?= e($f['function']) ?>()</code>
                            <span class="text-[10px] text-slate-500 ml-2"><?= e($f['file']) ?>:<?= $f['line'] ?></span>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase <?= $f['severity'] === 'critical' ? 'bg-rose-50 text-rose-600' : ($f['severity'] === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-600') ?>"><?= e($f['severity']) ?></span>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-1"><?= e($f['description']) ?></p>
                    <pre class="mt-1 text-[9px] bg-slate-50 rounded-lg p-2 overflow-x-auto font-mono text-slate-600"><?= e($f['context']) ?></pre>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Obfuscation -->
        <?php if (!empty($r['obfuscation'])): ?>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mt-4">
            <div class="p-4 border-b border-slate-100 bg-amber-50/50">
                <p class="text-xs font-semibold text-amber-700">Obfuscation Detected — <?= count($r['obfuscation']) ?> pola</p>
            </div>
            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                <?php foreach ($r['obfuscation'] as $o): ?>
                <div class="px-4 py-3 hover:bg-slate-50">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-800"><?= e($o['pattern']) ?></p>
                            <p class="text-[10px] text-slate-500"><?= e($o['file']) ?>:<?= $o['line'] ?></p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase <?= $o['severity'] === 'critical' ? 'bg-rose-50 text-rose-600' : ($o['severity'] === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-600') ?>"><?= e($o['severity']) ?></span>
                    </div>
                    <pre class="mt-1 text-[9px] bg-slate-50 rounded-lg p-2 overflow-x-auto font-mono text-slate-600"><?= e($o['context']) ?></pre>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Suspicious Files -->
        <?php if (!empty($r['suspicious_files'])): ?>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mt-4">
            <div class="p-4 border-b border-slate-100 bg-rose-50/50">
                <p class="text-xs font-semibold text-rose-700">Suspicious Files — <?= count($r['suspicious_files']) ?> item</p>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($r['suspicious_files'] as $s): ?>
                <div class="px-4 py-3 flex items-start justify-between hover:bg-slate-50">
                    <div>
                        <p class="text-xs font-medium text-slate-800"><?= e($s['file']) ?></p>
                        <p class="text-[10px] text-slate-500"><?= e($s['desc']) ?></p>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <span class="rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase <?= $s['severity'] === 'critical' ? 'bg-rose-50 text-rose-600' : ($s['severity'] === 'high' ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-600') ?>"><?= e($s['severity']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Clean result -->
        <?php if (empty($r['dangerous_functions']) && empty($r['obfuscation']) && empty($r['suspicious_files'])): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center mt-4">
            <svg class="h-12 w-12 mx-auto text-emerald-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-semibold text-emerald-800">Tidak ditemukan file mencurigakan</p>
            <p class="text-xs text-emerald-600 mt-1">Source code bersih dari backdoor, malware, dan obfuscation.</p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Info Panel -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm h-fit">
        <h3 class="text-sm font-semibold text-slate-800 mb-3">Tentang Scanner</h3>
        <div class="space-y-2 text-xs text-slate-600">
            <p>Memindai source code PHP untuk mendeteksi:</p>
            <ul class="space-y-1.5">
                <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>Fungsi berbahaya (eval, system, exec)</li>
                <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>Obfuscation & encoded payload</li>
                <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>Webshell & backdoor</li>
                <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>SEO spam & hidden redirect</li>
                <li class="flex gap-2"><span class="h-1.5 w-1.5 rounded-full bg-indigo-500 mt-1.5 shrink-0"></span>File mencurigakan di folder upload</li>
            </ul>
            <div class="mt-4 p-3 rounded-xl bg-slate-50">
                <p class="font-medium text-slate-700 mb-1">Severity Levels</p>
                <div class="space-y-0.5 text-[10px]">
                    <p><span class="text-rose-600 font-semibold">Critical</span> — Potensi backdoor/execution</p>
                    <p><span class="text-amber-600 font-semibold">High</span> — Mencurigakan, perlu dicek</p>
                    <p><span class="text-slate-500 font-semibold">Medium</span> — Perlu verifikasi manual</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>
