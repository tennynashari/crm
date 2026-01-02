<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company',
        'is_individual',
        'area_id',
        'email',
        'address',
        'phone',
        'source',
        'assigned_sales_id',
        'lead_status_id',
        'next_action_date',
        'next_action_plan',
        'next_action_priority',
        'next_action_status',
        'notes',
    ];

    protected $casts = [
        'next_action_date' => 'date',
        'is_individual' => 'boolean',
    ];

    protected $appends = ['next_action_status_computed'];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function assignedSales()
    {
        return $this->belongsTo(User::class, 'assigned_sales_id');
    }

    public function leadStatus()
    {
        return $this->belongsTo(LeadStatus::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    public function emails()
    {
        return $this->hasMany(Email::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function getNextActionStatusComputedAttribute()
    {
        if (!$this->next_action_date) {
            return null;
        }

        $today = Carbon::today();
        $actionDate = Carbon::parse($this->next_action_date);

        if ($this->next_action_status === 'done') {
            return 'done';
        }

        if ($actionDate->isBefore($today)) {
            return 'overdue';
        }

        return 'pending';
    }
}
