<?php

namespace App\Notifications\Business\EmailTemplates;

use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptTemplate extends Notification
{
    use Queueable;

    public Business $business;

    public array $emailTemplateData;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Business $business, array $emailTemplateData)
    {
        $this->business = $business;

        $this->emailTemplateData = $emailTemplateData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $subject = $this->emailTemplateData['email_subject'];

        $title = $this->emailTemplateData['title'];
        $subtitle = $this->emailTemplateData['subtitle'];
        $footer = $this->emailTemplateData['footer'];

        return (new MailMessage)->view('hitpay-email.test-email-templates.payment-receipt', [
            'title' => $title,
            'subtitle' => $subtitle,
            'footer' => $footer,
            'business_logo' => $this->business->logo ? $this->business->logo->getUrl() : asset('hitpay/logo-000036.png'),
        ])->subject($subject);
    }
}
