<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     */
    public function check()
    {
        try {
            // Check database connection
            DB::connection()->getPdo();

            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toIso8601String(),
                'app' => config('app.name'),
                'version' => '0.1.0',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }
}
