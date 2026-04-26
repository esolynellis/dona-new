<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class TranslateProductNames extends Command
{
    protected $signature = 'products:translate-mn
                            {--batch=50 : Number of products per API call}
                            {--offset=0 : Start from this offset}
                            {--limit=0 : Max products to translate (0 = all)}
                            {--product-ids= : Comma-separated product_ids to translate}
                            {--dry-run : Preview without saving}';

    protected $description = 'Translate product names to natural Mongolian using Claude API';

    private string $model = 'claude-haiku-4-5-20251001';

    public function handle(): int
    {
        $apiKey = config('services.anthropic.key', '');
        $batchSize = (int) $this->option('batch');
        $offset    = (int) $this->option('offset');
        $limit     = (int) $this->option('limit');
        $dryRun    = $this->option('dry-run');

        $productIds = $this->option('product-ids')
            ? array_map('intval', explode(',', $this->option('product-ids')))
            : [];

        $query = DB::table('product_descriptions')
            ->where('locale', 'mn')
            ->orderBy('product_id');

        if ($productIds) {
            $query->whereIn('product_id', $productIds);
        }

        if ($limit > 0) {
            $query->limit($limit)->skip($offset);
        }

        $total = (clone $query)->count();
        $this->info("Found {$total} Mongolian product descriptions.");

        if ($total === 0) {
            $this->warn('No products found with locale=mn.');
            return 0;
        }

        $bar      = $this->output->createProgressBar($total);
        $updated  = 0;
        $failed   = 0;
        $client   = new Client(['timeout' => 60]);

        $products = $query->get(['id', 'product_id', 'name']);

        foreach ($products->chunk($batchSize) as $batch) {
            $names = $batch->pluck('name', 'id')->toArray();

            $translated = $this->translateBatch($client, $names, $apiKey);

            if ($translated === null) {
                $failed += count($names);
                $bar->advance(count($names));
                continue;
            }

            foreach ($batch as $row) {
                $newName = $translated[$row->id] ?? null;
                if ($newName && $newName !== $row->name) {
                    if (!$dryRun) {
                        DB::table('product_descriptions')
                            ->where('id', $row->id)
                            ->update(['name' => $newName]);
                    } else {
                        $this->line("\n  [#{$row->product_id}] {$row->name}  →  {$newName}");
                    }
                    $updated++;
                }
                $bar->advance();
            }

            // Avoid rate limits
            usleep(300_000);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Updated: {$updated} | Failed batches: {$failed}");

        return 0;
    }

    private function translateBatch(Client $client, array $idToName, string $apiKey): ?array
    {
        $lines = [];
        foreach ($idToName as $id => $name) {
            $lines[] = "{$id}|{$name}";
        }
        $input = implode("\n", $lines);

        $prompt = <<<PROMPT
Дараах бараануудын нэрийг боловсруул. Дүрэм:
- Бараануудын нэр нь "тайлбар-нэр-брэнд" дарааллаар бичигдсэн байна — энэ дарааллыг өөрчлөхгүй хадгал
- Хаалтан дотор байгаа хайрцгийн тоо, ширхэгийн мэдээлэл (жишээ: "1 хайрцаг * 50 хос", "12 лааз" гэх мэт) бол агуулалтын мэдээлэл — УСТГА
- Жин, хэмжээ (г, мл, kg, л) болон загварын дугаар зэрэг чухал мэдээллийг хадгал
- Монгол үгийг байгалийн, товч хэлбэрт оруул
- Брэндийн нэрийг (Lay's, Oreo, Miaowu, Longbang гэх мэт) мөн үлдээ, орчуулахгүй
- ЧУХАЛ: Хятад брэндийн нэрийг монголоор шууд орчуулсан хачин, байгалийн бус үг хэллэгийг (жишээ: "Гал тогооны сүйт бүсгүй", "Гал тогооны хатагтай" гэх мэт) ХЯТАД ТРАНСЛИТЕРАЦИ болгон бич эсвэл товчилж бич — монгол утга нь ойлгомжгүй бол хятадаар нь транслитерат хий (厨娘 → Чуняан гэх мэт)
- Хэрэв аль хэдийн товч, зөв монгол бол утгыг нь өөрчлөхгүй, зөвхөн хаалтан мэдээллийг хас
- Хариултыг ЗӨВХӨН "id|нэр" форматаар өг, нэмэлт тайлбар хэрэггүй

{$input}
PROMPT;

        try {
            $resp = $client->post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'json' => [
                    'model'      => $this->model,
                    'max_tokens' => 4096,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);

            $body   = json_decode((string) $resp->getBody(), true);
            $text   = $body['content'][0]['text'] ?? '';
            $result = [];

            foreach (explode("\n", trim($text)) as $line) {
                $line = trim($line);
                if (!str_contains($line, '|')) continue;
                [$id, $name] = explode('|', $line, 2);
                $id = (int) trim($id);
                if ($id > 0) {
                    $result[$id] = trim($name);
                }
            }

            return $result;
        } catch (\Throwable $e) {
            $this->error("\nAPI error: " . $e->getMessage());
            return null;
        }
    }
}
