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
        $exactGalleries = $this->exactGalleries();

        Product::query()->where('status', 'active')->orderBy('id')->each(
            function (Product $product) use ($exactGalleries): void {
                $gallery = $exactGalleries[$product->slug] ?? null;

                // Unknown products keep their existing images. Fabricating four
                // crops from one photo makes a misleading gallery.
                if ($gallery === null) {
                    return;
                }

                $images = $gallery['images'];
                $productSourceUrl = $gallery['source_url'] ?? $product->source_url;
                $imageSourceUrl = array_key_exists('image_source_url', $gallery)
                    ? $gallery['image_source_url']
                    : $productSourceUrl;
                $storageDisk = $gallery['storage_disk'] ?? 'external';
                $isLicensed = $gallery['is_licensed'] ?? false;

                if (array_key_exists('source_url', $gallery)) {
                    $product->forceFill(['source_url' => $productSourceUrl])->save();
                }

                $product->images()->update(['is_primary' => false]);

                foreach ($images as $index => $path) {
                    $sortOrder = $index + 1;

                    ProductImage::updateOrCreate(
                        ['product_id' => $product->id, 'sort_order' => $sortOrder],
                        [
                            'product_variant_id' => null,
                            'storage_disk' => $storageDisk,
                            'path' => $path,
                            'alt_text' => $product->name.' — ảnh '.($sortOrder),
                            'is_primary' => $sortOrder === 1,
                            'is_licensed' => $isLicensed,
                            'source_url' => $imageSourceUrl,
                        ],
                    );
                }

                $product->images()->whereNotIn('sort_order', range(1, count($images)))->delete();
            }
        );
    }

    /**
     * Product photos are deliberately keyed by the exact store slug/model. Do not
     * replace this with a shared watch/jewelry pool: that can mix different models.
     *
     * @return array<string, array{
     *     images: list<string>,
     *     source_url?: string,
     *     storage_disk?: string,
     *     is_licensed?: bool,
     *     image_source_url?: string|null
     * }>
     */
    private function exactGalleries(): array
    {
        return [
            'seiko-presage-cocktail-time-srpb43' => [
                'source_url' => 'https://seikousa.com/products/srpb43',
                'images' => [
                    'https://seikousa.com/cdn/shop/files/SRPB43_1_91811358-199c-4511-af1c-24d997ab93ad.png?v=1787072328&width=1946',
                    'https://seikousa.com/cdn/shop/files/SRPB43_2_cb18745c-bd6b-47a8-8c57-b6ea13a51ed8.png?v=1787072328&width=1946',
                    'https://seikousa.com/cdn/shop/files/SRPB43_3_1379e4c0-36aa-4c74-ba2e-0284892406c3.png?v=1787072328&width=1946',
                    'https://seikousa.com/cdn/shop/files/SRPB43_4_d4438d03-2937-4818-b4db-c8cfc412cab4.png?v=1787072328&width=1946',
                ],
            ],
            'citizen-tsuyosa-nj0150-56l' => [
                'source_url' => 'https://www.citizenwatch.com/ca/en/product/NJ0150-56L.html',
                'images' => [
                    'https://citizenwatch.widen.net/content/thxpu5c2rt/webp/TSUYOSA.webp?u=41zuoe&width=1000&height=1250&quality=86&crop=false&keep=c&color=F9F8F6',
                    'https://citizenwatch.widen.net/content/qhr0rjdfs1/webp/TSUYOSA.webp?u=41zuoe&width=1000&height=1250&quality=86&crop=false&keep=c&color=F9F8F6',
                    'https://citizenwatch.widen.net/content/vcsv9dkvun/webp/TSUYOSA.webp?u=41zuoe&width=1000&height=1250&quality=86&crop=false&keep=c&color=F9F8F6',
                    'https://citizenwatch.widen.net/content/hezi7vpbpu/webp/TSUYOSA.webp?u=41zuoe&width=1000&height=1250&quality=86&crop=false&keep=c&color=F9F8F6',
                ],
            ],
            'pandora-row-of-hearts-ring' => [
                'source_url' => 'https://us.pandora.net/en/rings/stackable-rings/row-of-hearts-ring/193427C00.html',
                'images' => [
                    'https://us.pandora.net/dw/image/v2/AAVX_PRD/on/demandware.static/-/Sites-pandora-master-catalog/default/dw22ae2168/productimages/main_rect_center/193427C00_RGB.jpg?bgcolor=F7F7F7&q=85&sfrm=png&sw=1000',
                    'https://us.pandora.net/dw/image/v2/AAVX_PRD/on/demandware.static/-/Sites-pandora-master-catalog/default/dw1db89bf9/productimages/singlepackshot_rect_center/193427C00_V2_RGB.jpg?bgcolor=F7F7F7&q=85&sfrm=png&sw=1000',
                    'https://us.pandora.net/dw/image/v2/AAVX_PRD/on/demandware.static/-/Sites-pandora-master-catalog/default/dw2fb47f89/productimages/modeldetailshot_rect/Q324_E_PDP_MODEL_SINGLE_17_1x1_RGB_ring.jpg?bgcolor=F7F7F7&q=85&sfrm=png&sw=1000',
                    'https://us.pandora.net/dw/image/v2/AAVX_PRD/on/demandware.static/-/Sites-pandora-master-catalog/default/dwa7236044/productimages/styledmodelimage_rect/Q324_E_PDP_MODEL_STYLED_04_1x1_RGB.jpg?bgcolor=F7F7F7&q=85&sfrm=png&sw=1000',
                ],
            ],
            'mejuri-mini-hoops' => [
                'source_url' => 'https://mejuri.com/products/mini-hoops',
                'images' => [
                    'https://cdn.shopify.com/s/files/1/0797/3637/3533/files/0-SingleMiniHoop-14K-Angled_045_new_1a0b95bc-7532-4ee0-a1aa-ad2079c57e04.png?v=1758043892&width=1200',
                    'https://cdn.shopify.com/s/files/1/0797/3637/3533/files/2-SingleMiniHoop-14k-Stack_025.jpg?v=1758043892&width=1200',
                    'https://cdn.shopify.com/s/files/1/0797/3637/3533/files/3-SingleMiniHoop-14K-Clasp_131_new_1bd23ca4-3c7c-4b8e-8435-3cd482275711.png?v=1758043892&width=1200',
                    'https://cdn.shopify.com/s/files/1/0797/3637/3533/files/1-Reshoot_MiniHoop_YG_Stack_016.jpg?v=1758043892&width=1200',
                ],
            ],
            'celeste-diamond-halo-ring' => $this->conceptGallery('celeste-diamond-halo-ring'),
            'aurora-pearl-drop-earrings' => $this->conceptGallery('aurora-pearl-drop-earrings'),
            'elan-tennis-bracelet' => $this->conceptGallery('elan-tennis-bracelet'),
            'nocturne-sapphire-pendant' => $this->conceptGallery('nocturne-sapphire-pendant'),
            'solstice-rose-gold-chain' => $this->conceptGallery('solstice-rose-gold-chain'),
            'eternal-pair-wedding-bands' => $this->conceptGallery('eternal-pair-wedding-bands'),
            'moonlit-clover-bracelet' => $this->conceptGallery('moonlit-clover-bracelet'),
            'tissot-prx-powermatic-80-40mm' => [
                'source_url' => 'https://www.tissotwatches.com/en-sg/T1374071104100.html',
                'images' => [
                    'https://www.tissotwatches.com/dw/image/v2/BKKD_PRD/on/demandware.static/-/Sites-Tissot-Catalogue/default/dw95c4331d/product-pictures/63f42767-a9f5-4cdd-b952-8ea7b82b7e0c_T137-407-11-041-00_shadow.png?sh=800%2Cgravity%3Dcenter&sm=fit&sw=800',
                    'https://www.tissotwatches.com/dw/image/v2/BKKD_PRD/on/demandware.static/-/Sites-Tissot-Catalogue/default/dwe70b269e/product-pictures/cb22b17c-ceea-4284-bffc-9e4eaa61acb3_T137_407_11_041_00_B1.png?sh=800%2Cgravity%3Dcenter&sm=fit&sw=800',
                    'https://www.tissotwatches.com/dw/image/v2/BKKD_PRD/on/demandware.static/-/Sites-Tissot-Catalogue/default/dwe4bc4324/product-pictures/0b15ba27-315a-43d9-ab0a-255d0ade805f_T605-046-447_ZOOM.png?sh=800%2Cgravity%3Dcenter&sm=fit&sw=800',
                    'https://www.tissotwatches.com/dw/image/v2/BKKD_PRD/on/demandware.static/-/Sites-Tissot-Catalogue/default/dwdb099ca4/product-pictures/181e9598-ab66-498a-a38a-f25cb9725dc6_T137_407_11_041_00_WRIST.png?sh=800%2Cgravity%3Dcenter&sm=fit&sw=800',
                ],
            ],
            'orient-bambino-38-small-seconds' => [
                'source_url' => 'https://www.orientwatchusa.com/products/ra-ap0105y30b',
                'images' => [
                    'https://www.ashford.com/cdn/shop/files/RA-AP0105Y_F.jpg?v=1761219502',
                    'https://i.ebayimg.com/images/g/X0MAAOSw2u1oBPaS/s-l1200.jpg',
                    'https://i.ebayimg.com/images/g/6fEAAOSwx11oBPaT/s-l1200.jpg',
                    'https://www.hsjohnson.com/cdn/shop/files/jpeg-optimizer_RA-AP0105Y30B_Custom.jpg?v=1726473252&width=1080',
                ],
            ],
            'seiko-5-sports-srpd55k1' => [
                'source_url' => 'https://seikousa.com/products/srpd55',
                'images' => [
                    'https://seikousa.com/cdn/shop/files/SRPD55_1_7d0da364-2778-4b4d-a1e8-be406cb6965b.png?v=1786545460&width=1946',
                    'https://seikousa.com/cdn/shop/files/SRPD55_2_e057158a-412f-458d-a521-1b10a5eb1cb6.png?v=1786545460&width=1946',
                    'https://seikousa.com/cdn/shop/files/SRPD55_3_c3792c5b-d75a-43e2-b0e9-8f2d6f4cad3d.png?v=1786545460&width=1946',
                    'https://seikousa.com/cdn/shop/files/SRPD55_4_da371811-da57-44cc-b548-d2209fa47a9f.png?v=1786545460&width=1946',
                ],
            ],
            'citizen-eco-drive-field-bm8180' => [
                'source_url' => 'https://www.citizenwatch.com/us/en/product/BM8180-03E',
                'images' => [
                    'https://citizenwatch.widen.net/content/gzoliud0hm/webp',
                    'https://citizenwatch.widen.net/content/rqeea2ndfy/webp',
                    'https://citizenwatch.widen.net/content/menkjxay6b/webp',
                    'https://citizenwatch.widen.net/content/hezi7vpbpu/webp',
                ],
            ],
            'tissot-le-locle-powermatic-80' => [
                'source_url' => 'https://www.tissotwatches.com/es-es/T0064071603300.html',
                'images' => [
                    'https://www.tissotwatches.com/dw/image/v2/BKKD_PRD/on/demandware.static/-/Sites-Tissot-Catalogue/default/dw5a850c8f/product-pictures/2c472ef0-ed0f-435d-b047-2aa567465433_T006-407-16-033-00_shadow.png?sh=800%2Cgravity%3Dcenter&sm=fit&sw=800',
                    'https://www.tissotwatches.com/dw/image/v2/BKKD_PRD/on/demandware.static/-/Sites-Tissot-Catalogue/default/dw9dd9758a/product-pictures/9e6e841a-b73e-4cda-8ff3-ff667a680dc4_T006-407-16-033-00_Profil.png?sh=800%2Cgravity%3Dcenter&sm=fit&sw=800',
                    'https://www.tissotwatches.com/dw/image/v2/BKKD_PRD/on/demandware.static/-/Sites-Tissot-Catalogue/default/dwdb5fda20/product-pictures/7133006f-fe6d-4547-9fac-792fef26ef51_T006_407_16_033_00_B1.png?sh=800%2Cgravity%3Dcenter&sm=fit&sw=800',
                    'https://www.tissotwatches.com/dw/image/v2/BKKD_PRD/on/demandware.static/-/Sites-Tissot-Catalogue/default/dw18ff4806/product-pictures/c8598887-9038-4abe-8fc7-877a02c3f1ad_T006_407_16_033_00_WRIST.png?sh=800%2Cgravity%3Dcenter&sm=fit&sw=800',
                ],
            ],
            'orient-kamasu-red-dial' => [
                'source_url' => 'https://www.orientwatchusa.com/products/ra-aa0003r39b',
                'images' => [
                    'https://mzwatcheslk.com/cdn/shop/files/5C1CCA21-02BE-46CF-84E8-569FB681F9C5.jpg?v=1719190267&width=1946',
                    'https://img.chrono24.com/images/uhren/34934344-ix81jxy6hkaozmm7ux5g4id1-ExtraLarge.jpg',
                    'https://www.zegarek.net/imageslib/produkty/duze/zegarek-meski-orient-classic-automatic-ra-aa0003r19b-mako-iii-3.jpg',
                    'https://www.zegarek.net/imageslib/produkty/duze/zegarek-meski-orient-sports-ra-aa0003r19b-mako-iii-9.jpg',
                ],
            ],
            'casio-edifice-slim-sapphire' => [
                'source_url' => 'https://www.casio.com/intl/watches/edifice/product.EFR-S108D-2AV/',
                'images' => [
                    'https://www.casio.com/content/dam/casio/product-info/locales/intl/en/timepiece/product/watch/E/EF/EFR/efr-s108d-2av/assets/EFR-S108D-2AVU.png.transform/main-visual-sp/image.png',
                    'https://www.casio.com/content/dam/casio/product-info/locales/intl/en/timepiece/product/watch/E/EF/EFR/efr-s108d-2av/assets/EFR-S108D-2AV_theme02.jpg.transform/main-visual-sp/image.jpg',
                    'https://www.casio.com/content/dam/casio/product-info/locales/intl/en/timepiece/product/watch/E/EF/EFR/efr-s108d-2av/assets/EFR-S108D-2AV_theme03.jpg.transform/main-visual-sp/image.jpg',
                    'https://www.casio.com/content/dam/casio/product-info/locales/intl/en/timepiece/product/watch/E/EF/EFR/efr-s108d-2av/assets/EFR-S108D-2AV_theme04.jpg.transform/main-visual-sp/image.jpg',
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     images: list<string>,
     *     storage_disk: string,
     *     is_licensed: bool,
     *     image_source_url: null
     * }
     */
    private function conceptGallery(string $slug): array
    {
        return [
            'storage_disk' => 'public',
            'is_licensed' => true,
            'image_source_url' => null,
            'images' => array_map(
                fn (int $index): string => sprintf('/images/products/concepts/%s/%s-%02d.jpg', $slug, $slug, $index),
                range(1, 4),
            ),
        ];
    }
}
