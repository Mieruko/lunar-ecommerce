<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'support.view',
        'support.reply',
        'support.assign',
        'support.resolve',
        'support.manage_knowledge',
    ];

    public function up(): void
    {
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('guest_token_hash', 64)->nullable()->unique();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('bot');
            $table->string('priority', 20)->default('normal');
            $table->string('category', 80)->nullable();
            $table->string('subject')->nullable();
            $table->unsignedTinyInteger('fallback_count')->default(0);
            $table->json('handoff_transcript')->nullable();
            $table->timestamp('handed_off_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('customer_last_read_at')->nullable();
            $table->timestamp('staff_last_read_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['status', 'last_message_at']);
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->string('sender_type', 20);
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->string('kind', 30)->default('text');
            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
            $table->index(['conversation_id', 'read_at']);
        });

        Schema::create('support_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('question');
            $table->text('answer');
            $table->json('keywords');
            $table->string('category', 80)->nullable();
            $table->json('suggestions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('support_saved_replies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('shortcut', 80)->nullable()->unique();
            $table->string('category', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();

        foreach (self::PERMISSIONS as $slug) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => str($slug)->replace('.', ' ')->title(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        $customerServiceRoleId = DB::table('roles')->where('slug', 'customer-service')->value('id');

        if ($customerServiceRoleId) {
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', self::PERMISSIONS)
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $customerServiceRoleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', self::PERMISSIONS)
                ->pluck('id');

            if (Schema::hasTable('role_permissions') && $permissionIds->isNotEmpty()) {
                DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            }

            DB::table('permissions')->whereIn('slug', self::PERMISSIONS)->delete();
        }

        Schema::dropIfExists('support_saved_replies');
        Schema::dropIfExists('support_faqs');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_conversations');
    }
};
