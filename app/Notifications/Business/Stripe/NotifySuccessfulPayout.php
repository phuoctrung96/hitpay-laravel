<?php

namespace App\Notifications\Business\Stripe;

use App\Business\Transfer;
use App\Models\Business\BankAccount;
use App\Notifications\Notification;
use HitPay\Data\Countries;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;

class NotifySuccessfulPayout extends Notification
{
    use Queueable;

    public Transfer $transfer;

    public string $csvFile;

    public BankAccount $bankAccount;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Transfer $transfer, BankAccount $bankAccount, string $csvFile)
    {
        $this->transfer = $transfer;

        $this->bankAccount = $bankAccount;

        $this->csvFile = $csvFile;
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
     * @throws \Exception
     */
    public function toMail($notifiable)
    {
        $title = (App::environment('production') ? '' : '['.App::environment().'] ').'HitPay Balance Payouts for '
            .$this->transfer->business->name.' ('.$this->transfer->created_at->toDateString().')';

        $mailMessage = new MailMessage;

        $country = Countries::get($this->transfer->business->country);

        $bank = $country->banks()->where('id', $this->bankAccount->bank_swift_code)->first();

        if ($bank === null) {
            $bankName = "";
        } else {
            $bankName = $bank->toArray()['name'];
        }

        $mailMessage->view('hitpay-email.stripe-payouts', [
            'title' => $title,
            'transfer' => $this->transfer,
            'bankAccount' => $this->bankAccount,
            'bankName' => $bankName,
        ])->subject($title);

        if ($this->csvFile) {
            $mailMessage->attachData($this->csvFile, "transfer-{$this->transfer->getKey()}.csv");
        }

        return $mailMessage;
    }

    public function toFirebase($notifiable)
    {
        //
    }
}
