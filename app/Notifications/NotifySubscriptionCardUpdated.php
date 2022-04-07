<?php

namespace App\Notifications;

use App\Business;
use App\Business\RecurringBilling;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class NotifySubscriptionCardUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public $recurringPlan;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(RecurringBilling $recurringPlan)
    {
        $this->recurringPlan = $recurringPlan;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via(Business $notifiable)
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
    public function toMail(Business $notifiable)
    {
        $title = 'Customer Card Updated for Recurring Payment Plan '.$this->recurringPlan->name;
        $title = App::environment('production') ? $title : '['.App::environment().'] '.$title;

        return (new MailMessage)->view('hitpay-email.recurring.card-updated', [
            'title' => $title,
            'business_logo' => $notifiable->logo ? $notifiable->logo->getUrl() : asset('hitpay/logo-000036.png'),
            'business_name' => $notifiable->getName(),
            'business_email' => $notifiable->email,
            'plan_name' => $this->recurringPlan->name,
            'plan_id' => $this->recurringPlan->getKey(),
            'plan_description' => $this->recurringPlan->description,
            'plan_cycle' => $this->recurringPlan->cycle,
            'plan_status' => $notifiable->status,
            'plan_url' => route('recurring-plan.show', [
                $notifiable->getKey(),
                $this->recurringPlan->business_id,
            ]),
            'plan_next_charge_date' => $this->recurringPlan->expires_at->toDateString(),
            'plan_price' => getFormattedAmount($this->recurringPlan->currency, $this->recurringPlan->price),
            'plan_date' => $this->recurringPlan->created_at->toDateTimeString(),
            'customer' => [
                'name' => $this->recurringPlan->customer_name,
                'email' => $this->recurringPlan->customer_email,
            ],
        ])->subject($title);
    }
}
