<?php

namespace App\Business;

use App\Business;
use App\Enumerations\PaymentProvider;
use App\Exceptions\HitPayLogicException;
use App\Notifications\NotifySuccessfulCommissionPayout;
use ErrorException;
use Exception;
use HitPay\Agent\LogHelpers;
use HitPay\Business\Ownable;
use HitPay\Model\UsesUuid;
use HitPay\PayNow\Fast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;

class Commission extends Model
{
    use LogHelpers, Ownable, UsesUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'business_commissions';

    protected $casts = [
        'data' => 'array',
    ];

    public function business() : BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id', 'id', 'charge');
    }

    /**
     * Get the charges of this transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Business\Charge|\App\Business\Charge[]
     */
    public function charges() : BelongsToMany
    {
        return $this->belongsToMany(Charge::class, 'business_charge_commission', 'commission_id', 'charge_id', 'id', 'id',
            'charges');
    }

    /**
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Crypt_GPG_BadPassphraseException
     * @throws \Crypt_GPG_Exception
     * @throws \Crypt_GPG_FileException
     * @throws \Crypt_GPG_KeyNotFoundException
     * @throws \Crypt_GPG_NoDataException
     * @throws \PEAR_Exception
     * @throws \ReflectionException
     */
    public function doFastTransfer()
    {
        switch (false) {

            case $this->exists:
                throw new HitPayLogicException('The commission is not exist');
            case $this->payment_provider === PaymentProvider::DBS_SINGAPORE:
                throw new HitPayLogicException('The commission is not a DBS transfer');
            case $this->status === 'request_pending';
                throw new HitPayLogicException('The transfer status is not `request_pending`');
            case $this->amount !== 0:
                throw new HitPayLogicException('The commission has zero amount!');
        }

        $business = $this->business;

        try {
            [
                $bankSwiftCode,
                $bankAccountNumber,
            ] = explode('@', $this->payment_provider_account_id);

            if (!isset(Transfer::$availableBankSwiftCodes[$bankSwiftCode])) {
                $message = 'The payment provider DBS for business [ID: '.$business->id.'] has an invalid swift code'
                    .' [Value: '.$bankSwiftCode.']. A transfer is still created for this account with status pending,'
                    .' admin can update the account for this transfer and trigger it again.';
            } elseif (!preg_match('/^[0-9]+$/', $bankAccountNumber)) {
                $message = 'The payment provider DBS for business [ID: '.$business->id.'] has an invalid bank transfer'
                    .' is account number, it is having non-digits value [Value: '.$bankAccountNumber.']. However, a'
                    .' still created for this account with status pending, admin can update the account for this'
                    .' transfer and trigger it again.';
            }
        } catch (ErrorException $exception) {
            $message = 'The payment provider DBS for business [ID: '.$business->id.'] has an invalid bank account'
                .' format. It should be ":swift_code@:bank_account_number", but there\'s error exception.';
        }

        if (isset($message)) {
            Log::critical($message);

            throw new HitPayLogicException($message);
        }

        $data = $this->data;

        $fastData = Fast::new($this->getKey())
            ->setAmount(getReadableAmountByCurrency($this->currency, $this->amount))
            ->setBusinessName($business->getName())
            ->setReceiverBankSwiftCode($bankSwiftCode)
            ->setReceiverEmail($business->routeNotificationForMail())
            ->setReceiverName($data['company']['name'] ?? $business->getName())
            ->setReceiverAccountNumber($bankAccountNumber)
            ->setTransactionDetails($this->charges)
            ->generate();

        if ($fastData->getErrorMessages()) {
            Log::critical(implode("\n", $fastData->getErrorMessages()));
        }

        $fastDataResponse = $fastData->getResponse();

        // Not sure if the success response change, they only mentioned response for RJCT.
        if (isset($fastDataResponse['txnResponse']['txnStatus']) &&
            $fastDataResponse['txnResponse']['txnStatus'] === 'ACTC') {
            $this->payment_provider_transfer_id = $fastDataResponse['txnResponse']['txnRefId'];
            $this->payment_provider_transfer_type = 'fast';
            $this->status = 'succeeded';
        }

        $data['requests'][] = [
            'body' => $fastData->getRequestBody(),
            'response' => $fastDataResponse,
        ];

        $this->data = $data;

        $this->save();

        if ($this->status === 'succeeded') {
            try {
                $this->business->notify(new NotifySuccessfulCommissionPayout($this));
            } catch (Exception $exception) {
                Log::critical('PayNow Payout email for business ID ['.$this->business_id.'],'
                    .' date ['.$this->created_at->toDateString().'] sending failed.');
            }
        }
    }
}
