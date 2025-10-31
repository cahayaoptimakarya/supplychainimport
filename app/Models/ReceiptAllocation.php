<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_item_id',
        'po_line_id',
        'qty',
    ];

    public function receiptItem()
    {
        return $this->belongsTo(ReceiptItem::class);
    }

    public function poLine()
    {
        return $this->belongsTo(PoLine::class);
    }
}

