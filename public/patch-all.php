<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

function patchFile($path, $content) {
    @unlink($path);
    $r = file_put_contents($path, $content);
    return ['unlink' => true, 'write' => $r !== false, 'bytes' => $r];
}

// 1. HomeController - dynamic newest products
$results['HomeController'] = patchFile("$root/beike/ShopAPI/Controllers/HomeController.php", '<?php
namespace Beike\ShopAPI\Controllers;

use App\Http\Controllers\Controller;
use Beike\Services\DesignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        $appHomeData = system_setting(\'base.app_home_setting\');
        $modules     = $appHomeData[\'modules\'] ?? [];

        $productCodes = [\'product\', \'category\', \'latest\'];
        $moduleItems  = [];
        $productModuleIndex = 0;

        foreach ($modules as $module) {
            $code    = $module[\'code\'];
            $content = $module[\'content\'];

            if ($code === \'product\') {
                $offset = $productModuleIndex * 8;
                $ids = DB::table(\'products as p\')
                    ->join(\'product_descriptions as pd\', \'p.id\', \'=\', \'pd.product_id\')
                    ->whereNull(\'p.deleted_at\')
                    ->where(\'p.active\', 1)
                    ->where(\'pd.locale\', \'mn\')
                    ->where(\'pd.name\', \'!=\', \'\')
                    ->whereNotNull(\'p.images\')
                    ->where(\'p.images\', \'!=\', \'[]\')
                    ->orderBy(\'p.created_at\', \'desc\')
                    ->offset($offset)
                    ->limit(8)
                    ->pluck(\'p.id\')
                    ->toArray();
                $content[\'products\'] = $ids;
                $productModuleIndex++;
            } elseif (in_array($code, $productCodes)) {
                $content[\'products\'] = collect($content[\'products\'])->pluck(\'id\')->toArray();
            }

            $moduleItems[] = [
                \'code\'    => $code,
                \'content\' => DesignService::handleModuleContent($code, $content),
            ];
        }

        return json_success(trans(\'common.get_success\'), $moduleItems);
    }
}
');

// 2. ProductRepo - similar products with fallback
$productRepoPath = "$root/beike/Repositories/ProductRepo.php";
$repoContent = file_get_contents($productRepoPath);

$oldBlock = '        $products = $builder->inRandomOrder()->limit($limit)->get();

        // Хэрэв ижил ангилалд хангалттай бараа олдоогүй бол брэндээр нэмж авна
        if ($products->count() < $limit && $product->brand_id) {
            $existingIds = $products->pluck(\'id\')->toArray();
            $existingIds[] = $product->id;

            $extraBuilder = Product::query()
                ->with([\'description\', \'skus\', \'masterSku\', \'brand\', \'inCurrentWishlist\'])
                ->whereHas(\'masterSku\')
                ->where(\'products.active\', true)
                ->where(\'products.brand_id\', $product->brand_id)
                ->whereNotIn(\'products.id\', $existingIds);

            $extraBuilder->leftJoin(\'product_descriptions as pd\', function ($build) {
                $build->whereColumn(\'pd.product_id\', \'products.id\')
                    ->where(\'locale\', locale());
            })->select([\'products.*\', \'pd.name\', \'pd.content\', \'pd.meta_title\', \'pd.meta_description\', \'pd.meta_keywords\']);

            $extra = $extraBuilder->inRandomOrder()->limit($limit - $products->count())->get();
            $products = $products->merge($extra);
        }

        return ProductSimple::collection($products);';

$newBlock = '        $products = $builder->inRandomOrder()->limit($limit)->get();

        // Хэрэв ижил ангилалд хангалттай бараа олдоогүй бол брэндээр нэмж авна
        if ($products->count() < $limit && $product->brand_id) {
            $existingIds = $products->pluck(\'id\')->toArray();
            $existingIds[] = $product->id;

            $extraBuilder = Product::query()
                ->with([\'description\', \'skus\', \'masterSku\', \'brand\', \'inCurrentWishlist\'])
                ->whereHas(\'masterSku\')
                ->where(\'products.active\', true)
                ->where(\'products.brand_id\', $product->brand_id)
                ->whereNotIn(\'products.id\', $existingIds);

            $extraBuilder->leftJoin(\'product_descriptions as pd\', function ($build) {
                $build->whereColumn(\'pd.product_id\', \'products.id\')
                    ->where(\'locale\', locale());
            })->select([\'products.*\', \'pd.name\', \'pd.content\', \'pd.meta_title\', \'pd.meta_description\', \'pd.meta_keywords\']);

            $extra = $extraBuilder->inRandomOrder()->limit($limit - $products->count())->get();
            $products = $products->merge($extra);
        }

        // Хэрэв хүрэлцэхгүй бол хамгийн шинэ идэвхтэй бараануудаар нөхнө
        if ($products->count() < $limit) {
            $existingIds = $products->pluck(\'id\')->toArray();
            $existingIds[] = $product->id;

            $fallback = Product::query()
                ->with([\'description\', \'skus\', \'masterSku\', \'brand\', \'inCurrentWishlist\'])
                ->whereHas(\'masterSku\')
                ->where(\'products.active\', true)
                ->whereNotIn(\'products.id\', $existingIds)
                ->leftJoin(\'product_descriptions as pd\', function ($build) {
                    $build->whereColumn(\'pd.product_id\', \'products.id\')
                        ->where(\'locale\', locale());
                })
                ->select([\'products.*\', \'pd.name\', \'pd.content\', \'pd.meta_title\', \'pd.meta_description\', \'pd.meta_keywords\'])
                ->orderBy(\'products.created_at\', \'desc\')
                ->limit($limit - $products->count())
                ->get();

            $products = $products->merge($fallback);
        }

        return ProductSimple::collection($products);';

if (strpos($repoContent, $oldBlock) !== false) {
    $newRepoContent = str_replace($oldBlock, $newBlock, $repoContent);
    @unlink($productRepoPath);
    $r = file_put_contents($productRepoPath, $newRepoContent);
    $results['ProductRepo'] = ['write' => $r !== false, 'bytes' => $r];
} else {
    $results['ProductRepo'] = ['note' => 'already patched or block not found'];
}

// 3. ProductDetail - add similar to API response
$detailPath = "$root/beike/Shop/Http/Resources/ProductDetail.php";
$detailContent = file_get_contents($detailPath);

$oldDetail = "            'active'           => (bool) \$this->active,\n        ];\n\n        return hook_filter";
$newDetail = "            'active'           => (bool) \$this->active,\n            'similar'          => ProductSimple::collection(\n                ProductRepo::getSimilarProducts(\$this->resource, 8)\n            )->jsonSerialize(),\n        ];\n\n        return hook_filter";

if (strpos($detailContent, "'similar'") === false) {
    $newDetailContent = str_replace($oldDetail, $newDetail, $detailContent);
    @unlink($detailPath);
    $r = file_put_contents($detailPath, $newDetailContent);
    $results['ProductDetail'] = ['write' => $r !== false, 'bytes' => $r];
} else {
    $results['ProductDetail'] = ['note' => 'already patched'];
}

// Clear all caches
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
