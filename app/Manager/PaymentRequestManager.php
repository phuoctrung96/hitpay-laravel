<?php

namespace App\Manager;

use App\Business;
use App\Business\PaymentRequest;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\Business\PaymentRequestStatus;
use App\Logics\Business\PaymentRequestRepository;
use Illuminate\Validation\ValidationException;
use Validator;

class PaymentRequestManager extends AbstractManager implements ManagerInterface, PaymentRequestManagerInterface
{
    public function getClass()
    {
        return PaymentRequest::class;
    }

    public function getFind($id) : ?PaymentRequest
    {
        return PaymentRequest::find($id);
    }

    public function create(array $data, $businessKey, array $paymentMethods, Business $platform = null) : PaymentRequest
    {
        $apiKey                 = ApiKeyManager::findByApiKey($businessKey);
        $data['business_id']    = $apiKey->business->getKey();
        $data['status']         = PaymentRequestStatus::PENDING;
        $data['sms_status']     = PaymentRequestStatus::PENDING;
        $data['email_status']   = PaymentRequestStatus::PENDING;
        $data['channel']        = !isset($data['channel']) ? PluginProvider::DEFAULT_CHANNEL : $data['channel'];

        $data['expiry_date'] = $this->setExpiryDate($data);

        if ($platform) {
            $data['platform_business_id'] = $platform->getKey();
            $data['commission_rate'] = $platform->commission_rate;
        }

        if (!isset($data['allow_repeated_payments']) || empty($data['allow_repeated_payments']) || $data['allow_repeated_payments'] == false || $data['allow_repeated_payments'] === 'false') {
            $data['allow_repeated_payments'] = false;
        } else {
            $data['allow_repeated_payments'] = true;
        }

        if (!isset($data['send_sms']) || empty($data['send_sms']) || $data['send_sms'] == 'true') {
            $data['send_sms']   = true;
        } else {
            $data['send_sms']   = false;
        }

        if (isset($data['add_admin_fee']) && ($data['add_admin_fee'] === 'true' || $data['add_admin_fee'] === true)) {
          $data['add_admin_fee'] = true;
        } else {
          $data['add_admin_fee'] = false;
        }

        if (!isset($data['send_email']) || empty($data['send_email'])) {
            $data['send_email'] = false;
        } else {
            $data['send_email'] = true;
        }

        if (!isset($data['payment_methods']) || empty($data['payment_methods'])) {
            $data['payment_methods'] = $paymentMethods;
        }

        return PaymentRequestRepository::store($data);
    }

    public function update(PaymentRequest $paymentRequest, array $data) : PaymentRequest
    {
        return PaymentRequestRepository::update($paymentRequest, $data);
    }

    public function delete(PaymentRequest $paymentRequest)
    {
        return PaymentRequestRepository::delete($paymentRequest);
    }

    public function markAsCompleted(PaymentRequest $paymentRequest) : void
    {
        $paymentRequest->update([
            'status' => PaymentRequestStatus::COMPLETED
        ]);
    }

    private function setExpiryDate($data)
    {
        $expiryDate = isset($data['expiry_date']) ? $data['expiry_date'] : null;

        $expiresAfter = isset($data['expires_after']) ? $data['expires_after'] : null;

        $resultExpiryDate = null;

        if ($expiryDate) {
            $resultExpiryDate = $expiryDate;
        } else {
            if ($expiresAfter) {
                $expiresAfterExplode = explode(" ", trim($expiresAfter));

                if (count($expiresAfterExplode) == 2) {
                    $numberExpiresAfter = $expiresAfterExplode[0];

                    $evExpiresAfter = $expiresAfterExplode[1];

                    $rangesTime = ["mins", "hours", "days"];

                    if (in_array($evExpiresAfter, $rangesTime) && is_numeric($numberExpiresAfter)) {
                        if ($evExpiresAfter == "mins" && $numberExpiresAfter < 5 ) {
                            $validator = Validator::make([], []);
                            $validator->errors()->add('expires_after', 'Minimum expiry time should be 5 mins or more');
                            throw new ValidationException($validator);
                        } else {
                            $resultExpiryDate = date('Y-m-d H:i:s', strtotime("+ " . $expiresAfter));
                        }
                    }
                }

                if (!$resultExpiryDate) {
                    $validator = Validator::make([], []);
                    $validator->errors()->add('expires_after', 'expires_after format invalid');
                    throw new ValidationException($validator);
                }
            }
        }

        return $resultExpiryDate;
    }
}
