<?php
declare(strict_types=1);

namespace App\Services\ComplyAdvantage;


use App\Actions\User\UserInfoByIp;
use App\Business;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Enumerations\Business\Wallet\Type;
use App\Enumerations\PaymentProvider;
use App\Helpers\Conversion;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
use App\Enumerations\Business\PluginProvider;

/**
 * Class TransactionMonitoringService
 * @package App\Services
 */
class TransactionMonitoringService
{
    use UserInfoByIp;

    private static $baseApiUrl;

    public function __construct()
    {
        self::$baseApiUrl = 'https://' . config('services.comply_advantage.tm_environment') .'.api.tm.complyadvantage.com/external/';
    }

    private static function getApiToken()
    {
        try {

            $client = new Client([
                'base_uri' => self::$baseApiUrl,
                'headers' => [
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $response = $client->post('token-auth', [
                'form_params' => [
                    'username' => config('services.comply_advantage.tm_login'),
                    'password' => config('services.comply_advantage.tm_password'),
                ]
            ]);

            $token = json_decode((string)$response->getBody(), true)['token'];

            return $token;
        } catch (ServerException $exception) {
            Log::error($exception->getResponse()->getBody()->__toString());
            throw new \Exception($exception->getResponse()->getBody()->__toString());
        } catch (Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function submitTransaction(Business $business, Business\Charge $charge, Business\Refund $refund = null){
        try {
            $client = new Client([
                'base_uri' => self::$baseApiUrl,
                'headers' => [
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Authorization' => 'Token '. self::getApiToken(),
                    'Content-Type' => 'application/json',
                ],
            ]);

            if($charge->payment_provider === \App\Enumerations\PaymentProvider::DBS_SINGAPORE && isset($charge->data['txnInfo'])){
                $issuerId = $charge->data['txnInfo']['senderParty']['senderBankId'];
                $issuerName = $charge->data['txnInfo']['senderParty']['senderBankId'];
            } elseif(in_array($charge->payment_provider, [
                    PaymentProvider::STRIPE_MALAYSIA,
                    PaymentProvider::STRIPE_SINGAPORE,
                    PaymentProvider::STRIPE_US,
                ]) && ($card = $charge->card()) instanceof \HitPay\Data\Objects\PaymentMethods\Card){
                $issuerId = $card->issuer;
                $issuerName = $card->issuer;
            } else{
                $issuerId = $charge->payment_provider;
                $issuerName = $charge->payment_provider;;
            }


            $response = $client->post('v2/transactions', [
                'body' => json_encode([
                    'id' => $refund ? $refund->getKey() : $charge->getKey(),
                    'source_format' => '4',
                    'data' => [
                        'tx_id' => $refund ? $refund->getKey() : $charge->getKey(),
                        'tx_date_time' => gmdate('Y-m-d H:i:s', $charge->created_at->getTimestamp()),
                        'tx_direction' => 'Inbound',
                        'tx_type' => $refund ? 'Refund' : 'Payment',
                        'tx_base_currency' => SupportedCurrencyCode::USD,
                        'tx_base_amount' => Conversion::convertToUSD(getReadableAmountByCurrency($charge->currency, (int)$charge->amount), $charge->currency),
                        'tx_currency' => $charge->currency,
                        'tx_amount' => getReadableAmountByCurrency($charge->currency, (int)$charge->amount),
                        'tx_reference_text' => $charge->remark ?? null,
                        'tx_product' => null,
                        'tx_payment_channel' => null,
                        'tx_mcc_code' => $business->merchantCategory->code ?? null,
                        'tx_loan_funding_datetime' => null,
                        'tx_loan_settlement_datetime' => null,
                        'tx_loan_expected_repayment_date' => null,
                        'tx_loan_monthly_expected_installment_amount' => null,
                        'tx_calendar_year' => null,
                        'tx_calendar_month' => null,
                        'customer_id' => $business->getKey(),
                        'customer_name' => $business->name,
                        'customer_type' => $business->merchantCategory->code ?? null,
                        'customer_account_balance' => getReadableAmountByCurrency($business->currency, $business->wallet(Type::AVAILABLE, $business->currency)->balance ?? 0),
                        'customer_account_currency' => $business->currency,
                        'customer_base_account_balance' => Conversion::convertToUSD(getReadableAmountByCurrency($business->currency, $business->wallet(Type::AVAILABLE, $business->currency)->balance ?? 0), $business->currency),
                        'customer_account_number' => null,
                        'customer_account_type' => null,
                        'customer_sort_code' => null,
                        'customer_risk_category' => $business->complianceNotes->risk_level ?? null,
                        'customer_date_of_birth' => null,
                        'customer_country' => $business->country ?? null,
                        'customer_state' => $business->state ?? null,
                        'customer_city' => $business->city ?? null,
                        'customer_address' => $business->address ?? null,
                        'customer_postcode' => $business->postcode ?? null,
                        'customer_income' => null,
                        'customer_expected_amount' => null,
                        'customer_bank_branch_id' => null,
                        'customer_bank' => null,
                        'customer_loan_id' => null,
                        'customer_loan_amount' => null,
                        'customer_loan_base_balance' => null,
                        'customer_credit_limit' => null,
                        'customer_expected_installement_amount' => null,
                        'counterparty_id' => $charge->customer_email ?? 'unknown',
                        'counterparty_name' => $charge->customer_name ?? 'unknown',
                        'counterparty_type' => 'paying_customer',
                        'counterparty_account_number' => null,
                        'counterparty_sort_code' => null,
                        'counterparty_date_of_birth' => null,
                        'counterparty_country' => $this->getUserInformationByIp('countrycode', $charge->request_ip_address),
                        'counterparty_bank_country' => null,
                        'counterparty_state' => null,
                        'counterparty_address' => null,
                        'counterparty_city' => null,
                        'counterparty_postcode' => null,
                        'counterparty_reference' => null,
                        'counterparty_institution_id' => $issuerId,
                        'counterparty_institution_name' => $issuerName
                    ]
                ])
            ]);

            $response = json_decode((string)$response->getBody(), true);

            return $response;
        } catch (ServerException $exception) {
            Log::error($exception->getResponse()->getBody()->__toString());
            throw new \Exception($exception->getResponse()->getBody()->__toString());
        } catch (Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}
