<style scoped>

</style>
<template>
    <div>
        <div class="card">
            <div class="row no-gutters justify-content-center">
                <div class="col-12 col-md-6 d-flex">
                    <div class="card-body align-self-start">
                        <h5 class="text-primary text-center font-weight-bold mb-5">Personal</h5>
                        <div class="form-group">
                            <label for="nric">
                                {{ business.country == 'sg' ? form_names.sg.nric : form_names.my.nric }}
                            </label>
                            <input
                                id="nric"
                                class="form-control"
                                v-model="verification.nric"
                                :class="{'is-invalid' : errors.nric}"
                                :disabled="verification.nric"
                            />
                            <span class="invalid-feedback" role="alert">
                                {{ errors.nric }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input id="name"
                               class="form-control"
                               v-model="verification.name"
                               :class="{ 'is-invalid' : errors.name}"
                               :disabled="verification.name"
                            />
                            <span class="invalid-feedback" role="alert">
                                {{ errors.name }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label for="sex">Sex</label>
                            <input id="sex"
                               class="form-control"
                               v-model="verification.sex"
                               :class="{'is-invalid' : errors.sex}"
                               :disabled="fill_type!='manual'"
                            />
                            <span class="invalid-feedback" role="alert">
                                {{ errors.sex }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label for="residentialstatus">Residential Status</label>
                            <input id="residentialstatus" class="form-control"
                               v-model="verification.residentialstatus"
                               :class="{'is-invalid' : errors.residentialstatus}"
                               :disabled="fill_type!='manual'"
                            />
                            <span class="invalid-feedback" role="alert">
                                {{ errors.residentialstatus }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label for="nationality">Nationality</label>
                            <input id="nationality"
                               class="form-control"
                               v-model="verification.nationality"
                               :class="{'is-invalid' : errors.nationality}"
                               :disabled="fill_type!='manual'"
                            />
                            <span class="invalid-feedback" role="alert">
                                {{ errors.nationality }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <datepicker input-class="form-control w-100"
                                class="d-block w-100"
                                id="dob"
                                v-model="verification.dob"
                                :disabled-dates="disableDateForDob"
                                :open-date="defaultDate"
                                format="yyyy-MM-dd"
                                :class="{'is-invalid' : errors.dob}"
                                :disabled="true"
                            ></datepicker>
                            <span class="invalid-feedback" role="alert">
                                {{ errors.dob }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label for="regadd">Registered Address</label>
                            <textarea id="regadd" rows="6" class="form-control"
                                v-model="verification.regadd"
                                :disabled="true"
                                :class="{'is-invalid' : errors.regadd}"></textarea>
                            <span class="invalid-feedback" role="alert">
                                {{ errors.regadd }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input id="email"
                               class="form-control"
                               v-model="verification.email"
                               :class="{'is-invalid' : errors.email}"
                               :disabled="verification.email"
                            />
                            <span class="invalid-feedback" role="alert">
                                {{ errors.email }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label for="business_description">Business description</label>
                            <textarea id="business_description" rows="6" class="form-control"
                                      v-model="verification.business_description"
                                      :class="{'is-invalid' : errors.business_description}"
                                      :disabled="fill_type=='completed'"
                                      placeholder="Please add a description of the goods and services you are providing"></textarea>
                            <span class="invalid-feedback" role="alert">
                                {{ errors.business_description }}</span>
                        </div>
                        <template v-if="fill_type != 'completed' && (fill_type == 'manual' || type == 'individual')">
                            <div class="form-group border p-2">
                                <label class="form-label" for="supporting_documents">
                                    <br>{{ business.country == 'sg' ? form_names.sg.title_upload_document : form_names.my.title_upload_document }}<br/>
                                </label>
                                    <span class="small text-muted">
                                        <b>Corporate</b>
                                    </span>

                                    <div v-if="business.country == 'sg'">
                                        <span class="small text-muted">
                                            Please upload ACRA Biz File.
                                        </span>
                                    </div>
                                    <div v-if="business.country == 'my'">
                                        <span class="small text-muted">
                                            Please upload SSM Company Profile.
                                            <br>
                                            Notes: For Club/Society/Charities: In addition to SSM Company profile, kindly provide Certificate of Registration (e.g. from Registrar of Societies of Malaysia (ROS))
                                        </span>
                                    </div>

                                    <div v-if="business.country=='sg'">
                                        <div class="small text-muted mt-2">
                                            <b>Individual</b><br />
                                                Supporting documents can include government-approved licenses, screenshot of website and any other evidence that proves that you are accepting payments for selling goods and services.
                                            <br>
                                            <br>Please refer to section 6.3 of the  <a href="https://www.hitpayapp.com/acceptableusepolicy" target="_blank">Acceptable Use Policy</a>  for prohibited list of businesses.<br />
                                        </div>
                                    </div>

                                    <div v-if="business.country=='my'">
                                        <div class="small text-muted mt-4">
                                        <b>Individual</b><br />
                                            Supporting documents can include government-approved licenses, screenshot of website and any other evidence that proves that you are accepting payments for selling goods and services.
                                        <br>
                                        <br>Please refer to section 6.3 of the  <a href="https://www.hitpayapp.com/acceptableusepolicy" target="_blank">Acceptable Use Policy</a>  for prohibited list of businesses.<br />
                                        </div>
                                    </div>

                                <input type="file"
                                       class="form-control-file mt-3" id="supporting_documents"
                                       ref="supporting_documents" multiple="multiple"
                                       :class="{'is-invalid' : errors.supporting_documents}"/>
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.supporting_documents }}
                                </span>
                            </div>
                            <div class="form-group mt-2 border p-2">
                                <label class="form-label" for="identity_front">
                                    Please upload your
                                    {{ business.country == 'sg' ? form_names.sg.nric : form_names.my.nric }}
                                    (Front Side)
                                </label>
                                <input type="file" class="form-control-file mt-3" id="identity_front"
                                       placeholder="Please upload your NRIC copy front"
                                       ref="identity_front"
                                       :class="{'is-invalid' : errors.identity_front}"
                                />
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.identity_front }}
                                </span>
                            </div>
                            <div class="form-group border p-2">
                                <label class="form-label" for="identity_back">
                                    Please upload your
                                    {{ business.country == 'sg' ? form_names.sg.nric : form_names.my.nric }}
                                    (Back Side)
                                </label>
                                <input type="file" class="form-control-file mt-3" id="identity_back"
                                       placeholder="Please upload your NRIC copy back"
                                       ref="identity_back"
                                       :class="{'is-invalid' : errors.identity_back}"
                                />
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.identity_back }}
                                </span>
                            </div>
                        </template>
                    </div>
                </div>
                <div v-if="type == 'company'" class="col-12 col-md-6 d-flex">
                    <div class="card-body align-self-start">
                        <h5 class="text-primary text-center font-weight-bold mb-5">Corporate</h5>
                        <div class="form-group">
                            <label>{{ business.country == 'sg' ? form_names.sg.uen : form_names.my.uen }}</label>
                            <input class="form-control" id="uen" v-model="verification.uen" :disabled="fill_type!='manual'"
                                   :class="{'is-invalid' : errors.uen}">
                            <span class="invalid-feedback" role="alert">
                                {{ errors.uen }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label>{{ business.country == 'sg' ? form_names.sg.entity_name : form_names.my.entity_name }}</label>
                            <input class="form-control"
                               v-model="verification.entity_name"
                               id="entity_name"
                               :disabled="fill_type!='manual'"
                               :class="{'is-invalid' : errors.entity_name}" />
                            <span class="invalid-feedback" role="alert">
                                {{ errors.entity_name }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label>{{ business.country == 'sg' ? form_names.sg.entity_type : form_names.my.entity_type }}</label>
                            <select class="form-control" id="entity_type" v-model="verification.entity_type" :disabled="fill_type!='manual'"
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
                        <div class="form-group">
                            <label>{{ business.country == 'sg' ? form_names.sg.entity_status : form_names.my.entity_status }}</label>
                            <select class="form-control" id="entity_status" v-model="verification.entity_status" :disabled="fill_type!='manual'"
                                   :class="{'is-invalid' : errors.entity_status}">
                                <option value="LIVE">Live</option>
                                <option value="INACTIVE">Inactive</option>
                            </select>
                            <span class="invalid-feedback" role="alert">
                                {{ errors.entity_status }}</span>
                        </div>
                        <div class="form-group">
                            <label>Registration Date</label>
                            <datepicker input-class="form-control w-100"
                                        class="d-block w-100"
                                        id="registration_date"
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
                        <div class="form-group">
                            <label>{{ business.country == 'sg' ? form_names.sg.primary_activity_desc : form_names.my.primary_activity_desc }}</label>
                            <textarea class="form-control" id="primary_activity" v-model="verification.primary_activity" :disabled="fill_type!='manual'"
                                      :class="{'is-invalid' : errors.primary_activity}"></textarea>
                            <span class="invalid-feedback" role="alert">
                                {{ errors.primary_activity }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" rows="6" class="form-control"
                                      v-model="verification.address" :disabled="fill_type!='manual'"
                                      :class="{'is-invalid' : errors.address}"></textarea>
                            <span class="invalid-feedback" role="alert">
                                {{ errors.address }}
                            </span>
                        </div>
                        <div class="form-group">
                            <button
                                v-if="fill_type != 'completed' && verification.shareholders.length < 10"
                                :disabled="fill_type!='manual'"
                                class="btn btn-primary"
                                @click="addShareholder">
                                <i class="fa fa-user-plus"
                                   aria-hidden="true"
                                   @click="addShareholder"
                                >
                                </i>
                                Add Shareholder/Member
                            </button>
                            <span id="shareholders_error"></span>
                            <span v-if="errors.shareholders_error"
                                class="mb-2 invalid-feedback d-block" role="alert">
                                {{ errors.shareholders_error }}
                            </span>

                            <form>
                                <div v-for="(shareholder, key) in verification.shareholders" class="form-group p-4 mt-2 border">
                                    <div class="form-row" :id="`shareholder_${key}`">
                                        <div class="form-group col-md-9">
                                            <label>
                                                <b>SHAREHOLDER #{{ key + 1 }}</b>
                                            </label>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <i class="fa fa-user-times"
                                               aria-hidden="true"
                                               @click="fill_type != 'myinfo' ? deleteShareholder(key) : ''"
                                               v-if="fill_type != 'completed'"
                                            >
                                            </i>
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
                                                :disabled="(fill_type !='manual')"
                                                placeholder="input firstname of shareholder "
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
                                                :disabled="(fill_type !='manual')"
                                                placeholder="input lastname of shareholder "
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
                                            <label>Shareholder {{ business.country == 'sg' ? form_names.sg.nric : form_names.my.nric }}</label>
                                            <input
                                                class="form-control d-inline mb-2"
                                                v-model="verification.shareholders_id_number[key]"
                                                :class="{'is-invalid' : verification.shareholders_id_number_error[key]}"
                                                required="required"
                                                :disabled="(fill_type !='manual')"
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
                                                   :disabled="(fill_type !='manual')"
                                                   false-value="no"> Director

                                            <input type="checkbox"
                                                   v-model="verification.shareholders_is_owner[key]"
                                                   true-value="yes"
                                                   :disabled="(fill_type !='manual')"
                                                   false-value="no"> Owner

                                            <input type="checkbox"
                                                   v-model="verification.shareholders_is_executive[key]"
                                                   true-value="yes"
                                                   :disabled="(fill_type !='manual')"
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
                                                :disabled="(fill_type !='manual')"
                                                :class="{'is-invalid' : verification.shareholders_dob_error[key]}"
                                                placeholder="input date of birth shareholder "
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
                                                   :disabled="(fill_type !='manual')"
                                                   placeholder="input address of shareholder "
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
                                                   :disabled="(fill_type !='manual')"
                                                   placeholder="postal of shareholder "
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
                                                   placeholder="title of shareholder "
                                                   :disabled="(fill_type !='manual')"
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
                                                   placeholder="email of shareholder"
                                                   :disabled="(fill_type !='manual')"
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

                <div v-if="fill_type !='completed'"
                     class="form-check mb-3 col-12 text-center">
                    <input
                        type="checkbox" id="checkbox_agree"
                        class="form-check-input"
                        v-model="checkbox_agree"
                        :class="{'is-invalid' : errors.checkbox_agree}"
                        :disabled="is_processing " />
                    <label for="checkbox_agree" class="small text-muted form-check-label">I agree that I will use HitPay only for payment of goods and services</label>
                    <span
                        v-if="errors.checkbox_agree"
                        class="invalid-feedback d-block"
                        role="alert">
                        {{ errors.checkbox_agree }}
                    </span>
                </div>

                <button
                    v-if="fill_type !='completed'"
                    class="btn btn-primary btn-block"
                    :disabled="is_processing"
                    @click="saveVerification">
                    Confirm <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
                </button>

                <button
                    v-if="fill_type !='manual'"
                    class="btn btn-danger btn-block"
                    data-toggle="modal"
                    data-target="#confirmDeleteModal">
                    <template v-if="fill_type =='completed'">Delete</template>
                    <template v-else>Cancel</template>
                </button>

                <div id="confirmDeleteModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-body">
                                <h5 class="modal-title text-danger font-weight-bold mb-0">
                                    Are you sure you want to delete your account verification?
                                </h5>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal" :disabled="is_processing">
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
    name: "VerificationCognitoShow",
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
        },
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

        saveVerification() {
            this.is_processing = true;

            this.resetError();

            if (this.fill_type === 'manual') {
                if (this.type === 'company') {
                    this.mapFullname();

                    this.checkVerificationPersonal();

                    this.checkVerificationCompany();

                    this.checkHaveValidShareholderRelationship();
                } else {
                    this.checkVerificationPersonal();
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
                var nric = this.form_names[this.business.country].nric;
                this.errors.identity_front = "Please upload your "+nric+" copy front";
            }

            if (this.$refs.identity_back && this.$refs.identity_back.files.length == 0) {
                var nric = this.form_names[this.business.country].nric;
                this.errors.identity_back = "Please upload your "+nric+" copy back";
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
                return false;
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

            if (this.fill_type === 'manual') {
                this.verification.dob = this.getEndDate(this.verification.dob);

                if (this.type === 'company') {
                    this.verification.registration_date = this.getEndDate(this.verification.registration_date);

                    this.setDobShareholder();
                }
            }

            formData.append('type', this.type === 'company' ? 'business' : 'personal');
            formData.append('fill_type', this.fill_type);
            formData.append('verification', JSON.stringify(this.verification));

            var path = 'business/' + this.business.id + '/verification/cognito/' + this.verification_id;

            axios.post(this.getDomain(path, 'dashboard'), formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data'
                },
            }).then(({data}) => {
                this.is_processing = false;
                document.location.href=data;
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
                    document.location.href=data;
                })
        },

        checkVerificationPersonal() {
            if (!this.verification.nric || this.verification.nric === '')
                this.errors.nric = 'This is required field';
            if (!this.verification.name || this.verification.name === '')
                this.errors.name = 'This is required field';
            if (!this.verification.sex || this.verification.sex === '')
                this.errors.sex = 'This is required field';
            if (!this.verification.residentialstatus || this.verification.residentialstatus === '')
                this.errors.residentialstatus = 'This is required field';
            if (!this.verification.nationality || this.verification.nationality === '')
                this.errors.nationality = 'This is required field';
            if (!this.verification.dob || this.verification.dob === '')
                this.errors.dob = 'This is required field';
            if (!this.verification.regadd || this.verification.regadd === '')
                this.errors.regadd = 'This is required field';
            if (!this.verification.email || this.verification.email === '')
                this.errors.email = 'This is required field';
            else if (!(/\S+@\S+\.\S+/.test(this.verification.email)))
                this.errors.email = 'Invalid email format';

            if(this.errors.length > 0)
                return false;
            return true;
        },

        checkVerificationCompany() {
            if (!this.verification.uen || this.verification.uen == '') {
                this.errors.uen = 'This is required field';
            }
            if (!this.verification.entity_name || this.verification.entity_name == '')
                this.errors.entity_name = 'This is required field';
            if (!this.verification.entity_type || this.verification.entity_type === '')
                this.errors.entity_type = 'This is required field';
            if (!this.verification.entity_status || this.verification.entity_status === '')
                this.errors.entity_status = 'This is required field';
            if (!this.verification.registration_date || this.verification.registration_date === '')
                this.errors.registration_date = 'This is required field';
            if (!this.verification.primary_activity || this.verification.primary_activity === '')
                this.errors.primary_activity = 'This is required field';
            if (!this.verification.address || this.verification.address === '')
                this.errors.address = 'This is required field';
            if (this.verification.shareholders.length == 0)
                this.errors.shareholders_error = 'At least one shareholder must be added';
            else if(this.verification.shareholders.filter(item => item === "").length > 0)
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
    data() {
        return {
            type: 'company',
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
            form_names: {
                'sg': {
                    'nric': 'NRIC / FIN',
                    'uen': 'UEN',
                    'acra_biz_file_name': 'Please upload ACRA Biz File.',
                    'title_upload_document': 'Upload supporting documents for proof of business operations*',
                    'title_business_upload_document': '',
                    'primary_activity_desc': 'Primary Activity Description',
                    'entity_name': 'Entity Name',
                    'entity_type': 'Entity Type',
                    'entity_status': 'Entity Status',
                },
                'my': {
                    'nric': 'MyCoID / Identity Number',
                    'uen': 'Company Number',
                    'acra_biz_file_name': 'Please upload SSM Company File.',
                    'title_upload_document': 'Upload supporting documents for proof of business operations*',
                    'primary_activity_desc': 'Nature of Business',
                    'entity_name': 'Company Name',
                    'entity_type': 'Company Type',
                    'entity_status': 'Company Status',
                }
            }
        }
    },
    mounted() {
        this.business = window.Business;

        this.type = this.business.business_type === 'company' ? 'company' : 'individual';

        if (window.Verification) {
            this.verification = Verification;

            if (Verification.dob) {
                this.verification.dob = new Date(Verification.dob);
            }

            if (Verification.registration_date) {
                this.verification.registration_date = new Date(Verification.registration_date);
            }
        }
    },
    computed: {
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
