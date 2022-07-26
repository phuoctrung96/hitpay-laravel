<?php

namespace App\Notifications;

use App\Actions\Business\EmailTemplates\ConvertEmailTemplate;
use App\Business\EmailTemplate;
use App\Business\RecurringBilling;
use App\Enumerations\Business\RecurringPlanStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class SendSubscriptionLink extends Notification implements ShouldQueue
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
     * @throws \Exception
     */
    public function toMail(RecurringBilling $notifiable)
    {
        $isHaveTemplateEmail = false;

        $businessEmailTemplate = $notifiable->business->emailTemplate()->first();

        if ($businessEmailTemplate instanceof EmailTemplate) {
            $isHaveTemplateEmail = true;
        }

        $subtitle = null;
        $footer = null;
        $buttonText = null;
        $buttonBackgroundColor = null;
        $buttonTextColor = null;

        if ($isHaveTemplateEmail) {
            $emailTemplate = ConvertEmailTemplate::withBusiness($notifiable->business)
                ->setEmailTemplateData($businessEmailTemplate->recurring_invoice_template)
                ->process();

            $emailSubject = $emailTemplate['email_subject'] ?? null;

            if ($emailSubject === null) {
                if ($notifiable->status === RecurringPlanStatus::ACTIVE) {
                    $emailSubject = 'Recurring Payment Invoice from '.$notifiable->business->getName();
                } else {
                    $emailSubject = 'New Recurring Payment Invoice from '.$notifiable->business->getName();
                }
            }

            $title = $emailTemplate['title'] ?? null;

            if ($title === null) {
                if ($notifiable->status === RecurringPlanStatus::ACTIVE) {
                    $title = 'Recurring Payment Invoice from '.$notifiable->business->getName();
                } else {
                    $title = 'New Recurring Payment Invoice from '.$notifiable->business->getName();
                }
            }

            $subtitle = $emailTemplate['subtitle'] ?? null;

            $footer = $emailTemplate['footer'] ?? null;

            $buttonText = $emailTemplate['button_text'] ?? null;
            $buttonBackgroundColor = $emailTemplate['button_background_color'] ?? null;
            $buttonTextColor = $emailTemplate['button_text_color'] ?? null;
        } else {
            if ($notifiable->status === RecurringPlanStatus::ACTIVE) {
                $emailSubject = 'Recurring Payment Invoice from '.$notifiable->business->getName();
            } else {
                $emailSubject = 'New Recurring Payment Invoice from '.$notifiable->business->getName();
            }

            $title = $emailSubject;
        }

        $title = App::environment('production') ? $title : '['.App::environment().'] '.$title;

        $emailSubject = App::environment('production') ? $emailSubject : '['.App::environment().'] '.$emailSubject;

        return (new MailMessage)->view('hitpay-email.recurring.plan', [
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
            'plan_next_charge_date' => $notifiable->expires_at->toDateString(),
            'plan_price' => getFormattedAmount($notifiable->currency, $notifiable->price),
            'plan_date' => $notifiable->expires_at->toDateString(),
            'customer' => [
                'name' => $notifiable->customer_name,
                'email' => $notifiable->customer_email,
            ],
            'subtitle' => $subtitle,
            'footer' => $footer,
            'button_text' => $buttonText,
            'button_background_color' => $buttonBackgroundColor,
            'button_text_color' => $buttonTextColor,
            'isHaveTemplateEmail' => $isHaveTemplateEmail
        ])->subject($emailSubject);
    }
}
