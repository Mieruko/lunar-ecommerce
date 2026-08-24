<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SupportConversation;
use App\Models\SupportFaq;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_conversations_are_scoped_by_a_hashed_server_session_token(): void
    {
        $this->createFaq();

        $first = $this->withSession(['lunar_support_token' => 'guest-a-secret'])
            ->postJson(route('support.chat.messages'), [
                'body' => 'Phí giao hàng được tính ra sao?',
                'conversation_id' => 999999,
            ])
            ->assertCreated()
            ->assertJsonPath('status', SupportConversation::STATUS_BOT)
            ->assertJsonPath('messages.0.sender', SupportMessage::SENDER_CUSTOMER)
            ->assertJsonPath('messages.1.sender', SupportMessage::SENDER_BOT)
            ->json();

        $this->assertDatabaseHas('support_conversations', [
            'guest_token_hash' => hash('sha256', 'guest-a-secret'),
            'user_id' => null,
        ]);
        $this->assertDatabaseMissing('support_conversations', [
            'guest_token_hash' => 'guest-a-secret',
        ]);

        $this->withSession(['lunar_support_token' => 'guest-b-secret'])
            ->getJson(route('support.chat.current'))
            ->assertOk()
            ->assertExactJson([
                'conversation' => null,
                'status' => SupportConversation::STATUS_BOT,
                'messages' => [],
                'suggestions' => [
                    [
                        'type' => 'message',
                        'label' => 'Phí vận chuyển được tính thế nào?',
                        'value' => 'Phí vận chuyển được tính thế nào?',
                    ],
                    [
                        'type' => 'handoff',
                        'label' => 'Gặp nhân viên',
                    ],
                ],
            ]);

        $this->withSession(['lunar_support_token' => 'guest-a-secret'])
            ->getJson(route('support.chat.current', ['after_id' => $first['messages'][0]['id']]))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.sender', SupportMessage::SENDER_BOT);
    }

    public function test_bot_reply_is_deterministic_and_supports_body_alias(): void
    {
        $faq = $this->createFaq();

        $this->withSession(['lunar_support_token' => 'deterministic-guest'])
            ->postJson(route('support.chat.messages'), ['body' => 'Cho tôi hỏi phí giao hàng'])
            ->assertCreated()
            ->assertJsonPath('messages.1.body', $faq->answer)
            ->assertJsonPath('messages.1.metadata.faq_slug', $faq->slug)
            ->assertJsonPath('messages.1.metadata.intent', 'faq')
            ->assertJsonPath('suggestions.0.type', 'message');
    }

    public function test_two_fallbacks_or_an_explicit_request_hands_off_with_a_transcript(): void
    {
        $this->withSession(['lunar_support_token' => 'fallback-guest'])
            ->postJson(route('support.chat.messages'), ['message' => 'xyzzy first unknown'])
            ->assertCreated()
            ->assertJsonPath('status', SupportConversation::STATUS_BOT);

        $this->withSession(['lunar_support_token' => 'fallback-guest'])
            ->postJson(route('support.chat.messages'), ['message' => 'plugh second unknown'])
            ->assertCreated()
            ->assertJsonPath('status', SupportConversation::STATUS_UNASSIGNED)
            ->assertJsonPath('conversation.is_human', true);

        $fallbackConversation = SupportConversation::query()
            ->where('guest_token_hash', hash('sha256', 'fallback-guest'))
            ->firstOrFail();
        $this->assertSame(2, $fallbackConversation->fallback_count);
        $this->assertNotNull($fallbackConversation->handed_off_at);
        $this->assertCount(4, $fallbackConversation->handoff_transcript);

        $this->withSession(['lunar_support_token' => 'explicit-guest'])
            ->postJson(route('support.chat.messages'), ['message' => 'Tôi muốn gặp nhân viên tư vấn'])
            ->assertCreated()
            ->assertJsonPath('status', SupportConversation::STATUS_UNASSIGNED);

        $explicitConversation = SupportConversation::query()
            ->where('guest_token_hash', hash('sha256', 'explicit-guest'))
            ->firstOrFail();
        $this->assertNotEmpty($explicitConversation->handoff_transcript);
    }

    public function test_handoff_endpoint_creates_a_human_queue_conversation(): void
    {
        $this->withSession(['lunar_support_token' => 'handoff-button-guest'])
            ->postJson(route('support.chat.handoff'))
            ->assertOk()
            ->assertJsonPath('status', SupportConversation::STATUS_UNASSIGNED)
            ->assertJsonPath('messages.0.metadata.intent', 'handoff');
    }

    public function test_order_lookup_is_read_only_and_scoped_to_the_authenticated_owner(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $other = User::factory()->create(['status' => 'active']);
        $ownedOrder = $this->order($owner, 'LJ-OWNER-001', 'preparing');
        $otherOrder = $this->order($other, 'LJ-PRIVATE-999', 'completed');

        $privateLookup = $this->actingAs($owner)
            ->postJson(route('support.chat.messages'), [
                'message' => 'Trạng thái đơn hàng LJ-PRIVATE-999 là gì?',
            ])
            ->assertCreated()
            ->assertJsonPath('messages.1.metadata.intent', 'order_not_found')
            ->json();

        $this->assertStringNotContainsString('Hoàn tất', $privateLookup['messages'][1]['body']);
        $this->assertArrayNotHasKey('order', $privateLookup['messages'][1]['metadata']);

        $ownedLookup = $this->actingAs($owner)
            ->postJson(route('support.chat.messages'), [
                'message' => 'Kiểm tra đơn hàng LJ-OWNER-001',
            ])
            ->assertCreated()
            ->assertJsonPath('messages.3.metadata.intent', 'order_status')
            ->assertJsonPath('messages.3.metadata.order.order_number', $ownedOrder->order_number)
            ->assertJsonPath('messages.3.metadata.order.status', 'preparing')
            ->json();

        $conversation = SupportConversation::query()->where('user_id', $owner->id)->firstOrFail();
        $this->assertSame($ownedOrder->id, $conversation->order_id);
        $this->assertNotSame($otherOrder->id, $conversation->order_id);
        $this->assertNull($conversation->guest_token_hash);

        $guestLookup = $this->actingAsGuest()
            ->withSession(['lunar_support_token' => 'guest-order-lookup'])
            ->postJson(route('support.chat.messages'), [
                'message' => 'Theo dõi đơn hàng LJ-PRIVATE-999',
            ])
            ->assertCreated()
            ->assertJsonPath('messages.1.metadata.intent', 'order_tracking')
            ->assertJsonPath('suggestions.0.type', 'url')
            ->json();

        $this->assertArrayNotHasKey('order', $guestLookup['messages'][1]['metadata']);
    }

    public function test_internal_notes_never_appear_in_customer_state_or_handoff_transcript(): void
    {
        $this->createFaq();
        $token = 'internal-note-guest';

        $this->withSession(['lunar_support_token' => $token])
            ->postJson(route('support.chat.messages'), ['message' => 'Phí giao hàng'])
            ->assertCreated();

        $conversation = SupportConversation::query()
            ->where('guest_token_hash', hash('sha256', $token))
            ->firstOrFail();
        $conversation->messages()->create([
            'sender_type' => SupportMessage::SENDER_STAFF,
            'body' => 'Ghi chú nội bộ tuyệt mật',
            'kind' => 'internal_note',
        ]);

        $state = $this->withSession(['lunar_support_token' => $token])
            ->getJson(route('support.chat.current'))
            ->assertOk()
            ->json();
        $this->assertStringNotContainsString('Ghi chú nội bộ tuyệt mật', json_encode($state));

        $this->withSession(['lunar_support_token' => $token])
            ->postJson(route('support.chat.handoff'))
            ->assertOk();

        $this->assertStringNotContainsString(
            'Ghi chú nội bộ tuyệt mật',
            json_encode($conversation->fresh()->handoff_transcript),
        );
    }

    public function test_an_active_guest_conversation_is_safely_claimed_after_login(): void
    {
        $this->createFaq();
        $token = 'guest-before-login';
        $guestState = $this->withSession(['lunar_support_token' => $token])
            ->postJson(route('support.chat.messages'), ['message' => 'Phí vận chuyển'])
            ->assertCreated()
            ->json();
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->withSession(['lunar_support_token' => $token])
            ->getJson(route('support.chat.current'))
            ->assertOk()
            ->assertJsonPath('conversation.reference', $guestState['conversation']['reference'])
            ->assertJsonCount(2, 'messages');

        $conversation = SupportConversation::query()
            ->where('uuid', $guestState['conversation']['reference'])
            ->firstOrFail();
        $this->assertSame($user->id, $conversation->user_id);
        $this->assertNull($conversation->guest_token_hash);

        $this->actingAsGuest()
            ->withSession(['lunar_support_token' => $token])
            ->getJson(route('support.chat.current'))
            ->assertOk()
            ->assertJsonPath('conversation', null);
    }

    public function test_message_validation_rejects_missing_blank_and_oversized_content(): void
    {
        $this->postJson(route('support.chat.messages'), [])->assertUnprocessable();
        $this->postJson(route('support.chat.messages'), ['message' => '   '])->assertUnprocessable();
        $this->postJson(route('support.chat.messages'), ['body' => str_repeat('a', 2001)])->assertUnprocessable();
        $this->getJson(route('support.chat.current', ['after_id' => -1]))->assertUnprocessable();
        $this->assertDatabaseCount('support_conversations', 0);
    }

    private function createFaq(): SupportFaq
    {
        return SupportFaq::query()->create([
            'slug' => 'shipping-test',
            'question' => 'Phí vận chuyển được tính thế nào?',
            'answer' => 'Phí giao hàng được tính theo khu vực và hiển thị tại bước thanh toán.',
            'keywords' => ['phí giao hàng', 'phí vận chuyển'],
            'category' => 'shipping',
            'suggestions' => ['Tôi muốn theo dõi đơn hàng'],
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    private function order(User $user, string $number, string $status): Order
    {
        return Order::query()->create([
            'order_number' => $number,
            'user_id' => $user->id,
            'status' => $status,
            'payment_status' => 'paid',
            'fulfillment_status' => $status === 'completed' ? 'fulfilled' : 'unfulfilled',
            'currency' => 'VND',
            'subtotal_amount' => 2_500_000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 2_500_000,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0900000001',
            'placed_at' => now(),
        ]);
    }
}
