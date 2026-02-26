<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'variant_id',
        'type',
        'qty',
        'cost_per_unit',
        'reference_id',
        'note',
        'movement_at',
        'created_by',
    ];

    protected $casts = [
        'movement_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function variant()
    {
        return $this->belongsTo(ItemVariant::class);
    }

    public function referenceType()
    {
        return $this->belongsTo(ReferenceType::class, 'reference_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
