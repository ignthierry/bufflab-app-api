<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Return dashboard statistics.
     */
    public function stats()
    {
        $today = Carbon::today();

        // Sum of amount_paid for today's orders
        $todayRevenue = Order::whereDate('created_at', $today)->sum('amount_paid');

        // Count of orders with status pending or processing
        $activeQueue = Order::whereIn('order_status', ['pending', 'processing'])->count();

        // Count of orders with status ready
        $readyCount = Order::where('order_status', 'ready')->count();

        // Count of orders completed today
        $completedToday = Order::where('order_status', 'completed')
            ->whereDate('updated_at', $today)
            ->count();

        // Latest 5 orders with relations
        $recentOrders = Order::with(['customer', 'items.service'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'today_revenue' => $todayRevenue,
                'active_queue' => $activeQueue,
                'ready_count' => $readyCount,
                'completed_today' => $completedToday,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }
}
