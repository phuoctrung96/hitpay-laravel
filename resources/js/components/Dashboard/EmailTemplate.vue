<template>
    <div>
        <div v-if="eventMessage" class="alert alert-secondary alert-dismissible fade show" role="alert">
            <ul v-if="!isString">
                <li class="small" v-for="(msg,index) in eventMessage" :key="index">{{msg}}</li>
            </ul>
            <span class="small" v-else>{{eventMessage}}</span>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>
        <div class="tab">
            <ul class="tab__menu d-flex flex-row">
                <li class="medium" :class="{'tab__list--active': activeTab.name == template.name}" @click="activeTab = template" v-for="(template,index) in emailTemplates" :key="index">{{template.name}}</li>
            </ul>

            <div class="tab__content">
                <payment-email-template :emailTemplate="emailTemplate" @eventResponse="eventResponse" :business="business" v-if="activeTab.name === emailTemplates[0].name"></payment-email-template>
                <recurring-email-template :emailTemplate="emailTemplate" @eventResponse="eventResponse"  :business="business" v-if="activeTab.name === emailTemplates[2].name"></recurring-email-template>
                <order-confirmation-email-template @eventResponse="eventResponse" :emailTemplate="emailTemplate" :business="business" v-if="activeTab.name === emailTemplates[1].name"></order-confirmation-email-template>
            </div>
        </div>
    </div>  
</template>

<script>
export default {
    props: ["business"],
    data() {
        return {
            activeTab: {name: 'Payment Receipt'},
            emailTemplates: [
                {name: 'Payment Receipt'},
                {name: 'Order Confirmation'},
                {name: 'Recurring Invoice'}
            ],
            eventMessage: "",
            success: {},
            emailTemplate: {}
        }
    },
    computed: {
        isString() {
            return typeof this.eventMessage === 'string';
        }
    },
    methods: {
        eventResponse(msg) {
            this.eventMessage = msg;
        },
        async fetchTemplates() {
            let businessId = JSON.parse(this.business)?.id

            let url = `v1/business/${businessId}/email-templates`;
            axios.get(this.getDomain(url, "api"),{withCredentials: true})
            .then(s => {
                this.emailTemplate = s.data;
            })
        }
    },
    mounted() {
        Promise.all([
            this.fetchTemplates()
        ])
    }
}
</script>

<style lang="scss" scoped>
    .tab {
        ul {
            margin: 0;
            padding: 0;
            li{
                list-style: none;
                background: #FFFFFF;
                box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
                padding: 7px 25px;
                border-top-right-radius: 7px;
                border-top-left-radius: 7px;
                color: #545454;
                margin-right: 2px;
                cursor: pointer;
            }
        }

        &__list {
            &--active {
                background: #011B5F !important;
                color: white !important;
            }
        }

        &__content {
            background: #FFFFFF;
            /* dashboard card */

            box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.15);

            ::v-deep{
                input.form-control {
                    color:#101828 !important;
                }
                .content {
                    background: white;
                    margin-top: 1px;
                    box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.15);
                    &__input {
                        // background: red;
                        
                        &-wrapper {
                            padding: 31px 32px;
                        }

                        .btn {
                            padding: 12px 20px !important;
                        }

                        #uploadBtn,#customBtnSave {
                            background:#002771 !important;
                            color: white;
                            font-size: 14px;
                            border: none;
                        }
                        #customBtnRevert {
                            background: #7E8294;
                            color: white;
                            font-size: 14px;
                            border: none;
                        }
                        &-label {
                            font-weight: 500;
                            font-size: 14px;
                        }
                        &-footer {
                            position: relative;
                            padding: 31px 32px;
                            border-top: 1px solid #D9D9D9;

                            ul {
                                margin: 0;
                                padding: 0;
                                list-style-type: none;

                                li {
                                    position: relative;
                                    padding-left: 20px;
                                    font-size: 14px;
                                    
                                    &::before {
                                        content: "";
                                        padding-right: 5px;
                                        width: 1px;
                                        height: 5px;
                                        border-radius: 20px;
                                        background: #0058FC;
                                        position: absolute;
                                        display: flex;
                                        align-items: center;
                                        top: 40%;
                                        left: -5px;
                                    }
                                }
                            }
                        }
                    }
                    &__preview {
                        background: #F2F5F8;
                        padding: 31px 32px;

                        &-badge {
                            background: rgb(194, 198, 209,.2);
                            padding: 10px 15px;
                            display: flex;
                            align-items: center;
                            justify-content: flex-start;
                            border-radius: 6px;
                            // font-size: 13px;

                            h6 { font-size: 13px }
                        }
                        &-card {
                            background: white;
                            padding: 32px 32px;
                            border-radius: 6px;

                            .description {
                                background: #F8F9FC;
                                border-radius: 4px;
                                padding: 10px 24px 10px 24px;

                                &__header {
                                    padding: 20px 24px !important;
                                }

                                &__footer {
                                    padding: 10px 24px !important;
                                    ul li {font-size: 16px !important;}
                                }

                                &__content {
                                    // padding: 10px 24px 10px 24px;
                                    padding: 10px 0px 10px 0px;
                                    background: #EEF0F5;
                                    
                                    .sub-header li {
                                        font-size: 15px !important;
                                    }

                                    .sub-header, .sub-item {
                                        padding: 1px 15px 1px 15px;
                                    }

                                    .sub-footer {
                                        border-top: 1px solid #E4E4E4;
                                        padding: 2px 15px 2px 15px;
                                        margin-top: 10px;
                                    }

                                    .sub-item {
                                        font-size: 13px !important;
                                    }
                                }

                                &--order {
                                    padding: 0;
                                }

                                ul {
                                    margin: 0;
                                    padding: 0;
                                    list-style-type: none;

                                    li {
                                        font-size: 14px;
                                        color: #0A1734;
                                        font-weight: 500;
                                        &:first-child {
                                            font-size: 13px;
                                            color: #545454;
                                            font-weight: normal;
                                        }
                                    }
                                }
                            }
                            .footer {
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                justify-content: center;

                                h6 {
                                    font-size: 13px;
                                    color: #03102F;
                                    font-weight: 400;
                                }
                            }
                        }
                        &-footer {
                            button {
                                background: #002771;
                                padding: 12px 12px;
                                width: 70%;
                                border: none;
                            }
                        }
                    }
                }
            }
        }
    }
</style>