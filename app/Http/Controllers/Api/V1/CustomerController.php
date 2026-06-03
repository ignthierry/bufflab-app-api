<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Search customer by whatsapp number.
     * Returns customer with order count if found, or empty.
     */
    public function search(Request $request)
    {
        $whatsapp = $request->input('whatsapp');

        if (!$whatsapp) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $customer = Customer::withCount('orders')
            ->where('whatsapp_number', $whatsapp)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }
}
