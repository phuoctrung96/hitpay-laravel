@extends('layouts.admin', [
    'title' => 'Terminals'
])

@section('admin-content')
    <div class="row mb-3">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="form-group">
                <form action="{{route('admin.partner.index')}}" class="input-group input-group-lg">
                    <input type="text"
                           placeholder="Search Partner"
                           title="Search Partner"
                           name="query"
                           value="{{request('query')}}"
                           class="form-control border-0 shadow-sm">
                    <div class="input-group-append">
                        <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-3 col-lg-4">
            <admin-partners-export></admin-partners-export>
        </div>
    </div>
    @include('admin.partner._list', ['paginator' => $pendingPartners, 'title' => 'Pending Partners Approvals'])
    <div class="p-3">&nbsp;</div>
    @include('admin.partner._list', ['paginator' => $approvedPartners, 'title' => 'Approved Partners'])
    <div class="p-3">&nbsp;</div>
    @include('admin.partner._list', ['paginator' => $rejectedPartners, 'title' => 'Rejected Partners'])
@endsection
