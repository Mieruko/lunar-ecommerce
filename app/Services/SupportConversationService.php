<?php

namespace App\Services;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportConversationService
{
    private const SESSION_TOKEN = 'lunar_support_token';

    public function __construct(private SupportBotService $bot) {}

    /** @return array<string, mixed> */
    public function state(Request $request, int $afterId = 0): array
    {
        $conversation = $this->current($request);

        return $this->serializeState($conversation, $afterId);
    }

    /** @return array<string, mixed> */
    public function send(Request $request, string $body): array
    {
        $conversationId = DB::transaction(function () use ($request, $body): int {
            $conversation = $this->currentOrCreate($request);
            $conversation = SupportConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            $now = now();

            $conversation->messages()->create([
                'sender_type' => SupportMessage::SENDER_CUSTOMER,
                'sender_id' => $request->user()?->id,
                'body' => $body,
                'kind' => 'text',
            ]);

            $updates = ['last_message_at' => $now];

            if (! $conversation->subject) {
                $updates['subject'] = str($body)->squish()->limit(120)->toString();
            }

            if ($conversation->isHumanConversation()) {
                if ($conversation->status === SupportConversation::STATUS_WAITING_CUSTOMER) {
                    $updates['status'] = $conversation->assigned_to
                        ? SupportConversation::STATUS_ASSIGNED
                        : SupportConversation::STATUS_UNASSIGNED;
                }

                $conversation->update($updates);

                return $conversation->id;
            }

            if ($this->bot->isExplicitHandoff($body)) {
                $conversation->messages()->create([
                    'sender_type' => SupportMessage::SENDER_BOT,
                    'body' => 'Mình đã chuyển cuộc trò chuyện cho đội ngũ chăm sóc khách hàng. Nhân viên sẽ phản hồi ngay khi có thể.',
                    'kind' => 'text',
                    'metadata' => ['intent' => 'handoff'],
                ]);
                $conversation->update($updates);
                $this->moveToHumanQueue($conversation);

                return $conversation->id;
            }

            $reply = $this->bot->respond($conversation, $body, $request->user());

            if (! $reply['matched'] && $conversation->fallback_count + 1 >= 2) {
                $reply['body'] = 'Mình vẫn chưa xác định được đúng nhu cầu nên đã chuyển cuộc trò chuyện cho nhân viên chăm sóc khách hàng.';
                $reply['metadata']['intent'] = 'fallback_handoff';
                $reply['metadata']['suggestions'] = [];
                $reply['suggestions'] = [];
            }

            $botMessage = $conversation->messages()->create([
                'sender_type' => SupportMessage::SENDER_BOT,
                'body' => $reply['body'],
                'kind' => 'text',
                'metadata' => $reply['metadata'],
            ]);

            $updates['last_message_at'] = $botMessage->created_at;
            $updates['fallback_count'] = $reply['matched'] ? 0 : min(255, $conversation->fallback_count + 1);

            if ($reply['category']) {
                $updates['category'] = $reply['category'];
            }

            if ($reply['order_id']) {
                $updates['order_id'] = $reply['order_id'];
            }

            $conversation->update($updates);

            if (! $reply['matched'] && $conversation->fallback_count >= 2) {
                $this->moveToHumanQueue($conversation);
            }

            return $conversation->id;
        }, 3);

        return $this->serializeState(SupportConversation::query()->findOrFail($conversationId));
    }

    /** @return array<string, mixed> */
    public function handoff(Request $request): array
    {
        $conversationId = DB::transaction(function () use ($request): int {
            $conversation = $this->currentOrCreate($request);
            $conversation = SupportConversation::query()->lockForUpdate()->findOrFail($conversation->id);

            if (! $conversation->isHumanConversation()) {
                $message = $conversation->messages()->create([
                    'sender_type' => SupportMessage::SENDER_BOT,
                    'body' => 'Mình đã chuyển cuộc trò chuyện cho đội ngũ chăm sóc khách hàng. Nhân viên sẽ phản hồi ngay khi có thể.',
                    'kind' => 'text',
                    'metadata' => ['intent' => 'handoff'],
                ]);
                $conversation->update(['last_message_at' => $message->created_at]);
                $this->moveToHumanQueue($conversation);
            }

            return $conversation->id;
        }, 3);

        return $this->serializeState(SupportConversation::query()->findOrFail($conversationId));
    }

    /** @return array<string, mixed> */
    public function markRead(Request $request): array
    {
        $conversation = $this->current($request);

        if (! $conversation) {
            return $this->serializeState(null);
        }

        DB::transaction(function () use ($conversation): void {
            $now = now();
            $conversation->messages()
                ->where('kind', '!=', 'internal_note')
                ->whereIn('sender_type', [
                    SupportMessage::SENDER_BOT,
                    SupportMessage::SENDER_STAFF,
                    SupportMessage::SENDER_SYSTEM,
                ])
                ->whereNull('read_at')
                ->update(['read_at' => $now, 'updated_at' => $now]);
            $conversation->update(['customer_last_read_at' => $now]);
        });

        return $this->serializeState($conversation->fresh());
    }

    public function current(Request $request): ?SupportConversation
    {
        $query = $this->ownerQuery($request);

        if (! $query) {
            return null;
        }

        $active = (clone $query)
            ->active()
            ->orderByDesc('last_message_at')
            ->latest('id')
            ->first();

        if (! $active && $request->user() && ($claimed = $this->claimGuestConversation($request))) {
            return $claimed;
        }

        return $active ?? $query->latest('id')->first();
    }

    private function currentOrCreate(Request $request): SupportConversation
    {
        if ($request->user()) {
            $conversation = SupportConversation::query()
                ->where('user_id', $request->user()->id)
                ->active()
                ->latest('id')
                ->first();

            return $conversation
                ?? $this->claimGuestConversation($request)
                ?? SupportConversation::query()->create([
                    'user_id' => $request->user()->id,
                    'status' => SupportConversation::STATUS_BOT,
                ]);
        }

        $token = $request->session()->get(self::SESSION_TOKEN);

        if (is_string($token) && $token !== '') {
            $tokenHash = hash('sha256', $token);
            $conversation = SupportConversation::query()
                ->where('guest_token_hash', $tokenHash)
                ->first();

            if ($conversation && $conversation->status !== SupportConversation::STATUS_RESOLVED) {
                return $conversation;
            }

            if (! $conversation) {
                return SupportConversation::query()->create([
                    'guest_token_hash' => $tokenHash,
                    'status' => SupportConversation::STATUS_BOT,
                ]);
            }
        }

        $token = bin2hex(random_bytes(32));
        $request->session()->put(self::SESSION_TOKEN, $token);

        return SupportConversation::query()->create([
            'guest_token_hash' => hash('sha256', $token),
            'status' => SupportConversation::STATUS_BOT,
        ]);
    }

    private function claimGuestConversation(Request $request): ?SupportConversation
    {
        if (! $request->user()) {
            return null;
        }

        $token = $request->session()->get(self::SESSION_TOKEN);

        if (! is_string($token) || $token === '') {
            return null;
        }

        $hash = hash('sha256', $token);
        $guestConversation = SupportConversation::query()
            ->whereNull('user_id')
            ->where('guest_token_hash', $hash)
            ->active()
            ->latest('id')
            ->first();

        if (! $guestConversation) {
            return null;
        }

        $claimed = SupportConversation::query()
            ->whereKey($guestConversation->id)
            ->whereNull('user_id')
            ->where('guest_token_hash', $hash)
            ->update([
                'user_id' => $request->user()->id,
                'guest_token_hash' => null,
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            return null;
        }

        $request->session()->forget(self::SESSION_TOKEN);

        return $guestConversation->fresh();
    }

    private function ownerQuery(Request $request): ?Builder
    {
        if ($request->user()) {
            return SupportConversation::query()->where('user_id', $request->user()->id);
        }

        $token = $request->session()->get(self::SESSION_TOKEN);

        if (! is_string($token) || $token === '') {
            return null;
        }

        return SupportConversation::query()->where('guest_token_hash', hash('sha256', $token));
    }

    private function moveToHumanQueue(SupportConversation $conversation): void
    {
        $conversation->update([
            'status' => SupportConversation::STATUS_UNASSIGNED,
            'assigned_to' => null,
            'handed_off_at' => $conversation->handed_off_at ?? now(),
        ]);

        $transcript = $conversation->messages()
            ->where('kind', '!=', 'internal_note')
            ->oldest('id')
            ->get(['sender_type', 'body', 'created_at'])
            ->map(fn (SupportMessage $message): array => [
                'sender' => $message->sender_type,
                'body' => $message->body,
                'sent_at' => $message->created_at?->toIso8601String(),
            ])
            ->all();

        $conversation->update(['handoff_transcript' => $transcript]);
    }

    /** @return array<string, mixed> */
    private function serializeState(?SupportConversation $conversation, int $afterId = 0): array
    {
        if (! $conversation) {
            return [
                'conversation' => null,
                'status' => SupportConversation::STATUS_BOT,
                'messages' => [],
                'suggestions' => $this->bot->defaultSuggestions(),
            ];
        }

        $messages = $conversation->messages()
            ->where('kind', '!=', 'internal_note')
            ->when($afterId > 0, fn (Builder $query): Builder => $query->where('id', '>', $afterId))
            ->oldest('id')
            ->limit(100)
            ->get()
            ->map(fn (SupportMessage $message): array => [
                'id' => $message->id,
                'sender' => $message->sender_type,
                'body' => $message->body,
                'sent_at' => $message->created_at?->toIso8601String(),
                'read_at' => $message->read_at?->toIso8601String(),
                'metadata' => $this->publicMetadata($message->metadata),
            ])
            ->all();

        $latestMetadata = $conversation->messages()
            ->where('kind', '!=', 'internal_note')
            ->latest('id')
            ->value('metadata');
        $latestMetadata = is_string($latestMetadata) ? json_decode($latestMetadata, true) : $latestMetadata;
        $suggestions = is_array($latestMetadata) && isset($latestMetadata['suggestions']) && is_array($latestMetadata['suggestions'])
            ? $latestMetadata['suggestions']
            : ($conversation->status === SupportConversation::STATUS_BOT ? $this->bot->defaultSuggestions() : []);

        return [
            'conversation' => [
                'reference' => $conversation->uuid,
                'status' => $conversation->status,
                'assigned' => $conversation->assigned_to !== null,
                'is_human' => $conversation->isHumanConversation(),
                'updated_at' => $conversation->updated_at?->toIso8601String(),
            ],
            'status' => $conversation->status,
            'messages' => $messages,
            'suggestions' => $suggestions,
        ];
    }

    /** @param array<string, mixed>|null $metadata
     * @return array<string, mixed>
     */
    private function publicMetadata(?array $metadata): array
    {
        if (! $metadata) {
            return [];
        }

        $public = collect($metadata)
            ->only(['intent', 'faq_slug', 'order', 'suggestions', 'action_url'])
            ->all();

        $products = collect($metadata['products'] ?? [])
            ->filter(fn (mixed $product): bool => is_array($product) && filled($product['name'] ?? null))
            ->take(3)
            ->map(function (array $product): array {
                $stockStatus = in_array($product['stock_status'] ?? null, ['in_stock', 'low_stock', 'out_of_stock'], true)
                    ? $product['stock_status']
                    : 'out_of_stock';

                return [
                    'id' => (int) ($product['id'] ?? 0),
                    'slug' => str((string) ($product['slug'] ?? ''))->limit(255)->toString(),
                    'name' => str((string) $product['name'])->stripTags()->limit(255)->toString(),
                    'brand' => str((string) ($product['brand'] ?? 'LUNAR JEWELS'))->stripTags()->limit(255)->toString(),
                    'image_url' => is_string($product['image_url'] ?? null) ? $product['image_url'] : null,
                    'price_amount' => max(0, (int) ($product['price_amount'] ?? 0)),
                    'currency' => str((string) ($product['currency'] ?? 'VND'))->upper()->limit(3)->toString(),
                    'stock_status' => $stockStatus,
                    'stock_label' => match ($stockStatus) {
                        'in_stock' => 'Sẵn hàng',
                        'low_stock' => 'Sắp hết',
                        default => 'Tạm hết',
                    },
                    'url' => is_string($product['url'] ?? null) ? $product['url'] : null,
                ];
            })
            ->values()
            ->all();

        if ($products !== []) {
            $public['products'] = $products;
        }

        return $public;
    }
}
