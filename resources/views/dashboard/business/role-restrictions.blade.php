@extends('layouts.business', [
    'title' => 'Role Restrictions',
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-9 col-xl-8 main-content">
            <business-role-restrictions></business-role-restrictions>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business->toArray());
        window.Restrictions = @json($restrictions);
    </script>
@endpush
