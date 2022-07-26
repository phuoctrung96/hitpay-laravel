<template>
  <div>
    <div class="card-body p-4">
      <h2 class="text-primary mb-0 title">Form Import DBS Reconcile</h2>
    </div>
    <div class="card-body bg-light border-top ">
      <div v-if="message" class="d-flex flex-column">
        <div class="alert"
             :class="{ 'alert-danger': messageError, 'alert-success': !messageError }">
          {{ message }}
        </div>
      </div>

      <div class="form-row">
        <div class="col-12 mb-3">
          <label for="file" class="small text-muted text-uppercase">Upload CSV Files</label>
          <input
            multiple
            accept=".csv"
            type="file"
            id="file"
            ref="fileSelector"
            class="file-selector form-control-file"
            :disabled="is_processing"/>
        </div>
        <div class="col-12 mb-3">
          <button class="btn btn-primary btn-block" @click.prevent="doUpload" :disabled="is_processing">
            Save
            <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FormImportDbsReconcile',
  props: {
  },
  data () {
    return {
      message: '',
      messageError: false,
      is_processing: false,
    }
  },
  methods: {
    async doUpload(event) {
      this.is_processing = true;
      let formData = new FormData();
      if (this.$refs.fileSelector) {
        for (let i = 0; i < this.$refs.fileSelector.files.length; i++) {
          let file = this.$refs.fileSelector.files[i];
          formData.append('csv[' + i + ']', file);
        }
      }
      try {
        const res = await axios.post(this.getDomain(`import-dbs-reconcile`, 'admin'), formData);
        this.message = `Successfully processed`;
        this.messageError = false
        this.is_processing = false;
        this.$refs.fileSelector.value = null;
      } catch (error) {
        this.messageError = true
        this.message = error
        this.is_processing = false;
      }
    }
  }
}
</script>

<style lang="scss" scoped>
</style>
