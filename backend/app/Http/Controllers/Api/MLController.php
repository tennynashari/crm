<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
            Log::info('Starting ML model training...');
            
            // Call Python ML service
            $response = Http::timeout(120)->post($this->getMLServiceUrl() . '/train');
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info('ML model trained successfully', $data);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Model berhasil di-train',
                    'data' => $data
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
     */
    public function predict(Request $request)
    {
        try {
            Log::info('Requesting customer predictions...');
            
            // Call Python ML service
            $response = Http::timeout(30)->post($this->getMLServiceUrl() . '/predict');
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info('Predictions received', ['count' => count($data['predictions'] ?? [])]);
                
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
     * Get model information
     * GET /api/ml/model-info
     */
    public function modelInfo()
    {
        try {
            $response = Http::timeout(5)->get($this->getMLServiceUrl() . '/model-info');
            
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
