<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use App\Models\Customer;
use App\Models\Interaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class EmailController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'to' => 'required|string', // Changed to string to support multiple emails
            'subject' => 'required|string',
            'body' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', // Max 10MB per file
        ]);

        $user = $request->user();
        $emailSetting = EmailSetting::where('user_id', $user->id)->first();

        if (!$emailSetting) {
            return response()->json([
                'message' => 'Please configure your email settings first'
            ], 400);
        }

        // Configure mail settings dynamically
        Config::set('mail.mailers.smtp.host', $emailSetting->mail_host);
        Config::set('mail.mailers.smtp.port', $emailSetting->mail_port);
        Config::set('mail.mailers.smtp.username', $emailSetting->mail_username);
        Config::set('mail.mailers.smtp.password', $emailSetting->mail_password);
        Config::set('mail.mailers.smtp.encryption', $emailSetting->mail_encryption);
        Config::set('mail.from.address', $emailSetting->mail_from_address);
        Config::set('mail.from.name', $emailSetting->mail_from_name);

        try {
            // Send email with HTML support and attachments
            Mail::send([], [], function ($message) use ($validated, $emailSetting, $request) {
                $message->to($validated['to'])
                        ->subject($validated['subject'])
                        ->from($emailSetting->mail_from_address, $emailSetting->mail_from_name)
                        ->html($validated['body']); // Use html() instead of raw()
                
                // Attach files if present
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $message->attach($file->getRealPath(), [
                            'as' => $file->getClientOriginalName(),
                            'mime' => $file->getMimeType(),
                        ]);
                    }
                }
            });

            // Log interaction
            Interaction::create([
                'customer_id' => $validated['customer_id'],
                'interaction_type' => 'email_outbound',
                'channel' => 'email',
                'subject' => $validated['subject'],
                'content' => $validated['body'],
                'summary' => 'Email sent: ' . $validated['subject'],
                'interaction_at' => now(),
                'created_by_type' => 'user',
                'created_by_user_id' => $user->id,
                'lead_status_snapshot_id' => Customer::find($validated['customer_id'])->lead_status_id,
            ]);

            return response()->json([
                'message' => 'Email sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }
}
