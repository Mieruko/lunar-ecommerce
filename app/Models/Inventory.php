<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Inventory extends Model {
    protected $guarded = [];
    protected $table = 'inventory';
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function available(): int { return max(0, $this->quantity_on_hand - $this->quantity_reserved); }
}
