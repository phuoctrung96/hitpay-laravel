@extends('layouts.business', [
    'title' => 'Quickbooks Integration'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card-body p-4">
                @if(session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
                        {{ session('success_message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                @if(session('failed_message'))
                    <div class="alert alert-danger border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
                        {{ session('failed_message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <h2 class="text-primary mb-3 title">Quickbooks Integration</h2>
                <p>Automatically sync your HitPay sales, fees and refund data to Quickbooks</p>

                @if(!$business->quickbooksIntegration)
                    <a class="btn btn-primary" href="{{ route('dashboard.business.integration.quickbooks.login', $business->getKey()) }}">
                        Connect to Quickbooks
                    </a>
                @else
                    <a class="btn btn-danger" href="{{ route('dashboard.business.integration.quickbooks.disconnect', $business->getKey()) }}">
                        Disconnect
                    </a>
                @endif
            </div>
            @if($business->quickbooksIntegration)
                <div class="card-body border-top">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{route('dashboard.business.integration.quickbooks.save-settings', $business)}}" method="post">
                        {{csrf_field()}}
                        <div class="form-group">
                            <label for="company">Company</label>
                            <input type="text" readonly class="form-control" value="{{$business->quickbooksIntegration->organization}}">
                        </div>
                        <div class="form-group">
                            <label for="sales_account_id">Select Deposit Account For Sales Data</label>
                            <select name="sales_account_id" id="sales_account_id" class="form-control">
                                <option value=""></option>
                                @foreach($accounts as $account)
                                    <option value="{{$account->Id}}" {{$account->Id == $business->quickbooksIntegration->sales_account_id ? 'selected' : ''}}>{{$account->Name}}</option>
                                @endforeach
                            </select>
                        </div>
<!--                        <div class="form-group">
                            <label for="refund_account_id">Select Account Type For Refund Data Import</label>
                            <select name="refund_account_id" id="refund_account_id" class="form-control">
                                <option value=""></option>
                                @foreach($accounts as $account)
                                    <option value="{{$account->Id}}" {{$account->Id == $business->quickbooksIntegration->refund_account_id ? 'selected' : ''}}>{{$account->Name}}</option>
                                @endforeach
                            </select>
                        </div>-->
<!--                        <div class="form-group">
                            <label for="fee_account_id">Select Payment Account For Fee Sales Data</label>
                            <select name="fee_account_id" id="fee_account_id" class="form-control">
                                <option value=""></option>
                                @foreach($accounts as $account)
                                    <option value="{{$account->Id}}" {{$account->Id == $business->quickbooksIntegration->fee_account_id ? 'selected' : ''}}>{{$account->Name}}</option>
                                @endforeach
                            </select>
                        </div>-->
                        <div class="form-group">
                            <label for="sales_synchronization_enabled">Select Sync Start Date</label>
                            <input type="date" value="{{$business->quickbooksIntegration->initial_sync_date ? $business->quickbooksIntegration->initial_sync_date->toDateString() : ''}}" name="initial_sync_date" id="sync_date" class="datepicker form-control">
                        </div>
                        <button type="submit" class="btn btn-success btn-lg btn-block mb-3 shadow-sm">Update</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-sm-12 col-md-12 col-lg-8">
            <business-help-guide :page_type="'quickbooks'"></business-help-guide>
        </div>
    </div>
    @push('body-stack')
        <script>
        </script>
    @endpush
@endsection
