<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AttributeValue extends Model { public const CREATED_AT = null; public const UPDATED_AT = null; protected $guarded = []; public function attribute(): BelongsTo { return $this->belongsTo(ProductAttribute::class, 'attribute_id'); } }
