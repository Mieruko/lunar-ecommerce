<?php

namespace Tests\Feature;

use App\Filament\Resources\SupportConversations\Pages\ViewSupportConversation;
use App\Filament\Resources\SupportConversations\SupportConversationResource;
use App\Filament\Resources\SupportFaqs\SupportFaqResource;
use App\Filament\Resources\SupportSavedReplies\SupportSavedReplyResource;
use App\Models\AdminActivityLog;
use App\Models\Permission;
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
            ->callAction('reply', ['body' => $replyBody])
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
}
