@extends('layouts.admin', [
    'title' => 'Wallet Transactions'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('admin.business.show', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to {{ $business->name }}</a>
        </div>
        <div class="col-12 col-xl-9 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <span class="float-right text-right mb-0">
                        <div class="small font-weight-light text-muted text-uppercase">Total Balance</div>
                        @if ($totalBalance < 0)
                            <span class="h4 font-weight-bold text-danger">- {{ strtoupper($currency) }} {{ getFormattedAmount($currency, abs($totalBalance), false) }}</span>
                        @elseif ($totalBalance > 0)
                            <span class="h4 font-weight-bold text-success">{{ strtoupper($currency) }} {{ getFormattedAmount($currency, $totalBalance, false) }}</span>
                        @else
                            <span class="h4 font-weight-bold">{{ strtoupper($currency) }} {{ getFormattedAmount($currency, $totalBalance, false) }}</span>
                        @endif
                    </span>
                    <p class="mb-1">{{ $business->name }}</p>
                    <h3 class="font-weight-bold mb-3">Balance - {{ strtoupper($currency) }}</h3>
                    <a class="btn btn-danger btn-sm" href="#" data-toggle="modal" data-target="#deductModal">
                        Deduct From Balance
                    </a>
                    <a class="btn btn-warning btn-sm" href="#" data-toggle="modal" data-target="#setDepositModal">
                        Set Deposit
                    </a>
                    <a class="btn btn-link btn-sm" href="#" data-toggle="modal" data-target="#addModal">
                        Add To Balance
                    </a>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body bg-light border-top p-4">
                            <div class="media">
                                <div class="media-body">
                                    <p class="small mb-1">{{ $item->created_at->format('Y-m-d, h:i:s A') }} - @ {{ ucfirst($item->wallet->type) }}</p>
                                    @if ($item->amount < 0)
                                        <span class="float-right font-weight-bold text-danger">- {{ getFormattedAmount($item->wallet->currency, abs($item->amount))  }}</span>
                                    @else
                                        <span class="float-right font-weight-bold text-success">{{ getFormattedAmount($item->wallet->currency, $item->amount)  }}</span>
                                    @endif
                                    @if ($item->description)
                                        <p class="font-weight-bold mb-0">{{ $item->description }}</p>
                                    @endif
                                </div>
                            </div>

                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-dollar-sign fa-4x"></i></p>
                            <p class="small mb-0">- No Transactions found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top pt-0 pb-4"></div>
            </div>
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
    </div>
@endsection

@push('body-stack')
    <div class="modal fade" id="deductModal" tabindex="-1" role="dialog" aria-labelledby="deductModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-danger" id="deductModalLabel">Warning!</h5>
                    <button type="button" class="close closeButton" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form method="post">
                    <div class="modal-body">
                        <p id="warning-text">Are you confirm to deduct balance from
                            <span class="font-weight-bold">{{ $business->getName() }}</span>?</p>
                        @csrf @method('put')
                        <div class="form-group">
                            <label for="amountInput" class="small text-secondary">Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ strtoupper($currency) }}</span>
                                </div>
                                <input id="amountInput" type="number" class="form-control" step="0.01" autocomplete="off" autofocus>
                            </div>
                            <span id="amountErrorMessage" class="small text-danger mt-1" role="alert"></span>
                        </div>
                        <div class="form-group mb-0">
                            <label for="descriptionInput" class="small text-secondary">Description</label>
                            <textarea id="descriptionInput" class="form-control"></textarea>
                            <span id="descriptionErrorMessage" class="small text-danger mt-1" role="alert"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="deductButton" type="submit" class="btn btn-danger">Deduct</button>
                        <button type="button" class="btn btn-secondary closeButton" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="setDepositModal" tabindex="-1" role="dialog" aria-labelledby="setDepositModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-danger" id="setDepositModalLabel">Warning!</h5>
                    <button type="button" class="close setDepositCloseButton" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form method="post">
                    <div class="modal-body">
                        <p>Are you confirm to set deposit for <span class="font-weight-bold">{{ $business->getName() }}</span>?</p>
                        <p>Current Available Balance: <span class="font-weight-bold{{ $availableWalletBalance > 0 ? ' text-success' : ($availableWalletBalance < 0 ? ' text-danger' : '') }}">{{ getFormattedAmount($currency, $availableWalletBalance) }}</span></p>
                        <p>Current Deposit Balance: <span class="font-weight-bold{{ $depositWalletBalance > 0 ? ' text-success' : ($depositWalletBalance < 0 ? ' text-danger' : '') }}">{{ getFormattedAmount($currency, $depositWalletBalance) }}</span></p>
                        @csrf @method('put')
                        <div class="form-group mb-0">
                            <label for="setDepositAmountInput" class="small text-secondary">Deposit Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ strtoupper($currency) }}</span>
                                </div>
                                <input id="setDepositAmountInput" type="number" class="form-control" step="0.01" value="{{ getReadableAmountByCurrency($currency, $depositWalletReserveBalance, false) }}" autocomplete="off">
                            </div>
                            <span id="setDepositAmountErrorMessage" class="small text-danger mt-1" role="alert"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="setDepositButton" type="submit" class="btn btn-danger">Set</button>
                        <button type="button" class="btn btn-secondary setDepositCloseButton" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-danger" id="addModalLabel">Warning!</h5>
                    <button type="button" class="close closeButton" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form method="post">
                    <div class="modal-body">
                        <p id="add-warning-text">Are you want to add balance to
                            <span class="font-weight-bold">{{ $business->getName() }}</span>?</p>
                        @csrf @method('put')
                        <div class="form-group">
                            <label for="amountInput" class="small text-secondary">Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ strtoupper($currency) }}</span>
                                </div>
                                <input id="addAmountInput" type="number" class="form-control" step="0.01" autocomplete="off" autofocus>
                            </div>
                            <span id="addAmountErrorMessage" class="small text-danger mt-1" role="alert"></span>
                        </div>
                        <div class="form-group mb-0">
                            <label for="descriptionInput" class="small text-secondary">Description</label>
                            <textarea id="addDescriptionInput" class="form-control"></textarea>
                            <span id="addDescriptionErrorMessage" class="small text-danger mt-1" role="alert"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="addButton" type="submit" class="btn btn-danger">Add</button>
                        <button type="button" class="btn btn-secondary closeButton" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let amountInput = $("#amountInput");
            let amountErrorMessage = $("#amountErrorMessage");

            let descriptionInput = $("#descriptionInput");
            let descriptionErrorMessage = $("#descriptionErrorMessage");

            let closeButtons = $(".closeButton");
            let deductButton = $("#deductButton");

            $("#deductModal").on("show.bs.modal", function () {
                descriptionInput.val("Administrative Deduction");
            }).on("hidden.bs.modal", function () {
                amountInput.removeClass("is-invalid");
                amountErrorMessage.css("display", "none");

                descriptionInput.removeClass("is-invalid");
                descriptionErrorMessage.css("display", "none");
            });

            amountInput.keyup(function () {
                let inputValue = amountInput.val();
                let indexOfDot = inputValue.indexOf(".");

                if (indexOfDot >= 0) {
                    amountInput.val(inputValue.substr(0, indexOfDot) + inputValue.substr(indexOfDot, 3));
                } else {
                    amountInput.val(inputValue);
                }

                if (amountInput.val().length <= 0) {
                    amountInput.addClass("is-invalid");
                    amountErrorMessage.css("display", "block").text("The amount is required.");
                } else {
                    amountInput.removeClass("is-invalid");
                    amountErrorMessage.css("display", "none");
                }
            });

            descriptionInput.keyup(function () {
                if (descriptionInput.val().length <= 0) {
                    descriptionInput.addClass("is-invalid");
                    descriptionErrorMessage.css("display", "block").text("The description is required.");
                } else {
                    descriptionInput.removeClass("is-invalid");
                    descriptionErrorMessage.css("display", "none");
                }
            });

            deductButton.click(function (event) {
                event.preventDefault();

                deductButton.prop("disabled", true);
                closeButtons.prop("disabled", true);

                axios.post("{{ route('admin.business.wallet.deduct', [ $business->getKey(), $currency ]) }}", {
                    amount : amountInput.val(),
                    description : descriptionInput.val(),
                }).then(() => {
                    location.reload();
                }).catch(({ response }) => {
                    _.each(response.data.errors, (value, key) => {
                        $("#" + key + "Input").addClass("is-invalid");
                        $("#" + key + "ErrorMessage").css("display", "block").text(_.first(value));
                    });

                    deductButton.prop("disabled", false);
                    closeButtons.prop("disabled", false);
                });
            });

            let setDepositAmountInput = $("#setDepositAmountInput");
            let setDepositAmountErrorMessage = $("#setDepositAmountErrorMessage");

            let setDepositCloseButtons = $(".setDepositCloseButton");
            let setDepositButton = $("#setDepositButton");

            $("#setDepositModal").on("hidden.bs.modal", function () {
                setDepositAmountInput.removeClass("is-invalid");
                setDepositAmountErrorMessage.css("display", "none");
            });

            setDepositAmountInput.keyup(function () {
                let inputValue = setDepositAmountInput.val();
                let indexOfDot = inputValue.indexOf(".");

                if (indexOfDot >= 0) {
                    setDepositAmountInput.val(inputValue.substr(0, indexOfDot) + inputValue.substr(indexOfDot, 3));
                } else {
                    setDepositAmountInput.val(inputValue);
                }

                if (setDepositAmountInput.val().length <= 0) {
                    setDepositAmountInput.addClass("is-invalid");
                    setDepositAmountErrorMessage.css("display", "block").text("The amount is required.");
                } else {
                    setDepositAmountInput.removeClass("is-invalid");
                    setDepositAmountErrorMessage.css("display", "none");
                }
            });

            setDepositButton.click(function (event) {
                event.preventDefault();

                setDepositButton.prop("disabled", true);
                setDepositCloseButtons.prop("disabled", true);

                axios.post("{{ route('admin.business.wallet.set-deposit', [ $business->getKey(), $currency ]) }}", {
                    amount : setDepositAmountInput.val(),
                }).then(({data}) => {
                    location.reload();
                }).catch(({ response }) => {
                    _.each(response.data.errors, (value, key) => {
                        $("#" + key + "Input").addClass("is-invalid");
                        $("#" + key + "ErrorMessage").css("display", "block").text(_.first(value));
                    });

                    setDepositCloseButtons.prop("disabled", false);
                });
            });
        ////////////////////
            let addAmountInput = $("#addAmountInput");
            let addAmountErrorMessage = $("#addAmountErrorMessage");

            let addDescriptionInput = $("#addDescriptionInput");
            let addDescriptionErrorMessage = $("#addDescriptionErrorMessage");

            let addButton = $("#addButton");

            $("#addModal").on("show.bs.modal", function () {
                addDescriptionInput.val("Administrative Top Up");
            }).on("hidden.bs.modal", function () {
                addAmountInput.removeClass("is-invalid");
                addAmountErrorMessage.css("display", "none");

                addDescriptionInput.removeClass("is-invalid");
                addDescriptionErrorMessage.css("display", "none");
            });

            addAmountInput.keyup(function () {
                let inputValue = addAmountInput.val();
                let indexOfDot = inputValue.indexOf(".");

                if (indexOfDot >= 0) {
                    addAmountInput.val(inputValue.substr(0, indexOfDot) + inputValue.substr(indexOfDot, 3));
                } else {
                    addAmountInput.val(inputValue);
                }

                if (addAmountInput.val().length <= 0) {
                    addAmountInput.addClass("is-invalid");
                    addAmountErrorMessage.css("display", "block").text("The amount is required.");
                } else {
                    addAmountInput.removeClass("is-invalid");
                    addAmountErrorMessage.css("display", "none");
                }
            });

            addDescriptionInput.keyup(function () {
                if (addDescriptionInput.val().length <= 0) {
                    addDescriptionInput.addClass("is-invalid");
                    addDescriptionErrorMessage.css("display", "block").text("The description is required.");
                } else {
                    addDescriptionInput.removeClass("is-invalid");
                    addDescriptionErrorMessage.css("display", "none");
                }
            });

            addButton.click(function (event) {
                event.preventDefault();

                addButton.prop("disabled", true);
                closeButtons.prop("disabled", true);

                axios.post("{{ route('admin.business.wallet.add', [ $business->getKey(), $currency ]) }}", {
                    amount : addAmountInput.val(),
                    description : addDescriptionInput.val(),
                }).then(() => {
                    location.reload();
                }).catch(({ response }) => {
                    _.each(response.data.errors, (value, key) => {
                        key = key.charAt(0).toUpperCase() + key.slice(1);

                        $("#add" + key + "Input").addClass("is-invalid");
                        $("#add" + key + "ErrorMessage").css("display", "block").text(_.first(value));
                    });

                    addButton.prop("disabled", false);
                    closeButtons.prop("disabled", false);
                });
            });

        });
    </script>
@endpush
