<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowUpdate extends Model
{
    protected $fillable = [
        'workflow_id',
        'user_id',
        'message',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
