@extends('layouts.business', [
    'title' => 'Discount'
])

@section('business-content')
    <div class="row justify-content-center">
        <business-discount-list></business-discount-list>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
    </script>
@endpush
