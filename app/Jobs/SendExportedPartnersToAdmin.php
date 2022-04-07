<?php

namespace App\Jobs;

use App\Notifications\NotifyAdminPartnersExport;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\Csv\Writer;
use SplTempFileObject;

class SendExportedPartnersToAdmin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $startsAt;
    private string $endsAt;
    private string $adminEmail = 'aditya@hit-pay.com';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $startsAt, string $endsAt)
    {
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $startDate = Carbon::parse($this->startsAt);
        $endDate = Carbon::parse($this->endsAt);

        $csvWriter = Writer::createFromFileObject(new SplTempFileObject());
        $csvWriter->insertOne($this->getCsvHeaders());

        $i = 1;
        User::query()
            ->with('businessPartner')
            ->has('businessPartner')
            ->each(function (User $user) use (&$csvWriter, &$i) {
                $csvWriter->insertOne([
                    $i,
                    $user->getKey(),
                    $user->display_name,
                    $user->businessPartner->status,
                    $user->businessPartner->referral_code,
                    route('register', ['partner_referral' => $user->businessPartner->referral_code]),
                    $user->email,
                    $user->phone,
                    $user->businessPartner->website,
                    implode(', ', $user->businessPartner->platforms),
                    implode(', ', $user->businessPartner->services),
                    $user->businessPartner->short_description,
                    $user->businessPartner->special_offer,
                ]);
                $i++;
            });

        $period = $this->getPeriodAsString($startDate, $endDate);
        $csvPath = $this->getCsvPath($period);

        Storage::disk('local')->put($csvPath, $csvWriter->getContent());

        Notification::route('mail', $this->adminEmail)->notify(new NotifyAdminPartnersExport(
            $period,
            Storage::disk('local')->path($csvPath)
        ));
    }

    private function getCsvHeaders(): array
    {
        return [
            '#',
            'ID',
            'Display Name',
            'Status',
            'Referral code',
            'Referral url',
            'Email',
            'Phone',
            'Website',
            'Platforms',
            'Services',
            'Description',
            'Special sign up offer to HitPay Merchants',
        ];
    }

    private function getCsvPath(string $period): string
    {
        $path = 'email-attachments/';
        $path .= Date::today()->toDateString().'/';
        $path .= $this->adminEmail.'/';
        $path .= microtime(true);

        $path = str_replace(':', '-', $path);

        return "{$path}-partners.csv";
    }

    private function getPeriodAsString(Carbon $startDate, Carbon $endDate)
    {
        $period = '';

        if ($startDate) {
            $period .= "from {$startDate->toDateString()} ";
        } else {
            $period .= "from last time ";
        }

        if ($endDate) {
            $period .= "until {$endDate->toDateString()}";
        } else {
            $period .= "until now";
        }

        return $period;
    }
}
