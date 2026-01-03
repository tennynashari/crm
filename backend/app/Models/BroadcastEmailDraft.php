<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastEmailDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject',
        'body',
        'filter_type',
        'area_id',
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
