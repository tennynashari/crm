<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Contact;
use App\Models\Area;
use App\Models\EmailSetting;
use App\Models\Interaction;
use App\Models\BroadcastEmailHistory;
use App\Models\BroadcastEmailDraft;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class BroadcastEmailController extends Controller
{
    public function getRecipients(Request $request)
    {
        $request->validate([
            'filter_type' => 'required|in:all,area',
            'area_id' => 'required_if:filter_type,area|exists:areas,id'
        ]);

        $emails = [];
        $query = Customer::with('contacts');

        if ($request->filter_type === 'area') {
            $query->where('area_id', $request->area_id);
        }

        $customers = $query->get();

        foreach ($customers as $customer) {
            // Add company email
            if ($customer->email) {
                $emails[] = [
                    'email' => $customer->email,
                    'name' => $customer->company,
                    'type' => 'Company'
                ];
            }

            // Add all PIC emails
            foreach ($customer->contacts as $contact) {
                if ($contact->email) {
                    $emails[] = [
                        'email' => $contact->email,
                        'name' => $contact->name,
                        'type' => 'PIC - ' . $customer->company
                    ];
                }
            }
        }

        return response()->json([
            'recipients' => $emails,
            'total' => count($emails)
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'filter_type' => 'required|in:all,area',
            'area_id' => 'nullable|exists:areas,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', // Max 10MB per file
        ]);

        $user = auth()->user();
        $emailSetting = EmailSetting::where('user_id', $user->id)->first();

        if (!$emailSetting) {
            return response()->json([
                'message' => 'Please configure your email settings first'
            ], 400);
        }

        // Configure mail
        Config::set('mail.mailers.smtp.host', $emailSetting->mail_host);
        Config::set('mail.mailers.smtp.port', $emailSetting->mail_port);
        Config::set('mail.mailers.smtp.username', $emailSetting->mail_username);
        Config::set('mail.mailers.smtp.password', $emailSetting->mail_password);
        Config::set('mail.mailers.smtp.encryption', $emailSetting->mail_encryption);
        Config::set('mail.from.address', $emailSetting->mail_from_address);
        Config::set('mail.from.name', $emailSetting->mail_from_name);

        // Get recipients
        $query = Customer::with('contacts');
        
        if ($request->filter_type === 'area' && $request->area_id) {
            $query->where('area_id', $request->area_id);
        }

        $customers = $query->get();
        $sentCount = 0;
        $failedEmails = [];
        $allRecipients = [];

        foreach ($customers as $customer) {
            $emailsSent = [];

            // Send to company email
            if ($customer->email) {
                try {
                    Mail::send([], [], function ($message) use ($customer, $request) {
                        $message->to($customer->email)
                                ->subject($request->subject)
                                ->html($request->body);
                        
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
                    $emailsSent[] = $customer->email;
                    $allRecipients[] = $customer->email;
                    $sentCount++;
                } catch (\Exception $e) {
                    $failedEmails[] = $customer->email;
                }
            }

            // Send to all PICs
            foreach ($customer->contacts as $contact) {
                if ($contact->email) {
                    try {
                        Mail::send([], [], function ($message) use ($contact, $request) {
                            $message->to($contact->email)
                                    ->subject($request->subject)
                                    ->html($request->body);
                            
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
                        $emailsSent[] = $contact->email;
                        $allRecipients[] = $contact->email;
                        $sentCount++;
                    } catch (\Exception $e) {
                        $failedEmails[] = $contact->email;
                    }
                }
            }

            // Log interaction for this customer
            if (!empty($emailsSent)) {
                Interaction::create([
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'type' => 'email_broadcast',
                    'notes' => "Broadcast Email\nTo: " . implode(', ', $emailsSent) . "\nSubject: " . $request->subject . "\n\n" . $request->body,
                    'interaction_date' => now()
                ]);
            }
        }

        // Save broadcast history
        BroadcastEmailHistory::create([
            'user_id' => $user->id,
            'subject' => $request->subject,
            'body' => $request->body,
            'filter_type' => $request->filter_type,
            'area_id' => $request->area_id,
            'recipient_count' => $sentCount,
            'recipients' => $allRecipients,
            'has_attachments' => $request->hasFile('attachments'),
        ]);

        return response()->json([
            'message' => 'Broadcast email sent successfully',
            'sent_count' => $sentCount,
            'failed_count' => count($failedEmails),
            'failed_emails' => $failedEmails
        ]);
    }

    public function history()
    {
        $user = auth()->user();
        
        $query = BroadcastEmailHistory::with(['user', 'area'])
            ->orderBy('created_at', 'desc');
        
        // Only admin can see all history, others see only their own
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }
        
        $history = $query->get();
        
        return response()->json($history);
    }

    // Draft methods
    public function getDrafts()
    {
        $user = auth()->user();
        
        $drafts = BroadcastEmailDraft::with(['user', 'area'])
            ->where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get();
        
        return response()->json($drafts);
    }

    public function getDraft($id)
    {
        $user = auth()->user();
        
        $draft = BroadcastEmailDraft::with(['area'])
            ->where('user_id', $user->id)
            ->findOrFail($id);
        
        return response()->json($draft);
    }

    public function saveDraft(Request $request)
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'filter_type' => 'nullable|in:all,area',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $user = auth()->user();

        $draft = BroadcastEmailDraft::create([
            'user_id' => $user->id,
            'subject' => $request->subject,
            'body' => $request->body,
            'filter_type' => $request->filter_type,
            'area_id' => $request->area_id,
        ]);

        return response()->json([
            'message' => 'Draft saved successfully',
            'draft' => $draft
        ], 201);
    }

    public function updateDraft(Request $request, $id)
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'filter_type' => 'nullable|in:all,area',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $user = auth()->user();

        $draft = BroadcastEmailDraft::where('user_id', $user->id)
            ->findOrFail($id);

        $draft->update([
            'subject' => $request->subject,
            'body' => $request->body,
            'filter_type' => $request->filter_type,
            'area_id' => $request->area_id,
        ]);

        return response()->json([
            'message' => 'Draft updated successfully',
            'draft' => $draft
        ]);
    }

    public function deleteDraft($id)
    {
        $user = auth()->user();

        $draft = BroadcastEmailDraft::where('user_id', $user->id)
            ->findOrFail($id);

        $draft->delete();

        return response()->json([
            'message' => 'Draft deleted successfully'
        ]);
    }
}
