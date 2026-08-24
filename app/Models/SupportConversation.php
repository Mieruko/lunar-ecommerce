<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportConversation extends Model
{
    public const STATUS_BOT = 'bot';

    public const STATUS_UNASSIGNED = 'unassigned';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_WAITING_CUSTOMER = 'waiting_customer';

    public const STATUS_RESOLVED = 'resolved';

    public const HUMAN_STATUSES = [
        self::STATUS_UNASSIGNED,
        self::STATUS_ASSIGNED,
        self::STATUS_WAITING_CUSTOMER,
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fallback_count' => 'integer',
            'handoff_transcript' => 'array',
            'handed_off_at' => 'datetime',
            'last_message_at' => 'datetime',
            'customer_last_read_at' => 'datetime',
            'staff_last_read_at' => 'datetime',
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupportConversation $conversation): void {
            $conversation->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'conversation_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_RESOLVED);
    }

    public function isHumanConversation(): bool
    {
        return in_array($this->status, self::HUMAN_STATUSES, true);
    }
}
