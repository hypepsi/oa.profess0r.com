<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'client_id',
        'priority',
        'status',
        'description',
        'due_at',
        'created_by_user_id',
        'require_evidence',
        'approved_at',
        'deduction_amount',
        'is_overdue',
    ];

    protected $casts = [
        'due_at' => 'date',
        'approved_at' => 'datetime',
        'require_evidence' => 'boolean',
        'deduction_amount' => 'decimal:2',
        'is_overdue' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    public function assignees()
    {
        // 你现有的 belongsToMany 关系，保持不变
        return $this->belongsToMany(Employee::class, 'workflow_assignees', 'workflow_id', 'employee_id');
    }

    /** New: updates relation (hasMany) */
    public function updates()
    {
        return $this->hasMany(WorkflowUpdate::class)->latest('id');
    }

    /**
     * Get the last update time
     */
    public function getLastUpdateAtAttribute()
    {
        $lastUpdate = $this->updates()->latest('created_at')->first();
        return $lastUpdate ? $lastUpdate->created_at : null;
    }
}
