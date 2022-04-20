<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Business\PaymentProvider;
use App\Business\GatewayProvider;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Enumerations\OnboardingStatus;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Writer;
use App\Helpers\Customization;

class OnboardingController extends Controller
{
    const supportedProviders = [
      PaymentProviderEnum::GRABPAY,
      PaymentProviderEnum::SHOPEE_PAY
    ];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    public function index (Request $request)
    {
        $data = [];

        foreach (self::supportedProviders as $slug) {
          $count = PaymentProvider::where([
            'payment_provider' => $slug,
            'onboarding_status' => OnboardingStatus::PENDING_SUBMISSION
          ])->count();

          $data[$slug] = $count;
        }

        return Response::view('admin.onboarding.index', compact('data'));
    }

    public function provider (Request $request, $slug) {
        $requestData = Validator::make(['slug' => $slug], [
          'slug' => [
            'required',
            Rule::in(self::supportedProviders)]
        ])->validate();

        $all = false;
        $page = 1;
        $data = self::getMerchantList($requestData['slug'], $all, $page);
        $data['all'] = $all;

        return Response::view('admin.onboarding.provider', [
          'provider' => $requestData['slug'],
          'initialData' => $data
        ]);
    }

    public function merchantList (Request $request, $slug) {
      $request->merge([
        'slug' => $slug
      ]);

      $data = $request->validate([
        'slug' => [
          'required',
          Rule::in(self::supportedProviders)],
        'all' => [
          'required',
          Rule::in(['true', 'false'])],
        'page' => 'numeric|min:1'
      ]);

      return Response::json(self::getMerchantList($data['slug'], $data['all'] === 'true', $data['page']));
    }

    static function getMerchantList ($slug, $all, $page, $perPage = 10) {
      $where = [
        'payment_provider' => $slug
      ];

      if (!$all) {
        $where['onboarding_status'] = OnboardingStatus::PENDING_SUBMISSION;
      }

      $data = PaymentProvider::where($where)
        ->with('business:id,name,country,currency')
        ->paginate($perPage, ['*'], 'page')
        ->toArray();

      // filter business object
      for ($i = 0; $i < count($data['data']); $i++) {
        $item = $data['data'][$i];

        $item['business'] = [
          'name' => $item['business']['name']
        ];

        $data['data'][$i] = $item;
      }

      return [
        'count' => $data['total'],
        'page' => $data['current_page'],
        'data' => $data['data']
      ];
    }

    public function downloadCsv (Request $request, $slug) {
      $requestData = Validator::make(['slug' => $slug], [
        'slug' => [
          'required',
          Rule::in(self::supportedProviders)]
      ])->validate();

      $csv = Writer::createFromString('');

      switch ($slug) {
        case PaymentProviderEnum::GRABPAY:
          $csv->insertOne([
            's_no',
            'merchant_ref',
            'merchant_name',
            'trading_name',
            'business_type',
            'website',
            'business_registration',
            'country_of_registration',
            'address',
            'SSIC',
            'merchant_category_code',
            'submitted_date',
            'updated_date',
            'status',
            'merchant_id',
            'has_grabpay'
          ]);

          break;

        case PaymentProviderEnum::SHOPEE_PAY:
          $csv->insertOne([
            's_no',
            'merchant_ext_id',
            'store_ext_id',
            'merchant_name',
            'trading_name',
            'business_type',
            'website',
            'business_registration',
            'country_of_registration',
            'address',
            'mcc',
            'submitted_date',
            'updated_date',
            'status'
          ]);

          break;
      }

      $dbData = PaymentProvider::with('business')
                ->whereHas('business')
                ->where('payment_provider', $slug)
                ->where('onboarding_status', '!=', OnboardingStatus::SUCCESS)
                ->get()->toArray();

      $data = [];
      $i = 1;

      foreach ($dbData as $rec) {
        switch ($slug) {
          case PaymentProviderEnum::GRABPAY:
            $data[] = [
              's_no' => $i++,
              'merchant_ref' => $rec['payment_provider_account_id'],
              'merchant_name' => $rec['business']['name'],
              'trading_name' => '',
              'business_type' => 'Online',
              'website' => $rec['business']['website'],
              'business_registration' => $rec['data']['company_uen'],
              'country_of_registration' => 'SG',
              'address' => $rec['data']['city'] . ', ' . $rec['data']['postal_code'] . ', ' . $rec['data']['address'],
              'SSIC' => '',
              'merchant_category_code' => $rec['data']['merchant_category_code'],
              'submitted_date' => date('d-m-Y H:i:s'),
              'updated_date' => '',
              'status' => $rec['onboarding_status'],
              'merchant_id' => '',
              'has_grabpay' => isset($rec['data']['has_grabpay']) ? $rec['data']['has_grabpay'] : '0'
            ];

            break;

          case PaymentProviderEnum::SHOPEE_PAY:
            $data[] = [
              's_no' => $i++,
              'merchant_ext_id' => $rec['payment_provider_account_id'],
              'store_ext_id' => $rec['data']['sid'],
              'merchant_name' => $rec['business']['name'],
              'trading_name' => '',
              'business_type' => 'Online',
              'website' => $rec['business']['website'],
              'business_registration' => $rec['data']['company_uen'],
              'country_of_registration' => 'SG',
              'address' => $rec['data']['city'] . ', ' . $rec['data']['postal_code'] . ', ' . $rec['data']['address'],
              'mcc' => $rec['data']['mcc'],
              'submitted_date' => date('d-m-Y H:i:s'),
              'updated_date' => '',
              'status' => $rec['onboarding_status']
            ];

            break;
        }
      }

      $csv->insertAll($data);

      return Response::streamDownload(function () use ($csv) {
        echo $csv->getContent();
      }, $slug . '-onboarding.csv');
    }

    public function uploadCsv (Request $request, $slug) {
      $requestData = Validator::make(['slug' => $slug], [
        'slug' => [
          'required',
          Rule::in(self::supportedProviders)]
      ])->validate();

      $reader = Reader::createFromString($request->file('csv')->get());
      $reader->setHeaderOffset(0);
      $records = $reader->getRecords();

      $not_found = [];
      $failed = [];
      $success = [];

      foreach ($records as $offset => $record) {
        // Stored under different field names for different providers
        switch ($slug) {
          case PaymentProviderEnum::GRABPAY:
            $payment_provider_account_id = $record['merchant_ref'];
            break;

          case PaymentProviderEnum::SHOPEE_PAY:
            $payment_provider_account_id = $record['merchant_ext_id'];
            break;
        }

        $provider = PaymentProvider::where([
          'payment_provider' => $slug,
          'payment_provider_account_id' => $payment_provider_account_id
        ])->first();

        if ($provider) {
          if ($record['status'] === 'success') {
            $provider->onboarding_status = OnboardingStatus::SUCCESS;

            $data = $provider->data;

            switch ($slug) {
              case PaymentProviderEnum::GRABPAY:
                $data['merchant_id'] = rtrim($record['merchant_id']);
                break;
            }

            $provider->data = $data;

            DB::beginTransaction();

            try {
              $provider->save();

              switch ($slug) {
                case PaymentProviderEnum::GRABPAY:
                  // process Gateway providers and remove old GrabPay
                  $gatewayProviders = GatewayProvider::where('business_id', $provider->business_id)->get();

                  foreach($gatewayProviders as $gp) {
                    $methods = $gp->getArrayMethodsAttribute();

                    if (in_array(PaymentMethodType::GRABPAY, $methods)) {
                      $methods = array_values(array_filter($methods, function ($value) {
                        return $value !== PaymentMethodType::GRABPAY;
                      }));

                      // only enable GrabPay Direct
                      $methods[] = PaymentMethodType::GRABPAY_DIRECT;

                      $gp->methods = json_encode($methods);
                      $gp->save();
                    }
                  }

                  Customization::replaceOldGrabPay($provider->business_id);
                  break;
              }

              DB::commit();
              $success[] = $record;

            } catch (Throwable $exception) {
              DB::rollBack();
              throw $exception;
            }

          } else {
            $failed[] = $record;
          }
        } else {
          $not_found[] = $record;
        }
      }

      return Response::json([
        'success' => $success,
        'failed' => $failed,
        'not_found' => $not_found
      ]);
    }
}
