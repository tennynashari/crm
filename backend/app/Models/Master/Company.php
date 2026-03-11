<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $connection = 'master';
    protected $table = 'companies';
    
    protected $fillable = [
        'name',
        'slug',
        'database_name',
        'email',
        'phone',
        'is_active',
        'subscription_status',
        'subscription_expires_at',
        'max_users',
        'max_customers',
        'settings',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'settings' => 'array',
        'max_users' => 'integer',
        'max_customers' => 'integer',
    ];
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
