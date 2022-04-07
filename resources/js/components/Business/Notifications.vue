<template>
    <div>
        <div class="card shadow-sm mb-3">
            <div class="card-body border-top p-4">
                <p class="text-uppercase text-muted">Notification Settings</p>
                <div class="form-row">
                    <div class="col-12 col-md-6 mb-3">
                        <p class="small text-muted text-uppercase mb-2">Daily Collection</p>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input"
                                   v-model="notifications['daily_collection@email']" id="dailyCollectionForEmail"
                                   :disabled="is_processing_notification">
                            <label class="form-check-label" for="dailyCollectionForEmail">Email</label>
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input"
                                   v-model="notifications['daily_collection@push_notification']"
                                   id="dailyCollectionForPushNotification" :disabled="is_processing_notification">
                            <label class="form-check-label" for="dailyCollectionForPushNotification">Push
                                Notification</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input"
                                   v-model="notifications['daily_payout@email']"
                                   id="dailyCollectionForPayoutEmail" :disabled="is_processing_notification">
                            <label class="form-check-label" for="dailyCollectionForPayoutEmail">Payout Email</label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-md-6 mb-3">
                        <p class="small text-muted text-uppercase mb-2">New Order</p>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" v-model="notifications['new_order@email']"
                                   id="newOrderForEmail" :disabled="is_processing_notification">
                            <label class="form-check-label" for="newOrderForEmail">Email</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input"
                                   v-model="notifications['new_order@push_notification']"
                                   id="newOrderForPushNotification" :disabled="is_processing_notification">
                            <label class="form-check-label" for="newOrderForPushNotification">Push Notification</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <p class="small text-muted text-uppercase mb-2">Pending Order</p>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input"
                                   v-model="notifications['pending_order@email']" id="pendingOrderForEmail"
                                   :disabled="is_processing_notification">
                            <label class="form-check-label" for="pendingOrderForEmail">Email</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input"
                                   v-model="notifications['pending_order@push_notification']"
                                   id="pendingOrderForPushNotification" :disabled="is_processing_notification">
                            <label class="form-check-label" for="pendingOrderForPushNotification">Push
                                Notification</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <p class="small text-muted text-uppercase mb-2">Incoming Payment</p>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input"
                                   v-model="notifications['incoming_payment@email']" id="incomingPaymentForEmail"
                                   :disabled="is_processing_notification">
                            <label class="form-check-label" for="incomingPaymentForEmail">Email</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <p class="small text-muted text-uppercase mb-2">Customer receipt</p>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input"
                                   v-model="notifications['customer_receipt@email']" id="customerReceiptForEmail"
                                   :disabled="is_processing_notification">
                            <label class="form-check-label" for="customerReceiptForEmail">Email</label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <button class="btn btn-primary" @click="updateNotification"
                                :disabled="is_processing_notification || is_notification_succeeded">
                            <i class="fas fa-save mr-1"></i> Update
                            <i v-if="is_processing_notification" class="fas fa-spinner fa-spin"></i>
                        </button>
                        <p v-if="is_notification_succeeded" class="text-success font-weight-bold mb-0 mt-3">
                            <i class="fas fa-check-circle mr-2"></i> Updated successfully!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import 'vue2-timepicker/dist/VueTimepicker.css';

import VueTimepicker from 'vue2-timepicker';

export default {

    data() {
        return {
            business: {
                country_name: null,
                street: null,
                city: null,
                state: null,
                postal_code: null,
                can_pick_up: false,
                phone_number: null,
                currency_name: null,
                identifier: null,
                name: null,
                email: null,
                display_name: null,
                statement_description: null,
                slots: [],
                introduction: null,
                seller_notes: null,
            },

            notifications: {
                'daily_collection@email': false,
                'daily_collection@push_notification': false,
                'daily_payout@email': false,
                'new_order@email': false,
                'new_order@push_notification': false,
                'pending_order@email': false,
                'pending_order@push_notification': false,
                'incoming_payment@email': false,
                'customer_receipt@email': false,
            },
            errors: {
                //
            },
            is_processing: false,
            is_processing_notification: false,
            is_notification_succeeded: false,
        };
    },

    mounted() {
        this.business = Business;

        _.each(this.business.subscribed_events, (value) => {
            if (this.notifications[value.event + '@' + value.channel] !== undefined) {
                this.notifications[value.event + '@' + value.channel] = true;
            }
        });
    },

    methods: {
        updateNotification() {
            this.is_processing_notification = true;
            this.errors = {};

            axios.put(this.getDomain('business/' + this.business.id + '/notifications', 'dashboard'), this.notifications).then(({data}) => {
                this.is_processing_notification = false;
                this.is_notification_succeeded = true;

                setTimeout(() => {
                    this.is_notification_succeeded = false;
                }, 5000);
            });
        },

        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);
            }

            this.is_processing = false;
        },
    }
}
</script>
