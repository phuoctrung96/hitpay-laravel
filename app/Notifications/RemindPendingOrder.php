<?php

namespace App\Notifications;

use App\Enumerations\Business\Event;
use HitPay\Firebase\Channel;
use HitPay\Firebase\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;

class RemindPendingOrder extends Notification
{
    public $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function via($notifiable)
    {
        return $this->getVia($notifiable, Event::PENDING_ORDER);
    }

    public function toMail($notifiable)
    {
        $prefix = App::environment('production') ? '' : '['.App::environment().'] ';

        return (new MailMessage)->view('hitpay-email.pending-order', [
            'collection' => $this->collection,
        ])->subject($prefix.'You have pending shipment orders');
    }

    public function toFirebase($notifiable)
    {
        $prefix = App::environment('production') ? '' : '['.App::environment().'] ';

        return new Message($prefix.'Pending Shipping Alert', 'You have customer orders that are yet to be fulfilled or completed. Mark your orders as completed under Products > Pending Orders in the app or web dashboard');
    }
}
