@php($title = __('Account Verification'))
@extends('layouts.business')

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-12 col-lg-9">
            <h5 class="text-center mt-3 mb-4">{{$verification->status == \App\Enumerations\VerificationStatus::MANUAL_VERIFIED || $verification->status == \App\Enumerations\VerificationStatus::VERIFIED ? 'Verification Completed' : 'Verification Submitted'}}</h5>

            @if($firstSubmit && in_array($verificationStatusName, ['verified', 'submitted']))
                <div class="modal fade in" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog success" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" style="color:#011B5F;" id="modal_title_success_message_completed">Account Verification {{ $verificationStatusName === 'verified' ? 'Completed' : 'Submitted' }}</h5>
                            </div>
                            <div class="modal-body">
                                @if($verificationStatusName === 'verified')
                                    Your account verification has been completed. Would you like to start accepting card payments?
                                @else
                                    Your account verification has been submitted. Would you like to start accepting card payments?
                                @endif
                            </div>
                            <div class="modal-footer">
                                <a href="{{ route('dashboard.business.payment-provider.home', ['business_id' => $business->getKey()]) }}" class="btn btn-secondary">Cancel</a>
                                <a href="{{ route('dashboard.business.payment-provider.stripe.home', ['business_id' => $business->getKey()]) }}" class="btn btn-primary">Accept Card Payments</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-backdrop fade show" id="backdrop" style="display: none;"></div>
            @endif

            <verification
                :fill_type="'completed'"
                :verification_id="'{{$verification->id}}'"
            >
            </verification>
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
                let backdrop = document.getElementById("backdrop");

                function openModal() {
                    backdrop.style.display = "block"
                    modal.style.display = "block"
                    modal.classList.add("show")
                }

                openModal();
            });
        @endif
    </script>
@endpush
