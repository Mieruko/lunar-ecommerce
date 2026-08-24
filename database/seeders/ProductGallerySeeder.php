<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductGallerySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $watchImages = [
            'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1522312346375-d1a52e2b99b3?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1547996160-81dfa63595aa?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1612817159949-195b6eb9e31a?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1434056886845-dac89ffe9b56?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1622434641406-a158123450f9?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1539874754764-5a96559165b0?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1495856458515-0637185db551?auto=format&fit=crop&w=1400&q=88',
        ];
        $jewelryImages = [
            'https://images.unsplash.com/photo-1605100804763-247f67b3557e?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1611652022419-a9419f74343d?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1573408301185-9146fe634ad0?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1617038220319-276d3cfab638?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1673131158657-4404fd1f041a?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1599459183200-59c7687a0275?auto=format&fit=crop&w=1400&q=88',
            'https://images.unsplash.com/photo-1602173574767-37ac01994b2a?auto=format&fit=crop&w=1400&q=88',
        ];

        Product::query()->where('status', 'active')->orderBy('id')->each(
            function (Product $product) use ($watchImages, $jewelryImages): void {
                $pool = $product->product_type === 'watch' ? $watchImages : $jewelryImages;
                $primaryPath = $product->images()->where('is_primary', true)->value('path')
                    ?? $product->images()->orderBy('sort_order')->value('path');
                $candidates = collect($pool)
                    ->reject(fn (string $path): bool => $path === $primaryPath)
                    ->values();
                $offset = ($product->id * 3) % $candidates->count();

                foreach (range(2, 4) as $sortOrder) {
                    $path = $candidates[($offset + $sortOrder - 2) % $candidates->count()];

                    ProductImage::updateOrCreate(
                        ['product_id' => $product->id, 'sort_order' => $sortOrder],
                        [
                            'product_variant_id' => null,
                            'storage_disk' => 'external',
                            'path' => $path,
                            'alt_text' => $product->name.' — góc chụp '.($sortOrder - 1),
                            'is_primary' => false,
                            'is_licensed' => true,
                            'source_url' => 'https://unsplash.com/',
                        ],
                    );
                }
            }
        );
    }
}
