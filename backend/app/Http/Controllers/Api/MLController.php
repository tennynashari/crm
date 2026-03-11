<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MLController extends Controller
{
    /**
     * Python ML Service base URL
     */
    private function getMLServiceUrl()
    {
        return env('ML_SERVICE_URL', 'http://127.0.0.1:5000');
    }
    
    /**
     * Get current tenant database name from session
     */
    private function getTenantDatabase()
    {
        $tenantDb = session('tenant_db', config('database.connections.pgsql.database'));
        
        Log::info('Using tenant database for ML', [
            'tenant_db' => $tenantDb,
            'company_id' => session('company_id'),
            'user_email' => auth()->user()->email ?? 'unknown'
        ]);
        
        return $tenantDb;
    }

    /**
     * Check ML service health
     */
    public function health()
    {
        try {
            $response = Http::timeout(5)->get($this->getMLServiceUrl() . '/health');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'ML Service not responding'
            ], 503);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot connect to ML Service',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * Train ML model
     * POST /api/ml/train
     */
    public function train(Request $request)
    {
        try {
            $tenantDb = $this->getTenantDatabase();
            
            Log::info('Starting ML model training...', ['database' => $tenantDb]);
            
            // Call Python ML service with tenant database parameter
            $response = Http::timeout(120)->post($this->getMLServiceUrl() . '/train', [
                'database' => $tenantDb,
                'company_id' => session('company_id')
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info('ML model trained successfully', array_merge($data, ['database' => $tenantDb]));
                
                return response()->json([
                    'success' => true,
                    'message' => 'Model berhasil di-train',
                    'data' => $data,
                    'database' => $tenantDb
                ]);
            }
            
            Log::error('ML training failed', ['response' => $response->body()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal training model',
                'error' => $response->json()
            ], $response->status());
            
        } catch (\Exception $e) {
            Log::error('ML training exception', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error saat training model',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get predictions for top potential customers
     * POST /api/ml/predict
     * Role-based filtering:
     * - Admin: Get top 7 from all customers
     * - Sales: Get top 7 from only assigned customers
     */
    public function predict(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantDb = $this->getTenantDatabase();
            
            $payload = [
                'top_n' => 7,
                'database' => $tenantDb,
                'company_id' => session('company_id')
            ];
            
            // ROLE-BASED FILTER
            if ($user->role === 'sales') {
                // Get only assigned customers
                $customerIds = Customer::where('assigned_sales_id', $user->id)
                    ->pluck('id')
                    ->toArray();
                
                Log::info('Sales user prediction', [
                    'user_id' => $user->id,
                    'assigned_customers_count' => count($customerIds),
                    'database' => $tenantDb
                ]);
                
                // If no customers assigned, return empty result
                if (empty($customerIds)) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'predictions' => [],
                            'generated_at' => now()->toIso8601String(),
                            'message' => 'No customers assigned to you'
                        ]
                    ]);
                }
                
                $payload['customer_ids'] = $customerIds;
            } else {
                Log::info('Admin user prediction - all customers', ['database' => $tenantDb]);
            }
            
            // Call Python ML service
            $response = Http::timeout(30)->post($this->getMLServiceUrl() . '/predict', $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info('Predictions received', [
                    'count' => count($data['predictions'] ?? []),
                    'database' => $tenantDb
                ]);
                
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }
            
            // If model not trained yet
            if ($response->status() === 400) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model belum di-train. Silakan train model terlebih dahulu.',
                    'error' => $response->json()
                ], 400);
            }
            
            Log::error('ML prediction failed', ['response' => $response->body()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan prediksi',
                'error' => $response->json()
            ], $response->status());
            
        } catch (\Exception $e) {
            Log::error('ML prediction exception', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error saat prediksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get prediction for a single customer
     * POST /api/ml/predict-single
     */
    public function predictSingle(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|integer|exists:customers,id'
            ]);

            $tenantDb = $this->getTenantDatabase();

            Log::info('Requesting single customer prediction...', [
                'customer_id' => $request->customer_id,
                'database' => $tenantDb
            ]);
            
            // Call Python ML service
            $response = Http::timeout(10)->post($this->getMLServiceUrl() . '/predict-single', [
                'customer_id' => $request->customer_id,
                'database' => $tenantDb,
                'company_id' => session('company_id')
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info('Single prediction received', ['customer_id' => $request->customer_id]);
                
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }
            
            // If model not trained yet
            if ($response->status() === 400) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model belum di-train. Silakan train model terlebih dahulu.',
                    'error' => $response->json()
                ], 400);
            }

            // If customer not found
            if ($response->status() === 404) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer tidak ditemukan dalam model',
                    'error' => $response->json()
                ], 404);
            }
            
            Log::error('ML single prediction failed', ['response' => $response->body()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan prediksi',
                'error' => $response->json()
            ], $response->status());
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('ML single prediction exception', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error saat prediksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get model information
     * GET /api/ml/model-info
     */
    public function modelInfo()
    {
        try {
            $tenantDb = $this->getTenantDatabase();
            
            // Pass database parameter to Python ML service
            $response = Http::timeout(5)->get($this->getMLServiceUrl() . '/model-info', [
                'database' => $tenantDb
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'model_exists' => false,
                'message' => 'Cannot get model info'
            ], $response->status());
            
        } catch (\Exception $e) {
            return response()->json([
                'model_exists' => false,
                'message' => 'ML Service not available',
                'error' => $e->getMessage()
            ], 503);
        }
    }
}
