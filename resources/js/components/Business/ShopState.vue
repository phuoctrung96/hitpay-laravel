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
                        <div class="form-row">
                            <div class="col-12">
                                <p v-if="errors.image" class="text-danger small mb-2" role="alert">{{ errors.image }}</p>
                            </div>
                        </div>
                        <p v-if="is_cover_image_succeeded" class="text-success font-weight-bold mb-0 mt-3">
                            <i class="fas fa-check-circle mr-2"></i> Cover image uploaded successfully!</p>
                        <label v-else class="d-inline-flex mb-1" for="profilePictureImage">
                            <input type="file" ref="image" id="profilePictureImage" class="custom-file-input d-none"
                                   accept="image/*" :disabled="is_processing" @change="handleImage">
                            <span id="uploadBtn" class="btn btn-primary btn-sm">
                            <i class="fas fa-cloud-upload-alt"></i> Upload
                            </span> 
                        </label>
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
                        <input id="switch-store" v-model="store_settings.shop_state" type="checkbox"
                               class="custom-control-input"
                               :disabled="is_processing">
                        <label v-if="store_settings.shop_state" for="switch-store" class="custom-control-label">Store is
                            enabled</label>
                        <label v-if="!store_settings.shop_state" for="switch-store" class="custom-control-label">Store is
                            disabled</label>
                    </div>
                    <div v-if="!store_settings.shop_state" class="form-check mt-2">
                        <input type="checkbox" class="form-check-input"
                               v-model="if_schedule_time" id="enableDateTime"
                               :disabled="is_processing">
                        <label class="form-check-label" for="enableDateTime">Schedule a time to make store
                            online</label>
                    </div>
                    <template v-if="!store_settings.shop_state && if_schedule_time">
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
                      <ckeditor id="introduction" v-model="business.introduction"
                                :class="{
                            'is-invalid': errors.introduction,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded"
                      ></ckeditor>
                        <span class="invalid-feedback" role="alert">{{ errors.introduction }}</span>
                    </div>
                </div>
                <div class="form-row mt-2">
                    <div class="col-12">
                        <label for="thank_message" class="small text-muted text-uppercase">Custom thank message</label>
                        <textarea id="thank_message" v-model="store_settings.thank_message" class="form-control"
                                  :class="{
                            'is-invalid': errors.thank_message,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded"></textarea>
                        <span class="invalid-feedback" role="alert">{{ errors.thank_message }}</span>
                    </div>
                </div>
                <div class="custom-control custom-switch mt-3">
                    <input id="switch-redirect" v-model="store_settings.is_redirect_order_completion" type="checkbox"
                           class="custom-control-input"
                           :disabled="is_processing">
                    <label for="switch-redirect" class="custom-control-label">Redirect after order completion</label>
                </div>
                <div class="form-row mt-2">
                    <div class="col-12">
                        <input id="url_redirect_order_completion" v-model="store_settings.url_redirect_order_completion"
                                v-if="store_settings.is_redirect_order_completion" class="form-control mt-1 mb-3"
                                :class="{
                                'is-invalid': errors.url_redirect_order_completion,
                                'bg-light': !(is_processing || is_succeeded),
                            }" :disabled="is_processing || is_succeeded" placeholder="User will be redirect to this URL after 10 secs">
                        <span class="invalid-feedback" role="alert">{{ errors.url_redirect_order_completion }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 mb-3">
                        <label for="seller_notes" class="small text-muted text-uppercase">Seller Notes</label>
                        <textarea id="seller_notes" v-model="store_settings.seller_notes" class="form-control"
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
                        <label for="url_instagram" class="small text-muted text-uppercase">Instagram</label>
                        <input id="url_instagram" v-model="store_settings.url_instagram" class="form-control" :class="{
                            'is-invalid': errors.url_instagram,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.url_instagram }}</span>
                    </div>
                    <div class="col-6 mb-3">
                        <label for="url_facebook" class="small text-muted text-uppercase">Facebook</label>
                        <input id="url_facebook" v-model="store_settings.url_facebook" class="form-control" :class="{
                            'is-invalid': errors.url_facebook,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.url_facebook }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-6 mb-3">
                        <label for="url_twitter" class="small text-muted text-uppercase">Twitter</label>
                        <input id="url_twitter" v-model="store_settings.url_twitter" class="form-control" :class="{
                            'is-invalid': errors.url_twitter,
                            'bg-light': !(is_processing || is_succeeded),
                        }" :disabled="is_processing || is_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.url_twitter }}</span>
                    </div>
                    <div class="col-6 mb-3">
                        <label for="url_tiktok" class="small text-muted text-uppercase">Tiktok</label>
                        <input id="url_tiktok" v-model="store_settings.url_tiktok" class="form-control" :class="{
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
import CKEditor from 'ckeditor4-vue';

export default {
    components: {
        Datepicker,
        VueTimepicker,
        ckeditor: CKEditor.component
    },

    data() {
        return {
            business: {
                introduction: null,
            },

            store_settings: {
                shop_state: true,
                seller_notes: '',
                enable_datetime: null,
                thank_message: null,
                is_redirect_order_completion: false,
                url_redirect_order_completion: null,
                url_facebook: '',
                url_instagram: '',
                url_twitter: '',
                url_tiktok: '',
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

    },

    mounted() {
        this.business = Business;

        this.retrieveStoreSettings();

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
        retrieveStoreSettings() {
          this.is_processing = true;

          axios.get(
            this.getDomain('v1/business/' + this.business.id + '/store-settings', 'api'),
            {
              withCredentials: true
            }
          ).then(({data}) => {
            this.is_processing = false;

            this.business = data.business;
            this.store_settings = data.store_settings;

            this.formatEnableDatetime();
          }).catch(({response}) => {
            console.log(response);
          });
        },
        formatEnableDatetime() {
          if (this.store_settings.shop_state) {
            this.if_schedule_time = false;
          } else {
            if (this.store_settings.enable_datetime) {
              this.if_schedule_time = true;
              this.enable_date = new Date(this.store_settings.enable_datetime);
              this.enable_time = this.store_settings.enable_time;
            } else {
              this.if_schedule_time = false;
            }
          }
        },
        saveChanges() {
            this.is_processing = true;
            this.errors = {};

            if (!this.store_settings.shop_state && this.if_schedule_time) {
                if (this.enable_date === '' || this.enable_time === '') {
                    this.errors.shop_state = "Please fill in date and time to make store online.";
                }
            }

            if (this.store_settings.thank_message && this.store_settings.thank_message.length > 1000) {
              this.errors.thank_message = 'The custom thank message may not be greater than 1000 characters.';
            }

            if (this.store_settings.is_redirect_order_completion) {
              if (!this.store_settings.url_redirect_order_completion) {
                this.errors.url_redirect_order_completion = "Please input url or set uncheck redirect after order completion";
              }
              if (
                this.store_settings.url_redirect_order_completion &&
                this.store_settings.url_redirect_order_completion !== "" &&
                !this.isValidURL(this.store_settings.url_redirect_order_completion)
              ) {
                this.errors.url_redirect_order_completion = "The url is invalid";
              }
            }

            if (this.store_settings.url_instagram && this.store_settings.url_instagram !== '' && !this.isValidURLNotHttp(this.store_settings.url_instagram)) {
                this.errors.url_instagram = 'The URL Instagram is invalid.';
            } else if (this.store_settings.url_instagram && this.store_settings.url_instagram.length > 255) {
                this.errors.url_instagram = 'The Instagram may not be greater than 255 characters.';
            }

            if (this.store_settings.url_facebook && this.store_settings.url_facebook.length > 255) {
                this.errors.url_facebook = 'The Facebook may not be greater than 255 characters.';
            } else if (this.store_settings.url_facebook && this.store_settings.url_facebook !== '' && !this.isValidURLNotHttp(this.store_settings.url_facebook)) {
                this.errors.url_facebook = 'The URL Facebook is invalid.';
            }

            if (this.store_settings.url_twitter && this.store_settings.url_twitter.length > 255) {
                this.errors.url_twitter = 'The Twitter may not be greater than 255 characters.';
            } else if (this.store_settings.url_twitter && this.store_settings.url_twitter !== '' && !this.isValidURLNotHttp(this.store_settings.url_twitter)) {
                this.errors.url_twitter = 'The URL Twitter is invalid.';
            }

            if (this.store_settings.url_tiktok && this.store_settings.url_tiktok.length > 255) {
                this.errors.url_tiktok = 'The Tiktok may not be greater than 255 characters.';
            } else if (this.store_settings.url_tiktok && this.store_settings.url_tiktok !== '' && !this.isValidURLNotHttp(this.store_settings.url_tiktok)) {
                this.errors.url_tiktok = 'The URL Tiktok is invalid.';
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            if (this.store_settings.shop_state || !this.if_schedule_time) {
                this.enable_date = '';
                this.enable_time = '';
            }

            this.store_settings.enable_datetime = this.getEnableDate();

            let submissionData = {
              introduction: this.business.introduction,
              shop_state: this.store_settings.shop_state,
              seller_notes: this.store_settings.seller_notes,
              enable_datetime: this.getEnableDate(),
              thank_message: this.store_settings.thank_message,
              is_redirect_order_completion: this.store_settings.is_redirect_order_completion,
              url_redirect_order_completion: this.store_settings.url_redirect_order_completion,
              url_instagram: this.store_settings.url_instagram,
              url_facebook: this.store_settings.url_facebook,
              url_twitter: this.store_settings.url_twitter,
              url_tiktok: this.store_settings.url_tiktok,
            }

            axios.put(
              this.getDomain('v1/business/' + this.business.id + '/store-settings', 'api'),
              submissionData,
              {
                withCredentials: true
              }
            ).then(({data}) => {
                this.is_processing = false;
                this.is_succeeded = true;

                this.business = data.business;
                this.store_settings = data.store_settings;

                this.formatEnableDatetime();

                setTimeout(() => {
                    this.is_notification_succeeded = false;
                    this.is_succeeded = false;
                }, 3000);
            }).catch(({response}) => {
                if (response.status === 422) {
                    this.is_processing = false;
                    this.is_succeeded = false;

                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                }
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

        handleImage() {
            this.image = this.$refs.image.files[0];

            this.reader.readAsDataURL(this.image);
        },

        uploadImage() {
            this.is_processing = true;
            this.errors = {};

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
                this.is_cover_image_succeeded = false;
                this.is_processing = false;
                this.image_modal.modal('hide'); 
                this.errors.image = 'The image was invalid';
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
