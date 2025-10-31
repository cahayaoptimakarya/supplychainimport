<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'qty_ordered',
        'koli_ordered',
        'notes',
    ];

    protected $appends = ['fulfilled_qty', 'open_qty'];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ReceiptAllocation::class, 'po_line_id');
    }

    public function getFulfilledQtyAttribute(): float
    {
        return (float) $this->allocations()->sum('qty');
    }

    public function getOpenQtyAttribute(): float
    {
        return max(0.0, (float) $this->qty_ordered - $this->fulfilled_qty);
    }
}
