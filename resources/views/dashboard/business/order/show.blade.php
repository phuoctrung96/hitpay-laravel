@extends('layouts.business', [
'title' => 'Orders'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-12 mb-4">
            <a href="{{ route('dashboard.business.order.index', [
                $business->getKey(),
                'status' => $status,
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to order list</a>
        </div>
        <div class="col-md-10 col-lg-8 order-2 order-md-1">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <p class="mb-2">Order ID: <span class="text-muted">{{ $order->id }}</span>
                    @switch ($order->status)
                        @case ('completed')
                        @if ($order->customer_pickup)
                            <h4 class="text-success mb-3">Completed - Picked Up!</h4>
                        @else
                            <h4 class="text-success mb-3">Completed - Shipped!</h4>
                        @endif
                        @break
                        @case ('requires_business_action')
                        @if ($order->customer_pickup)
                            <h4 class="text-warning mb-3">Pending - Pickup</h4>
                        @else
                            <h4 class="text-warning mb-3">Pending - Shipping</h4>
                        @endif
                        @break
                        @case ('requires_payment_method')
                        <h4 class="text-info mb-3">Payment In Progress…</h4>
                        @break
                        @case ('requires_customer_action')
                        <h4 class="text-info mb-3">Waiting For Customer…</h4>
                        @break
                        @case ('canceled')
                        <h4 class="text-danger mb-3">Canceled!</h4>
                        @break
                        @case ('expired')
                        <h4 class="text-secondary mb-3">Expired!</h4>
                        @break
                        @default
                        <h4 class="text-secondary mb-0">{{ $order->status }}</h4>
                    @endswitch
                    <p class="mb-0">Date: <span class="text-muted">{{ $order->created_at->toDateTimeString() }}</span>
                    </p>
                    @if($order->slot_date)
                        <p class="mb-0">Shipping Slot Date and Time: <span
                                class="text-muted">{{ date('Y-m-d',strtotime($order->slot_date)).' '.json_decode($order->slot_time)->from.' - '. json_decode($order->slot_time)->to}}</span>
                        </p>
                    @endif
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
                @if ($order->status === 'requires_business_action' || $order->status === 'requires_customer_action')
                    <div class="card-body p-4 border-top bg-light">
                        @switch ($order->status)
                            @case ('requires_business_action')
                            @if ($order->customer_pickup)
                                <button class="btn btn-sm btn-success" data-toggle="modal"
                                        data-target="#updateStatusModal">Mark As Picked Up
                                </button>
                            @else
                                <button class="btn btn-sm btn-success" data-toggle="modal"
                                        data-target="#updateStatusModal">Mark As Shipped
                                </button>
                            @endif
                            @break
                            @case ('requires_customer_action')
                                <button class="btn btn-sm btn-warning" data-toggle="modal"
                                        data-target="#cancelOrderModalRequiresCustomer">Cancel
                                </button>
                            @break
                        @endswitch
                    </div>
                @endif
                <div class="card-body p-4 border-top">
                    <div class="d-flex justify-content-between align-text-bottom">
                        <span>Total Product Price</span>
                        <span class="align-self-end">{{ $order->display('line_item_price') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total Shipping Amount</span>
                        <span>{{ $order->display('shipping_amount') }}</span>
                    </div>
                    @if($order->automatic_discount_amount > 0)
                    <div class="d-flex justify-content-between">
                        <span>Discount - {{$order->automatic_discount_name}}</span>
                        <span>- {{ $order->display('automatic_discount_amount') }}</span>
                    </div>
                    @endif
                    @if($order->coupon_amount > 0)
                    <div class="d-flex justify-content-between">
                        <span>Coupon discount</span>
                        <span>- {{ $order->display('coupon_amount') }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between align-text-bottom">
                        <span>Total Payable Amount</span>
                        <span class="align-self-end">{{ $order->display('amount') }}</span>
                    </div>
                </div>
                @if ($order->messages && count($order->messages) > 0)
                    <div class="card-body p-4 border-top">
                        <p class="mb-2">Messages to Buyer:</p>
                        @foreach ($order->messages as $message)
                            @if (isset($message['shipping_details']))
                                <p class="mb-0"><span class="badge badge-success">Shipping Details</span> <small
                                        class="text-muted">{{ $message['shipping_details'] }}</small></p>
                            @elseif (isset($message['plain_message']))
                                <p class="mb-0"><span class="badge badge-info">Message</span> <small
                                        class="text-muted">{{ $message['plain_message'] }}</small></p>
                            @endif
                        @endforeach
                    </div>
                @endif
                @if ($order->remark)
                    <div class="card-body p-4 border-top">
                        <p class="mb-2">Remark:</p>
                        <small
                            class="text-muted">{{ $order->remark }}</small>
                    </div>
                @endif
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    Products
                </div>
                @foreach ($order->products as $item)
                    <div class="card-body bg-light p-4 border-top">
                        <div class="media">
                            {{-- TODO - Display first image from ordered product. --}}
                            <img src="{{ asset('hitpay/images/product.jpg') }}"
                                 class="d-none d-phone-block listing rounded border mr-3" alt="{{ $item->name }}">
                            <div class="media-body align-self-center">
                                <span
                                    class="font-weight-bold text-dark float-right">{{ $item->getAmountFor($order->currency, 'price') }}</span>
                                <p class="font-weight-bold mb-2">{{ $item->name.' - '.$item->description }}</p>
                                {{--                                <p class="mb-2">{{ $item->display('variation') }}</p>--}}
                                <p class="small mb-0">Quantity: {{ $item->quantity }}</p>
                                <p class="small mb-0">Unit
                                    Price: {{ getFormattedAmount($order->currency, $item->unit_price) }}</p>
                                <p class="small mb-0">Remark: {{ $item->remark ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="card-body border-top px-4 py-2">
                    <p class="small text-muted mb-0">Total of {{ $order->products->count() }} ordered product types,
                        with quantity of {{ $order->products->sum('quantity') }}.</p>
                </div>
            </div>
        </div>
        <div class="col-md-10 col-lg-4 order-1 order-md-2 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <label class="small text-muted text-uppercase">Customer</label>
                    <p class="mb-2">{{ $order->display('customer_name', 'No Name') }}</p>
                    <label class="small text-muted text-uppercase">Phone Number</label>
                    <p class="mb-2">{{ $order->customer_phone_number ?? '-' }}</p>
                    <label class="small text-muted text-uppercase">Email</label>
                    <p class="mb-2">{{ $order->customer_email ?? '-' }}</p>
                    @php($customer_address = $order->display('customer_address'))
                    <label class="small text-muted text-uppercase">Shipping Address</label>
                    <p class="{{ $order->customer_country ? 'mb-2' : 'mb-0' }}">{{ $customer_address ?? '-' }}</p>
                    @if ($order->customer_country)
                        <p class="{{ $order->slot_date ? 'mb-2' : 'mb-0' }}"><span
                                class="flag-icon flag-icon-{{ $order->customer_country }} shadow-sm"></span></p>
                    @endif
                </div>
                @if ($business->customer_id)
                    <div class="card-footer px-4 py-2 text-center border-top">
                        <a class="small" href="{{ route('dashboard.business.customer.show', [
                        $business->getKey(),
                        $business->customer_id,
                    ]) }}">View Customer</a>
                    </div>
                @endif
            </div>
            @if($order->status == 'completed' || $order->status == 'requires_business_action')
                <button class="btn btn-danger btn-block mt-4" data-toggle="modal"
                        data-target="#cancelOrderModal">Cancel
                </button>
            @endif
        </div>
    </div>
    <div id="updateStatusModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">
                        Update Order Status
                    </h5>
                </div>
                <div class="modal-body">
                    <form method="post" action="{{ route('dashboard.business.order.update', [
                            $business->getKey(),
                            $order->getKey(),
                        ]) }}" onsubmit="freeze(this)">
                        @csrf
                        @method('put')
                        <div class="form-group">
                            <label class="mb-0" for="messageInput">Message to Buyer</label>
                            <p class="small text-muted mb-2">Tip: Enter shipping method and tracking ID/URL when marking
                                order as shipped</p>
                            <textarea class="form-control" id="messageInput" name="message" rows="3" maxlength="200"></textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="completedCheck"
                                       name="mark_as_ship" value="1">
                                <label class="custom-control-label" for="completedCheck">Mark as
                                    shipped/delivered</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="cancelOrderModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">
                        Cancel Order
                    </h5>
                    <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-4" for="messageInput">Do you want to refund transaction?</p>
                    <a href="{{route('dashboard.business.order.cancel', ['business_id'=>$business->id, 'b_order'=>$order->id, 'refund' => '1'])}}" class="btn btn-primary">Yes</a>
                    <a href="{{route('dashboard.business.order.cancel', ['business_id'=>$business->id, 'b_order'=>$order->id, 'refund' => '0'])}}" class="btn btn-danger">No</a>
                </div>
            </div>
        </div>
    </div>
    <div id="cancelOrderModalRequiresCustomer" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">
                        Cancel Order
                    </h5>
                    <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-4" for="messageInput">Do you want to cancel transaction?</p>
                    <form method="POST" action="{{route('dashboard.business.order.cancel.requires_customer_action', ['business_id'=>$business->id, 'b_order'=>$order->id])}}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Yes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>

    </script>
@endpush
