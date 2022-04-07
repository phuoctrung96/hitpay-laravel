<template>
    <div id="formTop">
        <div class="alert alert-success border-right-0 border-left-0 rounded-0 px-4 py-2 mb-0" v-if="success_message !== null">
            {{ success_message }}
        </div>
        <form @submit.prevent="store" class="card-body bg-light p-4" :class="{
            'border-top': success_message === null,
        }">
            <div class="form-group">
                <label for="currentPassword" class="small text-secondary">Current Password</label>
                <input id="currentPassword" type="password" v-model="form.current_password" class="form-control" :class="{
                    'is-invalid': errors.current_password,
                }" autocomplete="current-password" autofocus :disabled="is_processing">
                <span class="invalid-feedback" role="alert">{{ errors.current_password }}</span>
            </div>
            <div class="form-row">
                <div class="form-group col-12 col-md">
                    <label for="newPassword" class="small text-secondary">New Password</label>
                    <input id="newPassword" type="password" v-model="form.new_password" class="form-control" :class="{
                        'is-invalid': errors.new_password,
                    }" autocomplete="new-password" :disabled="is_processing">
                    <span class="invalid-feedback" role="alert">{{ errors.new_password }}</span>
                </div>
                <div class="form-group col-12 col-md">
                    <label for="newPasswordConfirmation" class="small text-secondary">Confirm New Password</label>
                    <input id="newPasswordConfirmation" type="password" v-model="form.new_password_confirmation" class="form-control" autocomplete="new-password" :disabled="is_processing">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="is_processing">
                Update <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
            </button>
        </form>
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
                    current_password: null,
                    new_password: null,
                    new_password_confirmation: null,
                },
                is_processing: false,
                success_message: null,
            };
        },

        methods: {
            store() {
                this.errors = {
                    //
                };

                this.is_processing = true;
                this.success_message = null;

                axios.put(this.getDomain('user/security/password', 'dashboard'), this.form).then(({data}) => {
                    this.form = {
                        current_password: null,
                        new_password: null,
                        new_password_confirmation: null,
                    };

                    this.success_message = data.message;

                    this.endProcessing('#formTop');
                }).catch(({response}) => {
                    _.each(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });

                    this.endProcessing();
                });
            },

            endProcessing(destination = '#currentPassword') {
                this.is_processing = false;

                this.scrollTo(destination);
            },
        }
    }
</script>
