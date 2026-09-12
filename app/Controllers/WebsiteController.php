<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\Auth;
use App\Models\Website;
use App\Models\User;

class WebsiteController
{
    public function index(): string
    {
        Auth::check();
        $websites = Website::withLatestStatus();
        return view('websites.index', ['websites' => $websites, 'pageTitle' => 'Websites']);
    }

    public function create(): string
    {
        Auth::check();
        return view('websites.form', ['pageTitle' => 'Tambah Website', 'website' => null]);
    }

    public function store(): void
    {
        Auth::check();
        $id = Website::insert([
            'nama_website' => $_POST['nama_website'] ?? '',
            'url' => $_POST['url'] ?? '',
            'deskripsi' => $_POST['deskripsi'] ?? '',
            'kategori' => $_POST['kategori'] ?? '',
            'ga_property_id' => trim($_POST['ga_property_id'] ?? '') ?: null,
            'ga_client_email' => trim($_POST['ga_client_email'] ?? '') ?: null,
            'ga_private_key' => trim($_POST['ga_private_key'] ?? '') ?: null,
        ]);
        User::logActivity($_SESSION['user_id'], 'Tambah Website', 'Menambahkan website: ' . ($_POST['nama_website'] ?? ''));
        $_SESSION['success'] = 'Website berhasil ditambahkan!';
        redirect('/websites');
    }

    public function edit(array $params): string
    {
        Auth::check();
        $website = Website::find((int)$params['id']);
        if (!$website) abort(404);
        return view('websites.form', ['pageTitle' => 'Edit Website', 'website' => $website]);
    }

    public function update(array $params): void
    {
        Auth::check();
        Website::update((int)$params['id'], [
            'nama_website' => $_POST['nama_website'] ?? '',
            'url' => $_POST['url'] ?? '',
            'deskripsi' => $_POST['deskripsi'] ?? '',
            'kategori' => $_POST['kategori'] ?? '',
            'ga_property_id' => trim($_POST['ga_property_id'] ?? '') ?: null,
            'ga_client_email' => trim($_POST['ga_client_email'] ?? '') ?: null,
            'ga_private_key' => trim($_POST['ga_private_key'] ?? '') ?: null,
            'status' => $_POST['status'] ?? 'active',
        ]);
        User::logActivity($_SESSION['user_id'], 'Edit Website', 'Mengedit website ID: ' . $params['id']);
        $_SESSION['success'] = 'Website berhasil diperbarui!';
        redirect('/websites');
    }

    /**
     * Test koneksi GA dengan kredensial yang diisi di form (sebelum disimpan).
     */
    public function testGa(): void
    {
        Auth::check();
        $creds = [
            'ga_client_email' => trim((string)($_POST['ga_client_email'] ?? '')),
            'ga_private_key' => trim((string)($_POST['ga_private_key'] ?? '')),
        ];
        $propertyId = trim((string)($_POST['ga_property_id'] ?? ''));

        $ga = new \App\Services\GoogleAnalytics();
        $result = $ga->testConnection($propertyId !== '' ? $propertyId : null, $creds);
        jsonResponse($result);
    }

    public function destroy(array $params): void
    {
        Auth::check();
        Website::delete((int)$params['id']);
        User::logActivity($_SESSION['user_id'], 'Hapus Website', 'Menghapus website ID: ' . $params['id']);
        $_SESSION['success'] = 'Website berhasil dihapus!';
        redirect('/websites');
    }
}
