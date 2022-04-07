<template>
    <div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="text-primary mb-0 title">Create Customers In Bulk</h2>
            </div>
            <div class="card-body border-top">
                <p>
                    Download the customer feed template :
                    <button
                        class="btn btn-secondary"
                        @click="downloadFeedTemplate()"
                        :disabled="is_downloading"
                    >Download
                        <i class="fas fa-spin fa-spinner" :class="{
                        'd-none' : !is_downloading
                    }"></i>
                    </button>
                </p>
            </div>
            <div class="card-body border-top">
                <h5 class="font-weight-bold mb-3">Upload Your Completed Feed File</h5>
                <p v-if="form.file !== null">
                    {{form.file.name}}
                    &nbsp;&nbsp;<a class="text-danger" href="#" @click="removeFile()"><i class="fa fa-times" aria-hidden="true"></i></a>
                </p>
                <label class="d-inline-flex mb-1" for="productImage" v-if="form.file === null">
                    <input type="file" id="productImage" ref="file" class="custom-file-input d-none"
                           @change="handleFile()"
                           accept=".csv"
                    >
                    <span id="uploadBtn" class="btn btn-primary">
                        <i class="fas fa-folder-open"></i> Choose File
                    </span>
                </label>
                <button v-if="form.file !== null" class="btn btn-primary" @click="uploadFeedTemplate">Upload
                    <i class="fas fa-spin fa-spinner" :class="{
                        'd-none' : !is_busy
                    }"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "BulkCustomers",
    data:() => {
        return {
            is_busy: false,
            is_downloading: false,
            form: {
                file: null,
            }
        }
    },
    methods: {
        handleFile()
        {
            this.form.file = this.$refs.file.files[0];
            if (!this.form.file)
            {
                return false
            }
        },
        removeFile()
        {
            this.form.file = null;
        },
        downloadFeedTemplate()
        {
            const fileName = window.HitPay.app_name + '-customer-feed-template.csv';
            this.is_downloading = true;
            axios.get(this.getDomain('business/' + Business.id + '/customer/download-feed-template', 'dashboard'),
                {
                    responseType: 'blob'
                }).then(({data}) => {
                const url = URL.createObjectURL(new Blob([data], {
                    type: 'data:text/csv;charset=utf-8'
                }))
                const link = document.createElement('a')
                link.href = url
                link.setAttribute('download', fileName)
                document.body.appendChild(link)
                link.click()
                this.is_downloading = false;
            }).catch(({response}) => {
                this.is_downloading = false;
            });
        },
        uploadFeedTemplate()
        {
            let formData = new FormData();
            formData.append('file', this.form.file);
            this.is_busy = true;
            axios.post(this.getDomain('business/' + Business.id + '/customer/upload-feed-file', 'dashboard'),
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            ).then(({data}) => {
                this.is_busy = false;
                window.location.href = data.redirect_url;
            })
                .catch((error) => {
                    this.is_busy = false;
                })
        }
    }
}
</script>

<style scoped>

</style>
