<style scoped>
    .qr-code {
        max-width: 256px;
    }
</style>

<template>
    <div id="enablingModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" :class="{ 'border-bottom-0' : step === 1 && message }">
                    <h5 class="modal-title font-weight-bold">Step {{ step === null ? 1 : step }} of 2</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" :disabled="is_processing">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div v-if="step === 1 && message" class="alert alert-danger border-y rounded-0 p-card-cap px-xs-4">
                    {{ message }}
                </div>
                <div v-if="step === null" class="modal-body text-center">
                    <i class="fas fa-spinner fa-3x fa-spin text-muted"></i>
                </div>
                <div v-if="step === 1" class="modal-body">
                    <p>Please use your authentication app (such as Google Authenticator) to scan this QR code.</p>
                    <div class="text-center">
                        <img :src="secret.qr_code" class="img-fluid card-border rounded qr-code mb-3">
                        <p>
                            <span class="small text-muted">Or enter this code manually</span><br><strong class="text-monospace">{{ secret.string }}</strong>
                        </p>
                        <button type="button" class="btn btn-primary" @click="goToStep(2)">Continue</button>
                    </div>
                </div>
                <div v-if="step === 2" class="modal-body">
                    <p>Please enter the code you see on your authentication app.</p>
                    <div class="form-group">
                        <input v-model="form.auth_code" type="password" class="form-control bg-light" placeholder="Authentication code" :class="{ 'is-invalid' : auth_code_error }" :disabled="is_processing">
                        <span v-if="auth_code_error" class="invalid-feedback" role="alert">{{ auth_code_error }}</span>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-link" @click="goToStep(1)" :disabled="is_processing">Back</button>
                        <button type="button" class="btn btn-primary" @click="enableTwoFactorAuth" :disabled="is_processing">
                            Verify <i v-if="is_processing" class="fas fa-circle-notch fa-spin text-white"></i>
                        </button>
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
                step: null,
                is_processing: false,
                message: null,
                auth_code_error: null,
                secret: {
                    string: null,
                    qr_code: null,
                },
                form: {
                    secret: null,
                    auth_code: null,
                },
            };
        },

        ready() {
            this.prepareComponent();
        },

        mounted() {
            this.prepareComponent();
        },

        methods: {
            prepareComponent() {
                $('#enablingModal').on('show.bs.modal', () => {
                    this.loadSecret();
                });
            },

            goToStep(step) {
                this.step = step;
                this.is_processing = false;
                this.message = null;
                this.auth_code_error = null;
                this.form.auth_code = null;
            },

            enableTwoFactorAuth() {
                this.is_processing = true;
                this.auth_code_error = null;

                if (!this.form.auth_code) {
                    this.auth_code_error = 'The authentication code field is required.';
                } else if (!/^\d{6}$/.test(this.form.auth_code)) {
                    this.auth_code_error = 'The authentication code must be 6 digits.';
                }

                if (this.auth_code_error) {
                    this.is_processing = false;

                    return;
                }

                axios.post(this.getDomain('user/security/auth-secret', 'dashboard'), this.form).then(({data}) => {
                    window.location.href = data.redirect_url;
                }).catch(({response}) => {
                    this.is_processing = false;

                    if (response.status === 400) {
                        this.message = response.data.message;
                        this.form.auth_code = null;

                        this.loadSecret();
                    } else if (response.status === 422) {
                        _.forEach(response.data.errors, value => {
                            this.auth_code_error = _.first(value);

                            return false;
                        });
                    }
                });
            },

            loadSecret() {
                this.step = null;

                axios.post(this.getDomain('user/security/auth-secret/code', 'dashboard')).then(({data}) => {
                    this.step = 1;

                    this.form.secret = data.secret.string;

                    this.secret.string = data.secret.string.match(/.{1,4}/g).join(' ');
                    this.secret.qr_code = data.secret.qr_code;
                });
            },
        },
    }
</script>
