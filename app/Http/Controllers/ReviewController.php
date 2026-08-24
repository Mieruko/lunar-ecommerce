<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'active', 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá.',
            'rating.between' => 'Số sao phải từ 1 đến 5.',
            'body.required' => 'Vui lòng chia sẻ trải nghiệm của bạn.',
            'body.min' => 'Nội dung đánh giá cần ít nhất 10 ký tự.',
        ]);

        $orderItem = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->where('status', 'completed'))
            ->latest('id')
            ->first();

        abort_unless($orderItem, 403, 'Chỉ khách đã hoàn thành đơn hàng mới có thể đánh giá sản phẩm này.');

        Review::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_id' => $product->id,
            ],
            [
                'order_item_id' => $orderItem->id,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
                'status' => 'pending',
                'verified_purchase' => true,
            ],
        );

        return to_route('products.show', $product)
            ->withFragment('reviews')
            ->with('success', 'Đánh giá đã được gửi và đang chờ quản lý duyệt.');
    }
}
