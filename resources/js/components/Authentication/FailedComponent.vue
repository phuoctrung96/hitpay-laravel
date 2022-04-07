<template>
    <div>
        <div v-if="!loaded && data.length === 0" class="text-center p-card-body py-4 py-xs-4 p-sm-5 p-md-6 border-card border-t">
            <i class="fas fa-circle-notch fa-spin fa-2x text-black-50"></i>
        </div>
        <div v-if="loaded && data.length === 0" class="text-center p-card-body p-xs-4 px-sm-5 px-md-6 border-card border-t">
            <p class="text-black-50 font-italic mb-0" v-t="'p.account.log.t.empty'"></p>
        </div>
        <div v-for="log in data" class="media p-card-body p-xs-4 px-sm-5 px-md-6 border-card border-t">
            <span class="fa-stack align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6">
                <i class="fas fa-stack-1x text-black-50" :class="log.class"></i>
                <i class="fas fa-ban fa-stack-2x text-danger"></i>
            </span>
            <div class="media-body align-self-center">
                <timeago class="float-right small text-black-50" :datetime="log.logged_at" :title="log.logged_at" :auto-update="60"></timeago>
                <p class="mb-0">{{ log.description }}</p>
                <p v-if="log.remark" class="small text-black-50 text-break mb-0">{{ log.remark }}</p>
            </div>
        </div>
        <button v-if="before !== false && data.length !== 0" class="btn btn-primary btn-block shadow-sm mt-3" @click="getActivityLog" :disabled="is_processing">
            Load more <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
        </button>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                loaded: false,
                before: null,
                data: [],
                is_processing: false
            };
        },

        mounted() {
            this.getActivityLog();
        },

        methods: {
            getActivityLog() {
                this.is_processing = true;

                axios.get(this.getDomain('ajax/security/failed-auths', 'account') + '?before=' + this.before).then(({data}) => {
                    _.each(data, (log) => {
                        if (this.before === null || log.id < this.before) {
                            this.before = log.id;
                        }

                        switch (log.reason) {

                            case 'incorrect_password':

                                log.class = 'fa-key';

                                break;

                            default:
                                log.class = 'fa-shield-alt';
                        }

                        this.data.push(log);
                    });

                    // NOTE: The length of 25 must match the number of limit of '/ajax/activity-log' endpoint, else it
                    // may show the 'load more' button at the wrong time or some log may not display properly. When the
                    // data length is less than 25, which means the last log is loaded.
                    if (data.length < 25) {
                        this.before = false;
                    }

                    this.loaded = true;
                }).finally(() => {
                    this.is_processing = false;
                });
            },
        }
    }
</script>
