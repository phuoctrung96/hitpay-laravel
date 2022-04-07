<?php

namespace App\Notifications;

use App\Business\ProductVariation;
use HitPay\Firebase\Channel;
use HitPay\Firebase\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;

class RemindLowQuantity extends Notification implements ShouldQueue
{
    use Queueable;

    public $product;

    public $outOfStock;

    public function __construct(ProductVariation $product, bool $outOfStock = false)
    {
        $this->product = $product;
        $this->outOfStock = $outOfStock;
    }

    public function via($notifiable)
    {
        return [
            'mail',
            Channel::class,
        ];
    }

    public function toMail($notifiable)
    {
        $title = $this->outOfStock ? 'You have an out of stock product' : 'Low Inventory Alert';
        $title = App::environment('production') ? $title : '['.App::environment().'] '.$title;

        return (new MailMessage)->view('hitpay-email.low-quantity', [
            'product' => $this->product,
            'out_of_stock' => $this->outOfStock,
            'title' => $title,
        ])->subject($title);
    }

    public function toFirebase($notifiable)
    {
        $prefix = App::environment('production') ? '' : '['.App::environment().'] ';

        if ($this->outOfStock) {
            return new Message($prefix.$this->product->product->name.' : Out of Stock',
                'Update product quantity in the app under Products');
        }

        return new Message($prefix.'Low Quantity Alert : '.$this->product->product->name,
            'Update product quantity in the app under Products');
    }
}
