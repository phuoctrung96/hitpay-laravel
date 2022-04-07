@extends('layouts.business', [
    'title' => 'Partner'
])

@section('business-content')
    <div class="row">
        <div class="col-md-12 col-lg-12 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Mapped merchants</h2>
                </div>
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($merchants->currentPage() - 1) * $merchants->perPage())
                    @php($count = count($merchants->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from  {{  number_format($from) }} to {{ number_format($last + count($merchants->items())) }}</p>
                </div>
                @if ($merchants->count())
                    @foreach ($merchants as $item)
                        <div>
                            <div class="card-body bg-light border-top p-4">
                                <div class="media">
                                    <div class="media-body">
                                        <span>{{ $item->getName() }}</span>
                                        <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-dollar-sign fa-4x"></i></p>
                            <p class="small mb-0">- No merchants found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top py-2">
                    <p class="small text-muted mb-0">Total of {{ number_format($merchants->total()) }} records.</p>
                </div>
            </div>
            @include('pagination', ['paginator' => $merchants])
        </div>

        <div class="col-md-12 col-lg-12 main-content mt-3">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Commissions</h2>
                </div>
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($commissions->currentPage() - 1) * $commissions->perPage())
                    @php($count = count($commissions->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from  {{  number_format($from) }} to {{ number_format($last + count($commissions->items())) }}</p>
                </div>
                @if ($commissions->count())
                    @foreach ($commissions as $item)
                        <div>
                            <div class="card-body bg-light border-top p-4">
                                <div class="media">
                                    <div class="media-body">
                                        <span class="float-right">{{ getFormattedAmount("SGD", $item->amount) }}</span>
                                        <span>{{ $item->business->getName() }}</span>
                                        <p class="text-dark small mb-0"><span class="text-muted"># {{ $item->business->getKey() }}</span></p>
                                        <p class="text-dark small mb-0">Email: <span class="text-muted">{{ $item->business->email }}</span></p>
                                        <p class="text-dark small mb-0">Period: <span class="text-muted">from {{ $item->date_from }} to {{$item->date_to}}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-dollar-sign fa-4x"></i></p>
                            <p class="small mb-0">- No merchants found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top py-2">
                    <p class="small text-muted mb-0">Total of {{ number_format($commissions->total()) }} records.</p>
                </div>
            </div>
            @include('pagination', ['paginator' => $commissions])
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
    </script>
@endpush
