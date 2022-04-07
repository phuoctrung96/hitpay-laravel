<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\PaymentCard;
use App\Events\Business\PaymentCard\Added;
use Exception;
use HitPay\Stripe\PaymentCard as StripePaymentCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\InvalidRequestException;
use Throwable;

class PaymentCardRepository
{
    /**
     * Create a new payment card.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business\PaymentCard
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business) : PaymentCard
    {
        $data = Validator::validate($request->all(), [
            'token' => [
                'required',
                'string',
                'max:64',
            ],
            'name' => [
                'nullable',
                'string',
                'max:64',
            ],
        ]);

        try {
            $stripeCard = StripePaymentCard::new($business->payment_provider);
            $stripeCard = $stripeCard->create($business->payment_provider_customer_id, $data['token']);

            unset($data['token']);

            $data['payment_provider'] = $business->payment_provider;
            $data['payment_provider_customer_id'] = $business->payment_provider_customer_id;
            $data['payment_provider_card_id'] = $stripeCard->id;
            $data['brand'] = $stripeCard->brand;
            $data['country'] = $stripeCard->country;
            $data['funding'] = $stripeCard->funding;
            $data['fingerprint'] = $stripeCard->fingerprint;
            $data['last_4'] = $stripeCard->last4;

            $paymentCard = DB::transaction(function () use ($business, $data) : PaymentCard {
                return $business->paymentCards()->create($data);
            }, 3);

            Event::dispatch(new Added($business, $paymentCard));

            return $paymentCard;
        } catch (InvalidRequestException $exception) {
            if ($exception->getStripeCode() === 'resource_missing') {
                throw ValidationException::withMessages([
                    'token' => 'Invalid Stripe token.',
                ]);
            }

            throw $exception;
        } catch (Exception|Throwable $exception) {
            $stripeCard->delete($business->payment_provider_customer_id, $stripeCard->id);

            throw $exception;
        }
    }

    /**
     * Update an existing payment card.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\PaymentCard $paymentCard
     *
     * @return \App\Business\PaymentCard
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function update(Request $request, PaymentCard $paymentCard) : PaymentCard
    {
        $data = Validator::validate($request->all(), [
            'name' => [
                'nullable',
                'string',
                'max:64',
            ],
        ]);

        $paymentCard = DB::transaction(function () use ($paymentCard, $data) : PaymentCard {
            $paymentCard->update($data);

            return $paymentCard;
        }, 3);

        return $paymentCard;
    }

    /**
     * Delete an existing payment card.
     *
     * @param \App\Business\PaymentCard $paymentCard
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(PaymentCard $paymentCard) : ?bool
    {
        return DB::transaction(function () use ($paymentCard) : ?bool {
            $deleted = $paymentCard->delete();

            if ($deleted) {
                StripePaymentCard::new($paymentCard->payment_provider)
                    ->delete($paymentCard->payment_provider_customer_id, $paymentCard->payment_provider_card_id);
            }

            return $deleted;
        }, 3);
    }
}
