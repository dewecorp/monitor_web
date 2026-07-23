<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\Quarantine;
use App\Models\User;

class QuarantineController
{
    public function index(): string
    {
        Auth::check();
        $items = Quarantine::allItems();
        $activeCount = Quarantine::countActive();
        return view('quarantine.index', [
            'items' => $items,
            'activeCount' => $activeCount,
            'pageTitle' => 'Karantina',
        ]);
    }

    public function restore(): void
    {
        Auth::check();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $item = Quarantine::find($id);
            if ($item && Quarantine::restore($id)) {
                User::logActivity($_SESSION['user_id'], 'Restore File', 'Mengembalikan file: ' . ($item['original_path'] ?? $item['file_name']));
                $_SESSION['success'] = 'File berhasil dikembalikan ke lokasi asal';
            } else {
                $_SESSION['error'] = 'Gagal mengembalikan file';
            }
        }
        redirect('/quarantine');
    }

    public function deleteForever(): void
    {
        Auth::check();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $item = Quarantine::find($id);
            if ($item && Quarantine::deletePermanently($id)) {
                User::logActivity($_SESSION['user_id'], 'Hapus File Permanen', 'Menghapus permanen file: ' . ($item['original_path'] ?? $item['file_name']));
                $_SESSION['success'] = 'File berhasil dihapus permanen';
            } else {
                $_SESSION['error'] = 'Gagal menghapus file';
            }
        }
        redirect('/quarantine');
    }
}
