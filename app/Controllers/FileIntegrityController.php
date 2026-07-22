<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\User;
use App\Services\FileIntegrityMonitor;

class FileIntegrityController
{
    public function index(): string
    {
        Auth::check();
        $db = \App\Config\Database::getConnection();
        $websiteId = (int)($_GET['website_id'] ?? 0);

        $websites = $db->query("SELECT id, nama_website FROM websites WHERE status='active' ORDER BY nama_website")->fetchAll();

        $changes = [];
        $summary = null;
        if ($websiteId > 0) {
            $stmt = $db->prepare("SELECT * FROM file_changes WHERE website_id = ? ORDER BY detected_at DESC LIMIT 50");
            $stmt->execute([$websiteId]);
            $changes = $stmt->fetchAll();

            $sumStmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN change_type='modified' THEN 1 ELSE 0 END) as modified, SUM(CASE WHEN change_type='added' THEN 1 ELSE 0 END) as added, SUM(CASE WHEN change_type='deleted' THEN 1 ELSE 0 END) as deleted FROM file_changes WHERE website_id = ?");
            $sumStmt->execute([$websiteId]);
            $summary = $sumStmt->fetch();
        }

        return view('file_integrity.index', [
            'websites' => $websites,
            'changes' => $changes,
            'summary' => $summary,
            'selectedWebsiteId' => $websiteId,
            'pageTitle' => 'File Integrity',
        ]);
    }

    public function scan(): void
    {
        Auth::check();
        $websiteId = (int)($_POST['website_id'] ?? 0);
        $path = $_POST['path'] ?? '';

        if ($websiteId <= 0 || !$path) {
            $_SESSION['error'] = 'Pilih website dan path direktori';
            redirect('/file-integrity');
        }

        $fim = new FileIntegrityMonitor($websiteId, $path);
        try {
            $results = $fim->run();
            User::logActivity($_SESSION['user_id'], 'FIM Scan', "Scan {$results['scanned']} files: {$results['modified']} modified, {$results['added']} new, {$results['deleted']} deleted");
            $_SESSION['success'] = "FIM Selesai! Scanned: {$results['scanned']}, Modified: {$results['modified']}, Added: {$results['added']}, Deleted: {$results['deleted']}";
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'FIM Error: ' . $e->getMessage();
        }
        redirect('/file-integrity?website_id=' . $websiteId);
    }

    public function markReviewed(): void
    {
        Auth::check();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            \App\Config\Database::getConnection()->prepare("UPDATE file_changes SET is_reviewed = 1 WHERE id = ?")->execute([$id]);
            jsonResponse(['success' => true]);
        }
    }
}
