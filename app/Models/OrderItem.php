<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'service_id',
        'brand',
        'model',
        'color',
        'material',
        'size',
        'photo_path',
        'photo_paths',
        'after_photo_paths',
    ];

    protected $casts = [
        'photo_paths' => 'array',
        'after_photo_paths' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
