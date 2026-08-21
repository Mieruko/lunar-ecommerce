<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warranty extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['starts_at' => 'date', 'ends_at' => 'date']; }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function claims(): HasMany { return $this->hasMany(WarrantyClaim::class); }
}
