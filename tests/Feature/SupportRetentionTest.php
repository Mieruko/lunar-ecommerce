<?php

namespace Tests\Feature;

use App\Models\SupportConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_command_only_deletes_resolved_conversations_past_retention(): void
    {
        $expired = SupportConversation::create([
            'status' => SupportConversation::STATUS_RESOLVED,
            'resolved_at' => now()->subDays(181),
        ]);
        $recent = SupportConversation::create([
            'status' => SupportConversation::STATUS_RESOLVED,
            'resolved_at' => now()->subDays(30),
        ]);
        $open = SupportConversation::create([
            'status' => SupportConversation::STATUS_BOT,
            'resolved_at' => now()->subDays(365),
        ]);

        $this->artisan('support:purge-resolved', ['--days' => 180, '--dry-run' => true])
            ->expectsOutput('Có 1 hội thoại đã giải quyết đủ điều kiện xóa.')
            ->assertSuccessful();
        $this->assertDatabaseHas('support_conversations', ['id' => $expired->id]);

        $this->artisan('support:purge-resolved', ['--days' => 180])
            ->expectsOutput('Đã xóa 1 hội thoại quá thời hạn lưu trữ.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('support_conversations', ['id' => $expired->id]);
        $this->assertDatabaseHas('support_conversations', ['id' => $recent->id]);
        $this->assertDatabaseHas('support_conversations', ['id' => $open->id]);
    }

    public function test_purge_command_rejects_an_invalid_retention_period(): void
    {
        $this->artisan('support:purge-resolved', ['--days' => 0])
            ->expectsOutput('Thời hạn lưu trữ phải từ 1 ngày trở lên.')
            ->assertFailed();
    }
}
