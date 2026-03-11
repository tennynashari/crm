<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'user_profiles';
    
    protected $fillable = [
        'master_user_id',
        'name',
        'email',
        'phone',
        'avatar_url',
        'role',
        'permissions',
        'language',
        'timezone',
        'notifications',
        'is_active',
    ];
    
    protected $casts = [
        'permissions' => 'array',
        'notifications' => 'array',
        'is_active' => 'boolean',
    ];
    
    // Relationships dalam tenant DB
    public function assignedCustomers()
    {
        return $this->hasMany(\App\Models\Customer::class, 'assigned_sales_id');
    }
    
    public function interactions()
    {
        return $this->hasMany(\App\Models\Interaction::class, 'created_by_user_id');
    }
}
