<template>
    <div>
        <div v-for="session in data" class="media p-card-body p-xs-4 px-sm-5 px-md-6 border-card border-t">
            <i class="fas fa-fw fa-2x align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6" :class="session.class"></i>
            <div class="media-body align-self-center">
                <timeago class="float-right small text-black-50" :datetime="session.created_at" :title="session.created_at" :auto-update="60"></timeago>
                <p class="font-weight-bold mb-0">{{ session.remark }}</p>
                <ul class="list-inline-dot text-muted small mb-0">
                    <li v-if="session.location" class="list-inline-dot-item">{{ session.location }}</li>
                    <li v-if="session.agent" class="list-inline-dot-item">{{ session.agent }}</li>
                    <li class="list-inline-dot-item">
                        <a v-if="is_revoking === false" class="text-danger cursor-pointer" @click="revoke(session)" v-t="'p.sessions.b.revoke'"></a>
                        <span v-else-if="is_revoking === session.id" class="text-danger">{{ $t('p.sessions.t.revoking') }} <i class="fas fa-circle-notch fa-spin"></i></span>
                        <span v-else class="text-muted">{{ $t('p.sessions.b.revoke') }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                data: [],
                is_revoking: false,
            };
        },

        mounted() {
            this.getSessions();
        },

        methods: {
            // TODO - 2019-10-13
            // We will need to study and plan how to provide the best experience to the user in sessions management
            // page. The page is currently will load all the user's sessions and reload again when user revoke any of
            // them. When there is some user with too many sessions and we are decided to use pagination, we have to
            // consider how is the user experience can be affected.

            getSessions() {
                axios.get(this.getDomain('ajax/security/sessions', 'account')).then(({data}) => {
                    this.is_revoking = false;
                    this.data = [];

                    _.each(data, (session) => {
                        switch (session.device.type) {

                            case 'phone':
                                session.class = 'fa-mobile-alt';
                                break;

                            case 'tablet':
                                session.class = 'fa-tablet-alt';
                                break;

                            case 'computer':
                                session.class = 'fa-desktop';
                                break;

                            case 'robot':
                                session.class = 'fa-robot';
                                break;

                            default:
                                session.class = 'fa-question-circle';
                        }

                        this.data.push(session);
                    });
                });
            },

            revoke(session) {
                if (this.is_revoking !== false) {
                    return;
                }

                this.is_revoking = session.id;

                axios.delete(this.getDomain('ajax/security/sessions/' + session.id, 'account')).then(() => {
                    this.getSessions();
                });
            }
        }
    }
</script>
