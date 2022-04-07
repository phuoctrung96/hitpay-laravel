@extends('layouts.app', [
    'title' => __('Choose business'),
])

@section('app-content')
    @include('components.breadcrumb', [
        'breadcrumb_items' => __('Choose business'),
    ])
    <div class="container pt-4 pb-5">
        <div class="row">
            <div class="col-md-9 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="text-primary mb-0 title">{{__('Choose business')}}</h2>
                    </div>
                    <div class="card-body p-4 border-top">
                        <ul>
                            @foreach($businesses as $business)
                                <li><a href="{{route('dashboard.business.home', $business)}}">{{$business->name}}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
