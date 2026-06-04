<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * List orders with eager-loaded relations.
     * Supports ?status= filter and ?search= for customer name/whatsapp/invoice.
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items.service']);

        // Filter by order status
        if ($request->filled('status')) {
            $query->where('order_status', $request->input('status'));
        }

        // Search by customer name, whatsapp, or invoice number
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                          ->orWhere('whatsapp_number', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Create a new order with customer and items.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer.name' => 'required|string|max:255',
            'customer.whatsapp_number' => 'required|string|max:20',
            'order_details.amount_paid' => 'nullable|numeric|min:0',
            'order_details.payment_status' => ['nullable', Rule::in(['unpaid', 'partial', 'paid'])],
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.brand' => 'required|string|max:100',
            'items.*.model' => 'nullable|string|max:100',
            'items.*.color' => 'required|string|max:50',
            'items.*.material' => 'required|string|max:100',
            'items.*.size' => 'nullable|integer',
            'items.*.initial_condition_notes' => 'nullable|string',
            'items.*.photo_base64' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Find or create customer by whatsapp_number
            $customerData = $request->input('customer');
            $customer = Customer::firstOrCreate(
                ['whatsapp_number' => $customerData['whatsapp_number']],
                ['name' => $customerData['name']]
            );
            // Update name if customer already existed
            if (!$customer->wasRecentlyCreated) {
                $customer->update(['name' => $customerData['name']]);
            }

            // 2. Generate invoice number: INV-YYYYMMDD-XXXX
            $today = Carbon::today();
            $todayStr = $today->format('Ymd');
            $todayCount = Order::whereDate('created_at', $today)->count();
            $sequence = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = "INV-{$todayStr}-{$sequence}";

            // 3. Calculate total_price by summing service prices
            $items = $request->input('items');
            $serviceIds = collect($items)->pluck('service_id');
            $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');
            $totalPrice = 0;
            foreach ($items as $item) {
                $totalPrice += $services[$item['service_id']]->price;
            }

            // 4. Create Order record
            $orderDetails = $request->input('order_details', []);
            $amountPaid = $orderDetails['amount_paid'] ?? 0;
            $paymentStatus = $orderDetails['payment_status'] ?? 'unpaid';

            $order = Order::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customer->id,
                'total_price' => $totalPrice,
                'amount_paid' => $amountPaid,
                'payment_status' => $paymentStatus,
                'order_status' => 'pending',
            ]);

            // 5 & 6. Create OrderItem records with photo handling
            foreach ($items as $index => $itemData) {
                $photoPath = null;

                // Decode photo_base64 if present
                if (!empty($itemData['photo_base64'])) {
                    $photoData = base64_decode($itemData['photo_base64']);
                    $filename = "{$invoiceNumber}_{$index}.jpg";
                    Storage::disk('public')->put("photos/{$filename}", $photoData);
                    $photoPath = "photos/{$filename}";
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $itemData['service_id'],
                    'brand' => $itemData['brand'],
                    'model' => $itemData['model'] ?? null,
                    'color' => $itemData['color'],
                    'material' => $itemData['material'],
                    'size' => $itemData['size'] ?? null,
                    'initial_condition_notes' => $itemData['initial_condition_notes'] ?? null,
                    'photo_path' => $photoPath,
                ]);
            }

            // 7. Return created order with all relations loaded
            $order->load(['customer', 'items.service']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order,
            ], 201);
        });
    }

    /**
     * Return single order with customer and items.service loaded.
     */
    public function show($id)
    {
        $order = Order::with(['customer', 'items.service'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Find order by invoice_number with relations.
     */
    public function showByInvoice($invoiceNumber)
    {
        $order = Order::with(['customer', 'items.service'])
            ->where('invoice_number', $invoiceNumber)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus($id, Request $request)
    {
        $request->validate([
            'order_status' => ['required', Rule::in(['pending', 'processing', 'ready', 'completed', 'cancelled'])],
        ]);

        $order = Order::findOrFail($id);
        
        $updateData = [
            'order_status' => $request->input('order_status'),
        ];

        // If order_status is set to completed, automatically mark as paid
        if ($request->input('order_status') === 'completed') {
            $updateData['payment_status'] = 'paid';
            $updateData['amount_paid'] = $order->total_price;
        }

        $order->update($updateData);

        $order->load(['customer', 'items.service']);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order,
        ]);
    }

    /**
     * Update order payment status.
     */
    public function updatePayment($id, Request $request)
    {
        $request->validate([
            'payment_status' => ['required', Rule::in(['unpaid', 'partial', 'paid'])],
            'amount_paid' => 'required|numeric|min:0',
        ]);

        $order = Order::findOrFail($id);
        $order->update([
            'payment_status' => $request->input('payment_status'),
            'amount_paid' => $request->input('amount_paid'),
        ]);

        $order->load(['customer', 'items.service']);

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran berhasil diperbarui',
            'data' => $order,
        ]);
    }
}
