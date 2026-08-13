<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'coupon_id', 'status', 'payment_status',
        'subtotal_cents', 'discount_cents', 'total_cents', 'shipping_address',
    ];

    protected function casts(): array
    {
        return ['shipping_address' => 'array'];
    }

    public function user()    
    { 
        return $this->belongsTo(User::class); 
    }

    public function coupon()  
    { 
        return $this->belongsTo(Coupon::class); 
    }

    public function items()   
    { 
        return $this->hasMany(OrderItem::class); 
    }
    
    public function payment() 
    { 
        return $this->hasOne(Payment::class); 
    }
}
