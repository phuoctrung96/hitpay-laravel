<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\Providers\ConfirmYesterday as ConfirmYesterday;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \HitPay\Enumeration\MakeCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule) : void
    {
        $schedule->command('hitpay:check-last-paynow-callback')->everyMinute();
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('hitpay:clear-email-attachments')->dailyAt('2:15');
        $schedule->command('hitpay:purge-trashed-recovery-codes')->weeklyOn(6, '2:0');
        $schedule->command('hitpay:purge-revoked-sessions')->weeklyOn(6, '3:0');
        // $schedule->command('hitpay:charge-businesses')->dailyAt('7:0');
        $schedule->command('hitpay:process-direct-debit-authorization')->dailyAt('7:30');
        $schedule->command('hitpay:charge-subscribers')->dailyAt('8:0');
        $schedule->command('business:daily-summary')->dailyAt('08:00');
        $schedule->command('business:pending-order')->dailyAt('08:00');

        // The 2 commands below are calling the same table, let's make the interval longer to avoid overload.
        //
        $schedule->command('admin:daily-email', ['--request' => 'charges', '--period' => 'yesterday'])->dailyAt('04:00');
        // $schedule->command('admin:daily-email', ['--request' => 'charges', '--period' => 'last_week'])->weeklyOn(1, '04:30');
        //

        // The 2 commands below are calling the same table, let's make the interval longer to avoid overload.
        //
        $schedule->command('export:transfers', ['--period' => 'yesterday'])->dailyAt('04:05');
        // $schedule->command('export:transfers', ['--period' => 'last_week'])->weeklyOn(1, '04:35');
        //

        // The 3 commands below are calling the same table, let's make the interval longer to avoid overload.
        //
        $schedule->command('admin:daily-email', ['--request' => 'refunds', '--period' => 'yesterday'])->dailyAt('04:10');
        $schedule->command('admin:daily-email', ['--request' => 'cashbacks', '--period' => 'yesterday'])->dailyAt('04:30');
        $schedule->command('admin:daily-email', ['--request' => 'campaigns', '--period' => 'yesterday'])->dailyAt('04:50');
        //

        $schedule->command('admin:daily-email', ['--request' => 'commissions', '--period' => 'yesterday'])->dailyAt('04:15');

        $schedule->command('export:charges.auto-refunds')->dailyAt('04:40');

        $schedule->command('hitpay:commission-payout')->dailyAt('9:00');
        $schedule->command('hitpay:commission-payout-check')->dailyAt('9:30');
        $schedule->command('hitpay:available-balance-payout-automatically', ['00:00:00'])->dailyAt('00:00');
        $schedule->command('hitpay:available-balance-payout-by-custom-list')->dailyAt('19:15');
        $schedule->command('hitpay:available-balance-payout-automatically', ['09:30:00'])->dailyAt('09:30');
        $schedule->command('hitpay:available-balance-payout-automatically-stripe', ['00:00:00'])->dailyAt('00:00');
        $schedule->command('hitpay:available-balance-payout-automatically-stripe', ['09:30:00'])->dailyAt('09:30');
        $schedule->command('hitpay:dbs-fast-payment')->dailyAt('10:30');
        $schedule->command('hitpay:dbs-fast-payment-check')->dailyAt('11:00');
        $schedule->command('hitpay:process-gateway-unsuccessful-callback')->everyMinute();
        $schedule->command('hitpay:process-payment-request-unsuccessful-callback')->everyMinute();
        $schedule->command('xero:clean-connections')->dailyAt('00:30');
        $schedule->command('invoice:remind')->dailyAt('00:10');
        $schedule->command('enable:shop')->everyMinute();
        $schedule->command('xero:refresh-tokens')->weekly();
        $schedule->command('hitpay:check-dbs-refund')->hourly();
        $schedule->command('verification:remind')->weeklyOn(1, '08:20');

        $schedule->command('hitpay:send-onboarding')->daily();
        //$schedule->command('hoolah:process-onboarding')->daily();

//        $schedule->command('proceed:xero-sales-feed --status=success')->dailyAt('15:20');
//        $schedule->command('proceed:xero-sales-feed --status=refund')->dailyAt('14:11');
//        $schedule->command('xero:payout')->dailyAt('14:12');
//        $schedule->command('xero:daily-sales-payout')->dailyAt('14:13');
//        $schedule->command('xero:send-clear-to-bank')->dailyAt('14:14');

        $schedule->command('quickbooks:sales-feed')->dailyAt('07:00');

        $schedule->command('charge:partner-commission')->monthlyOn(1,'03:15');

        $schedule->command('business-referral:calculate-fee')->dailyAt('01:00');
        // $schedule->command('notify:referral-program')->dailyAt('09:15')->when(function () {
        //     return \Carbon\Carbon::now()->endOfMonth()->isToday();
        // });

        $schedule->job(new ConfirmYesterday)->daily('02:00');

        // The commands below are related to HotGlue integrations, we have moved this to queue server and trigger
        // manually for now.
        //
        // $schedule->command('hotglue:check-job-status')->everyMinute();
        // $schedule->command('hotglue:pull-scheduled-jobs')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands() : void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
