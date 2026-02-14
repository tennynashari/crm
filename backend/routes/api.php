<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\LeadStatusController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EmailSettingController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\BroadcastEmailController;
use App\Http\Controllers\Api\InvoiceController;

// CSRF cookie route - MUST be in api.php with web middleware
Route::get('/csrf-cookie', function () {
    try {
        // Start session manually if needed
        if (!session()->isStarted()) {
            session()->start();
        }
        return response()->json([
            'message' => 'CSRF cookie set',
            'session_driver' => config('session.driver'),
            'session_id' => session()->getId()
        ])->cookie(
            'XSRF-TOKEN',
            csrf_token(),
            120,
            '/',
            null,
            false,
            false
        );
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Users
    Route::apiResource('users', UserController::class);

    // Customers
    Route::get('/customers/export', [CustomerController::class, 'export']);
    Route::get('/customers/{id}/export', [CustomerController::class, 'exportDetail']);
    Route::apiResource('customers', CustomerController::class);
    Route::post('/customers/{id}/next-action', [CustomerController::class, 'updateNextAction']);

    // Contacts (PICs)
    Route::apiResource('contacts', ContactController::class);

    // Interactions
    Route::apiResource('interactions', InteractionController::class);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);

    // Areas
    Route::apiResource('areas', AreaController::class);

    // Lead Statuses
    Route::apiResource('lead-statuses', LeadStatusController::class);

    // Email Settings
    Route::get('/email-settings', [EmailSettingController::class, 'show']);
    Route::post('/email-settings', [EmailSettingController::class, 'store']);
    Route::put('/email-settings', [EmailSettingController::class, 'update']);

    // Send Email
    Route::post('/send-email', [EmailController::class, 'send']);

    // Broadcast Email
    Route::post('/broadcast-email/recipients', [BroadcastEmailController::class, 'getRecipients']);
    Route::post('/broadcast-email/send', [BroadcastEmailController::class, 'send']);
    Route::get('/broadcast-email/history', [BroadcastEmailController::class, 'history']);
    
    // Broadcast Email Drafts
    Route::get('/broadcast-email/drafts', [BroadcastEmailController::class, 'getDrafts']);
    Route::get('/broadcast-email/drafts/{id}', [BroadcastEmailController::class, 'getDraft']);
    Route::post('/broadcast-email/drafts', [BroadcastEmailController::class, 'saveDraft']);
    Route::put('/broadcast-email/drafts/{id}', [BroadcastEmailController::class, 'updateDraft']);
    Route::delete('/broadcast-email/drafts/{id}', [BroadcastEmailController::class, 'deleteDraft']);
});
