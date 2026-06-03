<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    // Match existing DB columns: id, name, subname, description, price (int), icon_name, is_best_seller, sort_order, is_active, estimated_days
    protected $fillable = ['name', 'subname', 'description', 'price', 'icon_name', 'is_best_seller', 'sort_order', 'is_active', 'estimated_days'];

    protected $casts = [
        'price' => 'integer',
        'estimated_days' => 'integer',
        'is_best_seller' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Append service_name to JSON output for frontend compatibility
    protected $appends = ['service_name'];

    /**
     * Accessor for backward compatibility: service_name maps to name
     */
    public function getServiceNameAttribute(): string
    {
        return $this->name;
    }
}
