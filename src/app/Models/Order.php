<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'vtex_order_id','totalAmount', 'paymentMethod' , 'procesada'
    ];

    /**
     * Get the products for the order.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
