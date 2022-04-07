<div class="row">
    <div class="col-md-9 col-lg-8 main-content">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
                <h2 class="text-primary mb-0 title">{!! $title !!}</h2>
            </div>
            <div class="card-body border-top px-4 py-2">
                <p class="small text-muted mb-0">Showing the latest {{ $paginator->count() }} results</p>
            </div>
            @if ($paginator->count())
                @foreach ($paginator as $item)
                    <div class="card-body bg-light border-top p-4" style="position: relative">
                        <p class="font-weight-bold mb-2">{{ $item->display_name }}</p>
                        <p class="text-dark small mb-0"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                        <p class="text-dark small mb-0">Referral code: <span class="text-muted">{{ $item->businessPartner->referral_code }}</span></p>
                        <p class="text-dark small mb-0">Email: <span class="text-muted">{{ $item->email }}</span></p>
                        @if($item->businessPartner->status == \App\Enumerations\BusinessPartnerStatus::PENDING)
                            <p class="text-dark small mb-0">Email: <span class="text-muted">{{ $item->email }}</span></p>
                            <p class="text-dark small mb-0">Phone: <span class="text-muted">{{ $item->phone_number }}</span></p>
                        @endif
                        <br>
                        @if($item->businessPartner->status == \App\Enumerations\BusinessPartnerStatus::PENDING)
                            <a href="{{route('admin.partner.approve', $item)}}" class="btn btn-success btn-sm">Approve</a>
                            <a href="{{route('admin.partner.reject', $item)}}" class="btn btn-outline-danger btn-sm ml-3">Reject</a>
                        @elseif($item->businessPartner->status == \App\Enumerations\BusinessPartnerStatus::ACCEPTED)
                            <a href="{{route('admin.partner.show', $item)}}" class="btn btn-primary btn-sm">View Details</a>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="card-body bg-light border-top p-4">
                    <div class="text-center text-muted py-4">
                        <p><i class="fas fa-calculator fa-4x"></i></p>
                        <p class="small mb-0">- No partners found -</p>
                    </div>
                </div>
            @endif
            <div class="card-body border-top pt-0">
            </div>
        </div>
        @if($paginator->lastPage() > 1)
        <ul class="pagination mb-0">
            @if ($paginator->currentPage() <= 1)
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">@lang('pagination.previous')</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url($paginator->currentPage() - 1) }}" rel="prev">@lang('pagination.previous')</a>
                </li>
            @endif

            @if ($paginator->currentPage() < $paginator->lastPage())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url($paginator->currentPage() + 1) }}" rel="next">@lang('pagination.next')</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">@lang('pagination.next')</span>
                </li>
            @endif
        </ul>
        @endif
    </div>
</div>
