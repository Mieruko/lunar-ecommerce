<?php

namespace Tests\Feature;

use App\Filament\Resources\SupportConversations\Pages\ViewSupportConversation;
use App\Filament\Resources\SupportConversations\SupportConversationResource;
use App\Filament\Resources\SupportFaqs\SupportFaqResource;
use App\Filament\Resources\SupportSavedReplies\SupportSavedReplyResource;
use App\Models\AdminActivityLog;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupportAdminInboxTest extends TestCase
{
    use RefreshDatabase;

    private int $roleSequence = 0;

    public function test_support_resources_are_scoped_by_their_permissions(): void
    {
        $conversation = SupportConversation::create([
            'status' => SupportConversation::STATUS_UNASSIGNED,
            'priority' => 'normal',
            'subject' => 'Cần tư vấn bảo hành',
            'last_message_at' => now(),
        ]);

        $viewer = $this->staff(['admin.access', 'support.view']);
        $this->actingAs($viewer);

        $this->assertTrue(SupportConversationResource::canViewAny());
        $this->assertFalse(SupportFaqResource::canViewAny());
        $this->assertFalse(SupportSavedReplyResource::canViewAny());
        $this->get('/admin/support-conversations')->assertOk();
        $this->get('/admin/support-conversations/'.$conversation->id)->assertOk();
        $this->get('/admin/support-faqs')->assertForbidden();
        $this->get('/admin/support-saved-replies')->assertForbidden();

        $knowledgeManager = $this->staff(['admin.access', 'support.manage_knowledge']);
        $this->actingAs($knowledgeManager);

        $this->assertFalse(SupportConversationResource::canViewAny());
        $this->assertTrue(SupportFaqResource::canViewAny());
        $this->assertTrue(SupportSavedReplyResource::canViewAny());
        $this->get('/admin/support-conversations')->assertForbidden();
        $this->get('/admin/support-faqs')->assertOk();
        $this->get('/admin/support-saved-replies')->assertOk();
    }

    public function test_staff_can_claim_reply_note_prioritize_resolve_and_reopen(): void
    {
        $staff = $this->staff([
            'admin.access',
            'support.view',
            'support.reply',
            'support.assign',
            'support.resolve',
        ]);
        $customer = User::factory()->create(['status' => 'active']);
        $conversation = SupportConversation::create([
            'user_id' => $customer->id,
            'status' => SupportConversation::STATUS_UNASSIGNED,
            'priority' => 'normal',
            'subject' => 'Kiểm tra trạng thái đơn hàng',
            'handed_off_at' => now(),
            'last_message_at' => now(),
        ]);
        $customerMessage = $conversation->messages()->create([
            'sender_type' => SupportMessage::SENDER_CUSTOMER,
            'sender_id' => $customer->id,
            'body' => 'Nhờ LUNAR hỗ trợ kiểm tra giúp tôi.',
            'kind' => 'text',
        ]);
        $replyBody = 'LUNAR đã tiếp nhận và đang kiểm tra yêu cầu của bạn.';
        $internalBody = 'Đã đối chiếu nội bộ, chờ bộ phận kho xác nhận.';
        $product = $this->product();

        $this->actingAs($staff);

        $component = Livewire::test(ViewSupportConversation::class, [
            'record' => $conversation->getRouteKey(),
        ])
            ->assertActionVisible('claim')
            ->callAction('claim');

        $this->assertDatabaseHas('support_conversations', [
            'id' => $conversation->id,
            'assigned_to' => $staff->id,
            'status' => SupportConversation::STATUS_ASSIGNED,
        ]);
        $this->assertNotNull($customerMessage->fresh()->read_at);

        $component
            ->assertActionVisible('reply')
            ->callAction('reply', ['body' => $replyBody, 'product_ids' => [$product->id]])
            ->callAction('internalNote', ['body' => $internalBody])
            ->callAction('setPriority', ['priority' => 'urgent'])
            ->callAction('resolve')
            ->assertActionVisible('reopen')
            ->callAction('reopen');

        $this->assertDatabaseHas('support_messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => SupportMessage::SENDER_STAFF,
            'sender_id' => $staff->id,
            'kind' => 'text',
            'body' => $replyBody,
        ]);
        $reply = SupportMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('kind', 'text')
            ->where('body', $replyBody)
            ->firstOrFail();
        $this->assertSame($product->id, $reply->metadata['products'][0]['id']);
        $this->assertSame('Celeste Diamond Halo Ring', $reply->metadata['products'][0]['name']);
        $this->assertSame('/products/celeste-diamond-halo-ring', $reply->metadata['products'][0]['url']);
        $this->assertSame(17_000_000, $reply->metadata['products'][0]['price_amount']);
        $this->assertDatabaseHas('support_messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => SupportMessage::SENDER_STAFF,
            'sender_id' => $staff->id,
            'kind' => 'internal_note',
            'body' => $internalBody,
        ]);
        $this->assertDatabaseHas('support_conversations', [
            'id' => $conversation->id,
            'assigned_to' => $staff->id,
            'status' => SupportConversation::STATUS_ASSIGNED,
            'priority' => 'urgent',
            'resolved_at' => null,
        ]);

        $notification = $customer->notifications()->latest()->firstOrFail();
        $this->assertSame('support', $notification->data['category']);
        $this->assertSame(route('home', ['support' => 'chat']), $notification->data['action_url']);

        $this->actingAs($customer)
            ->getJson(route('support.chat.current'))
            ->assertOk()
            ->assertJsonPath('messages.1.metadata.products.0.name', 'Celeste Diamond Halo Ring')
            ->assertJsonPath('messages.1.metadata.products.0.price_amount', 17_000_000)
            ->assertJsonPath('messages.1.metadata.products.0.url', '/products/celeste-diamond-halo-ring');

        $auditPayload = AdminActivityLog::query()
            ->where('subject_type', $conversation->getMorphClass())
            ->where('subject_id', $conversation->id)
            ->get(['before', 'after'])
            ->toJson();
        $this->assertStringNotContainsString($replyBody, $auditPayload);
        $this->assertStringNotContainsString($internalBody, $auditPayload);
    }

    public function test_view_only_staff_cannot_run_inbox_actions(): void
    {
        $viewer = $this->staff(['admin.access', 'support.view']);
        $conversation = SupportConversation::create([
            'status' => SupportConversation::STATUS_UNASSIGNED,
            'priority' => 'normal',
            'last_message_at' => now(),
        ]);

        $this->actingAs($viewer);

        Livewire::test(ViewSupportConversation::class, [
            'record' => $conversation->getRouteKey(),
        ])
            ->assertActionHidden('claim')
            ->assertActionHidden('reply')
            ->assertActionHidden('internalNote')
            ->assertActionHidden('setPriority')
            ->assertActionHidden('resolve')
            ->assertActionHidden('reopen');
    }

    private function staff(array $permissionSlugs): User
    {
        $this->roleSequence++;
        $permissions = collect($permissionSlugs)->map(function (string $slug): Permission {
            return Permission::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => str($slug)->replace('.', ' ')->title()],
            );
        });
        $role = Role::create([
            'name' => 'Support test '.$this->roleSequence,
            'slug' => 'support-test-'.$this->roleSequence,
            'is_staff' => true,
            'is_system' => false,
        ]);
        $role->permissions()->sync($permissions->pluck('id'));
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    private function product(): Product
    {
        $category = Category::create([
            'name' => 'Nhẫn tư vấn',
            'slug' => 'support-ring-test',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Celeste Diamond Halo Ring',
            'slug' => 'celeste-diamond-halo-ring',
            'product_type' => 'jewelry',
            'status' => 'active',
            'base_price_amount' => 17_000_000,
            'currency' => 'VND',
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'https://images.example.test/celeste-ring.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        return $product;
    }
}
