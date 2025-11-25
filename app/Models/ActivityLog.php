<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联操作者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联被操作的模型（多态关联）
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * 获取操作者名称
     */
    public function getUserNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name ?? $this->user->email;
        }
        return 'System';
    }
}
