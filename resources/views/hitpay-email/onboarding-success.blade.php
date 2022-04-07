@extends('hitpay-email.layouts.base', [
  'title' => 'Onboarding success',
  'preheader' => ''
])

@section('content')
  <p>Good day,</p>

  <p>Your request to accept {{ $payment_provider }} has been accepted. Please navigate to
    <a href="{{ $integration_link }}">integrations page</a> in the HitPay Dashboard to enable this 
    payment method for your sales channel.
  </p>

  <p>Thank you,</p>
  <p>Team HitPay</p>
@endsection
