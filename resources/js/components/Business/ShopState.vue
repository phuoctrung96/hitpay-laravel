<style>
.vdp-datepicker {
    display: inline-block;
    position: relative;
    font-size: 1em;
    width: 10em;
    font-family: sans-serif;
}

.vdp-datepicker input {
    border: 1px solid #d2d2d2;
    width: 10em;
    height: 2.2em;
    padding: .3em .5em;
    font-size: 1em;
}

.d-text-block{
    display: block;
    clear: both;
}
</style>
<template>
    <div>
        <div class="card shadow-sm mb-3">
            <div class="card-body border-top bg-light p-4">
                <p class="text-uppercase text-muted">Store Cover Images</p>
                <div class="media">
                    <img :alt="business.name" id="avatar" :src="cover_image_url"
                         class="listing align-self-center rounded border mr-3">
                    <div class="media-body align-self-center">
                        <p v-if="is_cover_image_succeeded" class="text-success font-weight-bold mb-0 mt-3">
                            <i class="fas fa-check-circle mr-2"></i> Cover image uploaded successfully!</p>
                        <label v-else class="d-inline-flex mb-1" for="profilePictureImage">
                            <input type="file" ref="image" id="profilePictureImage" class="custom-file-input d-none"
                                   accept="image/*" :disabled="is_processing" @change="handleImage">
                            <span id="uploadBtn" class="btn btn-primary btn-sm">
                            <i class="fas fa-cloud-upload-alt"></i> Upload
                        </span> </label>
                        <a class="text-danger small" href="#" @click.prevent="deleteImage()">Remove</a>
                        <div class="mw-sm">
                            <p class="small text-muted mb-0 mt-1">For best results, use an image at least 1922 by 445
                                pixels in either
                                <strong>JPG</strong> or <strong>PNG</strong> format.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-top p-4">
                <p class="text-uppercase text-muted">Store Settings</p>
                <div class="mb-3">
                    <div class="custom-control custom-switch">
                        <input id="switch-store" v-model="business.shop_state" type="checkbox"
                               class="custom-control-input"
                               :disabled="is_processing">
                        <label v-if="business.shop_state" for="switch-store" class="custom-control-label">Store is
                            enabled</label>
                        <label v-if="!business.shop_state" for="switch-store" class="custom-control-label">Store is
                            disabled</label>
                    </div>
                    <div v-if="!business.shop_state" class="form-check mt-2">
                        <input type="checkbox" class="form-check-input"
                               v-model="if_schedule_time" id="enableDateTime"
                               :disabled="is_processing">
                        <label class="form-check-label" for="enableDateTime">Schedule a time to make store
                            online</label>
                    </div>
                    <template v-if="!business.shop_state && if_schedule_time">
                        <datepicker placeholder="Date" format="dd-MM-yyyy" v-model="enable_date"
                                    :disabled-dates="disableDates"
                                    class="mt-2 mr-2"></datepicker>
                        <vue-timepicker format="hh:mm A" v-model="enable_time" close-on-complete></vue-timepicker>
                    </template>
                    <div id="errors" v-if="errors.shop_state"
                         class="alert alert-danger">
                        <ul class="list-unstyled mb-0">
                            <li>{{ errors.shop_state }}</li>
                        </ul>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <label for="introduction" class="small text-muted text-uppercase">About Us Section</label>
                        <textarea id="introduction" v-model="business.introduction" class="form-control"
                                  :class="{
                            'is-invalid': errors.introduction,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded"></textarea>
                        <span class="invalid-feedback" role="alert">{{ errors.introduction }}</span>
                    </div>
                </div>
                <div class="form-row mt-2">
                    <div class="col-12">
                        <label for="thank_message" class="small text-muted text-uppercase">Custom thank message</label>
                        <textarea id="thank_message" v-model="business.thank_message" class="form-control"
                                  :class="{
                            'is-invalid': errors.thank_message,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded"></textarea>
                        <span class="invalid-feedback" role="alert">{{ errors.thank_message }}</span>
                    </div>
                </div>
                <div class="custom-control custom-switch mt-2">
                    <input id="switch-shipping" v-model="business.enabled_shipping" type="checkbox"
                           class="custom-control-input"
                           :disabled="is_processing">
                    <label v-if="business.enabled_shipping" for="switch-shipping" class="custom-control-label">Shipping is
                        enabled</label>
                    <label v-if="!business.enabled_shipping" for="switch-shipping" class="custom-control-label">Shipping is
                        disabled</label>
                </div>
                <div class="custom-control custom-switch mt-2">
                    <input id="switch-redirect" v-model="business.is_redirect_order_completion" type="checkbox"
                           class="custom-control-input"
                           :disabled="is_processing">
                    <label for="switch-redirect" class="custom-control-label">Redirect after order completion</label>
                </div>
                <div class="form-row mt-2">
                    <div class="col-12">
                        <input id="url_redirect_order_completion" v-model="business.url_redirect_order_completion" 
                                v-if="business.is_redirect_order_completion" class="form-control" 
                                :class="{
                                'is-invalid': errors.url_redirect_order_completion,
                                'bg-light': !(is_processing || is_succeeded),
                            }" :disabled="is_processing || is_succeeded" placeholder="User will be redirect to this URL after 10 secs">
                        <span class="invalid-feedback" role="alert">{{ errors.url_redirect_order_completion }}</span>
                    </div>
                </div>
                <h5 class="text-center mt-3">Pick Up Address</h5>
                <div class="form-row">
                    <div class="col-12 mb-3">
                        <label for="street" class="small text-muted text-uppercase">Street</label>
                        <input id="street" v-model="business.street" class="form-control" :class="{
                            'is-invalid': errors.street,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.street }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="city" class="small text-muted text-uppercase">City</label>
                        <input id="city" v-model="business.city" class="form-control" :class="{
                            'is-invalid': errors.city,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.city }}</span>
                    </div>
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="state" class="small text-muted text-uppercase">State</label>
                        <input id="state" v-model="business.state" class="form-control" :class="{
                            'is-invalid': errors.state,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.state }}</span>
                    </div>
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="postal_code" class="small text-muted text-uppercase">Postal Code</label>
                        <input id="postal_code" v-model="business.postal_code" class="form-control" :class="{
                            'is-invalid': errors.postal_code,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.postal_code }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="country" class="small text-muted text-uppercase">Country</label>
                        <input id="country" type="text" readonly class="form-control-plaintext"
                               :value="business.country_name">
                    </div>
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="country" class="small text-muted text-uppercase">Self Pick-Up</label>
                        <div class="custom-control custom-switch">
                            <input id="can_pick_up" v-model="business.can_pick_up" type="checkbox"
                                   class="custom-control-input"
                                   :disabled="is_processing || is_succeeded">
                            <label for="can_pick_up" class="custom-control-label">Customer can pick up</label>
                        </div>
                        <button v-if="business.can_pick_up" class="btn btn-primary mt-2" data-toggle="modal"
                                data-target="#timeSlotsModal">Time Slots for Pick-ups
                        </button>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 mb-3">
                        <label for="seller_notes" class="small text-muted text-uppercase">Seller Notes</label>
                        <textarea id="seller_notes" v-model="business.seller_notes" class="form-control"
                                  :class="{
                            'is-invalid': errors.seller_notes,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded"></textarea>
                        <span class="invalid-feedback" role="alert">{{ errors.seller_notes }}</span>
                    </div>
                </div>
                <h5 class="text-center mt-3">Social network</h5>
                <div class="form-row">
                    <div class="col-6 mb-3">
                        <label for="street" class="small text-muted text-uppercase">Instagram</label>
                        <input id="street" v-model="business.url_instagram" class="form-control" :class="{
                            'is-invalid': errors.url_instagram,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.url_instagram }}</span>
                    </div>
                    <div class="col-6 mb-3">
                        <label for="street" class="small text-muted text-uppercase">Facebook</label>
                        <input id="street" v-model="business.url_facebook" class="form-control" :class="{
                            'is-invalid': errors.url_facebook,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.url_facebook }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-6 mb-3">
                        <label for="street" class="small text-muted text-uppercase">Twitter</label>
                        <input id="street" v-model="business.url_twitter" class="form-control" :class="{
                            'is-invalid': errors.url_twitter,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.url_twitter }}</span>
                    </div>
                    <div class="col-6 mb-3">
                        <label for="street" class="small text-muted text-uppercase">Tiktok</label>
                        <input id="street" v-model="business.url_tiktok" class="form-control" :class="{
                            'is-invalid': errors.url_tiktok,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.url_tiktok }}</span>
                    </div>
                </div>
                <button class="btn btn-primary mt-2 d-block" @click="saveChanges()"
                        :disabled="is_processing">
                    <i class="fas fa-save mr-1"></i> Save Changes
                    <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
                </button>
                <p v-if="is_succeeded" class="text-success font-weight-bold mb-0 mt-3">
                    <i class="fas fa-check-circle mr-2"></i> Saved successfully!</p>
            </div>
            <div class="modal fade" id="timeSlotsModal" tabindex="-1" role="dialog"
                 aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="timeSlotsLabel">Date and Time slots for pick-up</h5>
                            <button id="btnclose" type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <a :class="{ 'text-danger' : pick_up_slots.has_slots }"
                               @click="triggerSlots($event, !pick_up_slots.has_slots)"
                               href="#" class="float-right small">
                                {{ pick_up_slots.has_slots ? 'Disable slots' : 'Enable slots' }}
                            </a>
                            <div v-if="pick_up_slots.has_slots">
                                <a v-for="(item, index) in usable_data.week_days_list"
                                   v-if="!pick_up_slots.slots.find(x => x.day === index)"
                                   @click="addSlot($event, index)" class="btn btn-outline-secondary btn-sm m-1"
                                   href="#">
                                    + {{ item }}
                                </a>
                            </div>
                            <span class="small text-danger d-text-block">{{ errors.slots }}</span>
                            <template v-if="pick_up_slots.has_slots">
                                <div v-for="slot in pick_up_slots.slots">
                                    <div class="form-group row mb-0">
                                        <label class="col-12 col-form-label">
                                            <a class="small text-danger float-right" href="#"
                                               @click="removeSlot($event, slot.day)">Remove</a>
                                            <span class="font-weight-bold">{{
                                                    usable_data.week_days_list[slot.day]
                                                }}</span>
                                        </label>
                                        <div class="col-12 mb-3">
                                            <span class="mr-2">From: </span>
                                            <vue-timepicker format="hh:mm A" v-model="slot.times.from"
                                                            close-on-complete></vue-timepicker>
                                            <span class="mr-2">To: </span>
                                            <vue-timepicker format="hh:mm A" v-model="slot.times.to"
                                                            close-on-complete></vue-timepicker>
                                            <span v-if="slot.error != ''" class="invalid-feedback d-block"
                                                  role="alert">{{ slot.error }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="modal-footer">
                            <button id="btnCreate"
                                    class="btn btn-success shadow-sm"
                                    @click="saveSlots()" :disabled="is_processing">{{ 'Save' }}
                                <i class="fas fa-spin fa-spinner" :class="{'d-none' : !is_processing}"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="confirmImageModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body border-bottom">
                        <h5 class="modal-title" :class="{
                            'mb-0': !image_error,
                            'mb-3': image_error,
                        }">Confirm Store Cover Image</h5>
                        <p v-if="image_error" class="text-danger mb-0">{{ image_error }}</p>
                    </div>
                    <img id="imageCanvas" class="img-fluid">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" :disabled="is_processing">
                            Close
                        </button>
                        <button type="button" class="btn btn-primary" @click="uploadImage" :disabled="is_processing">
                            <i class="fas fa-upload mr-1"></i> Upload <i v-if="is_processing"
                                                                         class="fas fa-spinner fa-spin"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Datepicker from 'vuejs-datepicker';

import 'vue2-timepicker/dist/VueTimepicker.css';

import VueTimepicker from 'vue2-timepicker';

export default {
    components: {
        Datepicker,
        VueTimepicker
    },

    data() {
        return {
            business: {
                country_name: null,
                street: null,
                city: null,
                state: null,
                postal_code: null,
                can_pick_up: false,
                slots: [],
                introduction: null,
                seller_notes: null,
                schedule_time: false,
                phone_number: null,
                currency_name: null,
                identifier: null,
                name: null,
                email: null,
                display_name: null,
                statement_description: null,
                enabled_shipping: true,
                thank_message: null,
                is_redirect_order_completion: false,
                url_redirect_order_completion: null,
                url_facebook: '',
                url_instagram: '',
                url_twitter: '',
                url_tiktok: ''
            },

            pick_up_slots: {
                has_slots: false,
                slots: [],
            },

            usable_data: {
                week_days_list: {
                    Monday: 'Monday',
                    Tuesday: 'Tuesday',
                    Wednesday: 'Wednesday',
                    Thursday: 'Thursday',
                    Friday: 'Friday',
                    Saturday: 'Saturday',
                    Sunday: 'Sunday',
                }
            },
            errors: {},
            is_processing: false,
            is_succeeded: false,
            enable_date: '',
            enable_time: '',
            if_schedule_time: false,

            // Cover image
            image: null,
            image_canvas: null,
            image_error: null,
            image_modal: null,
            file_error: '',

            is_cover_image_succeeded: false,
            default_cover_image_url: null,
            cover_image_url: null,
            modal: null,
            reader: null
        };
    },
    watch: {
        'pick_up_slots.slots': {
            handler(values) {
                if (values.length > 0) {
                    this.errors.slots = '';
                }
                _.forEach(values, function (value) {
                    if (value.times.from != '' && value.times.to != '') {

                        let from = "01/01/2011 " + value.times.from;
                        let to = "01/01/2011 " + value.times.to;
                        let fromDate = new Date(Date.parse(from));
                        let toDate = new Date(Date.parse(to));

                        if (fromDate > toDate) {
                            value.error = "'To' time cannot be earlier than 'from' time"
                        } else {
                            value.error = '';
                        }
                    }
                });
            },
            deep: true
        }
    },

    mounted() {
        this.business = Business;
        this.business.can_pick_up = this.business.can_pick_up ?? false;
        this.business.seller_notes = this.business.seller_notes ?? '';

        if (Business.slots != null) {
            this.pick_up_slots.has_slots = true;
            this.pick_up_slots.slots = this.orderSlot(JSON.parse(Business.slots));
        }

        if (this.business.enable_datetime === null)
            this.if_schedule_time = false;
        else {
            this.if_schedule_time = true;
            this.enable_date = new Date(EnableDate);
            this.enable_time = EnableTime;
        }

        this.default_cover_image_url = Data.default_cover_image_url;

        if (Data.cover_image_url) {
            this.cover_image_url = Data.cover_image_url;
        } else {
            this.cover_image_url = this.default_cover_image_url;
        }

        this.image_modal = $('#confirmImageModal');
        this.image_canvas = $('#imageCanvas');
        this.reader = new FileReader();

        this.reader.onload = (event) => {
            this.image_canvas.attr('src', this.reader.result);

            this.image_modal.modal('show');
        };

        this.image_modal.on('show.bs.modal', () => {
        }).on('hide.bs.modal', () => {
            this.image = null;
            this.image_error = null;
        });
    },

    methods: {
        saveChanges() {
            this.is_processing = true;
            this.errors = {};

            if (!this.business.shop_state && this.if_schedule_time) {
                if (this.enable_date === '' || this.enable_time === '') {
                    this.errors.shop_state = "Please fill in date and time to make store online.";
                }
            }

            if(this.business.can_pick_up){
                if( this.business.street == null || this.business.street.trim() == '' ) {
                    this.errors.street = 'The street field is required.';
                } else if(this.business.street.length > 255) {
                    this.errors.street = 'The street field may not be greater than 255 characters.';
                }

                if( this.business.city == null || this.business.city.trim() == '' ){
                    this.errors.city = 'The city field is required.';
                } else if(this.business.city.length > 255) {
                    this.errors.city = 'The city field may not be greater than 255 characters.';
                }

                if ( this.business.state == null || this.business.state.trim() == '' ){
                    this.errors.state = 'The state field is required.';
                } else if(this.business.state.length > 255) {
                    this.errors.state = 'The state field may not be greater than 255 characters.';
                }

                if( this.business.postal_code == null || this.business.postal_code.trim() == '') {
                    this.errors.postal_code = 'The postal code field is required.';
                } else if(this.business.postal_code.length > 16) {
                    this.errors.postal_code = 'The postal code may not be greater than 16 characters.';
                }
            }

            if(this.business.thank_message.length > 1000) {
                this.errors.thank_message = 'The custom thank message may not be greater than 1000 characters.';
            }

            if(this.business.is_redirect_order_completion && !this.isValidURL(this.business.url_redirect_order_completion)){
                this.errors.url_redirect_order_completion = "The url is invalid";
            }

            if(this.business.url_instagram != '' & !this.isValidURLNotHttp(this.business.url_instagram)) {
                this.errors.url_instagram = 'The URL Instagram is invalid.';
            }

            if(this.business.url_instagram.length > 255) {
                this.errors.url_instagram = 'The Instagram may not be greater than 255 characters.';
            }

            if(this.business.url_facebook.length > 255) {
                this.errors.url_facebook = 'The Facebook may not be greater than 255 characters.';
            }

            if(this.business.url_facebook != '' & !this.isValidURLNotHttp(this.business.url_facebook)) {
                this.errors.url_facebook = 'The URL Facebook is invalid.';
            }
            
            if(this.business.url_twitter.length > 255) {
                this.errors.url_twitter = 'The Twitter may not be greater than 255 characters.';
            }

            if(this.business.url_twitter != '' & !this.isValidURLNotHttp(this.business.url_twitter)) {
                this.errors.url_twitter = 'The URL Twitter is invalid.';
            }

            if(this.business.url_tiktok.length > 255) {
                this.errors.url_tiktok = 'The Tiktok may not be greater than 255 characters.';
            }

            if(this.business.url_tiktok != '' & !this.isValidURLNotHttp(this.business.url_tiktok)) {
                this.errors.url_tiktok = 'The URL Tiktok is invalid.';
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            if (this.business.shop_state || !this.if_schedule_time) {
                this.enable_date = '';
                this.enable_time = '';
            }

            this.business.enable_datetime = this.getEnableDate();

            axios.put(this.getDomain('business/' + this.business.id + '/setting/shop/update', 'dashboard'), this.business).then(({data}) => {
                this.is_processing = false;
                this.is_succeeded = true;
                this.business = data;

                setTimeout(() => {
                    this.is_notification_succeeded = false;
                    this.is_succeeded = false;
                }, 3000);
            });

        },
        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }
            this.is_processing = false;
        },
        getEnableDate() {
            if (this.enable_date != '' && this.enable_time != '') {
                const [time, modifier] = this.enable_time.split(' ');

                let [hours, minutes] = time.split(':');

                if (hours === '12') {
                    hours = '00';
                }

                if (modifier === 'PM') {
                    hours = parseInt(hours, 10) + 12;
                }

                let hour24 = `${hours}:${minutes}`;

                let fromMonth = this.enable_date.getMonth() + 1;

                if (fromMonth < 10) {
                    fromMonth = '0' + fromMonth;
                }

                let fromDay = this.enable_date.getDate();

                if (fromDay < 10) {
                    fromDay = '0' + fromDay;
                }

                return this.enable_date.getFullYear() + '-' + fromMonth + '-' + fromDay + ' ' + hour24;
            } else return null;
        },
        isValidURL(string) {
            var res = string.match(/(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g);
            return (res !== null)
        },
        isValidURLNotHttp(string) {
            var res = string.match(/[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g);
            return (res !== null)
        },
        triggerSlots(event, status) {
            event.preventDefault();

            this.pick_up_slots.has_slots = status;
        },

        addSlot(event, key) {
            event.preventDefault();

            if (this.pick_up_slots.slots.length < 7) {
                this.pick_up_slots.slots.push({
                    day: key,
                    times: {
                        from: '',
                        to: '',
                    },
                    error: ''
                });

                this.pick_up_slots.slots = this.orderSlot(this.pick_up_slots.slots);
            }
        },
        removeSlot(event, key) {
            event.preventDefault();

            let slot = this.pick_up_slots.slots.indexOf(this.pick_up_slots.slots.find(x => x.day === key));

            this.pick_up_slots.slots.splice(slot, 1);
        },

        handleImage() {
            this.image = this.$refs.image.files[0];

            this.reader.readAsDataURL(this.image);
        },

        uploadImage() {
            this.is_processing = true;

            var size = parseFloat(this.image.size / (1024 * 1024)).toFixed(2);
            if( size > 2){
                this.image_error = 'Please select image size less than 2 MB';
                this.is_processing = false;
                return;
            }

            let form = new FormData();

            form.append('image', this.image);

            axios.post(this.getDomain('business/' + this.business.id + '/setting/shop/cover-image', 'dashboard'), form, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then(({data}) => {
                this.is_processing = false;
                this.is_cover_image_succeeded = true;
                this.cover_image_url = data.cover_image_url;
                this.image_modal.modal('hide');

                setTimeout(() => {
                    this.is_cover_image_succeeded = false;
                }, 5000);
            }).catch(() => {
                console.log('FAILURE!!');
            });
        },
        deleteImage(){
            axios.delete(this.getDomain('business/' + this.business.id + '/setting/shop/cover-image', 'dashboard')).then(({data}) => {
                this.image = null;
                this.cover_image_url = this.default_cover_image_url;
            }).catch(({error}) => {
                console.log(error);
            });
        },
        saveSlots() {
            if (this.pick_up_slots.has_slots) {
                if (this.pick_up_slots.slots.length > 0) {
                    let err = false;
                    _.each(this.pick_up_slots.slots, value => {
                        if (value.error != '') {
                            err = true;
                            return;
                        }
                        if (value.times.from === '' || value.times.to === '') {
                            err = true;
                            value.error = "Please fill in both times";
                            return;
                        }
                    });
                    if (err) {
                        this.is_processing = false;
                        return;
                    }
                    this.business.slots = JSON.stringify(this.pick_up_slots.slots);
                } else {
                    this.errors.slots = 'Please choose date and time slots for your delivery';
                }
            } else {
                this.business.slots = null;
            }

            if (!this.business.can_pick_up) {
                this.business.slots = null;
            }

            axios.post(this.getDomain('business/' + this.business.id + '/setting/shop/slots', 'dashboard'), this.business).then(({data}) => {
                this.business = data;

                this.is_processing = false;
                this.is_succeeded = true;
                $("#timeSlotsModal").modal('hide');

                setTimeout(() => {
                    this.is_succeeded = false;
                }, 5000);
            }).catch(({response}) => {
                if (response.status === 422) {
                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                }
            });
        },

        orderSlot(slots) {
            let arr = ['', '', '', '', '', '', '']
            slots.forEach(day => {
                if (day.day === 'Monday') arr[0] = day
                if (day.day === 'Tuesday')  arr[1] = day
                if (day.day === 'Wednesday') arr[2] = day
                if (day.day === 'Thursday') arr[3] = day
                if (day.day === 'Friday') arr[4] = day
                if (day.day === 'Saturday') arr[5] = day
                if (day.day === 'Sunday') arr[6] = day
            });

            return arr.filter(str => str !== '')
        },
    },
    computed: {
        disableDates() {
            var date = new Date();
            date.setDate(date.getDate() - 1);
            return {
                to: date
            }
        }
    }
}
</script>
