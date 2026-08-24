<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Gemstone;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function home()
    {
        $featured = Product::with(['brand', 'primaryImage', 'variants.inventory'])->where('status', 'active')->where('is_featured', true)->take(4)->get();
        $latest = Product::with(['brand', 'primaryImage', 'variants.inventory'])->where('status', 'active')->latest()->take(4)->get();

        return view('store.home', compact('featured', 'latest'));
    }

    public function shop(Request $request, ?string $type = null)
    {
        $filters = $request->input('filter', []);
        $query = Product::with(['brand', 'category', 'primaryImage', 'variants.inventory', 'watchDetail', 'jewelryDetail'])->where('status', 'active');
        if ($type) {
            $query->where('product_type', $type);
        }
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('description', 'like', '%'.$request->string('q').'%'));
        }
        if (! empty($filters['brand'])) {
            $query->whereIn('brand_id', (array) $filters['brand']);
        }
        if (! empty($filters['category'])) {
            $query->whereIn('category_id', (array) $filters['category']);
        }
        if (! empty($filters['movement'])) {
            $query->whereHas('watchDetail', fn ($q) => $q->whereIn('movement', (array) $filters['movement']));
        }
        if (! empty($filters['dial_color'])) {
            $query->whereHas('watchDetail', fn ($q) => $q->whereIn('dial_color', (array) $filters['dial_color']));
        }
        if (! empty($filters['strap_material'])) {
            $query->whereHas('watchDetail', fn ($q) => $q->whereIn('strap_material', (array) $filters['strap_material']));
        }
        if (! empty($filters['water_resistance'])) {
            $query->whereHas('watchDetail', fn ($q) => $q->where('water_resistance_m', '>=', min((array) $filters['water_resistance'])));
        }
        if (! empty($filters['jewelry_type'])) {
            $query->whereHas('jewelryDetail', fn ($q) => $q->whereIn('jewelry_type', (array) $filters['jewelry_type']));
        }
        if (! empty($filters['material'])) {
            $query->whereHas('materials', fn ($q) => $q->whereIn('materials.id', (array) $filters['material']));
        }
        if (! empty($filters['gemstone'])) {
            $query->whereHas('gemstones', fn ($q) => $q->whereIn('gemstones.id', (array) $filters['gemstone']));
        }
        if ($request->filled('min_price')) {
            $query->where('base_price_amount', '>=', (int) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('base_price_amount', '<=', (int) $request->max_price);
        }
        match ($request->input('sort')) {
            'price_asc' => $query->orderBy('base_price_amount'), 'price_desc' => $query->orderByDesc('base_price_amount'), 'best_sellers' => $query->orderByDesc('is_featured'), default => $query->latest()
        };
        $products = $query->paginate(12)->withQueryString();
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNotNull('parent_id')
            ->when($type, fn ($query) => $query->whereHas('parent', fn ($parent) => $parent->where('slug', $type === 'watch' ? 'watches' : 'jewelry')))
            ->orderBy('sort_order')
            ->get();

        return view('store.shop', ['products' => $products, 'type' => $type, 'filters' => $filters, 'brands' => Brand::where('is_active', true)->get(), 'materials' => Material::all(), 'gemstones' => Gemstone::all(), 'categories' => $categories]);
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'active', 404);
        $product->load(['brand', 'category', 'images', 'variants.inventory', 'watchDetail', 'jewelryDetail', 'materials', 'gemstones']);
        $images = $product->images;
        $firstImage = $images->first()?->path;
        $related = Product::with(['brand', 'primaryImage', 'variants.inventory'])->where('status', 'active')->where('product_type', $product->product_type)->whereKeyNot($product->id)->take(4)->get();
        $isWishlisted = auth()->check() && WishlistItem::query()
            ->where('product_id', $product->id)
            ->whereHas('wishlist', fn ($query) => $query->where('user_id', auth()->id()))
            ->exists();

        $reviews = Review::query()
            ->with('user:id,name')
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->latest()
            ->get();
        $reviewAverage = $reviews->isEmpty() ? 0 : round((float) $reviews->avg('rating'), 1);
        $ratingCounts = collect(range(1, 5))->mapWithKeys(
            fn (int $rating): array => [$rating => $reviews->where('rating', $rating)->count()]
        );
        $eligibleOrderItem = null;
        $myReview = null;

        if (auth()->check()) {
            $eligibleOrderItem = OrderItem::query()
                ->with('order:id,order_number,user_id,status')
                ->where('product_id', $product->id)
                ->whereHas('order', fn ($query) => $query
                    ->where('user_id', auth()->id())
                    ->where('status', 'completed'))
                ->latest('id')
                ->first();
            $myReview = Review::query()
                ->where('product_id', $product->id)
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('store.product', compact(
            'product',
            'related',
            'images',
            'firstImage',
            'isWishlisted',
            'reviews',
            'reviewAverage',
            'ratingCounts',
            'eligibleOrderItem',
            'myReview',
        ));
    }

    public function trackingForm()
    {
        return view('store.track');
    }

    public function tracking(Request $request)
    {
        $data = $request->validate(['order_number' => ['required', 'string'], 'phone' => ['required', 'string']]);
        $order = Order::with(['items', 'payments', 'shipments'])->where('order_number', $data['order_number'])->where('customer_phone', $data['phone'])->first();

        return view('store.track', compact('order'));
    }
}
