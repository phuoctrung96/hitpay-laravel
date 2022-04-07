<?php

namespace App\Actions\Business\Stripe\BalanceTransaction;

use App\Actions\Business\Stripe\Action;
use HitPay\Stripe\CustomAccount\Balance\Transaction;
use Illuminate\Support\Facades\Date;
use Stripe;

class Index extends Action
{
    private ?string $first = null;

    private ?string $last = null;

    private int $perPage = 3;

    public function process()
    {
        $response = Transaction\Index::new($this->business->payment_provider)
            ->setBusiness($this->business)
            ->handle($this->last, $this->first, $this->perPage);

        $data = $response->getCollection();

        // Reporting Categories & Balance Transaction Types
        //
        // By rights, we should handle all of these types to display correctly. But we are going to filter out the
        // charges and refunds only for now. See https://stripe.com/docs/reports/reporting-categories for latest docs.
        //
        // ===============================================================================================
        // |  Reporting Category       |  Balance Transaction Types                                      |
        // ===============================================================================================
        // |  charge                   |  charge, payment                                                |
        // |  refund                   |  refund, payment_refund                                         |
        // |  payout_reversal          |  payout_cancel, payout_failure                                  |
        // |  transfer                 |  transfer, recipient_transfer                                   |
        // |  transfer_reversal        |  transfer_cancel, transfer_failure, recipient_transfer_cancel,  |
        // |                           |  recipient_transfer_failure                                     |
        // |                           |                                                                 |
        // |  platform_earning         |  application_fee                                                |
        // |  platform_earning_refund  |  application_fee_refund                                         |
        // |  fee                      |  stripe_fee                                                     |
        // |  connect_reserved_funds   |  reserve_transaction                                            |
        // |  risk_reserved_funds      |  reserved_funds                                                 |
        // ===============================================================================================
        //

        $transactions = $data->map(function (Stripe\BalanceTransaction $item) : ?array {
            if ($item->reporting_category === 'charge' && $item->source instanceof Stripe\Charge) {
                $charge = $item->source;
            } elseif ($item->reporting_category === 'refund' && $item->source instanceof Stripe\Refund) {
                if ($item->source->charge instanceof Stripe\Charge) {
                    $charge = $item->source->charge;
                }
            }

            $data = [
                'id' => $item->id,
                'reporting_category' => $item->reporting_category,
                'type' => $item->type,
                'currency' => $item->currency,
                'amount' => $item->amount,
                'available_on' => Date::createFromTimestamp($item->available_on),
                'created_at' => Date::createFromTimestamp($item->created),
                'charge' => null,
                'payout' => null,
            ];

            if ($item->reporting_category === 'payout') {
                $data['payout'] = [
                    'object' => $item->source->destination->object,
                ];
            } elseif (isset($charge)) {
                $data['charge'] = [
                    'id' => $charge->metadata->charge_id,
                    'stripe_id' => $charge->id,
                ];
            } else {
                return null;
            }

            return $data;
        })->filter(function ($item) {
            return !is_null($item);
        });

        $expectedChargeIds = $transactions->whereNotNull('charge.id')->pluck('charge.id')->unique();

        $charges = $this->business->charges()->whereIn('id', $expectedChargeIds)->get();

        $transactions = $transactions->map(function ($transaction) use ($charges) {
            if (isset($transaction['charge']['id'])) {
                $transaction['charge'] = $charges->where('id', $transaction['charge']['id'])->first();
            }

            return $transaction;
        });

        return [
            'ending_before' => $response->firstId(),
            'start_after' => $response->lastId(),
            'data' => $transactions,
        ];
    }

    public function setFirst(?string $first) : self
    {
        $this->first = $first;

        return $this;
    }

    public function setLast(?string $last) : self
    {
        $this->last = $last;

        return $this;
    }

    public function setPerPage(int $perPage) : self
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function restart() : self
    {
        $this->last = null;

        return $this;
    }
}
