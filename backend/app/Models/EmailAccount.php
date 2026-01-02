<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'provider',
        'imap_host',
        'imap_port',
        'smtp_host',
        'smtp_port',
        'username',
        'password',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'imap_port' => 'integer',
        'smtp_port' => 'integer',
    ];

    protected $hidden = [
        'password',
    ];

    public function emails()
    {
        return $this->hasMany(Email::class);
    }
}
