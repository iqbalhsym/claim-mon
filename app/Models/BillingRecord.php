<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingRecord extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'registered_date' => 'date',
        'discharge_date' => 'date',
        'total_guarantee' => 'float',
        'total_payment' => 'float',
        'total_discount_per_item' => 'float',
        'total_invoice_before_discount' => 'float',
        'hospital_guarantee' => 'float',
        'total_must_be_paid' => 'float',
        'hospital_fee' => 'float',
        'doctor_guarantee' => 'float',
        'doctor_fee' => 'float',
        'raw_data' => 'array',
    ];
}
