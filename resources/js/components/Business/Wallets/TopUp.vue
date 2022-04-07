<style scoped>
#paynow_online-qr-code img {
    max-width: 100%;
    height: auto;
    margin-left: auto;
    margin-right: auto;
}
</style>

<template>
    <div class="d-inline">
        <button class="btn btn-warning" data-toggle="modal" data-target="#topUpModal">Top Up</button>
        <div id="topUpModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div v-if="step === 'confirmation'" class="modal-body">
                        <h5 class="modal-title text-danger font-weight-bold mb-3">
                            Are you sure you want to top up?
                        </h5>
                        <div class="mb-3">
                            <label class="small text-uppercase">Enter top up amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text text-uppercase">{{ currency }}</span>
                                </div>
                                <input v-model="topping_up_amount" type="number" class="form-control bg-light" :class="{
                                    'is-invalid': error.amount,
                                }" title="Amount" :disabled="is_processing">
                            </div>
                            <span v-if="error.amount" class="text-danger" role="alert"><small>{{ error.amount }}</small></span>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-danger" @click="topUp" :disabled="is_processing">
                                Confirm <i class="fas fa-spinner fa-spin" v-if="is_processing"></i>
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" :disabled="is_processing">
                                Cancel
                            </button>
                        </div>
                    </div>
                    <div v-if="step === 'waiting_for_payment'" class="modal-body bg-light text-center">
                        <h3 class="mb-3">Scan below PayNow QR to complete wallet top up</h3>
                        <p>{{ currency.toUpperCase() }}{{
                                Number(topping_up_amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
                            }}</p>
                        <div class="mb-3">
                            <div id="paynow_online-qr-code"></div>
                        </div>
                        <p>Awaiting payment for top up. Please do not close this window until you receive top up
                            confirmation.</p>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                    <div v-if="step === 'topped_up'" class="modal-body bg-light text-center">
                        <h3 class="mb-3">Done!</h3>
                        <p><i class="fas fa-check-circle fa-3x text-success"></i></p>
                        <p>Top up successful.</p>
                        <p class="h3 mb-3">{{ currency }}{{
                                Number(topping_up_amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
                            }}</p>
                        <a class="btn btn-primary" href="#" onclick="event.preventDefault(); location.reload()">Back to
                            Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props : {
        businessId : String,
        currency : String,
    },

    data() {
        return {
            error : {
                //
            },
            is_processing : false,
            modal : null,
            topping_up_amount : null,
            top_up_intent_id : null,
            step : "confirmation",
        };
    },

    mounted() {
        this.modal = $("#topUpModal");
        this.modal.on("hidden.bs.modal", () => {
            this.step = "confirmation";
            this.is_processing = false;
            this.error = {
                //
            };
        });
    },

    methods : {
        async topUp() {
            if (Number.parseInt(this.topping_up_amount) < 0 || Number.parseInt(this.topping_up_amount) > 999999.99){
                alert('Invalid amount, must be more than 0 and less than 9999999.');
                return;
            }

            this.is_processing = true;
            this.error = {
                //
            };

            this.step = "waiting_for_payment";

            let url = `business/${ this.businessId }/balance/${ this.currency }/available/top-up/intent`;

            await axios.post(this.getDomain(url, "dashboard"), {
                amount : this.topping_up_amount,
            }, {
                withCredentials : true,
            }).then(({ data }) => {
                this.topping_up_amount = data.amount;
                this.top_up_intent_id = data.id;

                if (data.paynow_data === 'service_unavailable') {
                    alert('PayNow QR is currently not available. Please use another payment method.')
                }
                new QRCode("paynow_online-qr-code", {
                    text : data.paynow_data,
                    width : 256,
                    height : 256,
                    colorDark : "#840070",
                    colorLight : "#fff",
                    correctLevel : QRCode.CorrectLevel.H,
                });

                this.pollChargeStatus();
            }).catch((response) => {
                console.log(response.errors)
                if (response.status === 422) {
                    this.step = "confirmation";
                    this.is_processing = false;

                    _.forEach(response.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                        alert(value);
                    });
                }
            });
        },

        async pollChargeStatus(start = 0) {
            start++;

            let url = `business/${this.businessId}/balance/top-up-intent/${this.top_up_intent_id}`;

            await axios.post(this.getDomain(url, "dashboard")).then(({ data }) => {
                if (data.status === "succeeded") {
                    this.step = "topped_up";
                } else if (start <= 150) {
                    setTimeout(this.pollChargeStatus, 2000, start);
                } else {
                    console.warn(new Error("Polling timed out."));
                }
            });
        },
    },
};
</script>
