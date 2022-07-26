<template>
    <div>
        <div class="card shadow-sm mb-3">
            <div class="card-body border-top p-4">
                <p class="text-uppercase text-muted">Business Settings</p>
                <div class="form-row">
                    <div class="col-12 col-md-6 mb-3">
                        <p class="text-uppercase mb-2">Point of Sale Settings</p>
                        <div class="custom-control custom-switch">
                            <input id="switch-pos-settings"
                                   v-model="business_settings['point_of_sales_remark']"
                                   type="checkbox"
                                   class="custom-control-input"
                                   :disabled="is_processing"
                            >
                            <label v-if="business_settings['point_of_sales_remark']"
                                   for="switch-pos-settings" class="custom-control-label">
                                Point of Sale Remark is mandatory
                            </label>
                            <label v-if="!business_settings['point_of_sales_remark']"
                                   for="switch-pos-settings" class="custom-control-label">
                                Point of Sale Remark is optional
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <button class="btn btn-primary" @click="update"
                                :disabled="is_processing_business_settings || is_processing_business_settings_succeeded">
                            <i class="fas fa-save mr-1"></i> Update
                            <i v-if="is_processing_business_settings" class="fas fa-spinner fa-spin"></i>
                        </button>
                        <p v-if="is_processing_business_settings_succeeded" class="text-success font-weight-bold mb-0 mt-3">
                            <i class="fas fa-check-circle mr-2"></i> Updated successfully!</p>
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
            business_settings: {

            },
            errors: {
                //
            },
            is_processing: false,
            is_processing_business_settings: false,
            is_processing_business_settings_succeeded: false,
            is_initial_business_settings: true,
        };
    },

    mounted() {
        this.business = Business;

        this.getBusinessSettings();
    },

    methods: {
        getBusinessSettings() {
            let that = this;

            axios.get(this.getDomain('business/' + this.business.id + '/setting', 'dashboard')).then(({data}) => {
                data.map(function(item) {
                    that.business_settings = {
                        [item.key]: item.value,
                    };
                });

                this.is_processing_business_settings = false;
                this.is_processing_business_settings_succeeded = false;
            });
        },

        update() {
            this.is_processing_business_settings = true;

            let that = this;

            axios.post(this.getDomain('business/' + this.business.id + '/setting/', 'dashboard'), {
                'key': 'point_of_sales_remark',
                'value': that.business_settings['point_of_sales_remark'],
            }).then(({data}) => {
                data.map(function(item) {
                    that.business_settings = {
                        [item.key]: item.value,
                    };
                });

                this.is_processing_business_settings = false;
                this.is_processing_business_settings_succeeded = true;
            });

            setTimeout(() => {
                this.is_processing_business_settings_succeeded = false;
            }, 4000);
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
