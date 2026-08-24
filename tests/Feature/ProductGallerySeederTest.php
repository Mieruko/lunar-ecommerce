<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\ProductGallerySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductGallerySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_galleries_never_mix_unrelated_product_photos(): void
    {
        $category = Category::create([
            'name' => 'Đồng hồ',
            'slug' => 'watches',
            'is_active' => true,
        ]);
        $prx = Product::create([
            'category_id' => $category->id,
            'name' => 'Tissot PRX Powermatic 80 40mm',
            'slug' => 'tissot-prx-powermatic-80-40mm',
            'product_type' => 'watch',
            'status' => 'active',
            'base_price_amount' => 21800000,
            'currency' => 'VND',
        ]);
        foreach (range(1, 4) as $sortOrder) {
            ProductImage::create([
                'product_id' => $prx->id,
                'storage_disk' => 'external',
                'path' => 'https://images.unsplash.com/photo-unrelated-'.$sortOrder,
                'sort_order' => $sortOrder,
                'is_primary' => $sortOrder === 1,
            ]);
        }

        $concept = Product::create([
            'category_id' => $category->id,
            'name' => 'LUNAR Concept Watch',
            'slug' => 'lunar-concept-watch',
            'product_type' => 'watch',
            'status' => 'active',
            'base_price_amount' => 5000000,
            'currency' => 'VND',
            'source_url' => 'https://example.test/concept',
        ]);
        ProductImage::create([
            'product_id' => $concept->id,
            'storage_disk' => 'external',
            'path' => 'https://images.unsplash.com/photo-concept-model?auto=format&w=800',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        (new ProductGallerySeeder)->run();

        $prx->refresh();
        $prxImages = $prx->images()->orderBy('sort_order')->get();
        $this->assertCount(4, $prxImages);
        $this->assertTrue($prxImages->every(fn (ProductImage $image): bool => Str::contains($image->path, 'tissotwatches.com')));
        $this->assertFalse($prxImages->contains(fn (ProductImage $image): bool => Str::contains($image->path, 'unsplash.com')));
        $this->assertSame('https://www.tissotwatches.com/en-sg/T1374071104100.html', $prx->source_url);
        $this->assertSame(1, $prxImages->where('is_primary', true)->count());
        $this->assertSame(1, (int) $prxImages->first()->is_primary);

        $conceptImages = $concept->images()->orderBy('sort_order')->get();
        $this->assertCount(4, $conceptImages);
        $this->assertTrue($conceptImages->every(
            fn (ProductImage $image): bool => Str::startsWith($image->path, 'https://images.unsplash.com/photo-concept-model?')
        ));
        $this->assertFalse($conceptImages->contains(
            fn (ProductImage $image): bool => Str::contains($image->path, 'photo-unrelated')
        ));
        $this->assertSame(1, $conceptImages->where('is_primary', true)->count());
    }
}
