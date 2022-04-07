<template>
    <div>
        <form @submit.prevent="store">
            <div class="card-body bg-light p-4 border-top">
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        <label for="first_name" class="small text-secondary">First Name</label>
                        <input id="first_name" type="text" v-model="form.first_name" class="form-control" :class="{
                            'is-invalid': errors.first_name,
                        }" placeholder="First Name / Given Name" autocomplete="name" :disabled="is_processing || is_updated">
                        <span class="invalid-feedback" role="alert">{{ errors.first_name }}</span>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="last_name" class="small text-secondary">Last Name</label>
                        <input id="last_name" type="text" v-model="form.last_name" class="form-control" :class="{
                            'is-invalid': errors.last_name,
                        }" placeholder="Last Name / Family Name" autocomplete="name" :disabled="is_processing || is_updated">
                        <span class="invalid-feedback" role="alert">{{ errors.last_name }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-12">
                        <label for="display_name" class="small text-secondary">Display Name</label>
                        <input id="display_name" type="text" v-model="form.display_name" class="form-control" :class="{
                            'is-invalid': errors.display_name,
                        }" placeholder="Enter your full name" autocomplete="name" :disabled="is_processing || is_updated">
                        <span class="invalid-feedback" role="alert">{{ errors.display_name }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-12">
                        <label for="email" class="small text-secondary">Email Address</label>
                        <input id="email" type="text" v-model="form.email" class="form-control" disabled>
                    </div>
                </div>
                <button type="submit" class="btn btn-success" :disabled="is_processing || is_updated">
                    <i class="fas fa-save mr-2"></i>
                    <template v-if="is_processing">
                        Saving <i class="fas fa-circle-notch fa-spin ml-2"></i>
                    </template>
                    <template v-else-if="is_updated">
                        Saved <i class="fas fa-check-circle ml-2"></i>
                    </template>
                    <template v-else>Save</template>
                </button>
            </div>
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
                    first_name: null,
                    last_name: null,
                    display_name: null,
                    email: null,
                },
                is_processing: false,
                is_destroying: false,
                is_updated: false,
                percent_completed: null,
            };
        },

        mounted() {
            this.form.first_name = User.first_name;
            this.form.last_name = User.last_name;
            this.form.display_name = User.display_name;
            this.form.email = User.email;
        },

        methods: {
            store() {
                this.errors = {
                    //
                };

                this.is_processing = true;

                axios.put(this.getDomain('user/profile', 'dashboard'), this.form).then(({data}) => {
                    this.form.first_name = data.first_name;
                    this.form.last_name = data.last_name;
                    this.form.display_name = data.display_name;
                    this.form.email = data.email;

                    this.is_processing = false;
                    this.is_updated = true;

                    setTimeout(() => {
                        this.is_updated = false;
                    }, 1500);
                }).catch(({response}) => {
                    _.each(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                });
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
