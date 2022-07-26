<template>
    <div>
        <div class="card shadow-sm mb-3">
            <div class="card-body border-top bg-light p-4">
                <p class="text-uppercase text-muted">Business Logo</p>
                <div class="media">
                    <img :alt="business.name" id="avatar" :src="logo_url"
                         class="listing align-self-center rounded border mr-3">
                    <div class="media-body align-self-center">
                        <div class="form-row">
                            <div class="col-12">
                                <p v-if="errors.image" class="text-danger small mb-2" role="alert">{{ errors.image }}</p>
                            </div>
                        </div>
                        <p v-if="is_logo_succeeded" class="text-success font-weight-bold mb-0 mt-3">
                            <i class="fas fa-check-circle mr-2"></i> Logo uploaded successfully!</p>
                        <label v-else class="d-inline-flex mb-1" for="profilePictureImage">
                            <input type="file" ref="image" id="profilePictureImage" class="custom-file-input d-none"
                                   accept="image/*" :disabled="is_processing" @change="handleImage">
                            <span id="uploadBtn" class="btn btn-primary btn-sm">
                            <i class="fas fa-cloud-upload-alt"></i> Upload
                        </span> </label>
                        <a class="text-danger small" href="#" @click.prevent="deleteImage()">Remove</a>
                        <div class="mw-sm">
                            <p class="small text-muted mb-0 mt-1">For best results, use an image at least 800 by 800
                                pixels in either
                                <strong>JPG</strong> or <strong>PNG</strong> format.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-top p-4">
                <p class="text-uppercase text-muted">Basic Information</p>
                <div class="form-row">
                    <div class="col-12 col-sm-8 mb-3">
                        <label for="name" class="small text-muted text-uppercase">Name</label>
                        <input id="name" v-model="business.name" class="form-control" :class="{
                            'is-invalid': errors.name,
                            'bg-light': !(is_processing || is_basic_information_succeeded),
                        }" :disabled="is_processing || is_basic_information_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.name }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-sm-8 mb-3">
                        <label for="display_name" class="small text-muted text-uppercase">Display Name</label>
                        <input id="display_name" v-model="business.display_name" class="form-control" :class="{
                            'is-invalid': errors.display_name,
                            'bg-light': !(is_processing || is_basic_information_succeeded),
                        }" :disabled="is_processing || is_basic_information_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.display_name }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="registered_country" class="small text-muted text-uppercase">Registered
                            Country</label>
                        <input id="registered_country" type="text" readonly class="form-control-plaintext"
                               :value="business.address.country_name">
                    </div>
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="currency" class="small text-muted text-uppercase">Currency</label>
                        <input id="currency" type="text" readonly class="form-control-plaintext"
                               :value="business.currency_name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="street" class="small text-muted text-uppercase">Street</label>
                        <input id="street" v-model="business.address.street" class="form-control"
                               :class="{
                            'is-invalid': errors.address__street,
                            'bg-light': !(is_processing || is_basic_information_succeeded),
                        }" :disabled="is_processing || is_basic_information_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.address__street }}</span>
                    </div>
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="city" class="small text-muted text-uppercase">City</label>
                        <input id="city" v-model="business.address.city" class="form-control"
                               :class="{
                            'is-invalid': errors.address__city,
                            'bg-light': !(is_processing || is_basic_information_succeeded),
                        }" :disabled="is_processing || is_basic_information_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.address__city }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="state" class="small text-muted text-uppercase">State</label>
                        <input id="state" v-model="business.address.state" class="form-control"
                               :class="{
                            'is-invalid': errors.address__state,
                            'bg-light': !(is_processing || is_basic_information_succeeded),
                        }" :disabled="is_processing || is_basic_information_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.address__state }}</span>
                    </div>
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="postal_code" class="small text-muted text-uppercase">Postal Code</label>
                        <input id="postal_code" v-model="business.address.postal_code" class="form-control"
                               :class="{
                            'is-invalid': errors.address__postal_code,
                            'bg-light': !(is_processing || is_basic_information_succeeded),
                        }" :disabled="is_processing || is_basic_information_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.address__postal_code }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="statement_description" class="small text-muted text-uppercase">Statement
                            Description</label>
                        <input id="statement_description" v-model="business.statement_description" class="form-control"
                               :class="{
                            'is-invalid': errors.statement_description,
                            'bg-light': !(is_processing || is_basic_information_succeeded),
                        }" :disabled="is_processing || is_basic_information_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.statement_description }}</span>
                    </div>
                </div>
                <hr>
                <div class="form-row">
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="phone_number" class="small text-muted text-uppercase">Phone Number</label>
                        <input id="phone_number" type="tel" v-model="business.phone_number" class="form-control" :class="{
                            'is-invalid': errors.phone_number,
                            'bg-light': !(is_processing || is_basic_information_succeeded),
                        }" :disabled="is_processing || is_basic_information_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.phone_number }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-sm-6 mb-3">
                        <label for="email" class="small text-muted text-uppercase">Email</label>
                        <input id="email" v-model="business.email" class="form-control" :class="{
                            'is-invalid': errors.email,
                            'bg-light': !(is_processing || is_basic_information_succeeded),
                        }" :disabled="is_processing || is_basic_information_succeeded">
                        <span class="invalid-feedback" role="alert">{{ errors.email }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <button class="btn btn-primary" @click="updateInformation"
                                :disabled="is_processing || is_basic_information_succeeded">
                            <i class="fas fa-save mr-1"></i> Save Changes
                            <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
                        </button>
                        <p v-if="is_basic_information_succeeded" class="text-success font-weight-bold mb-0 mt-3">
                            <i class="fas fa-check-circle mr-2"></i> Updated successfully!</p>
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
                        }">Confirm Business Logo</h5>
                        <p v-if="image_error" class="font-weight-bold text-danger mb-0">{{ image_error }}</p>
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
import 'vue2-timepicker/dist/VueTimepicker.css';

import VueTimepicker from 'vue2-timepicker';

export default {
    props: {
        business_id: String
    },

    data() {
        return {
            business: {
                phone_number: null,
                currency_name: null,
                country: null,
                identifier: null,
                name: null,
                email: null,
                display_name: null,
                statement_description: null,
                introduction: null,
                logo_url: null,
                address: {
                    street: null,
                    city: null,
                    state: null,
                    postal_code: null,
                    country: null,
                    country_name: null
                }
            },

            image: null,
            image_canvas: null,
            image_error: null,
            image_modal: null,
            errors: {
                //
            },
            is_processing: false,
            is_processing_notification: false,
            is_basic_information_succeeded: false,
            is_identifier_succeeded: false,
            is_logo_succeeded: false,
            default_logo_url: null,
            logo_url: null,
            modal: null,
            prefixes: {
                checkout_url: null,
                shop_url: null,
            },
            reader: null,
        };
    },


    mounted() {
        this.prepareComponent();

        this.image_modal = $('#confirmImageModal');
        this.image_canvas = $('#imageCanvas');
        this.reader = new FileReader();

        this.reader.onload = (event) => {
            this.image_canvas.attr('src', this.reader.result);

            this.image_modal.modal('show');
        };

        this.image_modal.on('show.bs.modal', () => {
            // reader.readAsDataURL(this.image);
        }).on('hide.bs.modal', () => {
            this.image = null;
            this.image_error = null;
        });
    },

    methods: {
        prepareComponent() {
            this.getBusiness();
        },
        getBusiness() {
            axios.get(this.getDomain(`v1/business/${this.business_id}`, 'api'), {
                withCredentials: true
            })
                .then(response => {
                    this.business = response.data;

                    this.default_logo_url = response.data.default_logo_url;

                    if (response.data.logo_url) {
                        this.logo_url = response.data.logo_url;
                    } else {
                        this.logo_url = this.default_logo_url;
                    }
                });
        },
        updateInformation() {
            this.is_processing = true;
            this.errors = {};

            if (! /(^[A-Za-z0-9.\-\&\$ ]+$)+/.test(this.business.name)) {
                this.errors.name = 'Only chars and digits with spaces and dots are allowed in name';
            }

            if (this.business.phone_number == 0 || this.business.phone_number == null || this.business.phone_number == '') {
                this.errors.phone_number = 'The phone number is required.';
            } else if (this.business.phone_number.length > 14) {
                 this.errors.phone_number = 'The phone number may not be greater than 14 characters.';
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            axios.put(this.getDomain('v1/business/' + this.business.id, 'api'), this.business, {
                withCredentials: true
            }).then(({data}) => {
                this.business = data;

                this.is_processing = false;
                this.is_basic_information_succeeded = true;

                setTimeout(() => {
                    this.is_basic_information_succeeded = false;
                }, 5000);
            }).catch(({response}) => {
                if (response.status === 422) {
                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key.replace('.', '__')] = _.first(value);
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                }
            });
        },

        handleImage() {
            this.image = this.$refs.image.files[0];

            this.reader.readAsDataURL(this.image);
        },

        uploadImage() {
            this.is_processing = true;
            this.errors = {};

            let form = new FormData();

            form.append('image', this.image);

            axios.post(this.getDomain('v1/business/' + this.business.id + '/logo', 'api'), form, {
                withCredentials: true,
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then(({data}) => {
                this.is_processing = false;
                this.is_logo_succeeded = true;
                this.logo_url = data.logo_url;
                this.image_modal.modal('hide');

                setTimeout(() => {
                    this.is_logo_succeeded = false;
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
            axios.delete(this.getDomain('v1/business/' + this.business.id + '/logo', 'api'), {
                withCredentials: true
            }).then(({data}) => {
                this.image = null;
                this.logo_url = this.default_logo_url;
            }).catch(({error}) => {
                console.log(error);
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
