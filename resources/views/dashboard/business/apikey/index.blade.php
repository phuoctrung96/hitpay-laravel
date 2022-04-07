@extends('layouts.business', [
    'title' => 'Api Keys'
])
@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                @if ($paginator->count() == 0)
                    <div class="card-body p-4">
                        <h2 class="text-primary mb-0 title float-right"><a href="{{ route('dashboard.business.apikey.create', [
                                $business->getKey()
                            ]) }}" class="btn btn-primary"><i class="fa fa-plus"></i> Generate Api Key</a></h2>
                        <h2 class="text-primary mb-0 title">Api Keys</h2>
                    </div>
                @endif
                @if ($paginator->count())
                    @foreach ($paginator as $key)
                        <div class="card-body bg-light border-top text-dark p-4">
                            <div class="media">
                                <div class="media-body align-self-center">
                                    <span class="font-weight-bold text-dark float-right mt-1">
                                        <a href="javascript:void(0)" data-show="show" data-api-key="{{ $key->api_key }}" data-masked-api-key="{{ $key->masked_api_key }}" class="btn-show mr-2"><i class="fa fas fa-eye"></i></a>
                                        <a href="{{ route('dashboard.business.apikey.delete', [
                                            $business->getKey(),
                                            $key->id
                                        ]) }}" class="btn-delete text-danger"><i class="fa fas fa-trash"></i></a>
                                    </span>
                                    <p class="font-weight-bold mb-1">Api Key</p>
                                    <div class="input-group" style="width: 100%;">
                                        <input class="form-control api-key" readonly="true" value="{{ $key->masked_api_key }}" />
                                        <div class="input-group-append">
                                            <span class="input-group-text"><a href="javascript:void(0)" class="btn-copy"><i class="fas fa-copy"></i></a></span>
                                        </div>
                                    </div>
                                    <p class="font-weight-bold mb-1 mt-3">Salt</p>
                                    <div class="input-group" style="width: 100%;">
                                        <input class="form-control api-key-salt" readonly="true" value="{{ $key->salt }}" />
                                        <div class="input-group-append">
                                            <span class="input-group-text"><a href="javascript:void(0)" class="btn-copy-salt"><i class="fas fa-copy"></i></a></span>
                                        </div>
                                    </div>
                                    <p class="font-weight-light mb-1"><br> We strongly recommend using a secrets manager. Plain text files like dotenv lead to accidental costly leaks. Use <a href="https://www.doppler.com/l/partner-program?" target="_blank">Doppler</a> for a developer friendly experience. AWS and Google Cloud have native solutions as well.</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa far fa-list-alt fa-4x"></i></p>
                            <p class="small mb-0">- No api key found -</p>
                        </div>
                    </div>
                @endif
            </div>
            <business-help-guide :page_type="'api_keys'"></business-help-guide>
        </div>
    </div>
@endsection

@push('body-stack')
    <script type="text/javascript" defer>
        window.addEventListener('DOMContentLoaded', () => {
            $('.btn-show').on('click', function(e) {
                e.preventDefault();
                if ($(this).data('show') == 'show') {
                    $(this).closest('.media').find('.api-key').val($(this).data('api-key'));
                    $(this).data('show', 'hide');
                } else {
                    $(this).closest('.media').find('.api-key').val($(this).data('masked-api-key'));
                    $(this).data('show', 'show');
                }
            });

            $('.btn-copy').on('click', function(e) {
                let showed = false;

                if ($('.btn-show').data('show') == 'show') {
                    $('.btn-show').trigger('click');

                    showed = true;
                }

                let txt = $(this).closest('.media').find('.api-key');
                txt.focus();
                txt.select();

                try {
                    document.execCommand('copy');
                } catch (err) {
                    console.log('Oops, unable to copy');
                }

                if (showed) {
                    $('.btn-show').trigger('click');
                }
            });

            $('.btn-copy-salt').on('click', function(e) {
                let txt = $(this).closest('.media').find('.api-key-salt');
                txt.focus();
                txt.select();

                try {
                    document.execCommand('copy');
                } catch (err) {
                    console.log('Oops, unable to copy');
                }
            });
        });
    </script>
@endpush
