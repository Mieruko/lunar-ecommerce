<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Shipment extends Model {
    protected $guarded = [];
    protected function casts(): array { return ['shipped_at' => 'datetime', 'delivered_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
