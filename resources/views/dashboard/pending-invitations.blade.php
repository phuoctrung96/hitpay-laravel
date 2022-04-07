@extends('layouts.app', [
    'title' => __('Pending invitations'),
])

@section('app-content')
    @include('components.breadcrumb', [
        'breadcrumb_items' => __('Pending invitations'),
    ])
    <div class="container pt-4 pb-5">
        <div class="row">
            <div class="col-md-9 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="text-primary mb-0 title">{{__('Pending invitations')}}</h2>
                    </div>
                    <div class="card-body p-4 border-top">
                        <table class="table-bordered" style="width: 100%">
                            <thead>
                            <tr>
                                <th class="pl-2">{{__('Business')}}</th>
                                <th class="pl-2">{{__('Role')}}</th>
                                <th class="pl-2 text-center w-25">{{__('Actions')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($pendingInvitations as $businessUser)
                                <tr>
                                    <td class="p-2">{{$businessUser->business->name}}</td>
                                    <td class="p-2">{{$businessUser->role->title}}</td>
                                    <td class="p-2 text-center">
                                        <a class="btn btn-outline-success btn-sm" href="{{route('dashboard.pending-invitations.accept', $businessUser->id)}}">Accept</a>
                                        <a class="btn btn-outline-danger btn-sm" href="{{route('dashboard.pending-invitations.accept', $businessUser->id)}}">Decline</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
