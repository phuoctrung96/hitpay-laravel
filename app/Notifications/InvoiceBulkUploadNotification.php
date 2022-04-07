<?php


namespace App\Notifications;


use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class InvoiceBulkUploadNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $messageText;
    public $errors = array();

    public function __construct(Business\InvoiceFeedLog $log)
    {
        $this->messageText = "The ".$log->success_count." invoices are successfully upload and ".$log->error_count. " invoices are failed.";
        $this->errors = json_decode($log->error_msg, true);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param \App\User $notifiable
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
     * @param \App\User $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(Business $notifiable)
    {
        $title = 'Invoice Bulk Upload Notification';
        $title = App::environment('production') ? $title : '['.App::environment().'] '.$title;

        return (new MailMessage)->view('hitpay-email.invoice-bulk-upload', [
            'title' => $title,
            'errors' => $this->errors,
            'messageText' => $this->messageText
        ])->subject($title);
    }
}
