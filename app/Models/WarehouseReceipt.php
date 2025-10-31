<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'warehouse_id',
        'received_at',
        'status',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(ReceiptItem::class, 'warehouse_receipt_id');
    }
}

