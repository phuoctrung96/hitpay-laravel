@extends('layouts.business', [
    'title' => 'HitPay Balance'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.balance.homepage', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to All Balance</a>
        </div>
        <div class="col-12 col-xl-9 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <span class="float-right text-right mb-0">
                        <div class="small font-weight-light text-muted text-uppercase">Balance</div>
                        @if ($wallet->balance < 0)
                            <span class="h4 font-weight-bold text-danger">- {{ strtoupper($wallet->currency) }} {{ getFormattedAmount($wallet->currency, abs($wallet->balance), false) }}</span>
                        @elseif ($wallet->balance > 0)
                            <span class="h4 font-weight-bold text-success">{{ strtoupper($wallet->currency) }} {{ getFormattedAmount($wallet->currency, $wallet->balance, false) }}</span>
                        @else
                            <span class="h4 font-weight-bold">{{ strtoupper($wallet->currency) }} {{ getFormattedAmount($wallet->currency, $wallet->balance, false) }}</span>
                        @endif
                    </span>
                    <h3 class="font-weight-bold mb-0">{{ ucfirst($wallet->type) }} Balance - {{ strtoupper($wallet->currency) }}</h3>
                    @if($wallet->type === \App\Enumerations\Business\Wallet\Type::RESERVE)
                        <div class="mt-3">
                            <p class="mb-0">Click <a class="font-weight-bold" data-toggle="modal" data-target="#transferModal" href="#">here</a> top up reserve balance from available balance or move reserve balance to available balance</p>
                        </div>
                    @elseif($wallet->type === \App\Enumerations\Business\Wallet\Type::AVAILABLE)
                        <div class="mt-3">
                            <p class="mb-0">Click <a class="font-weight-bold" data-toggle="modal" data-target="#changeModal" href="#">here</a> to choose to receive automatic payouts or manual payouts</p>
                        </div>
                        @if (!$business->auto_pay_to_bank)
                            <balance-withdrawal url="{{ route('dashboard.business.balance.wallet.available.payout', [
                                $business->id,
                                $wallet->currency,
                            ]) }}" :can-send-balance-to-bank="{{ $canSendBalanceToBank ? 'true' : 'false' }}"></balance-withdrawal>
                        @endif
                        <business-payout-breakdown-export class="mt-3" :with-time-options="true"></business-payout-breakdown-export>
                    @endif
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body bg-light border-top p-4">
                            @include('dashboard.business.balance.components.transaction', compact('item'))
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
            @include('custom-pagination')
        </div>
    </div>
@endsection

@push('body-stack')
    @if($wallet->type === \App\Enumerations\Business\Wallet\Type::AVAILABLE)
        <div class="modal fade" id="changeModal" tabindex="-1" role="dialog" aria-labelledby="changeModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form class="modal-content" id="autoPayToBankForm" method="post" action="{{ route('dashboard.business.balance.wallet.available.update', [
                    $business->id,
                    $wallet->currency,
                ]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold" id="changeModalLabel">Payout To Bank</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Set to receive payouts automatically as per <a href="https://www.hitpayapp.com/pricing">standard payout schedule</a>, or manually payout funds to your bank account.</p>
                        @csrf
                        @method('put')
                        <div class="mb-3">
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="auto_pay_to_bank" id="auto_pay_to_bank_true" value="on" {{ $business->auto_pay_to_bank ? ' checked' : '' }}>
                                    <label class="form-check-label" for="auto_pay_to_bank_true">Automatic</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <select id="auto_pay_to_bank_day" class="custom-select" name="auto_pay_to_bank_day"{{ $business->auto_pay_to_bank ? '' : ' style=display:none' }}>
                                    <option value="daily"{{ $business->auto_pay_to_bank_day === 'daily' ? ' selected' : '' }}>Daily</option>
                                    <option value="weekly"{{ \Illuminate\Support\Str::startsWith($business->auto_pay_to_bank_day, 'weekly_') ? ' selected' : '' }}>Weekly</option>
                                    <option value="monthly"{{ \Illuminate\Support\Str::startsWith($business->auto_pay_to_bank_day, 'monthly_') ? ' selected' : '' }}>Monthly</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select id="auto_pay_to_bank_weekly_day" class="custom-select" name="auto_pay_to_bank_weekly_day"{{ $business->auto_pay_to_bank && \Illuminate\Support\Str::startsWith($business->auto_pay_to_bank_day, 'weekly_') ? '' : ' style=display:none' }}>
                                    @php($day = explode('_', $business->auto_pay_to_bank_day)[1] ?? 'mon')
                                    <option value="mon"{{ $day === 'mon' ? ' selected' : '' }}>Monday</option>
                                    <option value="tue"{{ $day === 'tue' ? ' selected' : '' }}>Tuesday</option>
                                    <option value="wed"{{ $day === 'wed' ? ' selected' : '' }}>Wednesday</option>
                                    <option value="thu"{{ $day === 'thu' ? ' selected' : '' }}>Thursday</option>
                                    <option value="fri"{{ $day === 'fri' ? ' selected' : '' }}>Friday</option>
                                    <option value="sat"{{ $day === 'sat' ? ' selected' : '' }}>Saturday</option>
                                    <option value="sun"{{ $day === 'sun' ? ' selected' : '' }}>Sunday</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select id="auto_pay_to_bank_time" class="custom-select" name="auto_pay_to_bank_time"{{ $business->auto_pay_to_bank && $business->auto_pay_to_bank_day === 'daily' ? '' : ' style=display:none' }}>
                                    <option value="00:00:00"{{ $business->auto_pay_to_bank_time === '00:00:00' ? ' selected' : '' }}>12:00 a.m.</option>
                                    <option value="09:30:00"{{ $business->auto_pay_to_bank_time === '09:30:00' ? ' selected' : '' }}>09:30 a.m.</option>
                                </select>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="auto_pay_to_bank" id="auto_pay_to_bank_false" value="off" {{ !$business->auto_pay_to_bank ? ' checked' : '' }}>
                                <label class="form-check-label" for="auto_pay_to_bankwe_false">Manual<br><small class="text-secondary">You'll no longer be able to see which transactions are included in a payout.</small></label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    @elseif($wallet->type === \App\Enumerations\Business\Wallet\Type::RESERVE)
        <div class="modal fade" id="transferModal" tabindex="-1" role="dialog" aria-labelledby="transferModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content" id="transferForm">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold" id="transferModalLabel">Manage Reserve Balance</h5>
                        <button type="button" class="close closeButton" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body bg-light border-bottom">
                        <pre class="text-monospace small mb-0">Current Available Balance : <span class="font-weight-bold{{ $availableWalletBalance > 0 ? ' text-success' : ($availableWalletBalance < 0 ? ' text-danger' : '') }}">{{ getFormattedAmount($currency, $availableWalletBalance) }}</span>
Current Reserve Balance   : <span class="font-weight-bold{{ $reserveWalletBalance > 0 ? ' text-success' : ($reserveWalletBalance < 0 ? ' text-danger' : '') }}">{{ getFormattedAmount($currency, $reserveWalletBalance) }}</span></pre>
                    </div>
                    <div class="modal-body">
                        @csrf
                        @method('put')
                        <div class="form-group">
                            <label class="small text-secondary">Choose your transfer request：</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="transfer_type" id="transfer_type_to_available" value="to_available" checked>
                                <label class="form-check-label" for="transfer_type_to_available">From Reserve to Available Balance</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="transfer_type" id="transfer_type_to_reserve" value="from_available">
                                <label class="form-check-label" for="transfer_type_to_reserve">From Available to Reserve Balance</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="amountInput" class="small text-secondary">Transfer Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ strtoupper($currency) }}</span>
                                </div>
                                <input id="amountInput" type="number" class="form-control" step="0.01" autocomplete="off">
                            </div>
                            <span id="amountErrorMessage" class="small text-danger mt-1" role="alert"></span>
                        </div>
                        <button id="transferButton" type="button" class="btn btn-success">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endpush

@push('body-stack')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            @if($wallet->type === \App\Enumerations\Business\Wallet\Type::AVAILABLE)
                let autoPayToBank = $('input[name=auto_pay_to_bank]');
                let defaultAutoPayToBankValue = $('#auto_pay_to_bank_{{ $business->auto_pay_to_bank ? 'true' : 'false' }}');
                let autoPayToBankDay = $('#auto_pay_to_bank_day');
                let defaultAutoPayToBankDayValue = autoPayToBankDay.val();
                let autoPayToBankWeeklyDay = $('#auto_pay_to_bank_weekly_day');
                let defaultAutoPayToBankWeeklyDayValue = autoPayToBankWeeklyDay.val();
                let autoPayToBankTime = $('#auto_pay_to_bank_time');
                let defaultAutoPayToBankTimeValue = autoPayToBankTime.val();

                $("#changeModal").on("hidden.bs.modal", () => {
                    defaultAutoPayToBankValue.prop("checked", true);
                    autoPayToBank.change();
                    autoPayToBankDay.val(defaultAutoPayToBankDayValue).change();
                    autoPayToBankTime.val(defaultAutoPayToBankTimeValue).change();
                    autoPayToBankWeeklyDay.val(defaultAutoPayToBankWeeklyDayValue).change();
                });

                autoPayToBank.change(() => {
                    if ($('input[name="auto_pay_to_bank"]:checked').val() === 'on') {
                        autoPayToBankDay.css('display', 'block');
                        autoPayToBankDay.change();
                    } else {
                        autoPayToBankDay.css('display', 'none');
                        autoPayToBankWeeklyDay.css('display', 'none');
                        autoPayToBankTime.css('display', 'none');
                    }
                });

                autoPayToBankDay.change(() => {
                    if (autoPayToBankDay.val() === 'daily') {
                        autoPayToBankTime.css('display', 'block');
                        autoPayToBankWeeklyDay.css('display', 'none');
                    } else if (autoPayToBankDay.val() === 'weekly') {
                        autoPayToBankTime.css('display', 'none');
                        autoPayToBankWeeklyDay.css('display', 'block');
                    } else {
                        autoPayToBankTime.css('display', 'none');
                        autoPayToBankWeeklyDay.css('display', 'none');
                    }
                })
            @elseif($wallet->type === \App\Enumerations\Business\Wallet\Type::RESERVE)
                let url = '{{ route('dashboard.business.balance.wallet.reserve.update', [ $business->getKey(), $currency, '--transfer_type--' ]) }}';
                let transferableReserveAmount = {{ getReadableAmountByCurrency($currency, $reserveWalletBalance) }};
                let transferableAvailableAmount = {{ getReadableAmountByCurrency($currency, $availableWalletBalance) }};

                let amountInput = $("#amountInput");
                let amountErrorMessage = $("#amountErrorMessage");

                let closeButtons = $(".closeButton");
                let transferButton = $("#transferButton");

                $("#transferModal").on("hidden.bs.modal", function () {
                    $('#transfer_type_to_available').prop("checked", true)

                    amountInput.removeClass("is-invalid");
                    amountErrorMessage.css("display", "none");
                });

                $('input[name=transfer_type]').change(() => amountInput.keyup());

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
                        let transfer_type = $('input[name=transfer_type]:checked').val();

                        if (transfer_type === 'from_available' && amountInput.val() > transferableAvailableAmount) {
                            amountInput.addClass("is-invalid");
                            amountErrorMessage.css("display", "block").text("The amount is exceeding the available balance.");
                        } else if (transfer_type === 'to_available' && amountInput.val() > transferableReserveAmount) {
                            amountInput.addClass("is-invalid");
                            amountErrorMessage.css("display", "block").text("The amount is exceeding the reserve balance.");
                        } else {
                            amountInput.removeClass("is-invalid");
                            amountErrorMessage.css("display", "none");
                        }
                    }
                });

                transferButton.click(function (event) {
                    event.preventDefault();

                    transferButton.prop("disabled", true);
                    closeButtons.prop("disabled", true);

                    axios.put(url.replace('--transfer_type--', $('input[name=transfer_type]:checked').val()), {
                        amount : amountInput.val(),
                    }).then(() => {
                        location.reload();
                    }).catch(({ response }) => {
                        _.each(response.data.errors, (value, key) => {
                            $("#" + key + "Input").addClass("is-invalid");
                            $("#" + key + "ErrorMessage").css("display", "block").text(_.first(value));
                        });

                        transferButton.prop("disabled", false);
                        closeButtons.prop("disabled", false);
                    });
                });
            @endif
        });
    </script>
@endpush
