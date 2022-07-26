<?php

namespace App\Http\Controllers\Api\Business;

use App\Business as BusinessModel;
use App\Enumerations\Business\OrderStatus;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class InsightsController extends Controller
{
    /**
     * InsightsController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, BusinessModel $business)
    {
        Gate::inspect('view', $business)->authorize();

        $defaultDayRange = 6;

        if ($request->period == 'current_week') {
            $startDate = Carbon::now()->startOfWeek();
        } else {
            $startDate = Carbon::now()->subDays($defaultDayRange);
        }

        $endDate = Carbon::now()->endOfDay();

        $cache_key = "business_{$business->id}_insights_{$request->period}";

        if (Cache::has($cache_key)) {
            $data = Cache::get($cache_key);
        } else {
            $ordersWithProducts = $business
                ->setConnection('mysql_read')
                ->orders()
                ->whereDate('created_at', '>=', $startDate)
                ->whereIn('status', [OrderStatus::COMPLETED, OrderStatus::REQUIRES_BUSINESS_ACTION])
                ->orderBy('created_at')
                ->with('products')
                ->get();

            if ($ordersWithProducts->count()) {
                foreach ($ordersWithProducts as $order) {
                    foreach ($order->products as $product)
                        $subset[] = $product;
                }

                $groupedOrderedProducts = collect($subset)->groupBy('business_product_id');

                $limit = 5;

                $topProducts = $groupedOrderedProducts
                    ->map(function ($product) {
                        $count = 0;
                        foreach ($product as $item)
                            $count += $item->quantity;

                        return ['name' => $product->first()->name, 'count' => $count];
                    })
                    ->sortByDesc('count')
                    ->take($limit);
            }

            $totalOrders = [];

            $date = $startDate->copy();
            while ($date <= $endDate) {
                $day = $date;
                $day = $day->format('Y-m-d');

                $totalOrders[$day] = [
                    'date' => $day,
                    'count' => 0
                ];

                $date->addDay();
            }

            $results = $business
                ->setConnection('mysql_read')
                ->orders()
                ->whereDate('created_at', '>=', $startDate)
                ->whereIn('status', [OrderStatus::COMPLETED, OrderStatus::REQUIRES_BUSINESS_ACTION])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();

            foreach ($results as $date => $count) {
                $totalOrders[$date]['count'] = $count;
            }

            $data = [
                'total_orders' => array_values($totalOrders),
                'top_products' => $topProducts ?? null,
            ];

            $expires_at = Carbon::now()->addHours(1);

            Cache::put($cache_key, $data, $expires_at);
        }

        return Response::json($data);
    }
}
