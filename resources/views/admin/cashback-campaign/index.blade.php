@extends('layouts.admin', [
    'title' => 'Campaigns'
])
@section('admin-content')
    <div class="row">
        <div class="col-md-12 col-lg-12 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4 d-flex justify-content-between">
                    <h2 class="text-primary mb-0 title">Campaigns</h2>
                    <admin-business-refund-export :type="'campaigns'"></admin-business-refund-export>
                    <a href="{{route('admin.campaigns.create')}}" class="btn btn-primary">Create Campaign</a>
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
                    <p class="small text-muted mb-0">Showing result from {{  number_format($from) }}
                        to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Initial Fund</th>
                            <th scope="col">Balance</th>
                            <th scope="col">Status</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($paginator as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ getFormattedAmount($item->campaignBusiness->currency, $item->fund) }}</td>
                                <td>{{getFormattedAmount('sgd', $item->campaignBusiness->wallets()->whereIn('type', [
                                        App\Enumerations\Business\Wallet\Type::AVAILABLE,
                                        App\Enumerations\Business\Wallet\Type::RESERVE,
                                        ])->where('currency', 'sgd')->sum('balance'))}}
                                </td>
                                <td>
                                    @if ($item->status)
                                        Enabled
                                    @else
                                        Disabled
                                    @endif
                                </td>
                                <td><a href="{{route('admin.campaigns.edit', $item->id)}}">Edit</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-dollar-sign fa-4x"></i></p>
                            <p class="small mb-0">- No campaigns found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top py-2">
                    <p class="small text-muted mb-0">Total of {{ number_format($paginator->total()) }} records.</p>
                </div>
            </div>
            <ul class="pagination mb-0">
                @if ($paginator->currentPage() <= 1)
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.previous')</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->currentPage() - 1) }}"
                           rel="prev">@lang('pagination.previous')</a>
                    </li>
                @endif

                @if ($paginator->currentPage() < $paginator->lastPage())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->currentPage() + 1) }}"
                           rel="next">@lang('pagination.next')</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.next')</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        function check(trigger) {
            let current = new URL(window.location.href);
            let query = current.search;
            let params = new URLSearchParams(query);

            if ($(trigger).is(':checked')) {
                params.set('verification_status', 'myinfo_unverified');
            } else {
                params.set('verification_status', null);
            }

            current.search = params.toString();
            window.location = current.toString();
        }
        window.Business = null;

    </script>
@endpush
