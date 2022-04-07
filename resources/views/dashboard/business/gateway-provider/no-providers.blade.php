@extends('layouts.business', [
    'title' => 'Integrations'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body bg-light border-top p-4">
                    <div class="text-center text-muted py-4">
                        <a href="{{ route('dashboard.business.payment-provider.home', [ $business->getKey() ]) }}">Your business hasn't setup payment provider. Please setup at least one Payment Provider first.</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
