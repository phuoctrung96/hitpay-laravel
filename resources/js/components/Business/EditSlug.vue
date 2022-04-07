<template>
    <div>
        <button class="btn btn-primary ml-3" data-toggle="modal"
                data-target="#slugModal">Edit
        </button>
        <div class="modal fade" id="slugModal" tabindex="-1" role="dialog"
             aria-labelledby="addTaxModalLabel"
             aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">Edit Default Link</h5>
                        <button id="closeBtn" type="button" class="close" data-dismiss="modal"
                                aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="slug" class="small">{{ defaultPaymentLink }}</label>
                            <input id="slug" type="text" class="form-control bg-light" title="Slug" name="slug"
                                   v-model="slug">
                        </div>
                        <p class="invalid-feedback d-block" role="alert" v-if="errors.slug">{{ errors.slug }}</p>
                        <p class="small"><span class="text-danger">Warning:</span> Changing the URL will break existing
                            payment links. Allowed characters "a to z" , "0 to 9" and "-"</p>
                        <div class="text-right">
                            <button id="addBtn" @click="saveSlug()" class="btn btn-primary">
                                Change
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "EditSlug",
    props: {
        business: {},
    },
    data: () => {
        return {
            payment_url: '',
            slug: '',
            errors: {
                slug: '',
            }
        }
    },
    mounted() {
        this.payment_url = this.getDomain('business/' + Business.id + '/payment-request/', 'securecheckout');
        this.slug = this.business.slug;
    },
    methods: {
        saveSlug() {
            axios.post(this.getDomain('business/' + Business.id + '/basic-details/slug', 'dashboard'), {'slug': this.slug}).then(({data}) => {
                window.location.reload();
            }).catch(({response}) => {
                if (response.status === 422) {
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
            this.is_busy = false;
        },
    },
    computed: {
        defaultPaymentLink() {
            return this.payment_url + this.slug;
        }
    }
}
</script>
