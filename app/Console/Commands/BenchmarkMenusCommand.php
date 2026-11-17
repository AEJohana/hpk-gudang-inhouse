<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BenchmarkMenusCommand extends Command
{
    protected $signature = 'benchmark:menus';
    protected $description = 'Benchmark response times and query counts across all navigation menus';

    public function handle(): int
    {
        $user = User::where('role', 'admin_gudang')->first() ?? User::first();
        if (!$user) {
            $this->error('No user found in database.');
            return 1;
        }

        auth()->login($user);

        $routes = [
            'Dashboard' => '/dashboard',
            'Data Gudang (Components)' => '/components',
            'Transaksi Barang' => '/transactions',
            'Work Request (WRI)' => '/work-requests',
            'Material Handling' => '/work-station-supplies',
            'Permintaan Label QR' => '/qr-requests',
            'Cycle Count (Opname)' => '/cycle-counts',
            'Peta Gudang (Locations)' => '/locations',
            'Pengaturan Profil' => '/profile',
            'ECR Revisi Part' => '/ecrs',
            'Pengajuan Disposal' => '/disposals',
        ];

        $this->info("User Logged In: {$user->name} ({$user->role})");
        $this->info(str_repeat('=', 85));
        $this->line(sprintf("%-30s | %10s | %10s | %10s | %10s", "Nama Menu", "Waktu (ms)", "Jml Query", "Status", "Size (KB)"));
        $this->info(str_repeat('=', 85));

        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

        foreach ($routes as $name => $uri) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $req = Request::create($uri, 'GET');
            $req->setUserResolver(fn() => $user);

            $start = microtime(true);
            try {
                $response = $kernel->handle($req);
                $content = $response->getContent();
                $duration = round((microtime(true) - $start) * 1000, 2);
                $queryCount = count(DB::getQueryLog());
                $status = $response->getStatusCode();
                $sizeKb = round(strlen($content) / 1024, 1);

                $formattedTime = $duration > 500 ? "<fg=red>{$duration} ms</>" : ($duration > 200 ? "<fg=yellow>{$duration} ms</>" : "<fg=green>{$duration} ms</>");
                $formattedQueries = $queryCount > 30 ? "<fg=red>{$queryCount}</>" : ($queryCount > 15 ? "<fg=yellow>{$queryCount}</>" : "<fg=green>{$queryCount}</>");

                $this->line(sprintf("%-30s | %18s | %18s | %10d | %8.1f KB", $name, $formattedTime, $formattedQueries, $status, $sizeKb));
            } catch (\Throwable $e) {
                $this->error("{$name} FAILED: " . $e->getMessage());
            }
        }

        $this->info(str_repeat('=', 85));
        return 0;
    }
}
