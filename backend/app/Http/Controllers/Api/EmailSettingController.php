<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use Illuminate\Http\Request;

class EmailSettingController extends Controller
{
    public function show(Request $request)
    {
        $setting = EmailSetting::where('user_id', $request->user()->id)->first();
        
        if ($setting) {
            // Mask password for security
            $setting->mail_password = '********';
        }
        
        return response()->json($setting);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mail_host' => 'required|string',
            'mail_port' => 'required|integer',
            'mail_username' => 'required|string',
            'mail_password' => 'required|string',
            'mail_encryption' => 'required|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        $setting = EmailSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json([
            'message' => 'Email settings saved successfully',
            'setting' => $setting
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'mail_host' => 'sometimes|required|string',
            'mail_port' => 'sometimes|required|integer',
            'mail_username' => 'sometimes|required|string',
            'mail_password' => 'sometimes|required|string',
            'mail_encryption' => 'sometimes|required|in:tls,ssl',
            'mail_from_address' => 'sometimes|required|email',
            'mail_from_name' => 'sometimes|required|string',
        ]);

        // Skip password if it's masked
        if (isset($validated['mail_password']) && $validated['mail_password'] === '********') {
            unset($validated['mail_password']);
        }

        $setting = EmailSetting::where('user_id', $request->user()->id)->firstOrFail();
        $setting->update($validated);

        return response()->json([
            'message' => 'Email settings updated successfully',
            'setting' => $setting
        ]);
    }
}
