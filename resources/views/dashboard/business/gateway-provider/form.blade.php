@php($title = !empty($gatewayProvider->getKey()) ? 'Edit Integration' : 'Add Integration')

@extends('layouts.business', [
    'title' => $title,
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.gateway.index', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Integrations</a>
        </div>
        <div class="col-12 col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Integrations</label>
                    <h2 class="text-primary mb-0 title">{{ $title }}</h2>
                </div>
                @if(session('success_message'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('success_message') !!}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                <div class="card-body bg-light border-top p-4">
                    <form method="post" action="{{ !empty($gatewayProvider->getKey()) ? route('dashboard.business.gateway.update', [
                        $business->getKey(),
                        $gatewayProvider->getKey()
                    ]): route('dashboard.business.gateway.store', [
                        $business->getKey()
                    ]) }}">
                        @csrf
                        @if (!empty($gatewayProvider->getKey()))
                            @method('put')
                        @endif
                        <div class="form-group">
                            <label for="name" class="small text-secondary">Name</label>
                            <select id="name" class="custom-select bg-light @error('name') is-invalid @enderror" name="name" onchange="if($(this).val() == 'xero') $('#theme').show(); else $('#theme').hide()">
                                @foreach ($data['providers'] as $key => $val)
                                    <option value="{{ $key }}" {{ old('name', $gatewayProvider->name) === $key ? 'selected' : '' }}>
                                        {{ $val }}
                                    </option>
                                @endforeach
                            </select>
                            @error('name')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="name" class="small text-secondary">Methods</label>
                            @foreach ($data['methods'] as $key => $val)
                                @php($checked = in_array($key, $gatewayProvider->methods ?? []) ? 'CHECKED' : '')
                                <div class="custom-control custom-checkbox">
                                    <input {{ $checked }} type="checkbox" class="custom-control-input" id="completedCheck{{ $key }}" name="methods[]" value="{{ $key }}">
                                    <label class="custom-control-label" for="completedCheck{{ $key }}">{{ $val }}</label>
                                </div>
                            @endforeach
                        </div>
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-save mr-3"></i> Save
                        </button>
                </div>
                </form>
            </div>
        </div>
    </div>
@endsection
