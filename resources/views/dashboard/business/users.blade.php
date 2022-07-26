@extends('layouts.business', [
    'title' => 'User Management'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 main-content">
            <div class="card border-0 shadow-sm mx-auto mb-5 mb-xs-6">
                <div class="card-body p-4">
                    <business-users
                        :roles="{{json_encode($roles)}}"
                        :current_business_user_id="'{{ $currentBusinessUser->user_id }}'"
                    />
                </div>
            </div>
            <business-role-restrictions></business-role-restrictions>
            <business-settings></business-settings>
            <business-help-guide :page_type="'user_management'"></business-help-guide>
        </div>
    </div>

@endsection
@push('body-stack')
    <script>
        window.Business = @json($business->toBladeModel());
        window.Restrictions = @json($restrictions);
    </script>
@endpush
