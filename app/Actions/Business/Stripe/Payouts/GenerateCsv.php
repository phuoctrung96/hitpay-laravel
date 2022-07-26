<?php

namespace App\Actions\Business\Stripe\Payouts;

use App\Actions\Business\Stripe\Action as BaseAction;
use App\Business;
use Exception;
use HitPay\Stripe\CustomAccount\Balance\Transaction\IndexByPayout;
use Illuminate\Support\Facades;
use League\Csv\Writer;
use Stripe;

/**
 *
 */
class GenerateCsv extends BaseAction
{
    protected ?Business\Transfer $businessTransfer = null;

    protected ?string $businessTransferId = null;

    /**
     * Set the business transfer.
     *
     * @param  \App\Business\Transfer  $businessTransfer
     *
     * @return $this
     * @throws \Exception
     */
    public function businessTransfer(Business\Transfer $businessTransfer) : self
    {
        if ($this->businessId && $this->businessId !== $businessTransfer->business_id) {
            throw new Exception(
                "The transfer (ID : {$businessTransfer->getKey()}) doesn't belonged to the business (ID : {$this->businessId})"
            );
        }

        if ($businessTransfer->payment_provider_transfer_type !== 'stripe') {
            throw new Exception(
                "The payment provider transfer type of the transfer (ID : {$businessTransfer->getKey()}) must be 'stripe' to continue."
            );
        } else {
            $payoutObjectName = Stripe\Payout::OBJECT_NAME;

            if ($businessTransfer->payment_provider_transfer_method !== $payoutObjectName) {
                throw new Exception(
                    "The payment provider transfer method of the transfer (ID : {$businessTransfer->getKey()}) must be '{$payoutObjectName}' to continue."
                );
            }
        }

        $this->businessTransfer = $businessTransfer;
        $this->businessTransferId = $businessTransfer->getKey();

        return $this;
    }

    /**
     * Set the business transfer to start.
     *
     * @param  \App\Business\Transfer  $businessTransfer
     *
     * @return static
     * @throws \Exception
     */
    public static function withBusinessTransfer(Business\Transfer $businessTransfer) : self
    {
        return ( new static )->businessTransfer($businessTransfer);
    }

    /**
     * Set the business.
     *
     * @param  \App\Business  $business
     *
     * @return $this
     * @throws \Exception
     */
    public function business(Business $business) : self
    {
        if ($this->businessTransfer && $this->businessTransfer->business_id !== $business->getKey()) {
            throw new Exception(
                "The business (ID : {$business->getKey()}) has no right to the transfer (ID : {$this->businessTransferId})"
            );
        }

        return parent::business($business);
    }

    /**
     * Generate the CSV.
     *
     * @return void
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \League\Csv\CannotInsertRecord
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function process() : void
    {
        $response = IndexByPayout::new($this->businessTransfer->payment_provider)
            ->setBusiness($this->business)
            ->handle($this->businessTransfer->payment_provider_transfer_id);

        $csv = Writer::createFromString();

        $csv->insertOne([
            '#',
            'Datetime',
            'Description',
            'Charge ID',
            'Payout ID',
            'Debit',
            'Credit',
        ]);

        $counter = 1;

        $response->each(function (Stripe\BalanceTransaction $item) use (&$csv, &$counter) : void {
            if ($item->reporting_category === 'charge' && $item->source instanceof Stripe\Charge) {
                [ $description, $chargeId ] = $this->getDetailsFromStripeCharge($item->source);
            } elseif ($item->reporting_category === 'refund' && $item->source instanceof Stripe\Refund) {
                [ $description, $chargeId ] = $this->getDetailsFromStripeRefund($item->source);
            } elseif ($item->reporting_category === 'payout' && $item->source instanceof Stripe\Payout) {
                [ $description, $payoutId ] = $this->getDetailsFromStripePayout($item->source);
            }

            $csv->insertOne([
                '#' => $counter++,
                'Datetime' => Facades\Date::createFromTimestamp($item->created)->toDateTimeString(),
                'Description' => $description ?? null,
                'Charge ID' => $chargeId ?? null,
                'Payout ID' => $payoutId ?? null,
                'Debit' => $item->amount < 0 ? $item->amount : '-',
                'Credit' => $item->amount > 0 ? $item->amount : '-',
            ]);
        });

        $createdAtTimeStamp = Facades\Date::now()->getTimestamp();

        $filename = "business-docs/payouts/{$this->businessTransfer->getKey()}-{$createdAtTimeStamp}.csv";

        Facades\Storage::put($filename, (string) $csv);

        $data = $this->businessTransfer->data;

        $data['file'] = [
            'path' => $filename,
            'type' => 'processed',
            'created_at' => $createdAtTimeStamp,
        ];

        $this->businessTransfer->data = $data;

        $this->businessTransfer->save();
    }

    /**
     * Get the description for the Stripe charge.
     *
     * @param  \Stripe\Charge  $stripeCharge
     *
     * @return array|string[]
     */
    protected function getDetailsFromStripeCharge(Stripe\Charge $stripeCharge) : array
    {
        // The metadata 'charge_id' is the old name we were using. Just try to check on it too.
        //
        if ($stripeCharge->metadata->business_charge_id || $stripeCharge->metadata->charge_id) {
            $businessChargeId = $stripeCharge->metadata->business_charge_id ?? $stripeCharge->metadata->charge_id;

            return [ "Received from charge # {$businessChargeId}", $businessChargeId ];
        }

        $stripeChargeId = "{$stripeCharge->id} *";

        return [ "Received from charge (# {$stripeChargeId})", $stripeChargeId ];
    }

    /**
     * Get the description for the Stripe refund.
     *
     * @param  \Stripe\Refund  $stripeRefund
     *
     * @return array|string[]
     */
    protected function getDetailsFromStripeRefund(Stripe\Refund $stripeRefund) : array
    {
        if ($stripeRefund->charge instanceof Stripe\Charge) {
            $stripeCharge = $stripeRefund->charge;

            if ($stripeCharge->metadata->business_charge_id || $stripeCharge->metadata->charge_id) {
                $businessChargeId = $stripeCharge->metadata->business_charge_id ?? $stripeCharge->metadata->charge_id;

                return [ "Refund for charge # {$businessChargeId}", $businessChargeId ];
            }

            $stripeChargeId = "{$stripeCharge->id} *";

            return [ "Refund for charge (# {$stripeChargeId})", $stripeChargeId ];
        }

        $stripeRefundId = "{$stripeRefund->id} **";

        return [ "Refund for charge # {$stripeRefundId})", $stripeRefundId ];
    }

    /**
     * Get the description for the Stripe payout.
     *
     * @param  \Stripe\Payout  $stripePayout
     *
     * @return array|string[]
     */
    protected function getDetailsFromStripePayout(Stripe\Payout $stripePayout) : array
    {
        if ($stripePayout->metadata->business_transfer_id) {
            return [
                "Payout (Transfer # {$stripePayout->metadata->business_transfer_id})",
                $stripePayout->metadata->business_transfer_id,
            ];
        }

        $stripePayoutId = "{$stripePayout->id} *";

        return [ "Payout (# {$stripePayoutId})", $stripePayoutId ];
    }
}
