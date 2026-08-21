<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    protected $guarded = [];
    protected function casts(): array { return ['provider_payload' => 'array', 'paid_at' => 'datetime']; }
    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Order::class); }
    public function refunds(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Refund::class); }
}
