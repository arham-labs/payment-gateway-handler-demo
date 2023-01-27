<?php

namespace Arhamlabs\PaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PgOrder extends Model
{
    use HasFactory;

     
        public function order_status()
        {
            return $this->hasMany(PgOrderLog::class, 'order_id', 'order_id');
        }
    
}
