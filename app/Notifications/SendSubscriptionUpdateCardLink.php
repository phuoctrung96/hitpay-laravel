<?php

namespace App\Notifications;

use App\Business\RecurringBilling;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class SendSubscriptionUpdateCardLink extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via(RecurringBilling $notifiable)
    {
        return [
            'mail',
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(RecurringBilling $notifiable)
    {
        $title = App::environment('production') ? '' : '['.App::environment().'] ';
        $title .= 'Failed Charge for Recurring Payment Invoice from '.$notifiable->business->getName();

        return (new MailMessage)->view('hitpay-email.recurring.update-card', [
            'title' => $title,
            'business_logo' => $notifiable->business->logo ? $notifiable->business->logo->getUrl() : asset('hitpay/logo-000036.png'),
            'business_name' => $notifiable->business->getName(),
            'business_email' => $notifiable->business->email,
            'plan_name' => $notifiable->name,
            'plan_id' => $notifiable->getKey(),
            'plan_description' => $notifiable->description,
            'plan_cycle' => $notifiable->cycle,
            'plan_status' => $notifiable->status,
            'plan_url' => route('recurring-plan.show', [
                $notifiable->business_id,
                $notifiable->getKey(),
            ]),
            'plan_payment_provider' => $notifiable->payment_provider,
            'plan_next_charge_date' => $notifiable->expires_at->toDateString(),
            'plan_price' => getFormattedAmount($notifiable->currency, $notifiable->price),
            'plan_date' => $notifiable->expires_at->toDateString(),
            'customer' => [
                'name' => $notifiable->customer_name,
                'email' => $notifiable->customer_email,
            ],
        ])->subject($title);
    }
}
