<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyClaim extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['resolved_at' => 'datetime']; }
    public function warranty(): BelongsTo { return $this->belongsTo(Warranty::class); }
}
