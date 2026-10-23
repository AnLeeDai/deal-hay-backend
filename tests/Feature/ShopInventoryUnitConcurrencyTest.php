<?php

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

it('serializes simultaneous backfills and reuses the same two physical labels across processes', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'deal-hay-unit-concurrency-');
    $processes = [];
    try {
        // A dedicated temporary SQLite database keeps this concurrency fixture
        // separate from both the test runner and the configured application DB.
        $pdo = new PDO('sqlite:'.$path);
        $pdo->exec('CREATE TABLE shop_products (id TEXT PRIMARY KEY, shop_owner_id TEXT, stock INTEGER, has_variants INTEGER, name TEXT, price INTEGER)');
        $pdo->exec('CREATE TABLE shop_inventory_units (id TEXT PRIMARY KEY, shop_owner_id TEXT, shop_product_id TEXT, shop_product_variant_id TEXT, sku TEXT UNIQUE, status TEXT, removed_at TEXT, removed_reference_type TEXT, removed_reference_id TEXT, created_at TEXT, updated_at TEXT)');
        $productId = (string) Str::uuid7();
        $pdo->prepare('INSERT INTO shop_products (id, shop_owner_id, stock, has_variants, name, price) VALUES (?, ?, 2, 0, ?, 10000)')
            ->execute([$productId, (string) Str::uuid7(), 'Concurrent physical stock']);
        // Hold the first insert long enough for the other request to overlap.
        $pdo->exec('CREATE TRIGGER pause_unit_insert BEFORE INSERT ON shop_inventory_units BEGIN SELECT inventory_insert_delay(); END');
        $pdo = null;

        for ($index = 0; $index < 2; $index++) {
            $process = new Process([
                PHP_BINARY, '-r', <<<'PHP'
                require $argv[1].'/vendor/autoload.php';
                $app = require $argv[1].'/bootstrap/app.php';
                $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
                Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout = 5000');
                Illuminate\Support\Facades\DB::connection()->getPdo()->sqliteCreateFunction('inventory_insert_delay', function (): int { usleep(100000); return 1; });
                touch($argv[3]);
                $deadline = microtime(true) + 10;
                while (!file_exists($argv[4])) {
                    if (microtime(true) > $deadline) throw new RuntimeException('Concurrency barrier timed out.');
                    usleep(1000);
                }
                $product = App\Models\ShopProduct::findOrFail($argv[2]);
                $service = $app->make(App\Services\ShopInventoryUnitService::class);
                $units = $service->ensureForProduct($product);
                echo json_encode(['ids' => $units->modelKeys(), 'skus' => $units->pluck('sku')->all()], JSON_THROW_ON_ERROR);
                PHP,
                base_path(), $productId, $path.'.ready-'.$index, $path.'.go',
            ], env: [
                'APP_ENV' => 'testing', 'APP_DEBUG' => 'false',
                'APP_CONFIG_CACHE' => $path.'.config.php',
                'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
                'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $path, 'DB_URL' => '',
                'CACHE_STORE' => 'array', 'LOG_CHANNEL' => 'null',
            ]);
            $process->start();
            $processes[] = $process;
        }
        $deadline = microtime(true) + 10;
        while (! file_exists($path.'.ready-0') || ! file_exists($path.'.ready-1')) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Worker processes did not reach the concurrency barrier.');
            }
            usleep(1000);
        }
        file_put_contents($path.'.go', 'go');
        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
            $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        }
        expect($results[0]['ids'])->toHaveCount(2)->toBe($results[1]['ids']);
        expect($results[0]['skus'])->toHaveCount(2)->toBe($results[1]['skus']);
        $pdo = new PDO('sqlite:'.$path);
        expect((int) $pdo->query('SELECT COUNT(*) FROM shop_inventory_units')->fetchColumn())->toBe(2);
        $pdo = null;
    } finally {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        foreach (glob($path.'*') as $file) {
            @unlink($file);
        }
    }
});
