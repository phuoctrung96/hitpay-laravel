<?php

namespace App\Actions\Business\Stripe\Account;

use App\Actions\Business\Action as BaseAction;
use App\Business;
use App\Enumerations\PaymentProvider;
use App\Enumerations\PaymentProviderAccountType;
use App\Notifications\Business\Stripe\NotifyAccountUnverified;
use HitPay\Stripe\CustomAccount\Balance\Retrieve;
use HitPay\Stripe\CustomAccount\Sync;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades;

class SyncFromWebhook extends BaseAction
{
    public function process(string $paymentProviderCode, string $stripeAccountId) : void
    {
        $businessPaymentProvider = Business\PaymentProvider::query()->where([
            'payment_provider' => $paymentProviderCode,
            'payment_provider_account_id' => $stripeAccountId,
        ])->first();

        if (!( $businessPaymentProvider instanceof Business\PaymentProvider )) {
            Facades\Log::critical(
                "The business payment provider isn't found when using the Stripe Account ID : {$stripeAccountId}."
            );

            return;
        }

        if ($businessPaymentProvider->payment_provider_account_type !==
            PaymentProviderAccountType::STRIPE_CUSTOM_TYPE) {
            Facades\Log::info(
                "The Stripe Account (ID : {$stripeAccountId}) is not a custom type account, we will not handle it for now."
            );

            return;
        }

        $business = $businessPaymentProvider->business;

        $businessPaymentProvider = Sync::new($businessPaymentProvider->payment_provider)
            ->setBusiness($business)
            ->handle(null, false);

        if (!$businessPaymentProvider->payment_provider_account_ready) {
            $hadSentEmailEarlier = Facades\Cache::get($this->getEmailSentCacheKey($business), false);

            if ($hadSentEmailEarlier) {
                return;
            }

            $stripeAccount = $businessPaymentProvider->data['account'];

            // If the following codes throw error relating to index not found, most probably that a different version
            // of Stripe account object is stored.
            //

            $requirements = $stripeAccount['requirements'];
            $disabledReason = $requirements['disabled_reason'] ?? false;

            // If the account is under review, we will just return and not sending anything to the business.
            //
            if ($disabledReason === 'under_review') {
                Facades\Log::info(
                    "Stripe Connected Custom Account (ID : {$stripeAccount['id']}), Business ID (ID : {$business->getKey()}) : Do nothing. Account is under review by Stripe."
                );

                return;
            }

            $accountBalances = Retrieve::new($business->payment_provider)->setBusiness($business)->handle();

            $hasBalance = false;

            /**
             * @var string $currency
             * @var \Illuminate\Support\Collection $balance
             */
            foreach ($accountBalances as $balance) {
                foreach ($balance->toArray() as $details) {
                    $hasBalance = $details['amount'] > 0;

                    if ($hasBalance) {
                        break 2;
                    }
                }
            }

            if (!$hasBalance) {
                Facades\Log::info(
                    "Stripe Connected Custom Account (ID : {$stripeAccount['id']}), Business ID (ID : {$business->getKey()}) : Does not have any balance, so we are not send any email to business, even with anything due."
                );

                return;
            }

            $_email_messages = [];
            $_internal_message = null;

            if (!$stripeAccount['charges_enabled'] && !$stripeAccount['payouts_enabled']) {
                $_email_messages[] = 'Your account payments and payouts have been disabled temporarily';
            } elseif (!$stripeAccount['charges_enabled']) {
                $_email_messages[] = 'Your account payments have been disabled temporarily';
            } elseif (!$stripeAccount['payouts_enabled']) {
                $_email_messages[] = 'Your payouts have been temporarily disabled';
            }

            $capabilities = $stripeAccount['capabilities'];

            // For now, we changed to check `transfers` only. By right we should check `card_payments` as well.
            // Let's monitor if anything happen again.
            //
            if ($capabilities['transfers'] === 'inactive') {
                $_email_messages[] = 'We detected that the transfers capability of your account is inactive.';
            }

            $disabledCausedByRequirementsPastDue = false;

            if (array_key_exists('current_deadline', $requirements) && is_int($requirements['current_deadline'])) {
                $currentDeadline = Facades\Date::createFromTimestamp($requirements['current_deadline']);
            }

            if ($disabledReason !== false) {
                $disabledCausedByRequirementsPastDue = $disabledReason === 'requirements.past_due';

                if ($disabledCausedByRequirementsPastDue) {
                    if (isset($currentDeadline)) {
                        $_email_messages[] =
                            "Additional verification information is required by {$currentDeadline->toDateString()} to enable payout or charge capabilities on your business account.";
                        $_internal_message =
                            "Additional verification information is required by {$currentDeadline->toDateString()} to enable payout or charge capabilities on this account.";
                    } else {
                        $_email_messages[] =
                            'Additional verification information is required to enable payout or charge capabilities on your business account.';
                        $_internal_message =
                            'Additional verification information is required to enable payout or charge capabilities on this account.';
                    }
                } elseif ($disabledReason === 'action_required.requested_capabilities') {
                    $_internal_message = 'You need to request capabilities for the connected account.';
                } elseif ($disabledReason === 'requirements.pending_verification') {
                    $_internal_message = 'Stripe is currently verifying information on the connected account.';
                } elseif ($disabledReason === 'rejected.fraud') {
                    $_internal_message = 'Account is rejected due to suspected fraud or illegal activity.';
                } elseif ($disabledReason === 'rejected.listed') {
                    $_internal_message =
                        'Account is rejected because it is on a third-party prohibited persons or companies list (such as financial services provider or government).';
                } elseif ($disabledReason === 'rejected.other') {
                    $_internal_message = 'Account is rejected for another reason.';
                } elseif ($disabledReason === 'rejected.terms_of_service') {
                    $_internal_message = 'Account is rejected due to suspected terms of service violations.';
                } elseif ($disabledReason === 'listed') {
                    $_internal_message =
                        'Account might be on a prohibited persons or companies list (Stripe will investigate and either reject or reinstate the account appropriately).';
                } elseif ($disabledReason === 'other') {
                    $_internal_message =
                        'Account is not rejected but is disabled for another reason while being reviewed.';
                } else {
                    $_internal_message = "Account is disabled for reason '{$disabledReason}'.";
                }
            }

            if (!$disabledCausedByRequirementsPastDue) {
                $requirementsCurrentlyDue = Collection::make($requirements['currently_due'] ?? []);

                if ($requirementsCurrentlyDue->count()) {
                    if (isset($currentDeadline)) {
                        $_email_messages[] =
                            "Additional verification information is required before {$currentDeadline->toDateString()} to keep the payout or charge capabilities on your business account enabled.";
                    } else {
                        $_email_messages[] =
                            "Additional verification information is required to keep the payout or charge capabilities on your business account enabled.";
                    }
                }
            }

            if (!is_null($_internal_message)) {
                Facades\Log::critical("Stripe Connected Custom Account (ID : {$stripeAccount['id']}), Business ID (ID : {$business->getKey()}) : {$_internal_message}");
            }

            if (count($_email_messages)) {
                array_unshift($_email_messages, "Hi, {$business->getName()}!");

                if ($businessPaymentProvider->payment_provider === PaymentProvider::STRIPE_SINGAPORE) {
                    $paymentMethodName = 'Cards and AliPay';
                } else {
                    $paymentMethodName = 'HitPay Payment Gateway';
                }

                $_email_messages[] =
                    "Log in to your HitPay dashboard, navigate to \"Settings > Payment Methods > View Details\" and complete the missing information.";

                $title = "Finish Setting Up The {$paymentMethodName} To Keep Your Business Account Active";

                $business->notify(new NotifyAccountUnverified($business, $title, $_email_messages));

                Facades\Cache::put($this->getEmailSentCacheKey($business), true, Facades\Date::now()->addMinutes(30));
            }
        }
    }

    /**
     * Get the cache key to check whether an email is sent to the business earlier.
     *
     * @param  \App\Business  $business
     *
     * @return string
     */
    protected function getEmailSentCacheKey(Business $business) : string
    {
        return sha1(__CLASS__).":{$business->getKey()}";
    }
}
