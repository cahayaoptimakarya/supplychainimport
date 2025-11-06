<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'order_date',
        'ref_no',
        'status',
    ];

    protected $casts = [
        'order_date' => 'date',
    ];

    public function lines()
    {
        return $this->hasMany(PoLine::class);
    }
}
