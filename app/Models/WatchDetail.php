<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WatchDetail extends Model { protected $guarded = []; protected $primaryKey = 'product_id'; public $incrementing = false; public $timestamps = false; protected function casts(): array { return ['functions' => 'array']; } }
