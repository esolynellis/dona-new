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
                            {--newest : Order by newest product_id first}
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
        $newest    = $this->option('newest');

        $productIds = $this->option('product-ids')
            ? array_map('intval', explode(',', $this->option('product-ids')))
            : [];

        $query = DB::table('product_descriptions')
            ->where('locale', 'mn')
            ->orderBy('product_id', $newest ? 'desc' : 'asc');

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

            // Avoid rate limits (2s between batches)
            sleep(2);
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

1. ДАРААЛАЛ: Нэр нь "тайлбар-нэр-брэнд" дарааллаар байна — дарааллыг өөрчлөхгүй хадгал.

2. ХААЛТАН МЭДЭЭЛЭЛ УСТГА: "(1 хайрцаг * 50 хос)", "(12 лааз)" зэрэг хайрцгийн тоо/ширхэгийн мэдээллийг бүгдийг хас.

3. ЖИНГИЙН МЭДЭЭЛЭЛ: Жин, хэмжээ (г, мл, kg, л) болон загварын дугаарыг хадгал.

4. БРЭНДИЙН НЭР — ЗӨВХӨН ДУУДЛАГААР БИЧ, УТГААР НЬ БҮҮ ОРЧУУЛ:
   ДҮРЭМ: Ямар ч брэндийн нэрийг монгол утгаар орчуулж болохгүй. Хятад дуудлагаар кирилл бич.
   - Латин үсгийн брэнд → монгол дуудлагаар (Lay's→Лэйс, Oreo→Орео, Sanyang→Саньян, Longbang→Лонбан, Yingduo→Индуо, Miaowu→Мяоуу)
   - Хятад үсгийн брэнд эсвэл монгол утгаар орчуулагдсан хятад брэнд → хятад дуудлагаар кирилл (厨娘→Чуняан, 金汤→Жинтан, 熊果→Шюнгуо, 山姆大叔→Шэмдашу, 银鼠→Инзгу)
   - БУРУУ: "Алтан шөл" (утга), "Гал тогооны сүйт бүсгүй" (утга), "Мөнгөн хулгана" (утга)
   - ЗӨВШӨӨРӨХ: "Жинтан" (дуудлага), "Чуняан" (дуудлага), "Инзгу" (дуудлага)
   - Монгол брэнд бол хэвээр үлдээ

5. МАРКЕТИНГИЙН ХЭЛЛЭГ УСТГА: "Бэлчээрийн мал аж ахуйн", "Бага илчлэг удаан нүүрс ус", "Өндөр чанартай", "Байгалийн органик" зэрэг урт маркетингийн тайлбар хэллэгийг бүгдийг хас. Бараа юу болохыг товч нэрлэ (жишээ: "Бэлчээрийн мал аж ахуйн ногоо Цүйүэ лийр хатаасан 150г" → "Цүйүэ хатаасан лийр 150г").

6. ХООСОН ДҮРСЛЭЛИЙН ҮГС УСТГА: "агшин зуурын", "шуурхай", "хурдан", "түргэн" зэрэг бараанаас ойлгомжтой байдаг илүүц тодотгол үгсийг хас. Тайлбар нь чухал мэдээлэл агуулж байвал л үлдээ (жишээ: "Агшин зуурын гоймон далайн хоолны амт" → "Далайн хоолтой гоймон", "Сагаган шуурхай гоймон чинжүүтэй" → "Сагаган гоймон чинжүүтэй").

7. УЛСЫН НЭР ЗААВАЛ ХАС: "Турк", "Туркийн", "Солонгос", "Солонгосын", "Хятад", "Хятадын", "Япон", "Японы", "Итали", "Итали маягийн", "Тайланд" зэрэг улс орны нэрийг БҮГДИЙГ хас. Брэндийн нэрний салшгүй хэсэг биш бол ямар ч тохиолдолд хас (жишээ: "Туркийн гоймон" → "гоймон", "Солонгосын мандугай" → "мандугай", "Турк хиам" → "хиам").

8. ОРЧУУЛГЫН АЛДАА ГАРГАХГҮЙ БАЙ: Буруу орчуулагдсан үгсийг хас. Тодорхой жишээ: "вазелин" (бараа нь вазелин биш бол), "үүр" (бараа нь үүр биш бол — "үүр" гэдэг нь хятадын 巢/窝 гэсэн үгний буруу орчуулга байж болно). Зөвхөн баталгаатай мэдээллийг нэрт оруул.

9. ТАЙЛБАР ҮГС: Зайлшгүй шаардлагатай тайлбар үгсийг байгалийн, товч хэлбэрт оруул.

10. Хариултыг ЗӨВХӨН "id|нэр" форматаар өг, нэмэлт тайлбар хэрэггүй.

{$input}
PROMPT;

        $attempts = 0;
        while ($attempts < 4) {
            $attempts++;
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
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getResponse()->getStatusCode() === 429 && $attempts < 4) {
                    $wait = $attempts * 30;
                    $this->warn("\n429 rate limit — waiting {$wait}s (attempt {$attempts}/4)");
                    sleep($wait);
                    continue;
                }
                $this->error("\nAPI error: " . $e->getMessage());
                return null;
            } catch (\Throwable $e) {
                $this->error("\nAPI error: " . $e->getMessage());
                return null;
            }
        }
        return null;
    }
}
