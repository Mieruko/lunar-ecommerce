<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Address extends Model {
    protected $guarded = [];
    protected function casts(): array { return ['is_default_shipping' => 'boolean', 'is_default_billing' => 'boolean']; }
}
