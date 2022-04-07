@extends('layouts.business', [
    'title' => 'Recurring Plans'
])

@section('business-content')
    <div class="row">
        <div class="col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Create Recurring Plan</h2>
                    <p class="mb-0">Pick a template below to start a new plan.</p>
                    <a href="{{ route('dashboard.business.recurring-plan.template.index', [
                        'business_id' => $business->id,
                    ]) }}" class="small">Manage Template</a>
                </div>
                @if(session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
                        {{ session('success_message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                @if ($templates->count())
                    @foreach ($templates as $item)
                        <div class="card-body bg-light border-top p-4">
                            <div class="media">
                                <div class="media-body">
                                    <span class="float-right">{{ $item->getPrice() }} / {{ ucfirst($item->cycle) }}</span>
                                    <p class="font-weight-bold mb-2">{{ $item->name }}</p>
                                    <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                                    <a class="btn btn-primary btn-sm float-right" href="{{ route('dashboard.business.recurring-plan.create', [
                                        'business_id' => $business->id,
                                        'template_id' => $item->id,
                                    ]) }}">Select</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-file fa-4x"></i></p>
                            <p class="small mb-0">- No template found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top py-2">
                    <p class="small text-muted mb-0">Total of {{ number_format($templates->count()) }} records.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
