@extends('layouts.card', [
    'title' => __('Login'),
])

@section('card-content')
    <main class="py-5 py-xs-6 py-sm-7">
        <div class="container">
            <div class="card width-sm border-0 shadow-sm mx-auto mb-5 mb-xs-6">
                <div class="card-body px-xs-4 py-5 py-md-6">
                    <h1 class="h5 text-center mb-5">@lang('Sign in to continue')</h1>
                    @if (isset($existing_accounts))
                        <div class="alert alert-danger">
                            @if (is_array($existing_accounts))
                                <p>This stripe account has already been connected to one or more HitPay user accounts (See belows). If you would like to create another user account and link it to same Stripe account click <a href="{{ route('register') }}">here</a>.</p>
                                <ul class="list-unstyled">
                                    @foreach ($existing_accounts as $account)
                                        <li>{{ $account }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p>This stripe account has already been connected to one or more HitPay user accounts. If you would like to create another user account and link it to same Stripe account click <a href="{{ route('register') }}">here</a>.</p>
                            @endif
                        </div>
                    @endif
                    <form role="form" method="post" action="{{ route('auth.process') }}">
                        @csrf
                        <div class="form-group">
                            <input id="firstInput" type="text" class="form-control text-center{{ $errors->has('email') ? ' is-invalid' : '' }} bg-light" placeholder="Enter Email Address" aria-label="Email address" name="email" autocomplete="email" autofocus value="{{ old('email') }}">
                            @error('email')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-block btn-success">Next</button>
                        </div>
                    </form>
                    <p class="text-center small mb-0 mt-3">
                        @lang('New to :app_name?<br><a class="font-weight-bold" href=":url">Create an account</a>', [
                            'app_name' => $app_name,
                            'url' => route('register', [
                                'src' => 'login',
                            ]),
                        ])
                    </p>
                </div>
            </div>
            <div class="{{ ($lighter_color_text ?? false) ? 'footer' : 'footer-dark' }}">
                <p class="small text-center">
                    <a href="{{ route('home') }}"><i class="fas fa-home"></i> @lang('Home')</a>
                </p>
                <ul class="list-inline small text-center mb-0">
                    <li class="list-inline-item">
                        <a href="https://www.hitpayapp.com/termsofservice">@lang('Terms of Service')</a>
                    </li>
                </ul>
            </div>
        </div>
    </main>
@endsection
