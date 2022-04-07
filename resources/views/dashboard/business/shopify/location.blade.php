@extends('layouts.business', [
    'title' => 'Locations - Shopify'
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.integration.shopify.home', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back
            </a>
        </div>
        <div class="col-12 col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Integrations - Shopify</label>
                    <h2 class="text-primary mb-0 title">Locations</h2>
                </div>
                <div class="alert alert-warning border-left-0 border-right-0 rounded-0 small mb-0">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Our platform is currently not allow a business to change it's location ID. To change it later, remove HitPay app from Shopify admin and install again.
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('dashboard.business.integration.shopify.setting.location.set', $business->getKey()) }}">
                        @csrf
                        <div class="form-group">
                            <label for="domain">Sync stock level from:</label>
                            <select class="custom-select bg-light" title="Stock Location" name="location_id">
                                @foreach ($locations['locations'] as $location)
                                    <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Continue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
