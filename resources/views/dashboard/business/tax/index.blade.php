@extends('layouts.business', [
    'title' => 'Taxes'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Taxes</h2>
                    <br/>
                    @if(count($paginator->items()) < 10)<button class="btn btn-primary" data-toggle="modal" data-target="#addTaxModal">Add Tax</button>@endif
                    <div class="modal fade" id="addTaxModal" tabindex="-1" role="dialog"
                         aria-labelledby="addTaxModalLabel"
                         aria-hidden="true" data-backdrop="static" data-keyboard="false">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exportModalLabel">Add Tax</h5>
                                    <button id="closeBtn" type="button" class="close" data-dismiss="modal"
                                            aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form method="post" action="{{route('dashboard.business.tax-setting.store', [
                                    $business->getKey(),
                                ])}}">
                                        @csrf
                                        <div class="form-group">
                                            <label for="name">Name<span class="text-danger">*</span></label>
                                            <input id="name" type="text" class="form-control bg-light" title="Name" name="name">
                                        </div>
                                        <div class="form-group">
                                            <label for="rate">Rate<span class="text-danger">*</span></label>
                                            <input id="rate" type="number" step="0.01" class="form-control bg-light" title="Rate" placeholder="%" name="rate">
                                        </div>
                                        <div class="text-right">
                                            <button id="addBtn" type="submit" class="btn btn-primary">
                                                Add
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#enterDetailsModal">Enter Tax Details</button>
                    <div class="modal fade" id="enterDetailsModal" tabindex="-1" role="dialog"
                         aria-labelledby="addTaxModalLabel"
                         aria-hidden="true" data-backdrop="static" data-keyboard="false">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exportModalLabel">Tax Details</h5>
                                    <button id="closeBtn" type="button" class="close" data-dismiss="modal"
                                            aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form method="post" action="{{route('dashboard.business.basic-details.update-tax-details', [
                                    $business->getKey(),
                                ])}}">
                                        @csrf
                                        <div class="form-group">
                                            <label for="individual_name">Registered Company name / Individual Name</label>
                                            <input id="individual_name" type="text" class="form-control bg-light" title="Name" name="individual_name" value="{{$business->individual_name}}">
                                        </div>
                                        <div class="form-group">
                                            <label for="tax_registration_number">TAX registration Number</label>
                                            <input id="tax_registration_number" type="text" class="form-control bg-light" name="tax_registration_number" value="{{$business->tax_registration_number}}">
                                        </div>
                                        <div class="text-right">
                                            <button id="addBtn" type="submit" class="btn btn-primary">
                                                Add
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                @if(session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show"
                         role="alert">
                        {{ session('success_message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($paginator->currentPage() - 1) * $paginator->perPage())
                    @php($count = count($paginator->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from {{ number_format($from) }}
                        to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body bg-light border-top p-4">
                            <div class="media">
                                <div class="media-body">
                                    <p class="font-weight-bold mb-2">{{ $item->name }}</p>
                                    <p class="text-dark small mb-2">Rate: {{$item->rate}}%
                                </div>
                            </div>
                            <div class="media-bottom">
                                <div class="mt-2">
                                    <a href="{{route('dashboard.business.tax-setting.edit', [
                                    $business->getKey(),
                                    $item->getKey(),
                                ])}}">
                                        <i class="fa fa-edit"></i> <span>Edit</span>
                                    </a>
                                    <a href="{{route('dashboard.business.tax-setting.delete', [
                                    $business->getKey(),
                                    $item->getKey(),
                                ])}}" class="float-right">
                                        <i class="fa fa-trash"></i> <span>Delete</span>
                                    </a>
                                </div>

                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-percent fa-4x"></i></p>
                            <p class="small mb-0">- No taxes -</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
@endpush
