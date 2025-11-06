<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'container_no',
        'pl_no',
        'etd',
        'eta',
        'status',
    ];

    protected $casts = [
        'etd' => 'date',
        'eta' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(WarehouseReceipt::class);
    }
}
