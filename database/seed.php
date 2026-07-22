<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$db = App\Config\Database::getConnection();
$seederDir = __DIR__ . '/seeders';
$files = glob($seederDir . '/*.sql');
sort($files);

echo "Running seeders...\n";

foreach ($files as $file) {
    $filename = basename($file);
    echo "  {$filename}... ";
    try {
        $sql = file_get_contents($file);
        $db->exec($sql);
        echo "OK\n";
    } catch (\PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\nSeeding complete.\n";
