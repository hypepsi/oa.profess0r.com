<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'website',
        'contact_wechat',
        'contact_email',
        'contact_telegram',
        'abuse_email',
        'active',
    ];
}

