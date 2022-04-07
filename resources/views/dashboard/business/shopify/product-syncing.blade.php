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
                    <h2 class="text-primary mb-3 title">Products Syncing</h2>
                    <p>We are syncing your products from Shopify…</p>
                    <div class="progress mb-3">
                        <div id="progress-bar" class="progress-bar progress-bar-striped" role="progressbar" style="width: 0" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p id="progress-message" class="small text-muted mb-0">Syncing <span id="current">0</span> out of
                        <span id="total">0</span> products.</p>
                    <p id="success" class="text-success font-weight-bold mt-3 mb-0 d-none"><i class="fas fa-check-circle mr-2"></i> Synced successfully.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script type="text/javascript" defer>
        window.addEventListener('DOMContentLoaded', () => {
            let progressBar = $('#progress-bar');
            let total = $('#total');
            let current = $('#current');
            let success = $('#success');
            let counter = setInterval(() => {
                axios.get(HitPay.scheme + '://' + HitPay.subdomains['dashboard'] + '/business/{{ $business->getKey() }}/integration/shopify/setting/product/sync').then(({data}) => {
                    progressBar.attr('style', 'width:' + data.percentage + '%');
                    progressBar.attr('aria-valuenow', data.percentage);
                    current.html(data.current);
                    total.html(data.total);

                    if (data.percentage === 100) {
                        clearInterval(counter);

                        $('#progress-message').addClass('d-none');
                        success.removeClass('d-none');
                    }
                });
            }, 1000);
        });
    </script>
@endpush
