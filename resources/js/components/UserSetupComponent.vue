<template>
    <div>
        <form @submit.prevent="store">
            <div class="card-body bg-light p-4 border-top">
                <div class="form-row">
                    <div class="form-group col-md-9 col-lg-8">
                        <label for="display_name" class="small text-secondary">Display Name</label>
                        <input id="display_name" type="text" v-model="form.display_name" class="form-control" :class="{
                            'is-invalid': errors.display_name,
                        }" placeholder="Enter your full name" autocomplete="name" :disabled="is_processing || is_updated">
                        <span class="invalid-feedback" role="alert">{{ errors.display_name }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-9 col-lg-8">
                        <label for="email" class="small text-secondary">Email</label>
                        <input id="email" type="text" v-model="form.email" class="form-control" :class="{
                            'is-invalid': errors.email,
                        }" placeholder="Enter your email address" autocomplete="name" :disabled="is_processing || is_updated">
                        <span class="invalid-feedback" role="alert">{{ errors.email }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-9 col-lg-8">
                        <label for="password" class="small text-secondary">Password</label>
                        <input id="password" type="password" v-model="form.password" aria-label="Password" class="form-control" :class="{
                            'is-invalid': errors.password,
                        }" placeholder="Password" autocomplete="new-password" :disabled="is_processing">
                        <small v-if="!errors.password" class="form-text text-muted">The password must contain a minimum of 8 chracters, containing 1 upper case, 1 lower case, 1 number and 1 special character</small>
                        <span class="invalid-feedback" role="alert">{{ errors.password }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-9 col-lg-8">
                        <label for="password_confirmation" class="small text-secondary">Confirm Password</label>
                        <input id="password_confirmation" type="password" v-model="form.password_confirmation" aria-label="Password" class="form-control" :class="{
                            'is-invalid': errors.password_confirmation,
                        }" placeholder="Confirm your password" autocomplete="new-password" :disabled="is_processing">
                        <span class="invalid-feedback" role="alert">{{ errors.password }}</span>
                    </div>
                </div>
                <button type="submit" class="btn btn-success" :disabled="is_processing || is_updated">
                    Create New HitPay Login <i v-if="is_processing" class="fas fa-circle-notch fa-spin ml-2"></i>
                </button>
            </div>
        </form>
        <div id="successModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body py-5 text-center">
                        <img class="img-fluid mb-4" :src="getDomain('hitpay/logo-000036.png', 'dashboard')" title="HitPay" style="max-width: 180px">
                        <p class="h3 mb-4">Success!</p>
                        <p><i class="fas fa-check-circle fa-4x"></i></p>
                        <p>Your login details has been updated.</p>
                        <a class="btn btn-success btn-lg" :href="getDomain('/', 'dashboard')">Continue to HitPay</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                errors: {
                    //
                },
                form: {
                    display_name: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                },
                is_processing: false,
                is_destroying: false,
                is_updated: false,
                percent_completed: null,
            };
        },

        methods: {
            store() {
                this.is_processing = true;
                this.errors = {
                    //
                };

                if (!this.form.display_name) {
                    this.errors.display_name = 'The display name field is required';
                } else if (this.form.display_name.length > 255) {
                    this.errors.display_name = 'The display name may not be greater than 255 characters.';
                }

                if (!this.form.email) {
                    this.errors.email = 'The email field is required';
                } else if (!this.validateEmail(this.form.email)) {
                    this.errors.email = 'The email field must be a valid email address.';
                } else if (this.form.email.length > 255) {
                    this.errors.email = 'The email may not be greater than 255 characters.';
                }

                if (!this.form.password) {
                    this.errors.password = 'The password field is required';
                } else if (!this.form.password.match(/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,}$/)) {
                    this.errors.password = 'The password must contain a minimum of 8 chracters, containing 1 upper case, 1 lower case, 1 number and 1 special character';
                } else if (this.form.password !== this.form.password_confirmation) {
                    this.errors.password = 'The password and confirmation doesn\'t match';
                }

                if (Object.keys(this.errors).length > 0) {
                    this.showError(_.first(Object.keys(this.errors)));
                } else {
                    axios.post(this.getDomain('user/setup', 'dashboard'), this.form).then(({data}) => {
                        $('#successModal').modal('show')
                    }).catch(({response}) => {
                        _.each(response.data.errors, (value, key) => {
                            this.errors[key] = _.first(value);
                        });

                        this.showError(_.first(Object.keys(this.errors)));
                    });
                }
            },

            showError(firstErrorKey) {
                if (firstErrorKey !== undefined) {
                    this.scrollTo('#' + firstErrorKey);
                }

                this.is_processing = false;
            },
        },
    }
</script>
