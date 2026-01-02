<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'interaction_type',
        'channel',
        'subject',
        'content',
        'summary',
        'created_by_type',
        'created_by_user_id',
        'lead_status_snapshot_id',
        'metadata',
        'interaction_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'interaction_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function leadStatusSnapshot()
    {
        return $this->belongsTo(LeadStatus::class, 'lead_status_snapshot_id');
    }
}
