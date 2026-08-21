<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminActivityLog extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = [];
    protected function casts(): array { return ['before' => 'array', 'after' => 'array']; }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
    public function subject(): MorphTo { return $this->morphTo(); }
}
