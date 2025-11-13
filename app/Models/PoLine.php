<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PoLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'qty_ordered',
        'cnt_ordered',
        'pcs_cnt',
        'notes',
    ];

    protected $appends = [
        'fulfilled_qty',
        'open_qty',
        'remaining_qty',
        'fulfillment_percent',
        'status',
    ];

    protected $casts = [
        'qty_ordered' => 'float',
        'cnt_ordered' => 'float',
        'qty_fulfilled' => 'float',
        'qty_remaining' => 'float',
        'fulfillment_percent' => 'float',
        'fulfillment_refreshed_at' => 'datetime',
    ];

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
        if ($this->relationLoaded('allocations')) {
            return (float) $this->allocations->sum(fn($allocation) => (float) $allocation->qty);
        }

        return (float) $this->allocations()->sum('qty');
    }

    public function getOpenQtyAttribute(): float
    {
        return $this->getRemainingQtyAttribute();
    }

    public function getRemainingQtyAttribute(): float
    {
        $ordered = (float) $this->qty_ordered;
        $fulfilled = $this->getFulfilledQtyAttribute();

        return max(0.0, $ordered - $fulfilled);
    }

    public function getFulfillmentPercentAttribute(): float
    {
        $ordered = (float) $this->qty_ordered;
        if ($ordered <= 0) {
            return 0.0;
        }

        $percent = ($this->getFulfilledQtyAttribute() / $ordered) * 100;

        return round(min(100, max(0, $percent)), 4);
    }

    public function getStatusAttribute(): string
    {
        $ordered = (float) $this->qty_ordered;
        $fulfilled = $this->getFulfilledQtyAttribute();
        $remaining = max(0.0, $ordered - $fulfilled);

        if ($ordered <= 0) {
            return 'open';
        }

        if ($remaining <= 0.00001) {
            return 'fulfilled';
        }

        if ($fulfilled > 0) {
            return 'partial';
        }

        return 'open';
    }

    public function refreshFulfillmentMetrics(): void
    {
        $metrics = $this->calculateFulfillmentMetrics();

        $this->forceFill($metrics + [
            'fulfillment_refreshed_at' => Carbon::now(),
        ])->saveQuietly();

        $this->syncAttributesFromMetrics($metrics);
    }

    public function calculateFulfillmentMetrics(): array
    {
        $ordered = (float) $this->qty_ordered;
        $fulfilled = $this->relationLoaded('allocations')
            ? (float) $this->allocations->sum(fn($allocation) => (float) $allocation->qty)
            : (float) $this->allocations()->sum('qty');
        $remaining = max(0.0, $ordered - $fulfilled);
        $percent = $ordered > 0 ? ($fulfilled / $ordered) * 100 : 0.0;

        $status = match (true) {
            $ordered <= 0 => 'open',
            $remaining <= 0.00001 => 'fulfilled',
            $fulfilled > 0 => 'partial',
            default => 'open',
        };

        return [
            'qty_fulfilled' => round($fulfilled, 4),
            'qty_remaining' => round($remaining, 4),
            'fulfillment_percent' => round(min(100, max(0, $percent)), 4),
            'status' => $status,
        ];
    }

    protected function syncAttributesFromMetrics(array $metrics): void
    {
        foreach ($metrics as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    protected static function booted(): void
    {
        static::creating(function (PoLine $line) {
            $line->qty_fulfilled = $line->qty_fulfilled ?? 0;
            $line->qty_remaining = $line->qty_remaining ?? (float) $line->qty_ordered;
            $line->fulfillment_percent = $line->fulfillment_percent ?? 0;
            $line->status = $line->status ?? 'open';
        });

        static::created(function (PoLine $line) {
            $line->refreshFulfillmentMetrics();
        });

        static::updated(function (PoLine $line) {
            if ($line->wasChanged(['qty_ordered', 'item_id'])) {
                $line->refreshFulfillmentMetrics();
            }
        });
    }
}
