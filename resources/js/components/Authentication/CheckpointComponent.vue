<template>
    <form role="form" @submit.prevent="login">
        <div class="form-group">
            <input type="password" v-model="form.password" class="form-control" :class="{
                'is-invalid': errors.password,
                'bg-light': !is_processing,
            }" placeholder="One Time Password" aria-label="One Time Password" autocomplete="current-password" :disabled="is_processing">
            <span class="invalid-feedback" role="alert">{{ errors.password }}</span>
        </div>
        <div class="form-group mb-0">
            <button type="submit" class="btn btn-block btn-success" :disabled="is_processing">
                Continue <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
            </button>
        </div>
    </form>
</template>

<script>
    export default {
        props: {
          token: String
        },
        data() {
            return {
                errors: {
                    //
                },
                form: {
                    password: ''
                },
                is_processing: false,
            };
        },

        methods: {
            login() {
                this.errors = {
                    //
                };

                this.is_processing = true;

                if (!this.form.password) {
                    this.errors.password = 'The password field is required.';
                }

                if (Object.keys(this.errors).length > 0) {
                    this.displayError();
                } else {
                    axios.post(this.getDomain('checkpoint', 'dashboard'), {
                      ...this.form,
                      authentication_token: this.token
                    }).then(({data}) => {
                        window.location.href = data.redirect_url;
                    }).catch(({response}) => {
                        console.log(response);
                        if (response.status === 422) {
                            _.forEach(response.data.errors, (value, key) => {
                                this.errors[key] = _.first(value);
                            });

                            this.displayError();
                        }
                    });
                }
            },

            displayError() {
                this.is_processing = false;

                this.scrollTo('#firstInput', 48, 0);
            },
        },
    }
</script>
