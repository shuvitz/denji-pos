<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCost extends Model
{
    protected $fillable = [
        'date',
        'amount',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
