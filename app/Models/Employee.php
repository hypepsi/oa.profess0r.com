<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'department', // sales / technical
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
