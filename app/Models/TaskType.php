<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'requires_evidence', // 仅要求必须上传证据，不限制数量
        'is_active',
    ];

    protected $casts = [
        'requires_evidence' => 'boolean',
        'is_active' => 'boolean',
    ];
}
