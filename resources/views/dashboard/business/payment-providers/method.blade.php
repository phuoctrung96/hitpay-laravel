@extends('layouts.business', [
    'title' => $title
])

@section('business-content')
  <a
    class="back-link mb-2 px-4 d-block"
    style="color: #5D9DE7"
    href="/business/{{$business->id}}/payment-provider">&lt; Back to Payment Methods list
  </a>

  @yield('method-content')
@endsection