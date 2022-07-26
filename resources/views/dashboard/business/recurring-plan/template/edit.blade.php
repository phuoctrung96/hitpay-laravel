@php($title = isset($recurringPlan) ? 'Edit Recurring Plan Template' : 'Add Recurring Plan Template')

@extends('layouts.business', [
    'title' => $title,
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.recurring-plan.template.index', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Recurring Plan Templates</a>
        </div>
        <div class="col-12 col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Recurring Plan Templates</label>
                    <h2 class="text-primary mb-0 title">{{ $title }}</h2>
                    @if(isset($recurringPlan))
                        <span class="text-muted small">ID: {{$recurringPlan->id}}</span>
                    @endif
                </div>
                @if(session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
                        {{ session('success_message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                <business-recurring-plan-template></business-recurring-plan-template>
                <div class="card-body border-top pt-0 pb-4">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Data = @json($data);
        @isset($recurringPlan)
            window.RecurringPlan = @json($recurringPlan)
            @else
            window.RecurringPlan = null;
        @endisset
    </script>
@endpush
