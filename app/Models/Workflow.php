<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'task_type_id',
        'client_id',
        'priority',
        'status',
        'description',
        'due_at',
    ];

    protected $casts = [
        'due_at' => 'date',
    ];

    public function taskType()
    {
        return $this->belongsTo(TaskType::class, 'task_type_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id'); // 若你的模型名/表名不同，等跑通后我再对齐
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
}
