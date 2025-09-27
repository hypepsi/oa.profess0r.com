<?php

namespace App\Models;

use App\Models\Employee;
use App\Models\TaskType;
use App\Models\User;
use App\Models\Customer; // Clients 对应的模型通常叫 Customer
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'task_type_id',
        'client_id',
        'priority',             // low / normal / high / urgent
        'status',               // open / in_review / follow_up / approved / closed / overdue / cancelled
        'due_at',
        'created_by_user_id',   // users.id
        // approver 固定为当前 admin（使用 created_by 作为 owner）
        'approved_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function creatorUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class, 'task_type_id');
    }

    public function client()
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    /** @return BelongsToMany<Employee> */
    public function assignees()
    {
        return $this->belongsToMany(Employee::class, 'workflow_assignees', 'workflow_id', 'employee_id')
            ->withTimestamps();
    }
}
