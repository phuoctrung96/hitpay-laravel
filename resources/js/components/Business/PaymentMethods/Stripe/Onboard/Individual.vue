<template>
    <div class="p-4">
        <h2 class="text-primary mb-4">Verification Summary</h2>
        <p>Please check your business detail and management ownership below.</p>

        <h4 class="mt-3">Business Details</h4>

        <div :class="(this.is_business_error) ? 'border-danger' : 'border-primary'"
             class="card mb-3"
             style="max-width: 25rem;"
        >
            <div class="card-header bg-primary text-light">
                {{ this.provider.data.account.business_profile.name }} -
                {{ this.provider.data.account.business_profile.url }}
            </div>
            <div class="card-body text-secondary">
                <h5 class="card-text">
                    {{ this.provider.data.account.business_profile.email }}
                </h5>

                <p class="card-text" v-if="!is_business_error">
                    <a class="btn btn-primary"
                       @click="onEditCompany()"
                       href="#management_company_modal"
                       data-toggle="modal" data-target="#management_company_modal"
                    ><i class="fa" :class="(is_on_edit_company) ? 'fa-minus' : 'fa-search'"></i></a>
                </p>

                <p class="card-text text-danger" v-if="is_business_error && !is_verified">
                    Missing Requirement Information, Please click here
                    <br />
                    <a class="btn btn-primary"
                       @click="onEditCompany()"
                       href="#management_company_modal"
                       data-toggle="modal" data-target="#management_company_modal"
                    ><i class="fa" :class="(is_on_edit_company) ? 'fa-minus' : 'fa-search'"></i></a>
                </p>
            </div>
        </div>

        <div class="modal fade" id="management_company_modal" tabindex="-1"
             role="dialog" aria-labelledby="modalCompany" aria-hidden="true"
        >
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"
                            id="modalCompany">
                            {{ this.provider.data.account.business_profile.name }} -
                            {{ this.provider.data.account.business_profile.url }}
                        </h5>
                        <button
                            type="button" @click="closeModal()"
                            class="close" data-dismiss="modal"
                            aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="business_detail_form">
                            <div class="form-group">
                                <label class="col-form-label">Legal business name</label>
                                <input id="business_name" v-model="provider.data.account.business_profile.name"
                                       class="form-control"
                                       :class="{'is-invalid' : errors.business_name}"
                                >
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.business_name }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label class="col-form-label">Website</label>
                                <input id="business_url"
                                       v-model="provider.data.account.business_profile.url"
                                       class="form-control"
                                       :class="{'is-invalid' : errors.business_url}"
                                >
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.business_url }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label class="col-form-label">Registered business address</label>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label>Country</label>
                                            <select id="business_country" disabled v-model="provider.data.account.company.address.country"
                                                    class="form-control"
                                                    :class="{'is-invalid' : errors.business_country}"
                                            >
                                                <option value="" disabled>Country</option>
                                                <option v-for="country in countries" :value="country.code">{{ country.name }}</option>
                                            </select>
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.business_country }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label>State</label>
                                            <input
                                                id="business_state"
                                                v-model="provider.data.account.company.address.state"
                                                class="form-control"
                                                :disabled="is_verified"
                                                :class="{'is-invalid' : errors.business_state}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.business_state }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group">
                                            <label>City</label>
                                            <input
                                                id="business_city"
                                                v-model="provider.data.account.company.address.city"
                                                class="form-control"
                                                :disabled="is_verified"
                                                :class="{'is-invalid' : errors.business_city}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.business_city }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label>Address</label>
                                            <input
                                                id="business_line1"
                                                v-model="provider.data.account.company.address.line1"
                                                class="form-control"
                                                :disabled="is_verified"
                                                :class="{'is-invalid' : errors.business_line1}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.business_line1 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label>Postal Code</label>
                                            <input
                                                id="business_postal_code"
                                                v-model="provider.data.account.company.address.postal_code"
                                                class="form-control"
                                                :disabled="is_verified"
                                                :class="{'is-invalid' : errors.business_postal_code}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.business_postal_code }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-form-label">Phone</label>
                                <input
                                    id="business_phone"
                                    v-model="provider.data.account.company.phone"
                                    class="form-control"
                                    placeholder=""
                                    :class="{'is-invalid' : errors.business_phone}"
                                >
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.business_phone }}
                                </span>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <span v-if="use_connect_onboarding" class="small text-info">You cant update this. Please use connect button</span>
                        <button type="button" @click="closeModal()" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button"
                            @click="saveCompany()"
                            class="btn btn-primary"
                            :disabled="is_processing || !is_on_edit_company || use_connect_onboarding"
                        >
                            Save <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <h4 class="mt-3">Management and Ownership</h4>

        <div
            :class="(this.is_person_error) ?
                'border-danger':'border-secondary'"
            class="card mb-3"
            style="max-width: 25rem;"
        >
            <div class="card-header bg-primary text-light">
                {{ active_person.first_name }}
                {{ active_person.last_name }}
            </div>
            <div class="card-body">
                <h5 class="card-text">
                    {{ active_person.email }}
                </h5>

                <p class="card-text" v-if="!is_person_error">
                    <a class="btn btn-primary"
                       href="#management_ownership_modal"
                       data-toggle="modal" data-target="#management_ownership_modal"
                       @click="onEditPerson()"
                    ><i class="fa" :class="(is_on_edit_person) ? 'fa-minus' : 'fa-search'"></i></a>
                </p>

                <p class="card-text text-danger" v-if="is_person_error && !is_verified">
                    Missing Requirement Information, Please click here
                    <a class="btn btn-primary"
                       href="#management_ownership_modal"
                       data-toggle="modal" data-target="#management_ownership_modal"
                       @click="onEditPerson()"
                    ><i class="fa" :class="(is_on_edit_person) ? 'fa-minus' : 'fa-search'"></i></a>
                </p>
            </div>
        </div>

        <div class="modal fade" id="management_ownership_modal" tabindex="-1"
             role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
        >
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"
                            id="exampleModalLabel">
                            {{ active_person.first_name }} {{ active_person.last_name }}
                        </h5>
                        <button
                            type="button" @click="closeModal()"
                            class="close" data-dismiss="modal"
                            aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="person-detail">
                            <div class="form-group">
                                <label class="col-form-label">Full Name aliases</label>
                                <input
                                    id="person_full_name_aliases"
                                    class="form-control"
                                    v-model="active_person.full_name_aliases"
                                    :class="{'is-invalid' : errors.person_full_name_aliases}"
                                />
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.person_full_name_aliases }}
                                </span>
                            </div>
                            <div class="form-group">
                                <label class="col-form-label">First name</label>
                                <input
                                    id="person_first_name"
                                    v-model="active_person.first_name"
                                    class="form-control"
                                    :class="{'is-invalid' : errors.person_first_name}"
                                />
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.person_first_name }}
                                </span>
                            </div>
                            <div class="form-group">
                                <label class="col-form-label">Last name</label>
                                <input
                                    id="person_last_name"
                                    v-model="active_person.last_name"
                                    class="form-control"
                                    :class="{'is-invalid' : errors.person_last_name}"
                                />
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.person_last_name }}
                                </span>
                            </div>
                            <div class="form-group">
                                <label class="col-form-label">Email</label>
                                <input
                                    id="person_email"
                                    v-model="active_person.email"
                                    class="form-control"
                                    :class="{'is-invalid' : errors.person_email}"
                                />
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.person_email }}
                                </span>
                            </div>
                            <div class="form-group">
                                <label class="col-form-label">Date of Birth</label>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label>Day <small>(1-31)</small></label>
                                            <input
                                                type="number"
                                                min="1" max="31"
                                                id="person_dob_day"
                                                v-model="active_person.dob_day"
                                                class="form-control"
                                                :class="{'is-invalid' : errors.person_dob_day}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.person_dob_day }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group">
                                            <label>Month <small>(1-12)</small></label>
                                            <input
                                                id="person_dob_month"
                                                type="number"
                                                min="1" max="12"
                                                v-model="active_person.dob_month"
                                                class="form-control"
                                                :class="{'is-invalid' : errors.person_dob_month}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.person_dob_month }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group">
                                            <label>Year</label>
                                            <input
                                                id="person_dob_year"
                                                v-model="active_person.dob_year"
                                                class="form-control"
                                                :class="{'is-invalid' : errors.person_dob_year}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.person_dob_year }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-form-label">Home Address</label>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label>Country</label>
                                            <select
                                                id="person_address_country"
                                                v-model="active_person.address_country"
                                                class="form-control"
                                                :class="{'is-invalid' : errors.person_address_country}"
                                            >
                                                <option value="" disabled>Country</option>
                                                <option v-for="country in countries" :value="country.code">{{ country.name }}</option>
                                            </select>
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.person_address_country }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label>State</label>
                                            <input
                                                id="person_address_state"
                                                v-model="active_person.address_state"
                                                class="form-control"
                                                :class="{'is-invalid' : errors.person_address_state}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.person_address_state }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group">
                                            <label>City</label>
                                            <input
                                                id="person_address_city"
                                                v-model="active_person.address_city"
                                                class="form-control"
                                                :class="{'is-invalid' : errors.person_address_city}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.person_address_city }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label>Address</label>
                                            <input
                                                id="person_address_line1"
                                                v-model="active_person.address_line1"
                                                class="form-control"
                                                :class="{'is-invalid' : errors.person_address_line1}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.person_address_line1 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label>Address 2</label>
                                            <input
                                                id="person_address_line2"
                                                v-model="active_person.address_line2"
                                                class="form-control"
                                                :class="{'is-invalid' : errors.person_address_line2}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.person_address_line2 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col">
                                        <div class="form-group">
                                            <label>Postal Code</label>
                                            <input
                                                id="person_address_postal_code"
                                                v-model="active_person.address_postal_code"
                                                class="form-control"
                                                :class="{'is-invalid' : errors.person_address_postal_code}"
                                            />
                                            <span class="invalid-feedback" role="alert">
                                                {{ errors.person_address_postal_code }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-form-label">Phone Number</label>
                                <input
                                    id="person_phone"
                                    v-model="active_person.phone"
                                    class="form-control"
                                    placeholder="Phone Number"
                                    :class="{'is-invalid' : errors.person_phone}"
                                >
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.person_phone }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Percent Ownership <i class="fa fa-info-circle" data-toggle="tooltip" title="Decimal point with max 100"></i></label>
                                <input type="number" min="0" max="101" step="0.1"
                                       id="person_percent_ownership"
                                       v-model="active_person.percent_ownership"
                                       class="form-control"
                                       :class="{'is-invalid' : errors.person_percent_ownership}"
                                />
                                <span class="invalid-feedback" role="alert">
                                  {{ errors.person_percent_ownership }}
                              </span>
                            </div>

                            <div class="form-group" v-if="active_person.id_number_provided === false">
                                <label class="col-form-label">
                                    {{ business.country == 'sg' ? form_names.sg.nric : form_names.my.nric }}
                                </label>
                                <input v-model="provider.data.account.individual.id_number" class="form-control">
                                <p>
                                    To verify your identity, we’ll need to know your Identity Number.
                                </p>
                            </div>

                            <div class="form-group" v-if="active_person.is_need_upload_document">
                                <label class="col-form-label">Upload Verification documents</label>
                                <p>
                                    Please pick which document you'd like to upload
                                    for additional identity document verification
                                </p>
                                <input
                                    type="file"
                                    class="form-control-file mt-3" id="supporting_documents"
                                    ref="supporting_documents"
                                    :class="{'is-invalid' : errors.supporting_documents}"
                                />
                                <span class="invalid-feedback" role="alert">
                                    {{ errors.supporting_documents }}
                                </span>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <span v-if="use_connect_onboarding" class="small text-info">You cant update this. Please use connect button</span>
                        <button type="button" @click="closeModal()" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button"
                                @click="savePerson()"
                                class="btn btn-primary"
                                :disabled="is_processing || !is_on_edit_person || use_connect_onboarding"
                        >
                            Save <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3" v-if="!is_tos_acceptance">
            <div class="col-md-4">
                <div class="form-group">
                <p>
                    By clicking Submit, you agree that the information provided is accurate
                    to the best of your knowledge.
                </p>
                <button
                    class="btn btn-primary"
                    :disabled="is_processing || is_on_edit_person || is_on_edit_company"
                    @click="saveTos()"
                >
                    Submit <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
                </button>
            </div>
            </div>
        </div>

        <div class="row mt-3" v-if="is_verified">
            <div class="col-md-4">
                <div class="form-group">
                    <p>
                        Your account is verified!
                    </p>
                </div>
            </div>
        </div>

        <div class="row mt-3" v-if="use_connect_onboarding">
            <div class="col-md-4">
                <div class="form-group">
                    <p>
                        Your account has provided enough information to process payments and receive payouts.
                        Provide additional information in order to keep this account in good standing.
                    </p>
                    <button
                        class="btn btn-primary"
                        :disabled="is_processing"
                        @click="connectOnboarding()"
                    >
                        Connect <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'StripeOnboardVerificationIndividual',
    props: {
        provider: Object,
        account: Object,
        persons: Array,
        type: String,
        countries: Object,
        document_company: Array,
    },
    data () {
        return {
            business: window.Business,
            //csrf token
            csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            is_processing: false,
            is_person_set: false,
            disabled_reason: '',
            disabled_reason_obj: '',
            currently_due: [],
            eventually_due: [],
            is_business_error: false,
            is_person_error: false,
            is_on_edit_company: false,
            is_on_edit_person: false,
            is_tos_acceptance: false,
            is_verified: false,
            is_requirements_error: false,
            use_connect_onboarding: false,
            is_payouts_enabled: false,
            is_charges_enabled: false,
            errors: {},
            active_person: {
                id: '',
                id_number: '',
                id_number_provided: '',
                phone: '',
                address_postal_code: '',
                address_line2: '',
                address_line1: '',
                address_city: '',
                address_state: '',
                address_country: '',
                dob_day: '',
                dob_year: '',
                dob_month: '',
                email: '',
                title: '',
                first_name: '',
                last_name: '',
                full_name_aliases: '',
                is_need_upload_document: true,
                percent_ownership: '',
            },
            countryCodes: [
                {
                    "name": "Afghanistan",
                    "dial_code": "+93",
                    "code": "AF"
                },
                {
                    "name": "Aland Islands",
                    "dial_code": "+358",
                    "code": "AX"
                },
                {
                    "name": "Albania",
                    "dial_code": "+355",
                    "code": "AL"
                },
                {
                    "name": "Algeria",
                    "dial_code": "+213",
                    "code": "DZ"
                },
                {
                    "name": "AmericanSamoa",
                    "dial_code": "+1684",
                    "code": "AS"
                },
                {
                    "name": "Andorra",
                    "dial_code": "+376",
                    "code": "AD"
                },
                {
                    "name": "Angola",
                    "dial_code": "+244",
                    "code": "AO"
                },
                {
                    "name": "Anguilla",
                    "dial_code": "+1264",
                    "code": "AI"
                },
                {
                    "name": "Antarctica",
                    "dial_code": "+672",
                    "code": "AQ"
                },
                {
                    "name": "Antigua and Barbuda",
                    "dial_code": "+1268",
                    "code": "AG"
                },
                {
                    "name": "Argentina",
                    "dial_code": "+54",
                    "code": "AR"
                },
                {
                    "name": "Armenia",
                    "dial_code": "+374",
                    "code": "AM"
                },
                {
                    "name": "Aruba",
                    "dial_code": "+297",
                    "code": "AW"
                },
                {
                    "name": "Australia",
                    "dial_code": "+61",
                    "code": "AU"
                },
                {
                    "name": "Austria",
                    "dial_code": "+43",
                    "code": "AT"
                },
                {
                    "name": "Azerbaijan",
                    "dial_code": "+994",
                    "code": "AZ"
                },
                {
                    "name": "Bahamas",
                    "dial_code": "+1242",
                    "code": "BS"
                },
                {
                    "name": "Bahrain",
                    "dial_code": "+973",
                    "code": "BH"
                },
                {
                    "name": "Bangladesh",
                    "dial_code": "+880",
                    "code": "BD"
                },
                {
                    "name": "Barbados",
                    "dial_code": "+1246",
                    "code": "BB"
                },
                {
                    "name": "Belarus",
                    "dial_code": "+375",
                    "code": "BY"
                },
                {
                    "name": "Belgium",
                    "dial_code": "+32",
                    "code": "BE"
                },
                {
                    "name": "Belize",
                    "dial_code": "+501",
                    "code": "BZ"
                },
                {
                    "name": "Benin",
                    "dial_code": "+229",
                    "code": "BJ"
                },
                {
                    "name": "Bermuda",
                    "dial_code": "+1441",
                    "code": "BM"
                },
                {
                    "name": "Bhutan",
                    "dial_code": "+975",
                    "code": "BT"
                },
                {
                    "name": "Bolivia, Plurinational State of",
                    "dial_code": "+591",
                    "code": "BO"
                },
                {
                    "name": "Bosnia and Herzegovina",
                    "dial_code": "+387",
                    "code": "BA"
                },
                {
                    "name": "Botswana",
                    "dial_code": "+267",
                    "code": "BW"
                },
                {
                    "name": "Brazil",
                    "dial_code": "+55",
                    "code": "BR"
                },
                {
                    "name": "British Indian Ocean Territory",
                    "dial_code": "+246",
                    "code": "IO"
                },
                {
                    "name": "Brunei Darussalam",
                    "dial_code": "+673",
                    "code": "BN"
                },
                {
                    "name": "Bulgaria",
                    "dial_code": "+359",
                    "code": "BG"
                },
                {
                    "name": "Burkina Faso",
                    "dial_code": "+226",
                    "code": "BF"
                },
                {
                    "name": "Burundi",
                    "dial_code": "+257",
                    "code": "BI"
                },
                {
                    "name": "Cambodia",
                    "dial_code": "+855",
                    "code": "KH"
                },
                {
                    "name": "Cameroon",
                    "dial_code": "+237",
                    "code": "CM"
                },
                {
                    "name": "Canada",
                    "dial_code": "+1",
                    "code": "CA"
                },
                {
                    "name": "Cape Verde",
                    "dial_code": "+238",
                    "code": "CV"
                },
                {
                    "name": "Cayman Islands",
                    "dial_code": "+ 345",
                    "code": "KY"
                },
                {
                    "name": "Central African Republic",
                    "dial_code": "+236",
                    "code": "CF"
                },
                {
                    "name": "Chad",
                    "dial_code": "+235",
                    "code": "TD"
                },
                {
                    "name": "Chile",
                    "dial_code": "+56",
                    "code": "CL"
                },
                {
                    "name": "China",
                    "dial_code": "+86",
                    "code": "CN"
                },
                {
                    "name": "Christmas Island",
                    "dial_code": "+61",
                    "code": "CX"
                },
                {
                    "name": "Cocos (Keeling) Islands",
                    "dial_code": "+61",
                    "code": "CC"
                },
                {
                    "name": "Colombia",
                    "dial_code": "+57",
                    "code": "CO"
                },
                {
                    "name": "Comoros",
                    "dial_code": "+269",
                    "code": "KM"
                },
                {
                    "name": "Congo",
                    "dial_code": "+242",
                    "code": "CG"
                },
                {
                    "name": "Congo, The Democratic Republic of the Congo",
                    "dial_code": "+243",
                    "code": "CD"
                },
                {
                    "name": "Cook Islands",
                    "dial_code": "+682",
                    "code": "CK"
                },
                {
                    "name": "Costa Rica",
                    "dial_code": "+506",
                    "code": "CR"
                },
                {
                    "name": "Cote d'Ivoire",
                    "dial_code": "+225",
                    "code": "CI"
                },
                {
                    "name": "Croatia",
                    "dial_code": "+385",
                    "code": "HR"
                },
                {
                    "name": "Cuba",
                    "dial_code": "+53",
                    "code": "CU"
                },
                {
                    "name": "Cyprus",
                    "dial_code": "+357",
                    "code": "CY"
                },
                {
                    "name": "Czech Republic",
                    "dial_code": "+420",
                    "code": "CZ"
                },
                {
                    "name": "Denmark",
                    "dial_code": "+45",
                    "code": "DK"
                },
                {
                    "name": "Djibouti",
                    "dial_code": "+253",
                    "code": "DJ"
                },
                {
                    "name": "Dominica",
                    "dial_code": "+1767",
                    "code": "DM"
                },
                {
                    "name": "Dominican Republic",
                    "dial_code": "+1849",
                    "code": "DO"
                },
                {
                    "name": "Ecuador",
                    "dial_code": "+593",
                    "code": "EC"
                },
                {
                    "name": "Egypt",
                    "dial_code": "+20",
                    "code": "EG"
                },
                {
                    "name": "El Salvador",
                    "dial_code": "+503",
                    "code": "SV"
                },
                {
                    "name": "Equatorial Guinea",
                    "dial_code": "+240",
                    "code": "GQ"
                },
                {
                    "name": "Eritrea",
                    "dial_code": "+291",
                    "code": "ER"
                },
                {
                    "name": "Estonia",
                    "dial_code": "+372",
                    "code": "EE"
                },
                {
                    "name": "Ethiopia",
                    "dial_code": "+251",
                    "code": "ET"
                },
                {
                    "name": "Falkland Islands (Malvinas)",
                    "dial_code": "+500",
                    "code": "FK"
                },
                {
                    "name": "Faroe Islands",
                    "dial_code": "+298",
                    "code": "FO"
                },
                {
                    "name": "Fiji",
                    "dial_code": "+679",
                    "code": "FJ"
                },
                {
                    "name": "Finland",
                    "dial_code": "+358",
                    "code": "FI"
                },
                {
                    "name": "France",
                    "dial_code": "+33",
                    "code": "FR"
                },
                {
                    "name": "French Guiana",
                    "dial_code": "+594",
                    "code": "GF"
                },
                {
                    "name": "French Polynesia",
                    "dial_code": "+689",
                    "code": "PF"
                },
                {
                    "name": "Gabon",
                    "dial_code": "+241",
                    "code": "GA"
                },
                {
                    "name": "Gambia",
                    "dial_code": "+220",
                    "code": "GM"
                },
                {
                    "name": "Georgia",
                    "dial_code": "+995",
                    "code": "GE"
                },
                {
                    "name": "Germany",
                    "dial_code": "+49",
                    "code": "DE"
                },
                {
                    "name": "Ghana",
                    "dial_code": "+233",
                    "code": "GH"
                },
                {
                    "name": "Gibraltar",
                    "dial_code": "+350",
                    "code": "GI"
                },
                {
                    "name": "Greece",
                    "dial_code": "+30",
                    "code": "GR"
                },
                {
                    "name": "Greenland",
                    "dial_code": "+299",
                    "code": "GL"
                },
                {
                    "name": "Grenada",
                    "dial_code": "+1473",
                    "code": "GD"
                },
                {
                    "name": "Guadeloupe",
                    "dial_code": "+590",
                    "code": "GP"
                },
                {
                    "name": "Guam",
                    "dial_code": "+1671",
                    "code": "GU"
                },
                {
                    "name": "Guatemala",
                    "dial_code": "+502",
                    "code": "GT"
                },
                {
                    "name": "Guernsey",
                    "dial_code": "+44",
                    "code": "GG"
                },
                {
                    "name": "Guinea",
                    "dial_code": "+224",
                    "code": "GN"
                },
                {
                    "name": "Guinea-Bissau",
                    "dial_code": "+245",
                    "code": "GW"
                },
                {
                    "name": "Guyana",
                    "dial_code": "+595",
                    "code": "GY"
                },
                {
                    "name": "Haiti",
                    "dial_code": "+509",
                    "code": "HT"
                },
                {
                    "name": "Holy See (Vatican City State)",
                    "dial_code": "+379",
                    "code": "VA"
                },
                {
                    "name": "Honduras",
                    "dial_code": "+504",
                    "code": "HN"
                },
                {
                    "name": "Hong Kong",
                    "dial_code": "+852",
                    "code": "HK"
                },
                {
                    "name": "Hungary",
                    "dial_code": "+36",
                    "code": "HU"
                },
                {
                    "name": "Iceland",
                    "dial_code": "+354",
                    "code": "IS"
                },
                {
                    "name": "India",
                    "dial_code": "+91",
                    "code": "IN"
                },
                {
                    "name": "Indonesia",
                    "dial_code": "+62",
                    "code": "ID"
                },
                {
                    "name": "Iran, Islamic Republic of Persian Gulf",
                    "dial_code": "+98",
                    "code": "IR"
                },
                {
                    "name": "Iraq",
                    "dial_code": "+964",
                    "code": "IQ"
                },
                {
                    "name": "Ireland",
                    "dial_code": "+353",
                    "code": "IE"
                },
                {
                    "name": "Isle of Man",
                    "dial_code": "+44",
                    "code": "IM"
                },
                {
                    "name": "Israel",
                    "dial_code": "+972",
                    "code": "IL"
                },
                {
                    "name": "Italy",
                    "dial_code": "+39",
                    "code": "IT"
                },
                {
                    "name": "Jamaica",
                    "dial_code": "+1876",
                    "code": "JM"
                },
                {
                    "name": "Japan",
                    "dial_code": "+81",
                    "code": "JP"
                },
                {
                    "name": "Jersey",
                    "dial_code": "+44",
                    "code": "JE"
                },
                {
                    "name": "Jordan",
                    "dial_code": "+962",
                    "code": "JO"
                },
                {
                    "name": "Kazakhstan",
                    "dial_code": "+77",
                    "code": "KZ"
                },
                {
                    "name": "Kenya",
                    "dial_code": "+254",
                    "code": "KE"
                },
                {
                    "name": "Kiribati",
                    "dial_code": "+686",
                    "code": "KI"
                },
                {
                    "name": "Korea, Democratic People's Republic of Korea",
                    "dial_code": "+850",
                    "code": "KP"
                },
                {
                    "name": "Korea, Republic of South Korea",
                    "dial_code": "+82",
                    "code": "KR"
                },
                {
                    "name": "Kuwait",
                    "dial_code": "+965",
                    "code": "KW"
                },
                {
                    "name": "Kyrgyzstan",
                    "dial_code": "+996",
                    "code": "KG"
                },
                {
                    "name": "Laos",
                    "dial_code": "+856",
                    "code": "LA"
                },
                {
                    "name": "Latvia",
                    "dial_code": "+371",
                    "code": "LV"
                },
                {
                    "name": "Lebanon",
                    "dial_code": "+961",
                    "code": "LB"
                },
                {
                    "name": "Lesotho",
                    "dial_code": "+266",
                    "code": "LS"
                },
                {
                    "name": "Liberia",
                    "dial_code": "+231",
                    "code": "LR"
                },
                {
                    "name": "Libyan Arab Jamahiriya",
                    "dial_code": "+218",
                    "code": "LY"
                },
                {
                    "name": "Liechtenstein",
                    "dial_code": "+423",
                    "code": "LI"
                },
                {
                    "name": "Lithuania",
                    "dial_code": "+370",
                    "code": "LT"
                },
                {
                    "name": "Luxembourg",
                    "dial_code": "+352",
                    "code": "LU"
                },
                {
                    "name": "Macao",
                    "dial_code": "+853",
                    "code": "MO"
                },
                {
                    "name": "Macedonia",
                    "dial_code": "+389",
                    "code": "MK"
                },
                {
                    "name": "Madagascar",
                    "dial_code": "+261",
                    "code": "MG"
                },
                {
                    "name": "Malawi",
                    "dial_code": "+265",
                    "code": "MW"
                },
                {
                    "name": "Malaysia",
                    "dial_code": "+60",
                    "code": "MY"
                },
                {
                    "name": "Maldives",
                    "dial_code": "+960",
                    "code": "MV"
                },
                {
                    "name": "Mali",
                    "dial_code": "+223",
                    "code": "ML"
                },
                {
                    "name": "Malta",
                    "dial_code": "+356",
                    "code": "MT"
                },
                {
                    "name": "Marshall Islands",
                    "dial_code": "+692",
                    "code": "MH"
                },
                {
                    "name": "Martinique",
                    "dial_code": "+596",
                    "code": "MQ"
                },
                {
                    "name": "Mauritania",
                    "dial_code": "+222",
                    "code": "MR"
                },
                {
                    "name": "Mauritius",
                    "dial_code": "+230",
                    "code": "MU"
                },
                {
                    "name": "Mayotte",
                    "dial_code": "+262",
                    "code": "YT"
                },
                {
                    "name": "Mexico",
                    "dial_code": "+52",
                    "code": "MX"
                },
                {
                    "name": "Micronesia, Federated States of Micronesia",
                    "dial_code": "+691",
                    "code": "FM"
                },
                {
                    "name": "Moldova",
                    "dial_code": "+373",
                    "code": "MD"
                },
                {
                    "name": "Monaco",
                    "dial_code": "+377",
                    "code": "MC"
                },
                {
                    "name": "Mongolia",
                    "dial_code": "+976",
                    "code": "MN"
                },
                {
                    "name": "Montenegro",
                    "dial_code": "+382",
                    "code": "ME"
                },
                {
                    "name": "Montserrat",
                    "dial_code": "+1664",
                    "code": "MS"
                },
                {
                    "name": "Morocco",
                    "dial_code": "+212",
                    "code": "MA"
                },
                {
                    "name": "Mozambique",
                    "dial_code": "+258",
                    "code": "MZ"
                },
                {
                    "name": "Myanmar",
                    "dial_code": "+95",
                    "code": "MM"
                },
                {
                    "name": "Namibia",
                    "dial_code": "+264",
                    "code": "NA"
                },
                {
                    "name": "Nauru",
                    "dial_code": "+674",
                    "code": "NR"
                },
                {
                    "name": "Nepal",
                    "dial_code": "+977",
                    "code": "NP"
                },
                {
                    "name": "Netherlands",
                    "dial_code": "+31",
                    "code": "NL"
                },
                {
                    "name": "Netherlands Antilles",
                    "dial_code": "+599",
                    "code": "AN"
                },
                {
                    "name": "New Caledonia",
                    "dial_code": "+687",
                    "code": "NC"
                },
                {
                    "name": "New Zealand",
                    "dial_code": "+64",
                    "code": "NZ"
                },
                {
                    "name": "Nicaragua",
                    "dial_code": "+505",
                    "code": "NI"
                },
                {
                    "name": "Niger",
                    "dial_code": "+227",
                    "code": "NE"
                },
                {
                    "name": "Nigeria",
                    "dial_code": "+234",
                    "code": "NG"
                },
                {
                    "name": "Niue",
                    "dial_code": "+683",
                    "code": "NU"
                },
                {
                    "name": "Norfolk Island",
                    "dial_code": "+672",
                    "code": "NF"
                },
                {
                    "name": "Northern Mariana Islands",
                    "dial_code": "+1670",
                    "code": "MP"
                },
                {
                    "name": "Norway",
                    "dial_code": "+47",
                    "code": "NO"
                },
                {
                    "name": "Oman",
                    "dial_code": "+968",
                    "code": "OM"
                },
                {
                    "name": "Pakistan",
                    "dial_code": "+92",
                    "code": "PK"
                },
                {
                    "name": "Palau",
                    "dial_code": "+680",
                    "code": "PW"
                },
                {
                    "name": "Palestinian Territory, Occupied",
                    "dial_code": "+970",
                    "code": "PS"
                },
                {
                    "name": "Panama",
                    "dial_code": "+507",
                    "code": "PA"
                },
                {
                    "name": "Papua New Guinea",
                    "dial_code": "+675",
                    "code": "PG"
                },
                {
                    "name": "Paraguay",
                    "dial_code": "+595",
                    "code": "PY"
                },
                {
                    "name": "Peru",
                    "dial_code": "+51",
                    "code": "PE"
                },
                {
                    "name": "Philippines",
                    "dial_code": "+63",
                    "code": "PH"
                },
                {
                    "name": "Pitcairn",
                    "dial_code": "+872",
                    "code": "PN"
                },
                {
                    "name": "Poland",
                    "dial_code": "+48",
                    "code": "PL"
                },
                {
                    "name": "Portugal",
                    "dial_code": "+351",
                    "code": "PT"
                },
                {
                    "name": "Puerto Rico",
                    "dial_code": "+1939",
                    "code": "PR"
                },
                {
                    "name": "Qatar",
                    "dial_code": "+974",
                    "code": "QA"
                },
                {
                    "name": "Romania",
                    "dial_code": "+40",
                    "code": "RO"
                },
                {
                    "name": "Russia",
                    "dial_code": "+7",
                    "code": "RU"
                },
                {
                    "name": "Rwanda",
                    "dial_code": "+250",
                    "code": "RW"
                },
                {
                    "name": "Reunion",
                    "dial_code": "+262",
                    "code": "RE"
                },
                {
                    "name": "Saint Barthelemy",
                    "dial_code": "+590",
                    "code": "BL"
                },
                {
                    "name": "Saint Helena, Ascension and Tristan Da Cunha",
                    "dial_code": "+290",
                    "code": "SH"
                },
                {
                    "name": "Saint Kitts and Nevis",
                    "dial_code": "+1869",
                    "code": "KN"
                },
                {
                    "name": "Saint Lucia",
                    "dial_code": "+1758",
                    "code": "LC"
                },
                {
                    "name": "Saint Martin",
                    "dial_code": "+590",
                    "code": "MF"
                },
                {
                    "name": "Saint Pierre and Miquelon",
                    "dial_code": "+508",
                    "code": "PM"
                },
                {
                    "name": "Saint Vincent and the Grenadines",
                    "dial_code": "+1784",
                    "code": "VC"
                },
                {
                    "name": "Samoa",
                    "dial_code": "+685",
                    "code": "WS"
                },
                {
                    "name": "San Marino",
                    "dial_code": "+378",
                    "code": "SM"
                },
                {
                    "name": "Sao Tome and Principe",
                    "dial_code": "+239",
                    "code": "ST"
                },
                {
                    "name": "Saudi Arabia",
                    "dial_code": "+966",
                    "code": "SA"
                },
                {
                    "name": "Senegal",
                    "dial_code": "+221",
                    "code": "SN"
                },
                {
                    "name": "Serbia",
                    "dial_code": "+381",
                    "code": "RS"
                },
                {
                    "name": "Seychelles",
                    "dial_code": "+248",
                    "code": "SC"
                },
                {
                    "name": "Sierra Leone",
                    "dial_code": "+232",
                    "code": "SL"
                },
                {
                    "name": "Singapore",
                    "dial_code": "+65",
                    "code": "SG"
                },
                {
                    "name": "Slovakia",
                    "dial_code": "+421",
                    "code": "SK"
                },
                {
                    "name": "Slovenia",
                    "dial_code": "+386",
                    "code": "SI"
                },
                {
                    "name": "Solomon Islands",
                    "dial_code": "+677",
                    "code": "SB"
                },
                {
                    "name": "Somalia",
                    "dial_code": "+252",
                    "code": "SO"
                },
                {
                    "name": "South Africa",
                    "dial_code": "+27",
                    "code": "ZA"
                },
                {
                    "name": "South Sudan",
                    "dial_code": "+211",
                    "code": "SS"
                },
                {
                    "name": "South Georgia and the South Sandwich Islands",
                    "dial_code": "+500",
                    "code": "GS"
                },
                {
                    "name": "Spain",
                    "dial_code": "+34",
                    "code": "ES"
                },
                {
                    "name": "Sri Lanka",
                    "dial_code": "+94",
                    "code": "LK"
                },
                {
                    "name": "Sudan",
                    "dial_code": "+249",
                    "code": "SD"
                },
                {
                    "name": "Suriname",
                    "dial_code": "+597",
                    "code": "SR"
                },
                {
                    "name": "Svalbard and Jan Mayen",
                    "dial_code": "+47",
                    "code": "SJ"
                },
                {
                    "name": "Swaziland",
                    "dial_code": "+268",
                    "code": "SZ"
                },
                {
                    "name": "Sweden",
                    "dial_code": "+46",
                    "code": "SE"
                },
                {
                    "name": "Switzerland",
                    "dial_code": "+41",
                    "code": "CH"
                },
                {
                    "name": "Syrian Arab Republic",
                    "dial_code": "+963",
                    "code": "SY"
                },
                {
                    "name": "Taiwan",
                    "dial_code": "+886",
                    "code": "TW"
                },
                {
                    "name": "Tajikistan",
                    "dial_code": "+992",
                    "code": "TJ"
                },
                {
                    "name": "Tanzania, United Republic of Tanzania",
                    "dial_code": "+255",
                    "code": "TZ"
                },
                {
                    "name": "Thailand",
                    "dial_code": "+66",
                    "code": "TH"
                },
                {
                    "name": "Timor-Leste",
                    "dial_code": "+670",
                    "code": "TL"
                },
                {
                    "name": "Togo",
                    "dial_code": "+228",
                    "code": "TG"
                },
                {
                    "name": "Tokelau",
                    "dial_code": "+690",
                    "code": "TK"
                },
                {
                    "name": "Tonga",
                    "dial_code": "+676",
                    "code": "TO"
                },
                {
                    "name": "Trinidad and Tobago",
                    "dial_code": "+1868",
                    "code": "TT"
                },
                {
                    "name": "Tunisia",
                    "dial_code": "+216",
                    "code": "TN"
                },
                {
                    "name": "Turkey",
                    "dial_code": "+90",
                    "code": "TR"
                },
                {
                    "name": "Turkmenistan",
                    "dial_code": "+993",
                    "code": "TM"
                },
                {
                    "name": "Turks and Caicos Islands",
                    "dial_code": "+1649",
                    "code": "TC"
                },
                {
                    "name": "Tuvalu",
                    "dial_code": "+688",
                    "code": "TV"
                },
                {
                    "name": "Uganda",
                    "dial_code": "+256",
                    "code": "UG"
                },
                {
                    "name": "Ukraine",
                    "dial_code": "+380",
                    "code": "UA"
                },
                {
                    "name": "United Arab Emirates",
                    "dial_code": "+971",
                    "code": "AE"
                },
                {
                    "name": "United Kingdom",
                    "dial_code": "+44",
                    "code": "GB"
                },
                {
                    "name": "United States",
                    "dial_code": "+1",
                    "code": "US"
                },
                {
                    "name": "Uruguay",
                    "dial_code": "+598",
                    "code": "UY"
                },
                {
                    "name": "Uzbekistan",
                    "dial_code": "+998",
                    "code": "UZ"
                },
                {
                    "name": "Vanuatu",
                    "dial_code": "+678",
                    "code": "VU"
                },
                {
                    "name": "Venezuela, Bolivarian Republic of Venezuela",
                    "dial_code": "+58",
                    "code": "VE"
                },
                {
                    "name": "Vietnam",
                    "dial_code": "+84",
                    "code": "VN"
                },
                {
                    "name": "Virgin Islands, British",
                    "dial_code": "+1284",
                    "code": "VG"
                },
                {
                    "name": "Virgin Islands, U.S.",
                    "dial_code": "+1340",
                    "code": "VI"
                },
                {
                    "name": "Wallis and Futuna",
                    "dial_code": "+681",
                    "code": "WF"
                },
                {
                    "name": "Yemen",
                    "dial_code": "+967",
                    "code": "YE"
                },
                {
                    "name": "Zambia",
                    "dial_code": "+260",
                    "code": "ZM"
                },
                {
                    "name": "Zimbabwe",
                    "dial_code": "+263",
                    "code": "ZW"
                }
            ],
            chosenCountryCode: '+65',
            form_names: {
                'sg': {
                    'nric': 'NRIC / FIN',
                },
                'my': {
                    'nric': 'MyCoID / Identity Number'
                }
            }
        }
    },

    mounted() {
        if (this.business.country == 'sg') {
            this.chosenCountryCode = '+65';
        }

        if (this.business.country == 'my') {
            this.chosenCountryCode = '+60';
        }

        if (this.persons.length === 0) {
            this.is_person_set = false;
            this.is_person_error = true;
        } else {
            this.is_person_set = true;
            this.is_person_error = false;
        }

        if (!this.is_person_set) {
            alert('Please wait our system still working to collect data');
            return;
        }

        // stripe sometime show data person on individual key
        // but sometime not show data person, so we handle this
        var person;
        if (typeof this.provider.data.account.individual === 'undefined') {
            person = this.persons[0];
        } else {
            person = this.provider.data.account.individual
        }

        this.is_payouts_enabled = this.provider.data.account.payouts_enabled;
        this.is_charges_enabled = this.provider.data.account.charges_enabled;

        if (this.provider.data.account.tos_acceptance.date) {
            this.is_tos_acceptance = true;
        }

        let stripeRequirements = this.provider.data.account.requirements;

        if (stripeRequirements.disabled_reason) {
            this.is_requirements_error = true;
        }

        if (stripeRequirements.past_due.length > 0) {
            this.is_requirements_error = true;
        }

        if (stripeRequirements.currently_due.length > 0) {
            this.is_requirements_error = true;
        }

        if (stripeRequirements.eventually_due.length > 0) {
            this.is_requirements_error = true;
        }

        if (this.is_payouts_enabled && this.is_charges_enabled && this.is_tos_acceptance) {
            if (this.is_requirements_error) {
                this.use_connect_onboarding = true;
            } else {
                this.use_connect_onboarding = false;
            }
        }

        this.checkDisabledReason();

        this.setActivePerson(person);

        if (this.provider.payment_provider_account_ready === true) {
            this.is_verified = true;
        }
    },

    methods: {
        closeModal() {
            this.is_on_edit_person = false;
            this.is_on_edit_company = false;
        },

        connectOnboarding() {
            if (!this.use_connect_onboarding) {
                alert("You not allowed to use Connect Onboarding");
            }

            this.is_processing = true;

            let formData = new FormData();

            formData.append('businessPaymentProviderId', this.provider.id);

            const path = 'business/' + this.business.id + '/payment-provider/stripe/connect-onboarding';
            axios.post(this.getDomain(path, 'dashboard'), formData, {
                headers: {
                    'Accept': 'application/json',
                },
            }).then(({data}) => {
                this.is_processing = false;

                window.location.href = data.url;
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

        checkCompanyMandatoryAll() {
            // check name
            this.checkCompanyMandatoryError(
                'name',
                'business_name',
                'name is required'
            );

            // check url
            this.checkCompanyMandatoryError(
                'url',
                'business_url',
                'name is required'
            );

            // check url
            this.checkCompanyMandatoryError(
                'country',
                'business_country',
                'country is required'
            );

            // check country
            this.checkCompanyMandatoryError(
                'business_country',
                'business_country',
                'country is required'
            );

            // check line1
            this.checkCompanyMandatoryError(
                'line1',
                'business_line1',
                'address line 1 is required'
            );

            this.checkCompanyMandatoryError(
                'postal_code',
                'business_postal_code',
                'postal code is required'
            );

            this.checkCompanyMandatoryError(
                'phone',
                'business_phone',
                'phone is required'
            );
        },

        checkCompanyMandatoryError(objName, errorObjKey, messageError) {
            var stripeErrorMessage = this.getErrorMessageFromStripe('company', objName);

            if (this.disabled_reason_obj.length > 0) {
                var that = this;
                this.disabled_reason_obj.forEach(function(item, key) {
                    var splittedItem = item.split('.');
                    var lastSplittedItem = splittedItem[splittedItem.length - 1];
                    var firstSplittedItem = splittedItem[0];

                    if (firstSplittedItem === 'company') {
                        if (lastSplittedItem === objName) {
                            if (stripeErrorMessage === '') {
                                that.errors[errorObjKey] = messageError;
                            } else {
                                that.errors[errorObjKey] = stripeErrorMessage;
                            }
                        }
                    }
                });
            }
        },

        getErrorMessageFromStripe($subject, $object) {
            // subject type should be company or person
            var requirementErrors = this.provider.data.account.requirements.errors;

            var errorMessage = '';

            if (requirementErrors.length > 0) {
                requirementErrors.forEach(function(item, key) {
                    var splittedItem = item.requirement.split('.');
                    var lastSplittedItem = splittedItem[splittedItem.length - 1];
                    var firstSplittedItem = splittedItem[0];

                    if (firstSplittedItem == $subject) {
                        if (lastSplittedItem == $object) {
                            errorMessage = item.reason;
                            return;
                        }
                    }
                });
            }

            return errorMessage;
        },

        checkPersonMandatoryError(obj, objName, errorObjKey, messageError) {
            var stripeErrorMessage = this.getErrorMessageFromStripe('individual', objName);

            if (this.disabled_reason_obj.length > 0) {
                var that = this;
                this.disabled_reason_obj.forEach(function(item, key) {
                    var splittedItem = item.split('.');
                    var lastSplittedItem = splittedItem[splittedItem.length - 1];
                    var firstSplittedItem = splittedItem[0];

                    if (firstSplittedItem == 'individual') {
                        if (lastSplittedItem == objName) {
                            if (stripeErrorMessage == '') {
                                if (typeof obj == 'undefined' || obj == '' || obj == null) {
                                    that.errors[errorObjKey] = messageError;
                                }
                            } else {
                                that.errors[errorObjKey] = stripeErrorMessage;
                            }
                        }
                    }
                });
            }
        },

        setCheckDisabledReasonObject() {
            var that = this;

            if (this.disabled_reason_obj.length > 0) {
                this.disabled_reason_obj.forEach(function(item, _) {
                    if (item.startsWith('person_')) {
                        that.is_person_error = true;
                        that.is_person_error_ids.push(item.split('.')[0]);
                        return;
                    }

                    if (item.startsWith('individual.')) {
                        that.is_person_error = true;
                        return;
                    }

                    if (item.startsWith('owners.')) {
                        that.is_person_error = true;
                        return;
                    }

                    if (item.startsWith('directors.')) {
                        that.is_person_error = true;
                        return;
                    }

                    if (item.startsWith('business_profile.')) {
                        that.is_business_error = true;
                        return;
                    }

                    if (item.startsWith('company.')) {
                        that.is_business_error = true;
                        return;
                    }
                });
            }
        },

        checkDisabledReason() {
            this.disabled_reason = this.provider.data.account.requirements.disabled_reason;

            if (this.disabled_reason !== '') {
                if (this.disabled_reason == 'requirements.past_due') {
                    this.disabled_reason_obj = this.provider.data.account.requirements.past_due;
                }

                this.setCheckDisabledReasonObject();
            }

            // https://stripe.com/docs/connect/identity-verification-api#validation-and-verification-errors
            // not have good doc for currently_due will go to disabled_reason field or not
            // split to single currently_due but still use disabled_reason_obj
            this.currently_due = this.provider.data.account.requirements.currently_due;
            if (this.currently_due.length > 0) {
                this.disabled_reason_obj = this.currently_due;

                this.setCheckDisabledReasonObject();
            }
        },

        setActivePerson($person) {
            if (typeof $person.full_name_aliases === "undefined") {
                this.active_person.full_name_aliases = '';

                this.checkPersonMandatoryError(
                    this.active_person.full_name_aliases,
                    'full_name_aliases',
                    'person_full_name_aliases',
                    'full name aliases is mandatory'
                )
            } else {
                this.active_person.full_name_aliases = $person.full_name_aliases[0]; // because full name aliases on array
            }

            this.active_person.id = $person.id;
            this.active_person.first_name = $person.first_name;
            this.active_person.last_name = $person.last_name;
            this.checkPersonMandatoryError(
                this.active_person.last_name,
                'last_name',
                'person_last_name',
                'last name is mandatory'
            )

            this.active_person.title = $person.relationship.title;
            this.checkPersonMandatoryError(
                this.active_person.title,
                'title',
                'person_title',
                'title is mandatory'
            )

            this.active_person.id_number = '';
            this.active_person.id_number_provided = $person.id_number_provided;

            this.active_person.email = $person.email;
            this.checkPersonMandatoryError(
                this.active_person.email,
                'email',
                'person_email',
                'email is mandatory'
            )

            this.active_person.phone = $person.phone;
            this.checkPersonMandatoryError(
                this.active_person.phone,
                'phone',
                'person_phone',
                'phone is mandatory'
            )

            this.active_person.address_postal_code = $person.address.postal_code;
            this.checkPersonMandatoryError(
                this.active_person.address_postal_code,
                'postal_code',
                'person_address_postal_code',
                'postal code is mandatory'
            )

            this.active_person.address_line1 = $person.address.line1;
            this.checkPersonMandatoryError(
                this.active_person.address_line1,
                'line1',
                'person_address_line1',
                'address is mandatory'
            )

            this.active_person.address_line2 = $person.address.line2;
            this.active_person.address_city = $person.address.city;
            this.checkPersonMandatoryError(
                this.active_person.address_city,
                'city',
                'person_address_city',
                'city is mandatory'
            )

            this.active_person.address_state = $person.address.state;
            this.active_person.address_country = $person.address.country;
            this.active_person.dob_day = $person.dob.day;
            this.checkPersonMandatoryError(
                this.active_person.dob_day,
                'day',
                'person_dob_day',
                'dob day is mandatory'
            )

            this.active_person.dob_month = $person.dob.month;
            this.checkPersonMandatoryError(
                this.active_person.dob_month,
                'month',
                'person_dob_month',
                'dob month is mandatory'
            )

            this.active_person.dob_year = $person.dob.year;
            this.checkPersonMandatoryError(
                this.active_person.dob_year,
                'year',
                'person_dob_year',
                'dob year is mandatory'
            )

            this.active_person.percent_ownership = $person.relationship.percent_ownership;
            this.checkPersonMandatoryError(
                $person,
                'percent_ownership',
                'person_percent_ownership',
                'percent ownership is mandatory'
            )

            this.checkPersonDocumentError('supporting_documents');
        },

        checkPersonDocumentError(errorObjKey) {
            // reset
            this.active_person.is_need_upload_document = false;

            var stripeErrorMessage = this.getErrorMessageFromStripe('individual', 'document');

            if (this.disabled_reason_obj.length > 0) {
                var that = this;
                this.disabled_reason_obj.forEach(function(item, key) {
                    var splittedItem = item.split('.');
                    var lastSplittedItem = splittedItem[splittedItem.length - 1];
                    var firstSplittedItem = splittedItem[0];

                    if (firstSplittedItem == 'individual') {
                        if (lastSplittedItem == 'document') {
                            if (stripeErrorMessage == '') {
                                // mean user not yet upload document
                                // set message this is required
                                that.errors[errorObjKey] = 'Document required';
                            } else {
                                that.errors[errorObjKey] = stripeErrorMessage;
                            }
                            that.active_person.is_need_upload_document = true;
                            return
                        }
                    }
                });
            }
        },

        onEditCompany() {
            this.is_on_edit_company = !this.is_on_edit_company;

            this.checkCompanyMandatoryAll();
        },

        onEditPerson() {
            this.is_on_edit_person = !this.is_on_edit_person;
        },

        savePerson() {
            this.errors = {};

            if (this.active_person.full_name_aliases === "") {
                this.errors.person_full_name_aliases = "Please input full name aliases.";
            } else if (this.active_person.full_name_aliases !== "" && this.active_person.full_name_aliases.trim().length === 0) {
                this.errors.person_full_name_aliases = "Please input full name aliases.";
            }

            if (this.active_person.first_name === "") {
                this.errors.person_first_name = "Please input person first name.";
            } else if (this.active_person.first_name !== "" && this.active_person.first_name.trim().length === 0) {
                this.errors.person_first_name = "Please input person first name.";
            }

            if (this.active_person.email === "") {
                this.errors.person_email = "Please input person email.";
            } else if (this.active_person.email !== "" && this.active_person.email.trim().length === 0) {
                this.errors.person_email = "Please input person email.";
            }

            if (!(/\S+@\S+\.\S+/.test(this.active_person.email.trim()))) {
                this.errors.person_email = 'Invalid person email format';
            }

            if (this.active_person.dob_day === "") {
                this.errors.person_dob_day = "Please input person dob day.";
            }

            if (this.active_person.dob_month === "") {
                this.errors.person_dob_month = "Please input person dob month.";
            }

            if (this.active_person.dob_year === "") {
                this.errors.person_dob_year = "Please input person dob year.";
            }

            if (!/^(0?[1-9]|1[012])$/.test(this.active_person.dob_month)) {
                this.errors.person_dob_month = "Invalid dob month 1-12";
            }

            if (this.active_person.address_country === "") {
                this.errors.person_address_country = "Please select person address country.";
            } else if (this.active_person.address_country !== "" && this.active_person.address_country.trim().length === 0) {
                this.errors.person_address_country = "Please select person address country.";
            }

            if (this.active_person.address_line1 === "") {
                this.errors.person_address_line1 = "Please input person address.";
            } else if (this.active_person.address_line1 !== "" && this.active_person.address_line1.trim().length === 0) {
                this.errors.person_address_line1 = "Please input person address.";
            }

            if (this.active_person.address_postal_code === "") {
                this.errors.person_address_postal_code = "Please input person postal code.";
            } else if (this.active_person.address_postal_code !== "" && this.active_person.address_postal_code.trim().length === 0) {
                this.errors.person_address_postal_code = "Please input person postal code.";
            }

            if (this.active_person.is_need_upload_document) {
                // handling files
                if (this.$refs.supporting_documents && this.$refs.supporting_documents.files.length > 2) {
                    this.errors.supporting_documents = "Maximum 2 files are allowed";
                }

                if (this.$refs.supporting_documents && this.$refs.supporting_documents.files.length === 0) {
                    this.errors.supporting_documents = "Please upload supporting documents";
                }

                if (this.$refs.supporting_documents) {
                    for (var i = 0; i < this.$refs.supporting_documents.files.length; i++) {
                        let file = this.$refs.supporting_documents.files[i];
                        if (file.size > 1024 * 1024 * 2) {
                            this.errors.supporting_documents = "Files should not be greater than 2 MB.";
                        }
                    }
                }
            }

            // show error validation
            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            this.is_processing = true;

            let formData = new FormData();

            if (this.active_person.is_need_upload_document) {
                if (this.$refs.supporting_documents) {
                    for (var i = 0; i < this.$refs.supporting_documents.files.length; i++) {
                        let file = this.$refs.supporting_documents.files[i];
                        formData.append('supporting_documents[' + i + ']', file);
                    }
                }
            }

            let with_document = 'no';

            if (this.active_person.is_need_upload_document) {
                with_document = 'yes';
            }

            formData.append('with_document', with_document);
            formData.append('businessPaymentProviderId', this.provider.id);
            formData.append('person', JSON.stringify(this.active_person));
            formData.append('type', 'individual')
            formData.append('update_type_for', 'person');

            const path = 'business/' + this.business.id + '/payment-provider/stripe/onboard-verification';
            axios.post(this.getDomain(path, 'dashboard'), formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data'
                },
            }).then(({data}) => {
                this.is_processing = false;

                // reload for now
                location.reload();
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

        saveCompany() {
            this.errors = {};

            if (this.provider.data.account.business_profile.url === "") {
                this.errors.business_url = "Please input business url.";
            }

            if (this.provider.data.account.company.address.line1 === "") {
                this.errors.business_line1 = "Please input business address.";
            }

            if (this.provider.data.account.company.address.postal_code === "") {
                this.errors.business_postal_code = "Please input business postal code.";
            }

            if (this.provider.data.account.company.phone === "") {
                this.errors.business_phone = "Please input business phone.";
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));

                return;
            }

            let formData = new FormData();

            formData.append('businessPaymentProvider', JSON.stringify(this.provider));
            formData.append('type', 'individual')
            formData.append('update_type_for', 'company');

            this.is_processing = true;

            const path = 'business/' + this.business.id + '/payment-provider/stripe/onboard-verification';
            axios.post(this.getDomain(path, 'dashboard'), formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data'
                },
            }).then(({data}) => {
                this.is_processing = false;

                // reload for now
                location.reload();
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

        saveTos() {
            if (this.is_person_error === true || this.is_business_error === true) {
                alert("Please fill data first");
                return false;
            }

            let formData = new FormData();

            formData.append('businessPaymentProvider', JSON.stringify(this.provider));
            formData.append('type', 'individual')
            formData.append('update_type_for', 'tos');

            this.is_processing = true;

            const path = 'business/' + this.business.id + '/payment-provider/stripe/onboard-verification';
            axios.post(this.getDomain(path, 'dashboard'), formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data'
                },
            }).then(({data}) => {
                this.is_processing = false;

                // reload for now
                location.reload();
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

        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey)

                $('#' + firstErrorKey).focus()
            }
        }
    }
}
</script>
