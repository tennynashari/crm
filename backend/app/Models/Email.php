<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_account_id',
        'customer_id',
        'message_id',
        'from_email',
        'from_name',
        'to_emails',
        'cc_emails',
        'bcc_emails',
        'subject',
        'body_text',
        'body_html',
        'is_inbound',
        'is_processed',
        'raw_headers',
        'raw_body',
        'email_date',
    ];

    protected $casts = [
        'to_emails' => 'array',
        'cc_emails' => 'array',
        'bcc_emails' => 'array',
        'raw_headers' => 'array',
        'is_inbound' => 'boolean',
        'is_processed' => 'boolean',
        'email_date' => 'datetime',
    ];

    public function emailAccount()
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
