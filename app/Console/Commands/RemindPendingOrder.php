<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\Order;
use App\Enumerations\Business\OrderStatus;
use App\Notifications\RemindPendingOrder as RemindPendingOrderNotification;
use Illuminate\Console\Command;

class RemindPendingOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'business:pending-order-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() : int
    {
        $businessIds = Order::select('business_id')->groupBy('business_id')
            ->where('status', OrderStatus::REQUIRES_BUSINESS_ACTION)->pluck('business_id');

        $businesses = Business::whereIn('id', $businessIds)->get();

        foreach ($businesses as $business) {
            $collection = $business->orders()->where('status', OrderStatus::REQUIRES_BUSINESS_ACTION)
                ->with('products')->get();

            if ($collection->count() > 0) {
                $business->notify(new RemindPendingOrderNotification($collection));
                $business->businessUsers()->each(function($businessUser) use ($collection) {
                    if ($businessUser->isAdmin()) {
                        $businessUser->user->notify(new RemindPendingOrderNotification($collection));
                    }
                });
            }
        }

        return 0;
    }
}
