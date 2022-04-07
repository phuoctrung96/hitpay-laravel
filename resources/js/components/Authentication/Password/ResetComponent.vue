<template>
    <form role="form" @submit.prevent="reset">
        <div class="form-group">
            <input id="email" type="text" v-model="form.email" class="form-control" :class="{
                'is-invalid' : errors.email,
                'bg-light': !is_processing,
            }" placeholder="Enter your email" aria-label="Email" autocomplete="email" :disabled="is_processing">
            <span class="invalid-feedback" role="alert">{{ errors.email }}</span>
        </div>
        <div class="form-group">
            <input id="password" type="password" v-model="form.password" class="form-control" :class="{
                'is-invalid': errors.password,
                'bg-light': !is_processing,
            }" placeholder="Enter your new password" aria-label="New Password" autocomplete="new-password" :disabled="is_processing">
            <span class="invalid-feedback" role="alert">{{ errors.password }}</span>
        </div>
        <div class="form-group">
            <input id="password_confirmation" type="password" v-model="form.password_confirmation" class="form-control" :class="{
                'is-invalid': errors.password_confirmation,
                'bg-light': !is_processing,
            }" placeholder="Confirm your new password" aria-label="Confirm New Password" autocomplete="new-password" :disabled="is_processing">
            <span class="invalid-feedback" role="alert">{{ errors.password_confirmation }}</span>
        </div>
        <div class="form-group mb-0">
            <button type="submit" class="btn btn-block btn-dark" :disabled="is_processing">
                Reset <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
            </button>
        </div>
    </form>
</template>

<script>
    export default {
        data() {
            return {
                errors: {
                    //
                },
                form: {
                    token: _.last(_.split(window.location.pathname, '/')),
                    email: (new URLSearchParams(window.location.search)).get('email'),
                    password: '',
                    password_confirmation: '',
                },
                is_processing: false,
            };
        },

        mounted() {
            if (this.form.email) {
                $('#password').focus();
            } else {
                $('#email').focus();
            }
        },

        methods: {
            reset() {
                this.errors = {
                    //
                };

                this.is_processing = true;

                axios.post(this.getDomain('password/reset', 'dashboard'), this.form).then(({data}) => {
                    window.location.href = data.redirect_url;
                }).catch(({response}) => {
                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                });
            },

            showError(firstErrorKey) {
                if (firstErrorKey !== undefined) {
                    this.scrollTo('#' + firstErrorKey, 48, 0);

                    $('#' + firstErrorKey).focus();
                }

                this.is_processing = false;
            },
        },
    }
</script>
