<template>
    <div>
        <button data-toggle="modal" data-target="#invitetModal" class="btn btn-primary" style="float: right">Invite</button>
        <div class="modal fade" id="invitetModal" tabindex="-1" role="dialog" aria-labelledby="inviteModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="inviteModalLabel">Invite</h5>
                        <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input class="form-control" type="email" :class="{ 'is-invalid' : errors.email }" v-model="email" id="email">
                            <span v-if="errors.email" class="invalid-feedback" role="alert">{{ errors.email }}</span>
                        </div>
                        <div class="form-group">
                            <label for="role_id">Role <span class="text-danger">*</span></label>
                            <select class="form-control" v-model="role_id" id="role_id"  :class="{ 'is-invalid' : errors.role_id }">
                                <option v-for="(role) in roles" :value="role.id">{{role.title}}</option>
                            </select>
                            <span v-if="errors.role_id" class="invalid-feedback" role="alert">{{ errors.role_id }}</span>
                        </div>
                        <div class="form-group text-center">
                            <button :disabled="is_processing" id="downloadBtn" type="submit" class="btn btn-primary" v-on:click="inviteUser()">
                                <i
                                    v-if="is_processing"
                                    class="fas fa-spinner fa-spin mr-2"/>
                                Invite
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'InviteUser',
    props: {
        business: Object,
        roles: Array
    },
    data() {
        return {
            role_id: null,
            email: null,
            errors: {
                email: null,
                role_id: null,
            },
            is_processing: false,
        };
    },
    methods: {
        inviteUser() {
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
                this.errors.email = 'The email must be a valid email address.';
                return false;
            }
            this.is_processing = true;
            this.errors = {
                email: null,
                role_id: null,
            };

            let form = new FormData();
            form.append('email', this.email);
            form.append('role_id', this.role_id);

            axios.post(this.getDomain('business/' + this.business.id + '/user-management/invite', 'dashboard'), form, {
                headers: {
                    'Accept': 'application/json',
                }
            }).then(() => {
                this.$emit('reloadBusinessUsers');
                this.role_id = null;
                this.email = null;
                $('#closeBtn').click();
            }).catch(({response: { data: { errors } } }) => {
                if(errors.email) {
                    this.errors.email = errors.email[0];
                }

                if(errors.role_id) {
                    this.errors.role_id = errors.role_id[0];
                }

                console.log(errors, this.errors);
            }).finally(() => {
                this.is_processing = false;
            });
        }
    }
}
</script>
