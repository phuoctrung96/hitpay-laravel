@php($title = __('Account Verification'))
@extends('layouts.business')

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-12 col-lg-9">
            <h5 class="text-center mt-3 mb-4">{{ $verificationStatusTitle }}</h5>

            @if($isOwner)

                @if(in_array($verificationStatusName, ['verified', 'submitted']))
                    <div class="modal fade in" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog success" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" style="color:#011B5F;" id="modal_title_success_message_completed">Account Verification {{ $verificationStatusName === 'verified' ? 'Completed' : 'Submitted' }}</h5>
                                </div>
                                <div class="modal-body">
                                    @if($verificationStatusName === 'verified')
                                        Your account verification has been completed. You can start accepting payments.
                                    @else
                                        Your account verification has been submitted, you will be notified once the account is verified. You can start accepting payments.
                                    @endif
                                </div>
                                <div class="modal-footer">
                                    <button type="button" id="btn-close" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <a href="{{ route('dashboard.business.payment-provider.home', ['business_id' => $business->getKey()]) }}" class="btn btn-primary">Okay</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-backdrop fade show" id="backdrop" style="display: none;"></div>
                @endif

                <verification-cognito-show
                    :fill_type="'{{ $verificationStatus }}'"
                    :verification_id="'{{$verification->id}}'"
                    :countries="{{ $countries }}"
                >
                </verification-cognito-show>
            @else
                <p>Please ask the owner "<b>{{ $businessUserOwner->email }}</b>" to complete the verification</p>
            @endif
        </div>
    </div>
@endsection
@push('body-stack')
    <script>
        window.Verification = @json($verification_data);
        window.Type = @json($type);

        @if(in_array($verificationStatusName, ['verified', 'submitted']))
            document.addEventListener("DOMContentLoaded", function(event) {
                let modal = document.getElementById('exampleModal');
                let btnClose = document.getElementById('btn-close');
                let backdrop = document.getElementById("backdrop");

                function openModal() {
                    backdrop.style.display = "block"
                    modal.style.display = "block"
                    modal.classList.add("show")
                }

                btnClose.addEventListener('click', (e) => {
                    modal.style.display = "none";
                    modal.className="modal fade";
                    backdrop.style.display = "none";
                });

                openModal();
            });
        @endif
    </script>
@endpush
