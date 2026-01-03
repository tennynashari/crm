<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastEmailHistory extends Model
{
    use HasFactory;

    protected $table = 'broadcast_email_history';

    protected $fillable = [
        'user_id',
        'subject',
        'body',
        'filter_type',
        'area_id',
        'recipient_count',
        'recipients',
        'has_attachments',
    ];

    protected $casts = [
        'recipients' => 'array',
        'has_attachments' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
