<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Warehouse extends Model { protected $guarded = []; public $timestamps = false; public function inventory(): HasMany { return $this->hasMany(Inventory::class); } }
