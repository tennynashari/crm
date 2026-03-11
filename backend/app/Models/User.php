<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The connection name for the model.
     * Users are stored in master DB for authentication and company management
     *
     * @var string
     */
    protected $connection = 'master';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_id',  // Multi-tenant: Which company this user belongs to
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the user
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to only include users from a specific company
     */
    public function scopeInCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function assignedCustomers()
    {
        return $this->hasMany(Customer::class, 'assigned_sales_id');
    }

    public function interactions()
    {
        return $this->hasMany(Interaction::class, 'created_by_user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
