@extends('layouts.business', [
    'title' => 'Payment Methods'
])

@section('business-content')
  <payment-methods
    :current_business_user="{{ json_encode(resolve(\App\Services\BusinessUserPermissionsService::class)->getBusinessUser(Auth::user(), $business)) }}"
    business_id="{{ $business->id }}"
{{--    :business="{{ json_encode($business) }}"--}}
{{--    :providers="{{ json_encode($providers) }}"--}}
{{--    :disabled_providers="{{ json_encode($disabled_providers) }}"--}}
{{--    :banks_list="{{ json_encode($bankList) }}"--}}
    :user="{{json_encode(Auth::user()->load('businessUsers'))}}"
    tab="{{ $tab }}"
{{--    :business_verified="{{ json_encode($business_verified) }}"--}}
  ></payment-methods>

  <business-help-guide :page_type="'settings'"></business-help-guide>
@endsection
