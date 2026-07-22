<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$db = App\Config\Database::getConnection();
$db->exec("SET FOREIGN_KEY_CHECKS = 0");

$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

echo "Running migrations...\n";

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

$db->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "\nMigrations complete.\n";
