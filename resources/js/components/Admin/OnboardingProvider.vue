<template>
  <div class="d-flex flex-column align-items-center">
    <div
      v-if="message"
      class="alert"
      :class="{ 'alert-danger': messageError, 'alert-success': !messageError }">
      {{ message }}
    </div>

    <div class="onboarding-provider d-flex flex-column">
      <!-- Header row -->
      <div class="d-flex align-items-center">
        <provider-logo :value="provider"/>

        <div class="ml-3">
          {{ providerName(provider) }}
        </div>

        <div class="flex-grow-1 d-flex justify-content-end">
          <div class="d-flex flex-column">
            <div class="form-check">
              <label class="form-check-label">
                <input
                  v-model="all"
                  :value="true"
                  class="form-check-input"
                  type="radio"/>All merchants
              </label>
            </div>

            <div class="form-check">
              <label class="form-check-label">
                <input
                  v-model="all"
                  :value="false"
                  class="form-check-input"
                  type="radio"/>Pending
              </label>
            </div>
          </div>            
        </div>
      </div>

      <!-- Content row -->
      <table>
        <thead>
          <td
            v-for="(field, index) in fields"
            :key="`head${index}`">
            {{ field.label }}
          </td>
        </thead>

        <tbody>
          <tr
            v-for="(row, index) in data"
            :key="`row${index}`">
            <td>
              {{ row.business.name }}<br/>
              {{ row.business_id }}
            </td>

            <td>
              {{ row.data.company_uen }}
            </td>

            <td>
              {{ row.data.city }}<br/>
              {{ row.data.address }}
            </td>

            <td>
              {{ row.data.postal_code }}
            </td>
          </tr>
        </tbody>
      </table>

      <div
        v-if="total > perPage"
        class="d-flex justify-content-center mt-3">
        <b-pagination
          v-model="page"
          :total-rows="total"
          :per-page="perPage"/>
      </div>

      <!-- Buttons row -->
      <div class="mt-3 d-flex justify-content-center">
        <SmallButton
          :title="downloadCaption"
          :width="200"
          color="blue"
          @click="onDownload"/>

        <SmallButton        
          v-if="showUpload"
          title="Upload Pending Merchants"
          class="ml-2"
          :width="200"
          color="blue"
          @click="onUpload"/>

        <input
          type="file"
          ref="fileSelector"
          class="file-selector"
          @change="doUpload"/>
      </div>
    </div>      
  </div>
</template>

<script>
import SmallButton from '../UI/SmallButton'
import ProviderNamesMixin from '../../mixins/ProviderNamesMixin'

export default {
  name: 'OnboardingProvider',
  mixins: [
    ProviderNamesMixin
  ],
  components: {
    SmallButton
  },
  props: {
    provider: String,
    initial_data: Object
  },
  data () {
    return {
      fields: [
        { label: 'Business name / ID' },
        { label: 'Company UEN' },
        { label: 'City / Address' },
        { label: 'Postal code' },
      ],
      data: this.initial_data.data,
      page: this.initial_data.page,
      total: this.initial_data.count,
      perPage: 10,
      all: this.initial_data.all,
      message: '',
      messageError: false,
      uploadSupported: [
        'grabpay',
        'shopee_pay'
      ]
    }
  },
  watch: {
    page () {
      this.loadData()
    },
    all () {
      this.page = 1
      this.loadData()
    }
  },
  computed: {
    showUpload () {
      return this.uploadSupported.includes(this.provider)
    },
    downloadCaption () {
      return `Download ${this.all ? 'All' : 'Pending'} Merchants`
    }
  },
  methods: {
    async loadData () {
      const { data } = await axios.get(this.getDomain(`/onboarding/${this.provider}/merchants`, 'admin'), {
        params: {
          page: this.page,
          all: this.all
        }
      })

      this.data = data.data
      this.total = data.count
    },
    onDownload () {
      this.message = ''
      window.location = this.getDomain(`onboarding/${this.provider}/download?all=${this.all}`, 'admin')
    },
    onUpload () {
      this.message = ''
      this.$refs.fileSelector.click()
    },
    async doUpload (event) {
      const formData = new FormData()
      formData.append('csv', event.target.files[0], this.provider + '.csv')
      
      try {
        const res = await axios.post(this.getDomain(`/onboarding/${this.provider}/upload`, 'admin'), formData)
        this.message = `Successfully processed. ${res.data.success.length} enabled, ${res.data.failed.length} failed and ${res.data.not_found.length} not found`
        this.messageError = false
        console.log(res)
      } catch (error) {
        this.messageError = true
        this.message = error
      }
      
      await this.loadData()
    }
  }
}
</script>

<style lang="scss" scoped>
.onboarding-provider {
  //width: 500px;
  table {
    thead {
      font-size: 14px;

      td {
        padding: 4px 8px;
        border-bottom: 1px solid lightgrey;
      }
    }

    tbody {
      font-size: 13px;

      td {
        padding: 4px 8px;
      }
    }
  }

  .file-selector {
    display: none;
  }
}
</style>