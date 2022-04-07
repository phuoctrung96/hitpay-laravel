@extends('layouts.business', [
    'title' => 'Edit Tax'
])
@section('business-content')
    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.tax-setting.home', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Taxes</a>
        </div>
        <div class="col-12 col-lg-8 main-content">
            <div>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="text-primary mb-0 title">Edit Tax</h2>
                    </div>
                    <div class="card-body border-top">
                        <form method="post" action="{{route('dashboard.business.tax-setting.update', [
                                    $business->getKey(),
                                    $tax->id,
                                ])}}">
                            @csrf
                            <div class="form-group">
                                <label for="name">Name<span class="text-danger">*</span></label>
                                <input id="name" type="text" class="form-control bg-light" title="Name" name="name" value="{{$tax->name}}">
                            </div>
                            <div class="form-group">
                                <label for="rate">Rate<span class="text-danger">*</span></label>
                                <input id="rate" type="number" step="0.01" class="form-control bg-light" title="Rate" placeholder="%" name="rate" value="{{$tax->rate}}">
                            </div>
                            <div class="text-right">
                                <button id="addBtn" type="submit" class="btn btn-primary">
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
