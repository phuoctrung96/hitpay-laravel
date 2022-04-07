@php($name = $gatewayProvider->name)
@extends('layouts.business', [
    'title' => 'Integrations - '.$name,
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.gateway.index', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Integrations</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                     @if(session('success_message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success_message') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    <label class="small text-uppercase text-muted mb-3">Integration # {{ $gatewayProvider->getKey() }}</label>
                    <h2 class="text-primary text-uppercase mb-2 title">{{ $name }}</h2>
                    <h2 class="text-primary small mb-1 title">Payment Methods</h2>
                    @foreach ($gatewayProvider->array_methods as $method)
                        <p class="text-dark small mb-0"><span class="text-muted">{{ $method }}</span></p>
                    @endforeach
                    <p class="mb-3"></p>                    
                    <a href="{{ route('dashboard.business.gateway.edit', [
                        $business->getKey(),
                        $gatewayProvider->getKey(),
                    ]) }}">
                        <i class="fas fa-edit mr-2"></i> Edit
                    </a>
                    <a href="{{ route('dashboard.business.gateway.delete', [
                        $business->getKey(),
                        $gatewayProvider->getKey(),
                    ]) }}">
                        <i class="fas fa-trash mr-2"></i> Delete
                    </a>
                </div>
            </div>    
        </div>
    </div>
@endsection
