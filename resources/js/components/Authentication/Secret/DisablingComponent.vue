<template>
    <div id="disablingModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header px-xs-4">
                    <h5 class="modal-title font-weight-bold text-danger">Ouch!</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" :disabled="is_disabling">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-xs-4">
                    <p>This extra level of security is designed to protect your information. Are you sure you want to disable it?</p>
                    <div class="text-right">
                        <button type="button" class="btn btn-light" data-dismiss="modal" :disabled="is_disabling">Cancel</button>
                        <button type="submit" class="btn btn-danger" @click="disableTwoFactorAuth" :disabled="is_disabling">
                            Disable <i v-if="is_disabling" class="fas fa-circle-notch fa-spin text-white"></i>
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
                is_disabling: false,
            };
        },

        methods: {
            disableTwoFactorAuth() {
                this.is_disabling = true;

                axios.delete(this.getDomain('user/security/auth-secret', 'dashboard')).then(({data}) => {
                    window.location.href = data.redirect_url;
                });
            },
        },
    }
</script>
