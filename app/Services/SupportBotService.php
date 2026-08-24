<?php

namespace App\Services;

use App\Models\SupportConversation;
use App\Models\SupportFaq;
use App\Models\User;
use Illuminate\Support\Str;

class SupportBotService
{
    /**
     * @return array{
     *     matched: bool,
     *     body: string,
     *     category: ?string,
     *     order_id: ?int,
     *     suggestions: array<int, array<string, string>>,
     *     metadata: array<string, mixed>
     * }
     */
    public function respond(SupportConversation $conversation, string $message, ?User $user): array
    {
        if ($this->isOrderIntent($message)) {
            return $this->orderReply($message, $user);
        }

        $normalizedMessage = $this->normalize($message);
        $bestFaq = null;
        $bestScore = 0;

        foreach (SupportFaq::query()->active()->get() as $faq) {
            $score = $normalizedMessage === $this->normalize($faq->question) ? 100 : 0;

            foreach ($faq->keywords ?? [] as $keyword) {
                $normalizedKeyword = $this->normalize((string) $keyword);

                if ($normalizedKeyword !== '' && str_contains($normalizedMessage, $normalizedKeyword)) {
                    $score += max(1, count(explode(' ', $normalizedKeyword)));
                }
            }

            if ($score > $bestScore) {
                $bestFaq = $faq;
                $bestScore = $score;
            }
        }

        if ($bestFaq) {
            $suggestions = $this->normalizeSuggestions($bestFaq->suggestions ?? []);

            return [
                'matched' => true,
                'body' => $bestFaq->answer,
                'category' => $bestFaq->category,
                'order_id' => null,
                'suggestions' => $suggestions,
                'metadata' => [
                    'intent' => 'faq',
                    'faq_slug' => $bestFaq->slug,
                    'suggestions' => $suggestions,
                ],
            ];
        }

        return [
            'matched' => false,
            'body' => 'Mình chưa hiểu chính xác câu hỏi. Bạn có thể diễn đạt lại hoặc chọn “Gặp nhân viên” để được hỗ trợ trực tiếp.',
            'category' => null,
            'order_id' => null,
            'suggestions' => [$this->handoffSuggestion()],
            'metadata' => [
                'intent' => 'fallback',
                'suggestions' => [$this->handoffSuggestion()],
            ],
        ];
    }

    public function isExplicitHandoff(string $message): bool
    {
        $message = $this->normalize($message);

        foreach ([
            'gap nhan vien',
            'noi chuyen voi nhan vien',
            'ket noi nhan vien',
            'tu van vien',
            'nguoi that',
            'cham soc khach hang',
            'human support',
            'live agent',
        ] as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, array<string, string>> */
    public function defaultSuggestions(): array
    {
        $suggestions = SupportFaq::query()
            ->active()
            ->limit(3)
            ->get(['question'])
            ->map(fn (SupportFaq $faq): array => [
                'type' => 'message',
                'label' => $faq->question,
                'value' => $faq->question,
            ])
            ->values()
            ->all();

        $suggestions[] = $this->handoffSuggestion();

        return $suggestions;
    }

    /** @return array<string, string> */
    public function handoffSuggestion(): array
    {
        return [
            'type' => 'handoff',
            'label' => 'Gặp nhân viên',
        ];
    }

    private function isOrderIntent(string $message): bool
    {
        $message = $this->normalize($message);

        foreach (['don hang', 'ma don', 'trang thai don', 'theo doi don', 'order'] as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{
     *     matched: bool,
     *     body: string,
     *     category: string,
     *     order_id: ?int,
     *     suggestions: array<int, array<string, string>>,
     *     metadata: array<string, mixed>
     * }
     */
    private function orderReply(string $message, ?User $user): array
    {
        if (! $user) {
            $suggestions = [[
                'type' => 'url',
                'label' => 'Mở trang tra cứu đơn hàng',
                'url' => route('tracking.form'),
            ], $this->handoffSuggestion()];

            return [
                'matched' => true,
                'body' => 'Để bảo vệ thông tin đơn hàng, khách chưa đăng nhập vui lòng dùng trang tra cứu và xác minh thông tin mua hàng.',
                'category' => 'order',
                'order_id' => null,
                'suggestions' => $suggestions,
                'metadata' => [
                    'intent' => 'order_tracking',
                    'suggestions' => $suggestions,
                ],
            ];
        }

        $orderReference = $this->extractOrderReference($message);
        $query = $user->orders();

        $order = $orderReference
            ? $query->where('order_number', $orderReference)->first()
            : $query->latest('placed_at')->latest('id')->first();

        if (! $order) {
            $suggestions = [[
                'type' => 'url',
                'label' => 'Xem đơn hàng của tôi',
                'url' => route('account.orders'),
            ], $this->handoffSuggestion()];

            return [
                'matched' => true,
                'body' => $orderReference
                    ? 'Mình không tìm thấy mã đơn này trong tài khoản của bạn. Vui lòng kiểm tra lại mã đơn hoặc gặp nhân viên để được hỗ trợ.'
                    : 'Tài khoản của bạn chưa có đơn hàng để tra cứu.',
                'category' => 'order',
                'order_id' => null,
                'suggestions' => $suggestions,
                'metadata' => [
                    'intent' => 'order_not_found',
                    'suggestions' => $suggestions,
                ],
            ];
        }

        $status = $this->orderStatusLabel($order->status);
        $paymentStatus = $this->paymentStatusLabel($order->payment_status);
        $placedAt = $order->placed_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'chưa xác định';
        $publicOrder = [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $status,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $paymentStatus,
            'placed_at' => $order->placed_at?->toIso8601String(),
        ];
        $suggestions = [[
            'type' => 'url',
            'label' => 'Xem chi tiết đơn hàng',
            'url' => route('account.orders.show', $order),
        ], $this->handoffSuggestion()];

        return [
            'matched' => true,
            'body' => "Đơn {$order->order_number} hiện ở trạng thái “{$status}”; thanh toán “{$paymentStatus}”. Đơn được đặt lúc {$placedAt}.",
            'category' => 'order',
            'order_id' => $order->id,
            'suggestions' => $suggestions,
            'metadata' => [
                'intent' => 'order_status',
                'order' => $publicOrder,
                'suggestions' => $suggestions,
            ],
        ];
    }

    private function extractOrderReference(string $message): ?string
    {
        preg_match_all('/\b[A-Z0-9]+(?:-[A-Z0-9]+){1,5}\b/u', Str::upper($message), $matches);

        if ($matches[0] === []) {
            return null;
        }

        foreach ($matches[0] as $candidate) {
            if (str_starts_with($candidate, 'LJ-')) {
                return $candidate;
            }
        }

        return $matches[0][0];
    }

    private function orderStatusLabel(string $status): string
    {
        return [
            'pending_confirmation' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'preparing' => 'Đang chuẩn bị',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
            'returned' => 'Đã trả hàng',
        ][$status] ?? 'Đang xử lý';
    }

    private function paymentStatusLabel(string $status): string
    {
        return [
            'unpaid' => 'Chưa thanh toán',
            'pending' => 'Đang chờ',
            'paid' => 'Đã thanh toán',
            'partially_refunded' => 'Hoàn tiền một phần',
            'refunded' => 'Đã hoàn tiền',
            'failed' => 'Thất bại',
        ][$status] ?? 'Đang xử lý';
    }

    /** @param array<int, mixed> $suggestions
     * @return array<int, array<string, string>>
     */
    private function normalizeSuggestions(array $suggestions): array
    {
        return collect($suggestions)
            ->map(function (mixed $suggestion): ?array {
                if (is_string($suggestion) && trim($suggestion) !== '') {
                    return [
                        'type' => 'message',
                        'label' => trim($suggestion),
                        'value' => trim($suggestion),
                    ];
                }

                if (! is_array($suggestion) || ! isset($suggestion['label'], $suggestion['type'])) {
                    return null;
                }

                $type = in_array($suggestion['type'], ['message', 'url', 'handoff'], true)
                    ? $suggestion['type']
                    : 'message';
                $item = ['type' => $type, 'label' => (string) $suggestion['label']];

                if ($type === 'url' && isset($suggestion['url'])) {
                    $item['url'] = (string) $suggestion['url'];
                } elseif ($type === 'message') {
                    $item['value'] = (string) ($suggestion['value'] ?? $suggestion['label']);
                }

                return $item;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
