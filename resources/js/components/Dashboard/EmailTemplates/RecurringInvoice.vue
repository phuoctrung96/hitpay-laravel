<template>
    <div class="content d-flex flex-row">
        <div class="content__input w-50">
            <div class="content__input-wrapper">
                <div>
                    <h6 class="content__input-label small">Business Logo</h6>
                    <div>
                        <label class="d-inline-flex mb-1" for="productImage">
                            <input ref="image" v-on:change="uploadImage" type="file" id="productImage" class="small custom-file-input d-none" accept="image/*" multiple="multiple">
                            <span id="uploadBtn" class="btn">
                                <span v-if="is_processing" class="spinner-border spinner-border-sm text-light" role="status">
                                    <span class="sr-only">Loading...</span>
                                </span>
                                <i v-else class="fas fa-folder-open pr-1"></i> 
                                Choose image
                            </span>
                        </label>
                    </div>
                    <p class="small text-secondary mt-2" style="width:72%">The optimal product image size is at least 800x800 px.PNG and JPG format is supported.</p>
                </div>
                <div>
                    <h6 class="content__input-label small">Email Subject</h6>
                    <input v-model="template.email_subject" type="text" class="form-control medium  p-2" :placeholder="`New Recurring Payment Invoice from {{business_name}}`"/>
                </div>
                <div class="mt-3">
                    <h6 class="content__input-label small">Title</h6>
                    <input type="text" class="form-control medium  p-2" v-model="template.title" :placeholder="`{{business_name}}`"/>
                </div>
                <div class="mt-3">
                    <h6 class="content__input-label small">Subtitle</h6>
                    <input type="text" class="form-control medium  p-2" v-model="template.subtitle" placeholder="View Transaction details below"/>
                </div>
                <div class="mt-3">
                    <h6 class="content__input-label small">Footer</h6>
                    <vue-editor :editorToolbar="customToolbar" v-model="template.footer"></vue-editor>
                </div>
                <div class="mt-3">
                    <h6 class="content__input-label small">Button Text</h6>
                    <input type="text" class="form-control medium  p-2" v-model="template.button_text" :placeholder="`New Recurring Payment Invoice from {{business_name}}`"/>
                </div>
                <div class="mt-3 d-flex flex-row justify-content-between">
                    <div class="mr-2 w-50">
                        <h6 class="content__input-label small">Background color</h6>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text pl-4 pr-4" :style="{background: template.button_background_color}" id="basic-addon1">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M15.3624 0.57668C16.2074 1.38005 16.2074 2.68911 15.3925 3.49249L13.1592 5.75365L13.642 6.22968C13.8835 6.4677 13.9136 6.85444 13.6722 7.09246L12.4951 8.28253C12.2537 8.52055 11.8311 8.52055 11.5897 8.28253L9.62785 6.34849L7.63591 4.44437C7.39448 4.20635 7.39448 3.78973 7.63591 3.55171L8.78282 2.39134C9.02425 2.15332 9.44686 2.15332 9.68829 2.39134L10.1712 2.86737L12.4347 0.606202C13.2194 -0.197172 14.5475 -0.196985 15.3624 0.57668ZM10.5032 8.10415L6.6698 11.9537L5.58334 13.0248V13.0545L3.53114 15.0776L4.70322e-05 16L0.845081 12.4891L3.04832 10.2874L3.98393 9.36501L7.84743 5.48576L10.5032 8.10415Z" fill="#D0D5DD"/>
                                    </svg>
                                </span>
                            </div>
                            <input type="color" v-model="template.button_background_color" :data-color="template.button_background_color" class="form-control color-picker" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1">
                        </div>
                    </div>
                    <div class="ml-2 w-50">
                        <h6 class="content__input-label small">Text color</h6>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text pl-4 pr-4" :style="{background: template.button_text_color}" id="basic-addon1">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M15.3624 0.57668C16.2074 1.38005 16.2074 2.68911 15.3925 3.49249L13.1592 5.75365L13.642 6.22968C13.8835 6.4677 13.9136 6.85444 13.6722 7.09246L12.4951 8.28253C12.2537 8.52055 11.8311 8.52055 11.5897 8.28253L9.62785 6.34849L7.63591 4.44437C7.39448 4.20635 7.39448 3.78973 7.63591 3.55171L8.78282 2.39134C9.02425 2.15332 9.44686 2.15332 9.68829 2.39134L10.1712 2.86737L12.4347 0.606202C13.2194 -0.197172 14.5475 -0.196985 15.3624 0.57668ZM10.5032 8.10415L6.6698 11.9537L5.58334 13.0248V13.0545L3.53114 15.0776L4.70322e-05 16L0.845081 12.4891L3.04832 10.2874L3.98393 9.36501L7.84743 5.48576L10.5032 8.10415Z" fill="#D0D5DD"/>
                                    </svg>
                                </span>
                            </div>
                            <input type="color" v-model="template.button_text_color" :data-color="template.button_text_color" class="form-control color-picker" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1">
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-row justify-content-between">
                    <button :disabled="saving" id="customBtnSave" class="btn btn-primary" @click="save">
                        <span v-show="saving" class="spinner-border spinner-border-sm text-light" role="status">
                            <span class="sr-only">Loading...</span>
                        </span>
                        <span>Save Changes</span>
                    </button>
                    <button :disabled="saving" id="customBtnRevert" class="btn btn-primary" @click="reset">
                        <span v-show="saving" class="spinner-border spinner-border-sm text-light" role="status">
                            <span class="sr-only">Loading...</span>
                        </span>
                        <span>Revert to default</span>
                    </button>
                </div>
            </div>
            <div class="mt-4 content__input-footer">
                <h6 class="content__input-label">Available variables</h6>
                <ul class="row">
                    <li class="col-sm-6">{{`${'{{business_name}'}`}}}</li>
                    <li class="col-sm-6">{{`${'{{charge_id}'}`}}}</li>
                    <li class="col-sm-6">{{`${'{{business_email}'}`}}}</li>
                    <li class="col-sm-6">{{`${'{{charge_date}'}`}}}</li>
                    <li class="col-sm-6">{{`${'{{business_phone}'}`}}}</li>
                </ul>
            </div>
        </div>
        <div class="content__preview w-50">
            <h6 class="content-input__label">
                <span>Preview</span>
                <span>
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="22" height="22" rx="11" fill="#0058FC"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12.4823 15.5401C12.4823 16.3463 11.8287 17 11.0224 17C10.2161 17 9.5625 16.3463 9.5625 15.5401C9.5625 14.7337 10.2161 14.0801 11.0224 14.0801C11.8287 14.0801 12.4823 14.7337 12.4823 15.5401Z" fill="white"/>
                    <path d="M7.00004 8.38275L7.14251 7.91985C7.60541 6.13944 9.1722 5 10.9881 5C13.1601 5 15.0475 6.70918 15.0475 8.91692C15.0475 10.6973 13.8368 12.1929 12.1632 12.6558V13.4392H9.84872V12.0504C9.84872 11.267 10.4184 10.5905 11.2018 10.5193C12.0565 10.4125 12.7686 9.77153 12.7686 8.91692C12.7686 7.95549 11.914 7.31456 10.9882 7.31456C10.2048 7.31456 9.56387 7.74187 9.35025 8.52527L9.20778 8.98817L7 8.38281L7.00004 8.38275Z" fill="white"/>
                    </svg>
                </span>
            </h6>
            <div class="content__preview-badge mt-4">
                <h6 class="mb-0"><span style="font-weight:400" class="text-secondary pr-2">Subject:</span>{{previewSubject}}</h6>
            </div>
            <div class="content__preview-card mt-2">
                <img class="mb-3" :src="business_logo" v-if="business_logo" style="width:150px;height:70px; object-fit:contain"/>
                <h6>{{previewTitle}}</h6>

                <div class="mt-3">
                    <h6 class="small text-secondary">{{previewSubtitle}}</h6>

                    <div class="description"> 
                        <ul class="d-flex flex-row justify-content-between">
                            <li>Plan name</li>
                            <li>Apple Subscription</li>
                        </ul>
                        <ul class="d-flex flex-row justify-content-between mt-2">
                            <li>Recurring plan</li>
                            <li>#98580-00ca-099a</li>
                        </ul>
                        <ul class="d-flex flex-row justify-content-between mt-2">
                            <li>Start date</li>
                            <li>11.06.2022</li>
                        </ul>
                        <div class="mt-4 mb-4">
                            <h6 class="small text-bold" style="font-weight:500">Description of the recurring</h6>
                        </div>
                        <ul class="d-flex flex-row justify-content-between mt-2">
                            <li>Customer Name</li>
                            <li>Anna</li>
                        </ul>
                        <ul class="d-flex flex-row justify-content-between mt-2">
                            <li>Customer Email</li>
                            <li>anna@outlook.com</li>
                        </ul>
                        <ul class="d-flex flex-row justify-content-between mt-2">
                            <li>Billing Cycle</li>
                            <li>Weekly</li>
                        </ul>
                        <ul class="d-flex flex-row justify-content-between mt-2">
                            <li>Amount Due</li>
                            <li>SGD 100.00</li>
                        </ul>
                    </div>

                    <div class="content__preview-footer d-flex flex-row justify-content-center mt-5">
                        <button id="customBtnSave" :style="{background: previewButtonBackgroundColor, color: previewButtonColor}" class="btn btn-primary">{{ previewButtonText }}</button>
                    </div>

                    <div class="footer mt-5">
                        <h6 class="text-center" v-html="previewFooter"></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import moment from "moment"
export default {
    props: ["business", "emailTemplate"],
    data() {
        return {
            saving: false,
            business_logo: null,
            is_processing: false,
            is_saved: false,
            image:"",
            template: {
                email_subject: '',
                title: '',
                subtitle: '',
                footer: '',
                template_for: 'order_confirmation_template',
                seller_info: '',
                button_text:'',
                button_text_color: '#FFFFFF',
                button_background_color: '#002771'
            },
            customToolbar: [
                [{ header: [false, 1, 2, 3, 4, 5, 6] }],
                ["bold", "italic", "underline"],
                [{ color: [] }, { background: [] }],
                ["link"]
            ]
        }
    },
    methods: {
        parseContentWithVariables(previewContent) {
            let business_name = JSON.parse(this.business)?.name;
            let business_phone = JSON.parse(this.business)?.phone_number;
            let business_email = JSON.parse(this.business)?.email;
            let charge_id = "1020aa-399da-4532-c558";
            let charge_date = moment().format("MMMM Do, YYYY");

            let content = previewContent
                                .replace("{{business_name}}", business_name)
                                .replace("{{business_phone}}",business_phone)
                                .replace("{{business_email}}",business_email)
                                .replace("{{charge_id}}", charge_id)
                                .replace("{{charge_date}}", charge_date)
            return content;
        },
        reset() {
            this.$emit("eventResponse", "")
            this.saving = true;

            let businessId = JSON.parse(this.business)?.id

            let url = `v1/business/${businessId}/email-templates/reset-default`;
            
            axios.put(this.getDomain(url, "api"), {
                template_for: 'recurring_invoice_template'
            },{withCredentials: true}).then(s => {
                this.$emit("eventResponse", "Email template has been set to default!");

                const { recurring_invoice_template } = s.data;
                this.template =recurring_invoice_template;
                this.template['template_for'] = 'recurring_invoice_template'
                this.template.seller_info = this.businessFormatted.name + ' (' + this.businessFormatted.email + ')'

            }).catch(err => {
                this.$emit("eventResponse", err.message);
            })
            .finally(() => {
                this.saving = false;
                window.scrollTo(0,0);
            })
        },
        save() {
            this.$emit("eventResponse", "")
            this.saving = true;

            let businessId = JSON.parse(this.business)?.id

            let url = `v1/business/${businessId}/email-templates`;
            axios.put(this.getDomain(url, "api"), this.template,{withCredentials: true})
            .then(s => {
                this.$emit("eventResponse", "Email template successfully saved!");
            }).catch(err => {
                let errors = err.response?.data?.errors;
                if(errors) {
                    let concErrors = []
                    Object.values(errors).forEach(s => {
                        concErrors.push(s[0])
                    })
                    this.$emit("eventResponse", concErrors)
                }
                else {
                    this.$emit("eventResponse", err.message)
                }
  
            })
            .finally(() => {
                this.saving = false;
                window.scrollTo(0,0);
            })
        },
        async fetchTemplates() {
            let businessId = JSON.parse(this.business)?.id

            let url = `v1/business/${businessId}/email-templates`;
            axios.get(this.getDomain(url, "api"),{withCredentials: true})
            .then(s => {
                const { recurring_invoice_template } = s.data;
                this.template =recurring_invoice_template;
                this.template['template_for'] = 'recurring_invoice_template'
            })
            .catch(err => {
                const { status } = err.response;
                if(status == 404) {
                    axios.post(this.getDomain(url, "api"), this.template, {withCredentials: true})
                    .then(data => {
                        this.fetchTemplates()
                    })
                }
            })
        },
        async getBusiness() {
            let businessId = JSON.parse(this.business)?.id
            axios.get(this.getDomain(`v1/business/${businessId}`, 'api'), {
                withCredentials: true
            })
            .then(response => {
                if (response.data.logo_url) {
                    this.business_logo = response.data.logo_url;
                }
            });
        },
        sendTestEmail() {
            let businessId = JSON.parse(this.business)?.id

            let url = `v1/business/${businessId}/email-templates/send-email-test`;
            axios.post(this.getDomain(url, "api"),this.template,{withCredentials: true})
            .then(s => {
                this.$emit("eventResponse", "Test email successfully sent!")
            })
            .catch(err => {
                this.$emit("eventResponse", err.response);
            })
            .finally(() => {
                window.scrollTo(0,0);
            })
        },
        async uploadImage() {
            this.is_processing = true;

            let form = new FormData();

            this.image = this.$refs.image.files[0];

            form.append('image', this.image);
            let businessId = JSON.parse(this.business)?.id

            axios.post(this.getDomain('v1/business/' + businessId + '/logo', 'api'), form, {
                withCredentials: true,
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then(({data}) => {
                this.is_processing = false;
                this.business_logo = data.logo_url
                this.$emit("eventResponse", "Business Logo uploaded successfully!")
            }).catch((e) => {
            }).finally(() => { window.scrollTo(0,0); });
        },
        confirmLeave() {
            if(!this.is_saved) {
                return window.confirm('Do you really want to leave? you have unsaved changes!')
            }

        },

        confirmStayInDirtyForm() {
            return !this.confirmLeave()
        },

        beforeWindowUnload(e) {
            if (!this.is_saved && this.confirmStayInDirtyForm()) {
                // Cancel the event
                e.preventDefault()
                // Chrome requires returnValue to be set
                e.returnValue = ''
            }   
        },
    },
    created() {
        window.addEventListener('beforeunload', this.beforeWindowUnload)
    },

    beforeDestroy() {
        window.removeEventListener('beforeunload', this.beforeWindowUnload)
    },
    beforeRouteLeave (to, from, next) {
        // If the form is dirty and the user did not confirm leave,
        // prevent losing unsaved changes by canceling navigation
        if (this.confirmStayInDirtyForm()){
            next(false)
        } else {
            // Navigate to next view
            next()
        }
    },
    computed: {
        businessFormatted() {
            return JSON.parse(this.business);
        },
        previewSubject() {
            if(this.template?.email_subject) {
                //split the characters and check for business_name
                return this.parseContentWithVariables(this.template?.email_subject)
            }
            return "Your Receipt from Business name";
        },
        previewSellerInfo() {
            if(this.template?.seller_info) {
                return this.parseContentWithVariables(this.template?.seller_info)
            }
            return this.businessFormatted.name + ' (' + this.businessFormatted.email + ')'
        },
        previewTitle() {
            if(this.template?.title) {
                return this.parseContentWithVariables(this.template?.title)
            }
            return "Business name"
        },
        previewSubtitle() {
            if(this.template?.subtitle){
                return this.parseContentWithVariables(this.template?.subtitle)
            }
            return "New Recurring Payment Invoice from"
        },
        previewFooter() {
            if(this.template?.footer){
                return this.parseContentWithVariables(this.template?.footer)
            }
            return "Footer content goes here"
        },
        previewButtonText() {
            if(this.template?.button_text) {
                return this.parseContentWithVariables(this.template?.button_text)
            }
            return "Click here to view this invoice"
        },
        previewButtonColor() {
            if(this.template?.button_text_color) {
                return this.parseContentWithVariables(this.template?.button_text_color)
            }
            return "#FFFFFF"
        },
        previewButtonBackgroundColor() {
            if(this.template?.button_background_color) {
                return this.parseContentWithVariables(this.template?.button_background_color)
            }
            return "#002771"
        }
    },
    mounted() {
        Promise.all([
            this.fetchTemplates(),
            this.getBusiness(),
        ])
    },
}

window.addEventListener('beforeunload', (event) => {
   console.log('This is main')
   event.preventDefault()
   return false;

});
</script>
<style lang="scss" scoped>
input {
    &::placeholder{
        color: #101828 !important;
        font-size: 16px;
    }
    &.color-picker {
        &::before {
            content: attr(data-color);
            width: 99%;
            height: 90%;
            background: white;
            position: absolute;
            top: 0;
            left: 0;
            /* margin: 5px; */
            display: flex;
            align-items: center;
            justify-content: flex-start; 
            padding-left: 15px;
        }
    }
}
</style>