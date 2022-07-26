@php
    use \Firebase\JWT\JWT;

    $noltLink = config('app.nolt.board_url');
    $payload = [
    'id' => Auth::user()->id,
    'email' => Auth::user()->email,
    'name' => Auth::user()->display_name,
  ];

  $jwt = JWT::encode($payload, config('app.sso.secret_key'), 'HS256');
@endphp
@php
      $alertText = "";
      $alertLink = "";
      $alertLinkText = "";
      $type = "";

      if (Auth::check()) {
        $user = Auth::user();

        if (Str::startsWith(request()->url(), [ 'https://dashboard', 'http://dashboard' ])) {
          if (!$user->email_login_enabled && !request()->routeIs('dashboard.user.profile', 'dashboard.user.welcome')) {
            $alertText = "You account isn't complete.";
            $alertLink = route('dashboard.user.welcome');
            $alertLinkText = "Complete your account now";
            $type = 'account_not_completed';
          }
        }
      }

      if (!isset($title)) {
        $title = null;
      }
@endphp

@extends('layouts.app', [
    'navbar_text' => $business->getName(),
    'navbar_main_border_bottom' => false
])

@push('head-stack')
    <script>
        window.Business = @json($business->toBladeModel());
        window.User = @json(Auth::user()->load('businessUsers')->toBladeModel());
    </script>
@endpush

@section('app-content')
    <main-layout
        title="{{ $title }}"
        alert_text="{{ $alertText }}"
        alert_link="{{ $alertLink }}"
        alert_link_text="{{ $alertLinkText }}"
        type="{{$type}}"
        user_role="{{ $user->role_id }}"
        nolt_link="{{$noltLink}}">
        @yield('business-content')
    </main-layout>
@endsection
@push('body-stack')
    <script async src="https://cdn.nolt.io/widgets.js"></script>
    <script>window.noltQueue=window.noltQueue||[];function nolt(){noltQueue.push(arguments)}</script>

    <script>
        nolt('init', {
            newWindowOnCookieError: true
        });
        nolt('identify', {
            jwt: '{{$jwt}}'
        });
    </script>

    <!-- Start of Refiner client code snippet -->
    <script type="text/javascript">
        let project = '{{env('REFINER_SURVEY_KEY')}}';
        window._refinerQueue = window._refinerQueue || []; function _refiner(){_refinerQueue.push(arguments);} _refiner('setProject', project); (function(){var a=document.createElement("script");a.type="text/javascript";a.async=!0;a.src="https://js.refiner.io/v001/client.js";var b=document.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b)})();

        _refiner('identifyUser', {
            id: '{{$business->getKey()}}',
            email: '{{$business->email}}',
            name: '{{$business->name}}',
        });

    </script>
    <!-- End of Refiner client code snippet -->
@endpush
