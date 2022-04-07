<template>
    <div class="row">
        <div class="card-body border-top p-4">
            <p class="text-uppercase text-muted">Store URL</p>
            <template v-if="business.identifier">
                <p>Your customer can reach your home page via following URLs:</p>
                <ul>
                    <li>
                        <a target="_blank" :href="prefixes.shop_url + business.identifier">{{ prefixes.shop_url + business.identifier }}</a>
                    </li>
                </ul>
                <p v-if="is_identifier_succeeded" class="text-success font-weight-bold">
                    <i class="fas fa-check-circle mr-2"></i> Updated successfully!</p>
                <a v-else class="font-weight-bold" href="#" @click.prevent="openSetIdentifierModal">Update Store URL</a>
            </template>
            <template v-else>
                <p>Setup a homepage URL so that the customers can reach the business easily.</p>
                <button class="btn btn-primary btn-sm" @click="openSetIdentifierModal" :disabled="is_processing">
                    <i class="fas fa-link mr-1"></i> Setup Store URL
                </button>
            </template>
        </div>
<!--        Uncomment when the issue with url validation will be gone-->
<!--        <div class="card-body border-top bg-light p-4">-->
<!--            <p class="text-uppercase text-muted">Instagram Shopping / Facebook Catalogue Data Feed URL</p>-->
<!--            <div class="form-row col-sm-12 col-lg-6">-->
<!--                <div class="form-group">-->
<!--                    <label class="d-inline-flex mb-1" for="facebookProductFeedUrl">-->
<!--                        <input type="text" disabled ref="fbProductFeedUrl" id="facebookProductFeedUrl" v-model="fb_feed_url" class="form-control col-sm-12" >-->
<!--                        <button @click="handleURLBtnClick" class="btn btn-primary btn-sm" :disabled="is_generating" style="line-height: 2em;">-->
<!--                            {{has_fb_feed_url?'Copy':'Generate'}} </button>-->
<!--                    </label>-->
<!--                    <span class="invalid-feedback" v-if="errors.fb_feed_url" role="alert">{{ errors.fb_feed_url }}</span>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
        <div id="setIdentifierModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <h5 class="modal-title mb-3">
                            Set Homepage URL
                        </h5>
                        <p :class="{
                            'mb-0': !identifier_error,
                            'mb-3': identifier_error,
                        }">Setup a homepage URL so that the customers can reach the business easily.</p>
                        <p v-if="identifier_error" class="font-weight-bold text-danger mb-0">{{ identifier_error }}</p>
                    </div>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text border-left-0 rounded-0">{{ prefixes.shop_url }}</div>
                        </div>
                        <input id="identifier" v-model="identifier_input" type="text" class="form-control border-right-0 rounded-0" placeholder="yourshopname" aria-label="Identifier (Homepage URL)" :class="{
                            'is-invalid': identifier_error,
                            'bg-light': !is_processing,
                        }" :disabled="is_processing">
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" :disabled="is_processing">Close</button>
                        <button type="button" class="btn btn-primary" @click="updateIdentifier" :disabled="is_processing">
                            <i class="fas fa-save mr-1"></i> Save <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "ProductURLSetting",
        data: () => {
            return {
                is_identifier_succeeded: false,
                business: {
                    country_name: null,
                    street: null,
                    city: null,
                    state: null,
                    postal_code: null,
                    can_pick_up: false,
                    phone_number: null,
                    currency_name: null,
                    identifier: null,
                    name: null,
                    display_name: null,
                    statement_description: null,
                },
                prefixes: {
                    checkout_url: null,
                    shop_url: null,
                },

                errors: {
                    //
                },
                identifier_input: null,
                identifier_error: null,
                is_processing: false,
                is_generating: false,
                has_fb_feed_url: false,
                fb_feed_url: null,
                reader: null,
            }
        },
        mounted() {
            this.business = window.Business;

            this.default_logo_url = Data.default_logo_url;
            if (this.business.fb_feed_slot)
            {
                this.has_fb_feed_url = true
            }
            if (Data.fb_feed_url)
            {
                this.fb_feed_url = Data.fb_feed_url
            }
            this.prefixes = Data.prefixes;

            this.modal = $('#setIdentifierModal');

            this.modal.on('show.bs.modal', () => {
                if (this.business.identifier) {
                    this.identifier_input = this.business.identifier;
                }else{
                    this.identifier_input = this.business.slug;
                }
            }).on('hide.bs.modal', () => {
                this.is_processing = false;
                this.identifier_error = null;
            });
        },
        methods: {
            openSetIdentifierModal() {
                this.modal.modal('show');
            },
            handleURLBtnClick()
            {
                if (!this.fb_feed_url)
                {
                    this.generateFBFeedURL();
                }
                else {
                    let fbFeedURLInput = document.querySelector('#facebookProductFeedUrl')
                    fbFeedURLInput.removeAttribute('disabled')
                    fbFeedURLInput.select()
                    try {
                        document.execCommand('copy');
                        alert('Successfully copied' );
                    } catch (err) {
                        alert('Oops, unable to copy');
                    }
                    fbFeedURLInput.setAttribute('disabled', true)
                    window.getSelection().removeAllRanges()
                }
            },
            generateFBFeedURL()
            {

                this.is_generating = true
                axios.get(this.getDomain('business/' + this.business.id + '/setting/basic-details/fb-feed-url', 'dashboard'), this.business).then(({data}) => {
                    this.is_generating = false;
                    if (data.success)
                    {
                        this.has_fb_feed_url = true;
                        this.fb_feed_url = data.feed_url
                    }
                }).catch(({response}) => {
                    this.is_generating = false;
                    this.errors.fb_feed_url = 'Something went wrong, please try again later'
                });
            },
            updateIdentifier() {
                this.is_processing = true;
                this.identifier_error = null;

                if (! /(^[A-Za-z0-9]+$)+/.test(this.identifier_input)) {
                    this.identifier_error = 'Only chars and digits are allowed in name';
                    this.is_processing = false;
                    return;
                }

                axios.put(this.getDomain('business/' + this.business.id + '/basic-details/identifier', 'dashboard'), {
                    identifier: this.identifier_input,
                }).then(({data}) => {
                    this.business.identifier = data.identifier;

                    this.modal.modal('hide');

                    this.is_identifier_succeeded = true;

                    setTimeout(() => {
                        this.is_identifier_succeeded = false;
                    }, 5000);
                }).catch(({response}) => {
                    if (response.status === 422) {
                        this.is_processing = false;

                        _.forEach(response.data.errors, (value, key) => {
                            this.identifier_error = _.first(value);

                            return false;
                        });
                    }
                });
            },
        }
    }
</script>

<style scoped>

</style>
