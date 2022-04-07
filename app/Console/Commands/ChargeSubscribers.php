<?php

namespace App\Console\Commands;

use App\Business\RecurringBilling;
use App\Enumerations\Business\RecurringPlanStatus;
use App\Exceptions\CollectionFailedException;
use App\Logics\Business\ChargeRepository;
use App\Notifications\NotifySubscriptionRenewalFailure;
use App\Notifications\SendSubscriptionUpdateCardLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\CardException;
use Throwable;

class ChargeSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:charge-subscribers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge business subscribers.';

    /**
     * Execute the console command.
     */
    public function handle() : void
    {
        $date = Date::today();

        RecurringBilling::where('status', RecurringPlanStatus::ACTIVE)->whereDate('expires_at', '<=', $date)
            ->each(function (RecurringBilling $recurringPlan) {
                try {
                    $charge = $recurringPlan->charge();
                } catch (CollectionFailedException $exception) {
                    $recurringPlan->failed_reason = $exception->getDeclineCode();
                    $recurringPlan->save();

                    Log::critical('Failed: Recurring Plan via Collection, ID:'.$recurringPlan->id.' => '
                        .$exception->getMessage());

                    Log::channel('failed-collection')->critical("Failed: Recurring Plan via Collection\n"
                        .'Business ID: '.$recurringPlan->business_id."\n"
                        .'Business Name: '.$recurringPlan->business->name."\n"
                        .'Subscribed Recurring Plan ID: '.$recurringPlan->id."\n"
                        .'Decline Code: '.$exception->getDeclineCode()."\n"
                        .'Error Message: '.$exception->getMessage());

                    $recurringPlan->notify(new SendSubscriptionUpdateCardLink);
                    $recurringPlan->business->notify(new NotifySubscriptionRenewalFailure($recurringPlan));
                } catch (CardException $exception) {
                    $recurringPlan->failed_reason = $exception->getDeclineCode();
                    $recurringPlan->save();

                    Log::critical('Failed: Recurring Plan via Card Payment, ID:'.$recurringPlan->id.' => '
                        .$exception->getMessage());

                    $recurringPlan->notify(new SendSubscriptionUpdateCardLink);
                    $recurringPlan->business->notify(new NotifySubscriptionRenewalFailure($recurringPlan));
                } catch (Throwable $exception) {
                    Log::error('Error when charging recurring plan, ID:'.$recurringPlan->id.' => '.get_class($exception)
                        .' '.$exception->getMessage()."\n".$exception->getFile().':'.$exception->getLine());
                }

                try {
                    ChargeRepository::sendReceipt($charge, $charge->customer_email);
                } catch (Throwable $exception) {
                    Log::error('Error when sending receipt for recurring plan, ID:'.$recurringPlan->id.' => '
                        .get_class($exception).' '.$exception->getMessage()."\n".$exception->getFile()
                        .':'.$exception->getLine());
                }
            });
    }
}
