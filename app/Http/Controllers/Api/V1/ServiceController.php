<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Service;

class ServiceController extends Controller
{
    /**
     * Return all active services ordered by sort_order.
     * Maps 'name' field to 'service_name' for frontend compatibility.
     */
    public function index()
    {
        $services = Service::where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_name' => $service->name,
                    'price' => $service->price,
                    'estimated_days' => $service->estimated_days ?? 3,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    /**
     * Update the price of a service.
     */
    public function updatePrice(\Illuminate\Http\Request $request, Service $service)
    {
        $request->validate([
            'price' => 'required|integer|min:0',
        ]);

        $service->update([
            'price' => $request->price,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $service->id,
                'service_name' => $service->name,
                'price' => $service->price,
                'estimated_days' => $service->estimated_days ?? 3,
            ],
            'message' => 'Harga layanan berhasil diperbarui'
        ]);
    }
}
