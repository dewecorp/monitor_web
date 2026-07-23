<?php
$user = ['nama' => $_SESSION['user_nama'] ?? 'User', 'level' => $_SESSION['user_level'] ?? 'user'];
$activeMenu = 'traffic'; require VIEW_PATH . '/layouts/main.php';
?>
<nav class="text-[11px] text-slate-500 mb-5">Dashboard <span class="mx-1 text-slate-300">/</span> <span class="text-slate-700 font-medium">Traffic</span></nav>

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-600">Traffic Monitoring</p>
        <h2 class="text-xl font-semibold text-slate-900">Analisa traffic website</h2>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Perbandingan Traffic 7 Hari</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500 w-8">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-[11px] uppercase tracking-wide text-slate-500">Website</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Pengunjung</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Page Views</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Bandwidth</th>
                    <th class="px-4 py-3 text-right font-semibold text-[11px] uppercase tracking-wide text-slate-500">Rata-rata Response</th>
                    <th class="px-4 py-3 text-center font-semibold text-[11px] uppercase tracking-wide text-slate-500">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php $i = 1; foreach ($trafficSummary as $t): ?>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-3 py-3 text-center text-[11px] text-slate-400"><?= $i++ ?></td>
                    <td class="px-4 py-3 font-medium text-slate-800"><?= e($t['nama_website']) ?></td>
                    <td class="px-4 py-3 text-right text-slate-700"><?= number_format($t['visitors']) ?></td>
                    <td class="px-4 py-3 text-right text-slate-700"><?= number_format($t['page_views']) ?></td>
                    <td class="px-4 py-3 text-right text-slate-700"><?= ($t['bandwidth'] ?? 0) >= 1024 ? round(($t['bandwidth'] ?? 0)/1024,1).' GB' : round(($t['bandwidth'] ?? 0),1).' MB' ?></td>
                    <td class="px-4 py-3 text-right <?= ($t['avg_response'] ?? 0) < 500 ? 'text-emerald-600' : (($t['avg_response'] ?? 0) < 1000 ? 'text-amber-600' : 'text-rose-600') ?>"><?= round($t['avg_response'] ?? 0) ?> ms</td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="previewWebsite('<?= e($t['url'] ?? '') ?>', '<?= e($t['nama_website'] ?? '') ?>')" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-medium text-emerald-600 hover:bg-emerald-100 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Preview
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEW_PATH . '/layouts/footer.php'; ?>

<!-- Preview Modal -->
<div id="previewModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target===this)closePreview()">
    <div class="relative w-full max-w-5xl h-[85vh] bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <span id="previewTitle" class="text-xs font-semibold text-slate-700">Website</span>
            </div>
            <div class="flex items-center gap-2">
                <a id="previewOpenNew" href="#" target="_blank" class="rounded-lg border border-slate-200 px-2.5 py-1 text-[10px] text-slate-600 hover:bg-slate-100">Buka di Tab Baru</a>
                <button onclick="closePreview()" class="rounded-lg border border-slate-200 px-2.5 py-1 text-[10px] text-slate-600 hover:bg-rose-50 hover:text-rose-600">Tutup</button>
            </div>
        </div>
        <div class="flex-1 bg-white">
            <iframe id="previewFrame" class="w-full h-full border-0" sandbox="allow-scripts allow-same-origin allow-forms" loading="lazy"></iframe>
        </div>
        <div class="px-4 py-2 border-t border-slate-200 text-[10px] text-slate-400 bg-slate-50">
            Website dimuat dalam mode preview. Beberapa fitur mungkin tidak berfungsi karena pembatasan keamanan.
        </div>
    </div>
</div>

<script>
function previewWebsite(url, name) {
    if (!url || url === '') { toastr.error('URL website tidak tersedia'); return; }
    document.getElementById('previewTitle').textContent = '🔍 ' + name;
    document.getElementById('previewFrame').src = url;
    document.getElementById('previewOpenNew').href = url;
    document.getElementById('previewModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePreview() {
    document.getElementById('previewModal').classList.add('hidden');
    document.getElementById('previewFrame').src = 'about:blank';
    document.body.style.overflow = '';
}
</script>
