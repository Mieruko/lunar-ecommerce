<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ProductAttribute extends Model { protected $table = 'attributes'; protected $guarded = []; public function values(): HasMany { return $this->hasMany(AttributeValue::class, 'attribute_id'); } }
