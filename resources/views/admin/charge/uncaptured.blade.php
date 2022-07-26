@extends('layouts.admin', [
    'title' => 'Uncaptured Charge'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('admin.charge.index') }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Charges</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="form-group">
                <form class="input-group input-group-lg" action="{{ route('admin.charge.uncaptured') }}">
                    <input class="form-control border-0 shadow-sm" placeholder="Enter Uncaptured Charge ID" title="Search Charge" name="charge_id" value="{{ request('charge_id') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            @if(is_string($errorMessage) || is_string($errorMessage = session('errorMessage')))
                <div class="alert alert-danger">{{ $errorMessage }}</div>
            @elseif(is_string($successMessage = session('successMessage')))
                <div class="alert alert-success">{{ $successMessage }}</div>
            @endif
            @if (!is_null($charge) &&  $charge !== false)
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-4">
                        <div>
                            <label class="small text-uppercase text-muted mb-3">Charge # {{ $charge->getKey() }}</label>
                        </div>
                        <span class="float-right">
                            {{ $charge->display('amount') }}
                                @if ($charge->amount - ($charge->balance ?? 0) !== $charge->amount)
                                    <br><span class="small">Balance: {{ $charge->display('balance') }}</span>
                                @endif
                        </span>
                        <p class="small mb-0">Channel:
                            <span class="text-muted">{{ ucwords(str_replace('_', ' ', $charge->channel)) }}</span></p>
                        <p class="small mb-0">Business: <span class="text-muted">{{ $charge->business->name }}</span></p>
                        <p class="text-dark small mb-0">Payment Method: <span class="text-muted">{!! \App\Enumerations\Business\PaymentMethodType::displayName($charge->payment_provider_charge_method) !!}</span></p>
                        @php($card = $charge->card())
                        @if($card instanceof \HitPay\Data\Objects\PaymentMethods\Card)
                        <p class="text-dark small mb-0">Card Brand: <span class="text-muted">{{ $card->brand_name }}</span></p>
                        <p class="text-dark small mb-0">Card Last 4: <span class="text-muted">{{ $card->last_4 }}</span></p>
                        <p class="text-dark small mb-0">Card Country: <span class="text-muted">{{ $card->country_name }}</span></p>
                        <p class="text-dark small mb-0">Card Funding: <span class="text-muted">{{ $card->funding }}</span></p>
                        <p class="text-dark small mb-0">Card Network: <span class="text-muted">{{ $card->network }}</span></p>
                        <p class="text-dark small mb-0">Card Expiry Year: <span class="text-muted">{{ $card->exp_year }}</span></p>
                        <p class="text-dark small mb-0">Card Expiry Month: <span class="text-muted">{{ $card->exp_month }}</span></p>
                        @endif
                        @if ($charge->status === 'requires_payment_method')
                            <span class="badge badge-info">Payment In Progress</span>
                            <form class="mt-3" method="post" action="{{ route('admin.charge.capture', [
                                $charge->getKey(),
                            ]) }}">
                                @csrf
                                <button class="btn btn-success">Capture Now</button>
                            </form>
                        @elseif ($charge->status === 'succeeded' || $charge->status === 'refunded')
                            <span class="badge badge-seconday">{{ ucwords($charge->status) }}</span>
                        @else
                            <div class="text-danger mt-3">Something doesn't look correct, please contact developers.</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
