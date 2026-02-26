<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'name',
        'sku',
        'size',
        'color',
        'stock',
        'minimum_stock',
        'purchase_price',
        'selling_price',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
