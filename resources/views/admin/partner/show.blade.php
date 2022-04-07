@extends('layouts.admin', [
    'title' => $user->display_name,
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="col-12 col-md-9 col-lg-8 mb-4">
                <a href="{{ route('admin.partner.index') }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Partners</a>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">{{ $user->display_name }}</h2>
                    <div class="media">
                        <div class="media-body">
                            <p class="text-dark small mb-2">
                                <span class="text-muted"># {{ $user->businessPartner->business->getKey() }}</span></p>
                            <p class="text-dark small">Referral code:
                                <span class="text-muted">{{ $user->businessPartner->referral_code }}</span>
                            </p>
                            <p class="text-dark small">Referral url:
                                <span class="text-muted">{{ route('register', ['partner_referral' => $user->businessPartner->referral_code]) }}</span>
                            </p>
                            <p class="text-dark small">Email: <span class="text-muted">{{ $user->email }}</span></p>
                            <p class="text-dark small">Phone: <span class="text-muted">{{ $user->phone }}</span></p>
                            <p class="text-dark small">Website: <span class="text-muted">{{ $user->businessPartner->website }}</span></p>
                            <p class="text-dark small">Platforms: <span class="text-muted">{{ implode(', ', $user->businessPartner->platforms) }}</span></p>
                            <p class="text-dark small">Services: <span class="text-muted">{{ implode(', ', $user->businessPartner->services) }}</span></p>
                            <p class="text-dark small">Description: <span class="text-muted">{{ $user->businessPartner->short_description }}</span></p>
                            <p class="text-dark small">Special sign up offer to HitPay Merchants: <span class="text-muted">{{ $user->businessPartner->special_offer }}</span></p>
                            <img src="{{Storage::url($user->businessPartner->logo_path)}}" style="max-height: 100px; position: absolute; top: 5px; right: 15px;" class="mt-2 mb-2" alt="">

                            <form style="margin-left: -25px; margin-right: -25px" action="{{route('admin.partner.update', $user)}}" method="post" class="card-body bg-light pt-4 pb-4 border-top">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label for="">Commission(%)</label>
                                    <div class="input-group w-25">
                                        <input type="number" name="commission" step="0.01" value="{{$user->businessPartner->commission}}"
                                               required id="" class="form-control">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </form>

                            @include('admin.partner._pricing')

                            <div class="mt-5" style="margin-left: -25px; margin-right: -25px">
                                @if ($message = session('success_map'))
                                    <div class="alert alert-success alert-dismissible fade show mt-3 mb-0" role="alert">
                                        {{ $message }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                @endif
                                <form action="{{route('admin.partner.bulk-map-businesses', $user)}}" enctype="multipart/form-data"
                                      method="post" class="card-body bg-light pt-4 pb-4 border-top">
                                    @csrf
                                    <h4>Bulk mapping</h4>
                                    <div class="form-group">
                                        <label for="file">CSV file</label>
                                        <div class="input-group w-25">
                                            <input type="file" name="file" accept=".csv, text/csv"
                                                   required id="file" class="form-control">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Upload</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @if ($message = session('success'))
                        <div class="alert alert-success alert-dismissible fade show mt-3 mb-0" role="alert">
                            {{ $message }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                </div>
                @if ($user->businessPartner->businesses()->count())
                    <div class="card-body px-4 py-0">
                        <h4 class="mb-2 title text-primary">Mapped Merchants</h4>
                    </div>
                    @php($paginator = $user->businessPartner->businesses()->paginate(20))
                    @foreach($paginator as $record)
                        <div class="card-body bg-light border-top p-4" style="position: relative">
                            <p class="font-weight-bold mb-2">{{ $record->getName() }}</p>
                            <p class="text-dark small mb-2"><span class="text-muted"># {{ $record->getKey() }}</span></p>
                        </div>
                    @endforeach

                    <div class="card-body bg-light border-top p-4" style="position: relative">
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
                    </div>

                    <div class="card-body border-top pt-2">
                    </div>

                    <div class="mb-2 pl-3 pb-3">
                        <a href="{{route('admin.partner.show-custom-rates-form', $user)}}" class="btn btn-sm btn-primary">Set Custom Rates</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
