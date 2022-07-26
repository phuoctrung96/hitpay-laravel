<template>
  <div class="p-4">
      <h2 class="text-primary mb-4">Verification Summary</h2>
      <p>Please check your business detail and management ownership below.</p>

      <h4 class="mt-4">Business Details</h4>

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
                  {{ this.provider.data.account.email }}
              </h5>

              <p class="card-text" v-if="!this.is_business_error">
                  <a class="btn btn-primary"
                     @click="onEditCompany()"
                     href="#management_company_modal"
                     data-toggle="modal" data-target="#management_company_modal"
                  ><i class="fa" :class="(is_on_edit_company) ? 'fa-minus' : 'fa-search'"></i></a>
              </p>

              <p class="card-text text-danger" v-if="is_business_error && !is_verified">
                  Missing Requirement Information, please check this one
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
                              <input id="business_name"
                                     v-model="provider.data.account.business_profile.name"
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
                                  :class="{'is-invalid' : errors.business_phone}"
                              >
                              <span class="invalid-feedback" role="alert">
                                    {{ errors.business_phone }}
                              </span>
                          </div>

                          <div class="form-group" v-if="is_company_document_need_to_upload">
                              <label class="col-form-label">Upload Verification Company</label>
                              <p>
                                  Please pick which document you'd like to upload
                                  for additional Tax document verification
                              </p>
                              <input
                                  type="file"
                                  class="form-control-file mt-3"
                                  ref="tax_documents"
                                  :class="{'is-invalid' : errors.company_documents}"
                              />
                              <span class="invalid-feedback" role="alert">
                                    {{ errors.company_documents }}
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

      <div v-for="person in persons">
          <div class="card border-primary mb-3" style="max-width: 25rem;">
              <div class="card-header bg-primary text-light">{{ person.first_name }} {{ person.last_name }}</div>
              <div class="card-body text-secondary">
                  <h5 class="card-text">
                      {{ person.email }}
                  </h5>

                  <p class="card-text" v-if="!checkPersonHasError(person)">
                      <a class="btn btn-primary"
                         href="#management_ownership_modal"
                         data-toggle="modal" data-target="#management_ownership_modal"
                         @click="onEditPerson(person)"
                      ><i class="fa" :class="(is_on_edit_person) ? 'fa-minus' : 'fa-search'"></i></a>
                  </p>

                  <p class="card-text text-danger" v-if="checkPersonHasError(person) && !is_verified">
                      Missing Requirement Information, please check this one
                      <br />
                      <a class="btn btn-primary"
                         href="#management_ownership_modal"
                         data-toggle="modal" data-target="#management_ownership_modal"
                         @click="onEditPerson(person)"
                      ><i class="fa" :class="(is_on_edit_person) ? 'fa-minus' : 'fa-search'"></i></a>
                  </p>
              </div>
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
                              <label class="col-form-label">Title</label>
                              <input
                                  id="person_title"
                                  v-model="active_person.title"
                                  class="form-control"
                                  :class="{'is-invalid' : errors.person_title}"
                              />
                              <span class="invalid-feedback" role="alert">
                                    {{ errors.person_title }}
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
                              <label class="col-form-label">NRIC or FIN</label>
                              <input v-model="active_person.id_number" class="form-control">
                              <p>
                                  To verify your identity, we’ll need to know your National Registry Identity Card
                                  or Foreign Identity Number.
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
    name: 'StripeOnboardVerificationCompany',
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
            disabled_reason_obj: [],
            currently_due: [],
            eventually_due: [],
            is_business_error: false,
            is_person_error: false,
            is_person_error_ids: [],
            is_person_error_owner: '',
            is_person_error_director: '',
            is_on_edit_company: false,
            is_on_edit_person: false,
            is_verified: false,
            is_active_person: false,
            is_company_document_need_to_upload: true,
            is_charges_enabled: false,
            is_payouts_enabled: false,
            is_tos_acceptance: false,
            is_requirements_error: false,
            use_connect_onboarding: false,
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
            }
        }
    },

    mounted() {
        if (this.persons.length === 0) {
            this.is_person_set = false;
            this.is_person_error = true;
        } else {
            this.is_person_set = true;
            this.is_person_error = false;
        }

        // disable reason show as string only, not object like currently_due field
        this.disabled_reason = this.provider.data.account.requirements.disabled_reason;

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

        if (this.provider.payment_provider_account_ready) {
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

        setCheckDisabledReasonObject() {
            var that = this;

            if (this.disabled_reason_obj.length > 0) {
                this.disabled_reason_obj.forEach(function(item, _) {
                    if (item.startsWith('person_')) {
                        that.is_person_error = true;
                        that.is_person_error_ids.push(item.split('.')[0]);
                        return;
                    }

                    if (item.startsWith('owners.')) {
                        that.is_person_error = true;
                        that.is_person_error_owner = true;
                        return;
                    }

                    if (item.startsWith('directors.')) {
                        that.is_person_error = true;
                        that.is_person_error_director = true;
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

        checkPersonHasError($person) {
            if (this.is_person_error_owner) {
                if ($person.relationship.owner) {
                    return true;
                }
            }

            if (this.is_person_error_director) {
                if ($person.relationship.director) {
                    return true;
                }
            }

            if (this.is_person_error_ids.length > 0) {
                if (this.is_person_error_ids.includes($person.id)) {
                    return true;
                }
            }

            return false;
        },

        onEditCompany() {
            this.is_on_edit_company = true;
            this.is_on_edit_person = false;

            this.checkCompanyMandatoryAll();

            this.checkCompanyDocumentError('company_documents');
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

            this.checkCompanyMandatoryError(
                'city',
                'business_city',
                'city is required'
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
            var stripErrorMessage = this.getErrorMessageFromStripe('company', 'company', objName);

            if (this.disabled_reason_obj.length > 0) {
                var that = this;
                this.disabled_reason_obj.forEach(function(item, key) {
                    var splittedItem = item.split('.');
                    var lastSplittedItem = splittedItem[splittedItem.length - 1];
                    var firstSplittedItem = splittedItem[0];

                    if (firstSplittedItem == 'company') {
                        if (lastSplittedItem == objName) {
                            if (stripErrorMessage == "") {
                                that.errors[errorObjKey] = messageError;
                            } else {
                                that.errors[errorObjKey] = stripErrorMessage;
                            }
                        }
                    }
                });
            }
        },

        checkPersonMandatoryError($person, objName, errorObjKey, messageError) {
            var stripErrorMessage = this.getErrorMessageFromStripe('person', $person, objName);

            if (this.disabled_reason_obj.length > 0) {
                var that = this;
                this.disabled_reason_obj.forEach(function(item, key) {
                    var splittedItem = item.split('.');
                    var lastSplittedItem = splittedItem[splittedItem.length - 1];
                    var firstSplittedItem = splittedItem[0];

                    if (firstSplittedItem == $person.id) {
                        if (lastSplittedItem == objName) {
                            if (stripErrorMessage == "") {
                                that.errors[errorObjKey] = messageError;
                            } else {
                                that.errors[errorObjKey] = stripErrorMessage;
                            }
                        }
                    }
                });
            }
        },

        getErrorMessageFromStripe($subjectType, $subject, $object) {
            // subject type should be company or person
            var requirementErrors = this.provider.data.account.requirements.errors;

            var errorMessage = '';

            if ($subjectType == 'company') {
                $subject = 'company';
            } else {
                $subject = $subject.id; // $person.id
            }

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

        checkCompanyDocumentError(errorObjKey) {
            // reset
            this.is_company_document_need_to_upload = false;

            var stripeErrorMessage = this.getErrorMessageFromStripe('company', 'company', 'document');

            if (this.disabled_reason_obj.length > 0) {
                var that = this;
                this.disabled_reason_obj.forEach(function(item, key) {
                    var splittedItem = item.split('.');
                    var lastSplittedItem = splittedItem[splittedItem.length - 1];
                    var firstSplittedItem = splittedItem[0];

                    if (firstSplittedItem == 'company') {
                        if (lastSplittedItem == 'document') {
                            if (stripeErrorMessage == '') {
                                // mean user not yet upload document
                                // set message this is required
                                that.errors[errorObjKey] = 'Document required';
                            } else {
                                that.errors[errorObjKey] = stripeErrorMessage;
                            }

                            that.is_company_document_need_to_upload = true;
                            return
                        }
                    }
                });
            }
        },

        checkPersonDocumentError($person, errorObjKey) {
            // reset
            this.active_person.is_need_upload_document = false;

            var stripeErrorMessage = this.getErrorMessageFromStripe('person', $person, 'document');

            if (this.disabled_reason_obj.length > 0) {
                var that = this;
                this.disabled_reason_obj.forEach(function(item, key) {
                    var splittedItem = item.split('.');
                    var lastSplittedItem = splittedItem[splittedItem.length - 1];
                    var firstSplittedItem = splittedItem[0];

                    if (firstSplittedItem == $person.id) {
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

        setActivePerson($person) {
            if (typeof $person.full_name_aliases === "undefined") {
                this.active_person.full_name_aliases = '';

                this.checkPersonMandatoryError(
                    $person,
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
                $person,
                'last_name',
                'person_last_name',
                'last name is mandatory'
            )

            this.active_person.title = $person.relationship.title;
            this.checkPersonMandatoryError(
                $person,
                'title',
                'person_title',
                'title is mandatory'
            )

            this.active_person.percent_ownership = $person.relationship.percent_ownership;
            this.checkPersonMandatoryError(
                $person,
                'percent_ownership',
                'person_percent_ownership',
                'percent ownership is mandatory'
            )

            this.active_person.id_number = '';

            this.active_person.id_number_provided = $person.id_number_provided;

            this.active_person.email = $person.email;
            this.checkPersonMandatoryError(
                $person,
                'email',
                'person_email',
                'email is mandatory'
            )

            this.active_person.phone = $person.phone;
            this.checkPersonMandatoryError(
                $person,
                'phone',
                'person_phone',
                'phone is mandatory'
            )

            this.active_person.address_postal_code = $person.address.postal_code;
            this.checkPersonMandatoryError(
                $person,
                'postal_code',
                'person_address_postal_code',
                'postal code is mandatory'
            )

            this.active_person.address_line1 = $person.address.line1;
            this.checkPersonMandatoryError(
                $person,
                'line1',
                'person_address_line1',
                'address is mandatory'
            )

            this.active_person.address_line2 = $person.address.line2;
            this.active_person.address_city = $person.address.city;
            this.checkPersonMandatoryError(
                $person,
                'city',
                'person_address_city',
                'city is mandatory'
            )

            this.active_person.address_state = $person.address.state;
            this.active_person.address_country = $person.address.country;

            this.active_person.dob_day = $person.dob.day;
            this.checkPersonMandatoryError(
                $person,
                'day',
                'person_dob_day',
                'dob day is mandatory'
            )

            this.active_person.dob_month = $person.dob.month;
            this.checkPersonMandatoryError(
                $person,
                'month',
                'person_dob_month',
                'dob month is mandatory'
            )

            this.active_person.dob_year = $person.dob.year;
            this.checkPersonMandatoryError(
                $person,
                'year',
                'person_dob_year',
                'dob year is mandatory'
            )

            if ($person.file) {
                this.checkPersonDocumentError($person, 'supporting_documents');
            }
        },

        onEditPerson($person) {
            this.is_on_edit_person = true;
            this.is_on_edit_company = false;

            this.setActivePerson($person);

            this.is_active_person = true;
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
            formData.append('person', JSON.stringify(this.active_person));
            formData.append('businessPaymentProviderId', this.provider.id);
            formData.append('type', 'business');
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
            } else if (this.provider.data.account.business_profile.url !== "" && this.provider.data.account.business_profile.url.trim().length == 0) {
                this.errors.business_url = "Please input business url.";
            }

            if (this.provider.data.account.company.address.line1 === "") {
                this.errors.business_line1 = "Please input business address.";
            } else if (this.provider.data.account.business_profile.url !== "" && this.provider.data.account.company.address.line1.trim().length == 0) {
                this.errors.business_line1 = "Please input business address.";
            }

            if (this.provider.data.account.company.address.postal_code === "") {
                this.errors.business_postal_code = "Please input business postal code.";
            } else if (this.provider.data.account.company.address.postal_code !== "" && this.provider.data.account.company.address.postal_code.trim().length == 0) {
                this.errors.business_postal_code = "Please input business postal code.";
            }

            if (this.provider.data.account.company.phone === "") {
                this.errors.business_phone = "Please input business phone.";
            }

            if (this.provider.data.account.company.tax_id_provided === false) {
                // handling files
                if (this.$refs.company_documents && this.$refs.company_documents.files.length === 0) {
                    this.errors.company_documents = "Please upload company documents";
                }

                if (this.$refs.company_documents) {
                    for (var i = 0; i < this.$refs.company_documents.files.length; i++) {
                        let file = this.$refs.company_documents.files[i];
                        if (file.size > 1024 * 1024 * 2) {
                            this.errors.company_documents = "Files should not be greater than 2 MB.";
                        }
                    }
                }
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));

                return;
            }

            let formData = new FormData();

            if (this.provider.data.account.company.tax_id_provided === false) {
                if (this.$refs.company_documents) {
                    for (var i = 0; i < this.$refs.company_documents.files.length; i++) {
                        let file = this.$refs.company_documents.files[i];
                        formData.append('supporting_documents[' + i + ']', file);
                    }
                }
            }

            let with_document = 'yes';

            if (this.provider.data.account.company.tax_id_provided) {
                with_document = 'no';
            }

            formData.append('with_document', !this.provider.data.account.company.tax_id_provided);
            formData.append('businessPaymentProvider', JSON.stringify(this.provider));
            formData.append('type', 'business');
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
            formData.append('type', 'business')
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
