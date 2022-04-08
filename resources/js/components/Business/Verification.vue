<style lang="scss">
    .account-verification{
        label{
            color: #03102F;
            font-size: 13px;
            font-weight: 500;
        }
        textarea{
            &.form-control{
                height: 184px;
            }
        }
        .manual-verify{
            padding: 12px 16px 12px 16px;
            background: #FFF;
            border: 1px solid #E4E7ED;
            border-radius: 4px;
            span{
                img{
                    max-width: 166px;
                    border-radius: 5px;
                }
            }
        }
        .alert-info{
            padding: 14px 15px 8px 15px;
            margin-left: 12px;
            margin-right: 12px;
            border-radius: 4px;
            font-size: 14px;
            p{
                margin: 0px 0px 8px;
            }
        }
        .card{
            padding: 32px 30px 40px;
            .card-body{
                padding: 0;
            }
        }
        .btn-delete{
            cursor: pointer;
            img{
                width: 12px;
                height: auto;
            }
        }
        .form-row{
            margin: 0px -12px;
            .form-group{
                padding-right: 12px;
                padding-left: 12px;
            }
        }
        .form-control{
            height: 40px;
            border-radius: 4px;
            border: 1px solid #D4D6DD;
            &:focus{
                box-shadow: none;
            }
        }
        .text-primary{
            font-size: 16px;
            margin: 0px 0px 20px;
        }
        .btn{
            &.btn-outline-primary {
                border: 1px solid #002771;
                color: #002771;
                opacity: 1;
                font-size: 14px;
                height: 40px;
                span {
                    background: url(/images/ico-plus.svg) no-repeat left 0 center;
                    background-size: 9px;
                    padding-left: 21px;
                }
                &:hover, &:focus{
                    background: #FFF;
                    color: #002771;
                }
            }
        }
        .is-btn-group{
            .btn{
                height: 44px;
                min-width: 200px;
                border-radius: 40px;
                font-size: 18px;
                font-weight: 500;
            }
        }
        // .btn-upload{
        //     border: 1px solid rgb(1, 27, 95);
        //     color: rgb(1, 27, 95);
        //     background-color: white;
        //     font-size: 14px;
        //     input{
        //         opacity: 0;
        //         position: absolute;
        //         z-index: -1;
        //     }
        // }
        @media (min-width: 991px) {
            .is-btn-group{
                .btn{
                    min-width: 373px;
                }
            }
        }
        @media (min-width: 991px) {
            .manual-verify{
                padding: 15px 30px 15px 30px;
            }
        }
        @media (max-width: 480px) {
            .manual-verify{
                span{
                    margin: 5px 0px 0px;
                    img{
                        max-width: 156px;
                    }
                }
            }
        }
    }
</style>
<template>
    <div class="account-verification">
        <template v-if="fill_type == 'manual'">
            <a class="manual-verify mb-4 d-block d-lg-flex justify-content-between align-items-center" :href="myinfourl">
                Use MyInfo to fill the form
                <span>
                    <img src="/images/myinfo.svg" alt="MyInfo">
                </span>
            </a>
        </template>
        <div class="card">
            <div class="row no-gutters justify-content-center">
                <div class="col-12 personal">
                    <div class="card-body align-self-start">
                        <h5 class="text-primary font-weight-medium">Personal</h5>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nric">NRIC / FIN</label>
                                <input id="nric" class="form-control" v-model="verification.nric" :class="{
                                            'is-invalid' : errors.nric}" :disabled="fill_type!='manual'">
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.nric }}</span>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="name">Name</label>
                                <input id="name" class="form-control" v-model="verification.name" :class="{
                                            'is-invalid' : errors.name}" :disabled="fill_type!='manual'">
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.name }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="sex">Sex</label>
                                <input id="sex" class="form-control" v-model="verification.sex" :class="{
                                            'is-invalid' : errors.sex}" :disabled="fill_type!='manual'">
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.sex }}</span>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="residentialstatus">Residential Status</label>
                                <input id="residentialstatus" class="form-control"
                                       v-model="verification.residentialstatus" :class="{
                                            'is-invalid' : errors.residentialstatus}" :disabled="fill_type!='manual'">
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.residentialstatus }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nationality">Nationality</label>
                                <input id="nationality" class="form-control" v-model="verification.nationality" :class="{
                                            'is-invalid' : errors.nationality}" :disabled="fill_type!='manual'">
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.nationality }}</span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Date of Birth</label>
                                <datepicker input-class="form-control w-100"
                                            class="d-block w-100"
                                            id="ends_at"
                                            v-model="verification.dob"
                                            :open-date="defaultDate"
                                            :disabled-dates="disableDateForDob"
                                            format="yyyy-MM-dd"
                                            :class="{'is-invalid' : errors.dob}"
                                            :disabled="fill_type!='manual'"
                                ></datepicker>
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.dob }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="regadd">Registered Address</label>
                                <textarea id="regadd" rows="6" class="form-control"
                                          v-model="verification.regadd" :disabled="fill_type!='manual'"
                                          :class="{'is-invalid' : errors.regadd}"></textarea>
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.regadd }}</span>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email">Email Address</label>
                                <input id="email" class="form-control" v-model="verification.email" :class="{
                                            'is-invalid' : errors.email}"
                                       :disabled="fill_type!='manual'">
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.email }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="business_description">Business description</label>
                                <textarea id="business_description" rows="6" class="form-control"
                                          v-model="verification.business_description"
                                          :class="{'is-invalid' : errors.business_description}"
                                          :disabled="fill_type=='completed'"
                                          placeholder="Please add a description of the goods and services you are providing"></textarea>
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.business_description }}</span>
                            </div>
                            <div class="col-md-6">
                                <template v-if="fill_type != 'completed' && (fill_type == 'manual' || type == 'individual')">
                            <div class="form-group">
                                <label class="form-label" for="supporting_documents">Upload supporting documents for
                                    proof
                                    of business operations*</label>
                                    <span v-if="type == 'company'" class="small text-muted">
                                        <br>Please upload ACRA Biz File.<br/>
                                       Please refer to section 6.3 of the <a
                                        href="https://www.hitpayapp.com/acceptableusepolicy" target="_blank">Acceptable Use Policy</a> for prohibited list of businesses.</br>
                                    </span>
                                    <span v-else="type == 'individual'" class="small text-muted">
                                        Please upload supporting documents that establishes clear proof that you are using HitPay for payment of goods and services (For example : business license, customer invoices).                                    <br>
                                        Please refer to section 6.3 of the <a
                                        href="https://www.hitpayapp.com/acceptableusepolicy" target="_blank">Acceptable Use Policy</a> for prohibited list of businesses.</br>
                                    </span>
                                    <input type="file"
                                           class="form-control-file mt-3" id="supporting_documents"
                                           ref="supporting_documents" multiple="multiple"
                                           :class="{'is-invalid' : errors.supporting_documents}"/>
                                    <span class="invalid-feedback" role="alert">
                                        {{ errors.supporting_documents }}
                                    </span>
                                </div>
                                <div class="form-group mt-2">
                                    <label class="form-label" for="identity_front">Please upload your NRIC (Front Side)</label>
                                    <input type="file" class="form-control-file mt-3" id="identity_front"
                                           placeholder="Please upload your NRIC copy front"
                                           ref="identity_front" :class="{
                                            'is-invalid' : errors.identity_front}"/>
                                    <span class="invalid-feedback" role="alert">
                                    {{ errors.identity_front }}</span>
                                </div>
                                <div class="form-group mt-2">
                                    <label class="form-label" for="identity_back">Please upload your NRIC (Back Side)</label>
                                    <input type="file" class="form-control-file mt-3" id="identity_back"
                                           placeholder="Please upload your NRIC copy back"
                                           ref="identity_back" :class="{
                                            'is-invalid' : errors.identity_back}"/>
                                    <span class="invalid-feedback" role="alert">
                                    {{ errors.identity_back }}</span>
                                </div>
                            </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="type == 'company'" class="col-12 company">
                    <div class="card-body align-self-start">
                        <h5 class="text-primary font-weight-medium mt-4">Corporate</h5>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>UEN</label>
                                <input id="uen" class="form-control" v-model="verification.uen" :disabled="fill_type!='manual'"
                                       :class="{'is-invalid' : errors.uen}">
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.uen }}
                                </span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Entity Name</label>
                                <input class="form-control" v-model="verification.entity_name"
                                       :disabled="fill_type!='manual'"
                                       :class="{'is-invalid' : errors.entity_name}">
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.entity_name }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Entity Type</label>
                                <select class="form-control" v-model="verification.entity_type"
                                        :disabled="fill_type!='manual'"
                                        :class="{'is-invalid' : errors.entity_type}">
                                    <option value="Individual">Individual</option>
                                    <option value="Local Company">Local Company</option>
                                    <option value="Foreign Company">Foreign Company</option>
                                    <option value="Unregistered Local Entity">Unregistered Local Entity</option>
                                    <option value="Limited Liability Partnerships">Limited Liability Partnerships</option>
                                    <option value="Unregistered Foreign Entity">Unregistered Foreign Entity</option>
                                </select>
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.entity_type }}</span>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Entity Status</label>
                                <select class="form-control" v-model="verification.entity_status"
                                        :disabled="fill_type!='manual'"
                                        :class="{'is-invalid' : errors.entity_status}">
                                    <option value="LIVE">Live</option>
                                    <option value="INACTIVE">Inactive</option>
                                </select>
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.entity_status }}</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Registration Date</label>
                                <datepicker input-class="form-control w-100"
                                            class="d-block w-100"
                                            v-model="verification.registration_date"
                                            :disabled-dates="disableDates"
                                            format="yyyy-MM-dd"
                                            :class="{'is-invalid' : errors.registration_date}"
                                            :disabled="fill_type!='manual'"
                                ></datepicker>
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.registration_date }}
                                </span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Primary Activity Description</label>
                                <textarea class="form-control" v-model="verification.primary_activity"
                                          :disabled="fill_type!='manual'"
                                          :class="{'is-invalid' : errors.primary_activity}"></textarea>
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.primary_activity }}
                                </span>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="address">Address</label>
                                <textarea id="address" rows="6" class="form-control"
                                          v-model="verification.address" :disabled="fill_type!='manual'"
                                          :class="{'is-invalid' : errors.address}"></textarea>
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.address }}
                                </span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <button
                                    v-if="fill_type != 'completed' && verification.shareholders.length < 10"
                                    :disabled="fill_type!='manual'"
                                    class="btn btn-outline-primary font-weight-medium mb-2"
                                    @click="addShareholder">
                                    <!-- <i class="fa fa-user-plus"
                                       aria-hidden="true"
                                       @click="addShareholder"
                                    >
                                    </i> -->
                                    <span>Add Shareholder</span>
                                </button>
                                <span id="shareholders_error"></span>
                                <span v-if="errors.shareholders_error"
                                    class="mb-2 invalid-feedback d-block" role="alert">
                                    {{ errors.shareholders_error }}
                                </span>

                                <form>
                                    <div v-for="(shareholder, key) in verification.shareholders" class="form-group p-4 mt-2 border">
                                        <div class="form-row" :id="`shareholder_${key}`">
                                            <div class="form-group col-md-12">
                                                <label class="font-weight-medium">
                                                    SHAREHOLDER #{{ key + 1 }}
                                                </label>
                                                <span class="btn-delete ml-3" @click="fill_type != 'myinfo' ? deleteShareholder(key) : ''"
                                                   v-if="fill_type != 'completed'"><img src="/images/delete_icon.svg" alt="delete"></span>
                                            </div>
                                            <!-- <div class="form-group col-md-3">
                                                <i class="fa fa-user-times"
                                                   aria-hidden="true"
                                                   @click="fill_type != 'myinfo' ? deleteShareholder(key) : ''"
                                                   v-if="fill_type != 'completed'"
                                                >
                                                </i>
                                            </div> -->
                                        </div>

                                        <div class="form-row" v-if="is_more_confirm">
                                            <div class="alert-info mb-3">
                                                <p>Shareholder name from MyInfo is <b>{{ verification.shareholders[key] }}</b>.</p>
                                                <p>Please input first name and last name follow with that.</p>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label>Shareholder first name</label>
                                                <input
                                                    class="form-control d-inline mb-2"
                                                    v-model="verification.shareholders_first_name[key]"
                                                    :class="{'is-invalid' : verification.shareholders_first_name_error[key]}"
                                                    required="required"
                                                    :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                    placeholder="Input firstname of shareholder "
                                                />
                                            </div>
                                            <div class="form-group col-md-5">
                                                <span v-if="verification.shareholders_first_name_error[key]" class="mb-2 invalid-feedback d-block" role="alert">
                                                    {{ verification.shareholders_first_name_error[key] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label>Shareholder last name</label>
                                                <input
                                                    class="form-control d-inline mb-2"
                                                    v-model="verification.shareholders_last_name[key]"
                                                    :class="{'is-invalid' : verification.shareholders_last_name_error[key]}"
                                                    required="required"
                                                    :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                    placeholder="Input lastname of shareholder "
                                                />
                                            </div>
                                            <div class="form-group col-md-5">
                                                <span v-if="verification.shareholders_last_name_error[key]" class="mb-2 invalid-feedback d-block" role="alert">
                                                    {{ verification.shareholders_last_name_error[key] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label>Shareholder NRIC or ID Number</label>
                                                <input
                                                    class="form-control d-inline mb-2"
                                                    v-model="verification.shareholders_id_number[key]"
                                                    :class="{'is-invalid' : verification.shareholders_id_number_error[key]}"
                                                    required="required"
                                                    :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                    placeholder="Input nric or id of shareholder "
                                                />
                                            </div>
                                            <div class="form-group col-md-5">
                                                <span v-if="verification.shareholders_id_number_error[key]" class="mb-2 invalid-feedback d-block" role="alert">
                                                    {{ verification.shareholders_id_number_error[key] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-10">
                                                <label>Shareholder relationship</label> <br />

                                                <input type="checkbox"
                                                       v-model="verification.shareholders_is_director[key]"
                                                       true-value="yes"
                                                       :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                       false-value="no" class="mr-"> <span class="mr-4">Director</span>

                                                <input type="checkbox"
                                                       v-model="verification.shareholders_is_owner[key]"
                                                       true-value="yes"
                                                       :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                       false-value="no"> <span class="mr-4">Owner</span>

                                                <input type="checkbox"
                                                       v-model="verification.shareholders_is_executive[key]"
                                                       true-value="yes"
                                                       :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                       false-value="no"> Executive
                                            </div>
                                            <div class="form-group col-md-5">
                                                <span v-if="verification.shareholders_relationship_error[key]"
                                                      class="mb-2 invalid-feedback d-block" role="alert">
                                                    {{ verification.shareholders_relationship_error[key] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label>Shareholder date of birth</label>
                                                <datepicker
                                                    input-class="d-inline w-100"
                                                    v-model="verification.shareholders_dob[key]"
                                                    :disabled-dates="disableDateForDob"
                                                    :open-date="defaultDate"
                                                    format="yyyy-MM-dd"
                                                    wrapper-class="w-100"
                                                    required="required"
                                                    :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                    :class="{'is-invalid' : verification.shareholders_dob_error[key]}"
                                                    placeholder="Input date of birth shareholder "
                                                ></datepicker>
                                            </div>
                                            <div class="form-group col-md-5">
                                                <span v-if="verification.shareholders_dob_error[key]"
                                                      class="mb-2 invalid-feedback d-block" role="alert">
                                                    {{ verification.shareholders_dob_error[key] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label>Shareholder address</label>
                                                <input max="255"
                                                       class="form-control d-inline mb-2"
                                                       v-model="verification.shareholders_address[key]"
                                                       :class="{'is-invalid' : verification.shareholders_address_error[key]}"
                                                       required="required"
                                                       :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                       placeholder="Input address of shareholder "
                                                />
                                            </div>
                                            <div class="form-group col-md-5">
                                                <span v-if="verification.shareholders_address_error[key]"
                                                      class="mb-2 invalid-feedback d-block" role="alert">
                                                    {{ verification.shareholders_address_error[key] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label>Shareholder postal code</label>
                                                <input max="10"
                                                       class="form-control d-inline mb-2"
                                                       v-model="verification.shareholders_postal[key]"
                                                       :class="{'is-invalid' : verification.shareholders_postal_error[key]}"
                                                       required="required"
                                                       :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                       placeholder="Postal of shareholder "
                                                />
                                            </div>
                                            <div class="form-group col-md-5">
                                                <span v-if="verification.shareholders_postal_error[key]" class="mb-2 invalid-feedback d-block" role="alert">
                                                    {{ verification.shareholders_postal_error[key] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label>Shareholder title</label>
                                                <input max="255"
                                                       class="form-control d-inline mb-2"
                                                       v-model="verification.shareholders_title[key]"
                                                       :class="{'is-invalid' : verification.shareholders_title_error[key]}"
                                                       placeholder="Title of shareholder "
                                                       :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                />
                                            </div>
                                            <div class="form-group col-md-5">
                                                <span v-if="verification.shareholders_title_error[key]" class="mb-2 invalid-feedback d-block" role="alert">
                                                    {{ verification.shareholders_title_error[key] }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label>Shareholder email</label>
                                                <input type="email" max="255"
                                                       class="form-control d-inline mb-2"
                                                       v-model="verification.shareholders_email[key]"
                                                       :class="{'is-invalid' : verification.shareholders_email_error[key]}"
                                                       placeholder="Email of shareholder"
                                                       :disabled="(fill_type !='manual' && !is_more_confirm)"
                                                />
                                            </div>
                                            <div class="form-group col-md-5">
                                                <span v-if="verification.shareholders_email_error[key]" class="mb-2 invalid-feedback d-block" role="alert">
                                                    {{ verification.shareholders_email_error[key] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="fill_type !='completed'" class="form-check mb-3 col-12 text-center">
                    <input type="checkbox" id="checkbox_agree" class="form-check-input" v-model="checkbox_agree"
                           :class="{
                    'is-invalid' : errors.checkbox_agree
                }" :disabled="is_processing ">
                    <label for="checkbox_agree" class="small text-muted form-check-label">I agree that I will use HitPay only for payment of goods and services</label>
                    <span v-if="errors.checkbox_agree" class="invalid-feedback d-block" role="alert">
                                {{ errors.checkbox_agree }}</span>
                </div>
                <div class="is-btn-group d-block text-center">
                    <button
                        v-if="fill_type !='completed' && is_more_confirm == false"
                        class="btn btn-primary"
                        :disabled="is_processing"
                        @click="saveVerification">
                        Confirm <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
                    </button>
                    <button
                        v-if="is_more_confirm == true"
                        class="btn btn-primary mt-2"
                        :disabled="is_processing"
                        @click="moreConfirm">
                        Confirm <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
                    </button>
                    <button
                        v-if="fill_type !='manual' && is_more_confirm == false"
                        class="btn btn-danger mt-2"
                        :disabled="is_processing"
                        data-toggle="modal"
                        data-target="#confirmDeleteModal">
                        <template v-if="fill_type =='completed'">Delete <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i></template>
                        <template v-else>Cancel <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i></template>
                    </button>
                    <button
                        v-if="is_more_confirm == true"
                        class="btn btn-danger mt-2"
                        :disabled="is_processing"
                        data-toggle="modal"
                        data-target="#confirmDeleteModal">
                        <template v-if="fill_type =='completed'">Delete <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i></template>
                        <template v-else>Cancel <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i></template>
                    </button>
                </div>

                <div id="confirmDeleteModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-body">
                                <h5 class="modal-title text-danger font-weight-bold mb-0">
                                    Are you sure you want to delete your account verification?
                                </h5>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                        :disabled="is_processing">
                                    Close
                                </button>
                                <button type="button" class="btn btn-danger" @click="deleteVerification"
                                        :disabled="is_processing">
                                    <i class="fas fa-times mr-1"></i> Confirm Delete <i v-if="is_processing"
                                                                                        class="fas fa-spinner fa-spin"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Datepicker from 'vuejs-datepicker';

export default {
    name: "Verification",
    components: {
        Datepicker
    },
    props: {
        fill_type: {
            type: String,
            required: true
        },
        verification_id: {
            type: String,
            required: false,
        }
    },
    data() {
        return {
            type: 'company',
            is_more_confirm: false,
            business: {},
            errors: {},
            is_processing: false,
            hasOwner: false,
            hasRepresentative: true,
            hasDirector: false,
            isOwnerHasEmail: false,
            verification: {
                nric: '',
                name: '',
                sex: '',
                residentialstatus: '',
                nationality: '',
                dob: '',
                regadd: '',
                email: '',
                uen: '',
                entity_name: '',
                entity_type: '',
                entity_status: '',
                registration_date: '',
                primary_activity: '',
                address: '',
                business_description: '',
                shareholders_count: 0,
                shareholders: [],
                shareholders_error: [],
                shareholders_first_name: [],
                shareholders_first_name_error: [],
                shareholders_last_name: [],
                shareholders_last_name_error: [],
                shareholders_id_number: [],
                shareholders_id_number_error: [],
                shareholders_is_director: [],
                shareholders_is_owner: [],
                shareholders_is_executive: [],
                shareholders_dob: [],
                shareholders_dob_error: [],
                shareholders_address: [],
                shareholders_address_error: [],
                shareholders_postal: [],
                shareholders_postal_error: [],
                shareholders_title: [],
                shareholders_title_error: [],
                shareholders_email: [],
                shareholders_email_error: [],
                shareholders_relationship_error: [],
            },
            checkbox_agree: false,
        }
    },
    mounted() {
        this.business = window.Business;

        if (window.Verification) {
            this.verification = Verification;
            if (Verification.dob)
                this.verification.dob = new Date(Verification.dob);
            if (Verification.registration_date)
                this.verification.registration_date = new Date(Verification.registration_date);
        }

        if (window.Type) {
            this.type = Type;
        }

        if (window.IsMoreConfirm) {
            this.is_more_confirm = window.IsMoreConfirm;
        }
    },
    methods: {
        deleteShareholder(key) {
            if (!confirm('Are you sure want to delete shareholder ' + (key + 1))) {
                return;
            }

            this.verification.shareholders.splice(key,1);
            this.verification.shareholders_first_name.splice(key,1);
            this.verification.shareholders_last_name.splice(key,1);
            this.verification.shareholders_id_number.splice(key,1);
            this.verification.shareholders_is_director.splice(key,1);
            this.verification.shareholders_is_executive.splice(key,1);
            this.verification.shareholders_is_owner.splice(key,1);
            this.verification.shareholders_dob.splice(key,1);
            this.verification.shareholders_address.splice(key,1);
            this.verification.shareholders_postal.splice(key,1);
            this.verification.shareholders_title.splice(key,1);
            this.verification.shareholders_email.splice(key,1);

            this.verification.shareholders_count--;
        },

        resetShareHolderError() {
            for (var i=0; i<this.verification.shareholders_count; i++) {
                this.verification.shareholders_error[i] = "";
                this.verification.shareholders_first_name_error[i] = "";
                this.verification.shareholders_last_name_error[i] = "";
                this.verification.shareholders_id_number_error[i] = "";
                this.verification.shareholders_relationship_error[i] = "";
                this.verification.shareholders_dob_error[i] = "";
                this.verification.shareholders_address_error[i] = "";
                this.verification.shareholders_postal_error[i] = "";
                this.verification.shareholders_title_error[i] = "";
                this.verification.shareholders_email_error[i] = "";
            }
        },

        resetError() {
            if (this.fill_type === 'manual') {
                if (this.type === 'company') {
                    this.resetShareHolderError();
                }
            }

            if (this.is_more_confirm) {
                if (this.type === 'company') {
                    this.resetShareHolderError();
                }
            }

            this.errors = {};
        },

        addShareholder() {
            this.verification.shareholders.push('');

            this.verification.shareholders_error[this.verification.shareholders_count] = '';

            // set default
            this.verification.shareholders_first_name[this.verification.shareholders_count] = '';
            this.verification.shareholders_first_name_error[this.verification.shareholders_count] = '';
            this.verification.shareholders_last_name[this.verification.shareholders_count] = '';
            this.verification.shareholders_last_name_error[this.verification.shareholders_count] = '';
            this.verification.shareholders_id_number[this.verification.shareholders_count] = '';
            this.verification.shareholders_id_number_error[this.verification.shareholders_count] = '';

            this.verification.shareholders_is_director[this.verification.shareholders_count] = 'no';
            this.verification.shareholders_is_executive[this.verification.shareholders_count] = 'no';
            this.verification.shareholders_is_owner[this.verification.shareholders_count] = 'no';
            this.verification.shareholders_relationship_error[this.verification.shareholders_count] = '';

            this.verification.shareholders_dob[this.verification.shareholders_count] = '';
            this.verification.shareholders_dob_error[this.verification.shareholders_count] = '';

            this.verification.shareholders_address[this.verification.shareholders_count] = '';
            this.verification.shareholders_address_error[this.verification.shareholders_count] = '';

            this.verification.shareholders_postal[this.verification.shareholders_count] = '';
            this.verification.shareholders_postal_error[this.verification.shareholders_count] = '';

            this.verification.shareholders_title[this.verification.shareholders_count] = '';
            this.verification.shareholders_title_error[this.verification.shareholders_count] = '';

            this.verification.shareholders_email[this.verification.shareholders_count] = '';
            this.verification.shareholders_email_error[this.verification.shareholders_count] = '';

            this.verification.shareholders_count++;

            var that = this;
            setTimeout(function() {
                that.scrollTo('#shareholder_' + (that.verification.shareholders_count - 1));
            }, 1000);
        },


        checkHaveValidShareholderRelationship() {
            if (this.verification.shareholders_count == 0) {
                this.errors.shareholders = "At least one shareholder must be added";
            }

            // check shareholders name
            for (var i=0; i<this.verification.shareholders_count; i++) {
                const messageError = "Shareholder name required";
                if (typeof(this.verification.shareholders[i]) == 'undefined') {
                    this.errors.shareholders_error = messageError;
                }
                if (this.verification.shareholders[i] == "") {
                    this.verification.shareholders_error[i] = messageError;
                    this.errors.shareholders_error = messageError;
                }
            }

            // check shareholders first name
            for (var i=0; i<this.verification.shareholders_count; i++) {
                const messageError = "Shareholder first name required";

                if (typeof(this.verification.shareholders_first_name[i]) == 'undefined') {
                    this.errors.shareholders_error = messageError;
                }
                if (this.verification.shareholders_first_name[i] == "") {
                    this.verification.shareholders_first_name_error[i] = messageError;
                    this.errors.shareholders_error = messageError;
                }
            }

            // check shareholders last name
            for (var i=0; i<this.verification.shareholders_count; i++) {
                const messageError = "Shareholder last name required";

                if (typeof(this.verification.shareholders_last_name[i]) == 'undefined') {
                    this.errors.shareholders_error = messageError;
                }

                if (this.verification.shareholders_last_name[i] == "") {
                    this.verification.shareholders_last_name_error[i] = messageError;
                    this.errors.shareholders_error = messageError;
                }
            }

            // check NRIC
            for (var i=0; i<this.verification.shareholders_count; i++) {
                const messageError = "Shareholder NRIC / ID Number required";
                if (typeof(this.verification.shareholders_id_number[i]) == 'undefined') {
                    this.errors.shareholders_error = messageError;
                }
                if (this.verification.shareholders_id_number[i] == "") {
                    this.verification.shareholders_id_number_error[i] = messageError;
                    this.errors.shareholders_error = messageError;
                }
            }

            // check dob
            for (var i=0; i<this.verification.shareholders_count; i++) {
                const messageError = "Shareholder date of birth required";
                if (typeof this.verification.shareholders_dob == "undefined") {
                    this.errors.shareholders_error = messageError;
                }
                if (this.verification.shareholders_dob[i] == "") {
                    this.verification.shareholders_dob_error[i] = messageError;
                    this.errors.shareholders_error = messageError;
                }
            }

            // check address
            for (var i=0; i<this.verification.shareholders_count; i++) {
                const messageError = "Shareholder address required";
                if (typeof(this.verification.shareholders_address[i]) == 'undefined') {
                    this.errors.shareholders_error = messageError;
                }
                if (this.verification.shareholders_address[i] == "") {
                    this.verification.shareholders_address_error[i] = messageError;
                    this.errors.shareholders_error = messageError;
                }
            }

            // check postal
            for (var i=0; i<this.verification.shareholders_count; i++) {
                const messageError = "Shareholder postal required";
                if (typeof(this.verification.shareholders_postal[i]) == 'undefined') {
                    this.errors.shareholders_error = messageError;
                }
                if (this.verification.shareholders_postal[i] == "") {
                    this.verification.shareholders_postal_error[i] = messageError;
                    this.errors.shareholders_error = messageError;
                }
            }

            this.verification.shareholders_is_owner.forEach((value, keyIndex) => {
                if (value == 'yes') {
                    this.hasOwner = true;

                    if (typeof this.verification.shareholders_email[keyIndex] !== 'undefined') {
                        const email = this.verification.shareholders_email[keyIndex];

                        if (email != "" && email.trim() != "") {
                            if (!(/\S+@\S+\.\S+/.test(this.verification.shareholders_email[keyIndex]))) {
                                this.isOwnerHasEmail = false;
                            } else {
                                this.isOwnerHasEmail = true;
                            }
                        }

                        return;
                    }
                }
            });

            if (!this.hasOwner) {
                const messageError = "At least one shareholder with Owner relationship";

                this.verification.shareholders_is_owner.forEach((value, index) => {
                    this.verification.shareholders_relationship_error[index] = messageError;
                });

                this.errors.shareholders_error = messageError;
            }

            this.verification.shareholders_is_director.forEach((value, keyIndex) => {
                if (value == 'yes') {
                    this.hasDirector = true;
                    return;
                }
            });

            // has director
            if (!this.hasDirector) {
                const messageError = "At least one shareholder with Director relationship";

                if (Array.isArray(this.verification.shareholders_is_director)) {
                    if (this.verification.shareholders_is_director.length > 0) {
                        this.verification.shareholders_is_director.forEach((value, index) => {
                            this.verification.shareholders_relationship_error[index] = messageError;
                        });
                    }
                }

                this.errors.shareholders_error = messageError;
            }

            // owner has email
            if (!this.isOwnerHasEmail) {
                const messageError = "At least one shareholder with Owner relationship has valid email";

                if (Array.isArray(this.verification.shareholders_is_owner)) {
                    if (this.verification.shareholders_is_owner.length > 0) {
                        this.verification.shareholders_is_owner.forEach((value, index) => {
                            this.verification.shareholders_email_error[index] = messageError;
                        });
                    }
                }

                this.errors.shareholders_error = messageError;
            }

            if (Object.keys(this.errors).length > 0) {
                return false;
            }

            return true;
        },

        setDobShareholder() {
            if (this.verification.shareholders_count == 0) {
                return;
            }

            for (var i=0; i<this.verification.shareholders_count; i++) {
                let dob = this.verification.shareholders_dob[i];

                if (typeof dob == "undefined") {

                } else {
                    this.verification.shareholders_dob[i] = this.getEndDate(this.verification.shareholders_dob[i]);
                }
            }
        },

        mapFullname() {
            for (var i=0; i<this.verification.shareholders_count; i++) {
                if (this.verification.shareholders_first_name[i] != "") {
                    this.verification.shareholders[i] = this.verification.shareholders_first_name[i] + " " + this.verification.shareholders_last_name[i];
                } else {
                    this.verification.shareholders[i] = this.verification.shareholders_last_name[i];
                }

                if (this.verification.shareholders_last_name[i] != "") {
                    this.verification.shareholders[i] = this.verification.shareholders_first_name[i] + " " + this.verification.shareholders_last_name[i];
                } else {
                    this.verification.shareholders[i] = this.verification.shareholders_first_name[i];
                }
            }
        },

        validateMoreInfoFullname() {
            for (var i=0; i<this.verification.shareholders_count; i++) {
                const fullname = this.verification.shareholders[i];

                let arrFullName = fullname.split(' ');

                if (typeof this.verification.shareholders_first_name[i] == 'undefined') {
                    const messageError = "Shareholder first name is mandatory";
                    this.verification.shareholders_first_name_error[i] = messageError;
                    this.errors.shareholders_error = messageError;

                    continue;
                }

                if (typeof this.verification.shareholders_last_name[i] == 'undefined') {
                    const messageError = "Shareholder last name is mandatory";
                    this.verification.shareholders_last_name_error[i] = messageError;
                    this.errors.shareholders_error = messageError;

                    continue;
                }

                const firstName = this.verification.shareholders_first_name[i];
                const lastName = this.verification.shareholders_last_name[i];
                const firstNameArr = firstName.split(' ');
                const lastNameArr = lastName.split(' ');

                const foundFirstName = arrFullName.some(function(r) {
                    if (typeof r == 'string') {
                        r = r.toLowerCase();
                    }

                    return firstNameArr.map(function(item) {
                        if (typeof item == 'string') {
                            return item.toLowerCase();
                        }
                    }).includes(r);
                });

                if (!foundFirstName) {
                    const messageError = "Shareholder first name not same with fullname";
                    this.verification.shareholders_first_name_error[i] = messageError;
                    this.errors.shareholders_error = messageError;

                    continue;
                }

                const foundLastName = arrFullName.some(function(r) {
                    if (typeof r == 'string') {
                        r = r.toLowerCase();
                    }

                    return lastNameArr.map(function(item) {
                        if (typeof item == 'string') {
                            return item.toLowerCase();
                        }
                    }).includes(r);
                });

                if (!foundLastName) {
                    const messageError = "Shareholder last name not same with fullname";
                    this.verification.shareholders_last_name_error[i] = messageError;
                    this.errors.shareholders_error = messageError;

                    continue;
                }
            }
        },

        saveVerification() {
            this.is_processing = true;

            this.resetError();

            if (this.fill_type === 'manual') {
                if (this.type === 'company') {
                    this.mapFullname();

                    this.checkVerificationPersonal()

                    this.checkVerificationCompany();

                    this.checkHaveValidShareholderRelationship();
                } else {
                    this.checkVerificationPersonal();

                    this.isOwnerHasEmail = true;
                }

                if (this.type === 'individual') {
                    this.verification.uen = null;
                    this.verification.entity_name = null;
                    this.verification.entity_status = null;
                    this.verification.entity_type = null;
                    this.verification.registration_date = null;
                    this.verification.primary_activity = null;
                    this.verification.address = null;
                    this.verification.shareholders = null;
                }
            }

            if (this.$refs.supporting_documents && this.$refs.supporting_documents.files.length > 2) {
                this.errors.supporting_documents = "Maximum 2 files are allowed";
            }

            if (this.$refs.supporting_documents && this.$refs.supporting_documents.files.length == 0) {
                this.errors.supporting_documents = "Please upload supporting documents";
            }

            if (this.$refs.identity_front && this.$refs.identity_front.files.length == 0) {
                this.errors.identity_front = "Please upload your NRIC copy front";
            }

            if (this.$refs.identity_back && this.$refs.identity_back.files.length == 0) {
                this.errors.identity_back = "Please upload your NRIC copy back";
            }

            if (this.verification.business_description && this.verification.business_description.length > 1000) {
                this.errors.business_description = "Max length is 1000 characters";
            }
            if (!this.verification.business_description || this.verification.business_description === '') {
                this.errors.business_description = 'This is required field';
            }
            if (!this.checkbox_agree) {
                this.errors.checkbox_agree = 'Please confirm that you agree';
            }

            if (this.$refs.supporting_documents) {
                for (var i = 0; i < this.$refs.supporting_documents.files.length; i++) {
                    let file = this.$refs.supporting_documents.files[i];
                    if (file.size > 1024 * 1024 * 2) {
                        this.errors.supporting_documents = "Files should not be greater than 2 MB.";
                    }
                }
            }

            if (this.$refs.identity_front) {
                let file = this.$refs.identity_front.files[0];
                if (typeof file !== "undefined") {
                    if (file.size > 1024 * 1024 * 2) {
                        this.errors.identity_front = "Files should not be greater than 2 MB.";
                    }
                }
            }

            if (this.$refs.identity_back) {
                let file = this.$refs.identity_back.files[0];
                if (typeof file !== "undefined") {
                    if (file.size > 1024 * 1024 * 2) {
                        this.errors.identity_back = "Files should not be greater than 2 MB.";
                    }
                }
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            let formData = new FormData();

            if (this.$refs.identity_front) {
                let file = this.$refs.identity_front.files[0];
                formData.append('identity_front', file);
            }

            if (this.$refs.identity_back) {
                let file = this.$refs.identity_back.files[0];
                formData.append('identity_back', file);
            }

            if (this.$refs.supporting_documents) {
                for (var i = 0; i < this.$refs.supporting_documents.files.length; i++) {
                    let file = this.$refs.supporting_documents.files[i];
                    formData.append('supporting_documents[' + i + ']', file);
                }
            }

            if (this.fill_type === 'manual'){
                this.verification.dob = this.getEndDate(this.verification.dob);

                if (this.type === 'company') {
                    this.verification.registration_date = this.getEndDate(this.verification.registration_date);

                    this.setDobShareholder();
                }
            }

            formData.append('type', this.type === 'company' ? 'business' : 'personal');
            formData.append('fill_type', this.fill_type);
            formData.append('verification', JSON.stringify(this.verification));

            axios.post(this.getDomain('business/' + this.business.id + '/verification/confirm' + (this.verification_id ? '/'+this.verification_id : ''), 'dashboard'), formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data'
                },
            }).then(({data}) => {
                this.is_processing = false;
                document.location.href = data;
            }).catch(({response}) => {
                if (response.status === 422) {
                    this.is_processing = false;

                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                }
            });
        },

        deleteVerification() {
            this.is_processing = true;
            axios.post(
                this.getDomain('business/' + this.business.id + '/verification/delete' + (this.verification_id ? '/'+this.verification_id : ''), 'dashboard'))
                .then(({data}) => {
                    this.is_processing = false;
                    document.location.href = data;
                })
        },

        moreConfirm() {
            this.is_processing = true;

            this.resetError();

            if (this.type == 'company') {
                this.checkHaveValidShareholderRelationship();

                this.validateMoreInfoFullname();

                this.setDobShareholder();
            } else {
                this.isOwnerHasEmail = true;
            }
            // check type personal or company

            this.verification.dob = this.getEndDate(this.verification.dob);

            if (this.type === 'company') {
                this.verification.registration_date = this.getEndDate(this.verification.registration_date);

                this.setDobShareholder();
            }

            if (this.verification.business_description && this.verification.business_description.length > 1000) {
                this.errors.business_description = "Max length is 1000 characters";
            }
            if (!this.verification.business_description || this.verification.business_description === '') {
                this.errors.business_description = 'This is required field';
            }
            if (!this.checkbox_agree) {
                this.errors.checkbox_agree = 'Please confirm that you agree';
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            let formData = new FormData();

            formData.append('type', this.type === 'company' ? 'business' : 'personal');
            formData.append('fill_type', this.fill_type);
            formData.append('verification', JSON.stringify(this.verification));

            const path = 'business/' + this.business.id + '/verification/more_confirm' + (this.verification_id ? '/'+this.verification_id : '');

            axios.post(
                this.getDomain(path, 'dashboard'), formData, {
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'multipart/form-data'
                    },
                }).then(({data}) => {
                this.is_processing = false;
                document.location.href = data;
            }).catch(({response}) => {
                if (response.status === 422) {
                    this.is_processing = false;
                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });
                    this.showError(_.first(Object.keys(this.errors)));
                }
            });
        },

        checkVerificationPersonal() {
            if (this.verification.nric === '') {
                this.errors.nric = 'This is required field';
            }
            if (this.verification.name === '')
                this.errors.name = 'This is required field';
            if (this.verification.sex === '')
                this.errors.sex = 'This is required field';
            if (this.verification.residentialstatus === '')
                this.errors.residentialstatus = 'This is required field';
            if (this.verification.nationality === '')
                this.errors.nationality = 'This is required field';
            if (this.verification.dob === '')
                this.errors.dob = 'This is required field';
            if (this.verification.regadd === '')
                this.errors.regadd = 'This is required field';
            if (this.verification.email === '')
                this.errors.email = 'This is required field';
            else if (!(/\S+@\S+\.\S+/.test(this.verification.email)))
                this.errors.email = 'Invalid email format';

            if (Object.keys(this.errors).length > 0) {
                return false;
            }

            return true;
        },

        checkVerificationCompany() {
            if (this.verification.uen === '')
                this.errors.uen = 'This is required field';
            if (this.verification.entity_name === '')
                this.errors.entity_name = 'This is required field';
            if (this.verification.entity_type === '')
                this.errors.entity_type = 'This is required field';
            if (this.verification.entity_status === '')
                this.errors.entity_status = 'This is required field';
            if (this.verification.registration_date === '')
                this.errors.registration_date = 'This is required field';
            if (this.verification.primary_activity === '')
                this.errors.primary_activity = 'This is required field';
            if (this.verification.address === '')
                this.errors.address = 'This is required field';
            if (this.type !== 'individual' && this.verification.shareholders.length === 0)
                this.errors.shareholders_error = 'At least one shareholder must be added';
            else if (this.verification.shareholders.filter(item => item === "").length > 0)
                this.errors.shareholders_error = 'This is required field';

            if (Object.keys(this.errors).length > 0) {
                return false;
            }

            return true;
        },
        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }

            this.is_processing = false;
        },
        getEndDate(date) {
            if (typeof date == "undefined") {
                return date;
            }

            try {
                let fromMonth = date.getMonth() + 1;

                if (fromMonth < 10) {
                    fromMonth = '0' + fromMonth;
                }

                let fromDay = date.getDate();

                if (fromDay < 10) {
                    fromDay = '0' + fromDay;
                }

                return date.getFullYear() + '-' + fromMonth + '-' + fromDay;
            } catch (err) {
                return date;
            }
        },
    },
    computed: {
        myinfourl() {
            const path = this.type === 'individual'
                ? 'personal'
                : 'business'

            return this.getDomain(`business/${this.business.id}/verification/${path}/redirect`, 'dashboard')
        },

        disableDates() {
            var date = new Date();
            date.setDate(date.getDate() - 1);
            return {
                from: date
            }
        },

        disableDateForDob() {
            let minDate = this.defaultDate;

            return {
                from: minDate
            }
        },

        defaultDate() {
            // minimal 13 year follow stripe limitation
            var minDate = new Date();
            var minYear = 13;

            minDate.setFullYear(minDate.getFullYear() - minYear);

            return minDate;
        },
    },
}
</script>
