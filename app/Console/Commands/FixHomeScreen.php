<?php

namespace App\Console\Commands;

use Beike\Repositories\SettingRepo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixHomeScreen extends Command
{
    protected $signature   = 'home:fix {--limit=8 : Products per module}';
    protected $description = 'Replace soft-deleted home screen products with currently active ones';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $current = system_setting('base.app_home_setting');
        $modules = $current['modules'] ?? [];

        if (empty($modules)) {
            $this->error('No modules found in app_home_setting.');
            return 1;
        }

        $moduleIndex = 0;
        foreach ($modules as &$mod) {
            if ($mod['code'] !== 'product') {
                continue;
            }

            $existing = collect($mod['content']['products'] ?? []);
            $existingIds = $existing->pluck('id')->toArray();

            // Count how many are actually active (not soft-deleted)
            $activeCount = DB::table('products')
                ->whereIn('id', $existingIds)
                ->whereNull('deleted_at')
                ->where('active', 1)
                ->count();

            if ($activeCount >= count($existingIds)) {
                $this->info("Module {$moduleIndex}: all {$activeCount} products already active, skipping.");
                $moduleIndex++;
                continue;
            }

            $this->warn("Module {$moduleIndex}: {$activeCount}/{$existingIds} active. Replacing with new products...");

            // Pick random active products with Mongolian names and images
            $newProducts = DB::table('products as p')
                ->join('product_descriptions as pd', 'p.id', '=', 'pd.product_id')
                ->whereNull('p.deleted_at')
                ->where('p.active', 1)
                ->where('pd.locale', 'mn')
                ->where('pd.name', '!=', '')
                ->whereNotNull('p.images')
                ->where('p.images', '!=', '[]')
                ->whereNotIn('p.id', $existingIds)
                ->orderByRaw('RAND()')
                ->limit($limit)
                ->get(['p.id', 'pd.name', 'p.images']);

            $entries = [];
            foreach ($newProducts as $row) {
                $images = json_decode($row->images, true) ?? [];
                $image  = $images[0] ?? '';
                $entries[] = [
                    'id'           => $row->id,
                    'name'         => $row->name,
                    'image'        => $image,
                    'image_format' => $image,
                    'status'       => true,
                ];
                $this->line("  + #{$row->id} " . mb_substr($row->name, 0, 50));
            }

            $mod['content']['products'] = $entries;
            $moduleIndex++;
        }
        unset($mod);

        $current['modules'] = $modules;
        SettingRepo::storeValue('app_home_setting', $current);

        $this->newLine();
        $this->info('Home screen updated successfully.');

        return 0;
    }
}
