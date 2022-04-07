@extends('layouts.business', [
    'title' => 'Checkout Customisation'
])
@section('business-content')
  <business-checkout-customisation
    :business="{{ $business }}"
    :customisation="{{ $customisation }}"></business-checkout-customisation>

  <business-help-guide :page_type="'checkout_customisation'"></business-help-guide>
@endsection