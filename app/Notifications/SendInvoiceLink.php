<?php

namespace App\Notifications;

use App\Business\Invoice;
use App\Business\ProductVariation;
use App\Events\Business\SentInvoice;
use App\Helpers\Currency;
use App\Http\Resources\Business\Product as ProductResource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

/**
 * Class SendInvoiceLink
 * @package App\Notifications
 */
class SendInvoiceLink extends Notification
{

    public $filename;

    public $content;

    public $type;

    /**
     * Create a new notification instance.
     *
     * @param string $filename
     * @param string $content
     * @param string $type
     */
    public function __construct(string $filename, string $content, $type)
    {
        $this->filename = $filename;
        $this->content = $content;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via(Invoice $notifiable)
    {
        return [
            'mail',
        ];
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param Invoice $notifiable
     *
     * @return string
     */
    protected function checkoutUrl($notifiable)
    {
        return $notifiable->paymentRequest->url;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param Invoice $notifiable
     *
     * @return MailMessage
     * @throws \ReflectionException
     */
    public function toMail(Invoice $notifiable)
    {
        event(new SentInvoice($notifiable));

        $title = App::environment('production') ? '' : '['.App::environment().'] ';
        if ($notifiable->isOverdue()) {
            $title .= 'Overdue ';
        }
        $title = $title . 'Invoice from '.$notifiable->business->getName();

        $mail = (new MailMessage)->view('hitpay-email.invoice.invoice', [
            'title' => $title,
            'business_logo' => $notifiable->business->logo ? $notifiable->business->logo->getUrl() : asset('hitpay/logo-000036.png'),
            'business_name' => $notifiable->business->getName(),
            'business_email' => $notifiable->business->email,
            'reference' => $notifiable->reference,
            'invoice_number' => $notifiable->invoice_number,
            'invoice_id' => $notifiable->getKey(),
            'products' => $notifiable->products,
            'url' => $this->checkoutUrl($notifiable),
            'currency' => $notifiable->currency,
            'amount' => strtoupper($notifiable->currency) . ' ' . Currency::getReadableAmount($notifiable->amount, $notifiable->currency),
            'due_date' => Carbon::parse($notifiable->due_date)->format('d/m/Y'),
            'customer' => [
                'name' => $notifiable->customer->name,
                'email' => $notifiable->customer->email,
            ],
            'hosted_link' => route('invoice.hosted.show', [$notifiable->business->id, $notifiable->id])
        ])->subject($title)->attachData($this->content, $this->filename . '.pdf');

        if ($notifiable->attached_file){
            $mail->attach(Storage::disk(Storage::getDefaultDriver())->path($notifiable->attached_file));
        }

        return $mail;
    }
}
