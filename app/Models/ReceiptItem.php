<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_receipt_id',
        'item_id',
        'qty_received',
        'koli_received',
    ];

    public function receipt()
    {
        return $this->belongsTo(WarehouseReceipt::class, 'warehouse_receipt_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function allocations()
    {
        return $this->hasMany(ReceiptAllocation::class);
    }
}
