<template>
    <div>
        <InviteUser
            :business="business"
            :roles="roles"
            v-on:reloadBusinessUsers="reloadBusinessUsers"
        />
        <div>
            <br><br>
            <table class="table table-bordered mt-3">
                <thead>
                <tr>
                    <td class="name-column">User</td>
                    <td class="text-center">Role</td>
                    <td class="actions-column"></td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(businessUser) in users" :key="businessUser.id">
                    <td class="vertical-align-middle">
                        <span>{{businessUser.user.display_name || businessUser.user.email}}</span>
                        <p class="mb-0" v-if="businessUser.user.display_name"><span class="help-text">{{businessUser.user.email}}</span></p>
                    </td>
                    <td class="text-center vertical-align-middle">
                        <span class="btn btn-success" v-if="businessUser.invite_accepted_at">
                            {{businessUser.role.title}}
                        </span>
                        <span class="btn btn-info" v-if="!businessUser.invite_accepted_at">
                            Pending invitation
                        </span>
                    </td>
                    <td class="text-right vertical-align-middle">
                        <a v-if="isUpdatable(businessUser)" href="#" data-toggle="modal" data-target="#editModal" v-on:click="startEditing(businessUser)" title="Edit Role">
                            <i class="fas fa-edit mr-2"></i>
                        </a>
                        <a v-if="isDeletable(businessUser)" href="#" data-toggle="modal" data-target="#confirmRemoveModal"
                           v-on:click="startDeleting(businessUser)" class="text-danger" title="Remove User">
                            <i class="fas fa-trash mr-2"></i>
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div id="confirmRemoveModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div v-if="selectedRecord" class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <h5 class="modal-title text-danger font-weight-bold mb-3">
                            Do you really want to remove selected user from current business?
                        </h5>
                        <button type="submit" class="btn btn-danger" v-on:click="detachUser()">Yes</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" v-on:click="stopDeleting()">No</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div v-if="selectedRecord" class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit</h5>
                        <button id="closeBtn2" type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="role_id">Role <span class="text-danger">*</span></label>
                            <select class="form-control" v-model="selectedRecord.role.id" id="role_id">
                                <option v-for="(role) in roles" :value="role.id">{{role.title}}</option>
                            </select>
                        </div>
                        <div class="form-group text-center">
                            <button id="downloadBtn" type="submit" class="btn btn-primary" v-on:click="save()">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Vue from 'vue';
import InviteUser from "./InviteUser";

export default {
    name: 'BusinessUsers',
    components: { InviteUser},
    props: {
        roles: Array,
        current_business_user_id: String
    },
    data() {
        return {
            business: window.Business,
            users: [],
            selectedRecord: null,
            current_business_user: null
        }
    },
    mounted() {
        this.business = Business;

        this.reloadBusinessUsers()
    },
    methods: {
        isDeletable({role:{title}}) {
            switch(true) {
                case title === 'Manager':
                case title === 'Cashier':
                case title === 'Admin' && this.current_business_user.permissions.canDeleteAdminUsers:
                    return true;
            }

            return false;
        },
        isUpdatable({role:{title}}) {
            switch(true) {
                case title === 'Manager':
                case title === 'Cashier':
                case title === 'Admin' && this.current_business_user.permissions.canDeleteAdminUsers:
                    return true;
            }

            return false;
        },
        reloadBusinessUsers() {
            axios.get(this.getDomain('business/' + this.business.id + '/user-management', 'dashboard'), {
                headers: {
                    'Accept': 'application/json',
                }
            }).then(({data: {businessUsers}}) => {
                console.log(businessUsers)
                this.users = businessUsers;
                this.current_business_user = this.users.find(businessUser => businessUser.user_id === this.current_business_user_id);
            }).catch(({response: { data: { errors } } }) => {
                console.error(errors)
            });
        },

        startEditing(record) {
            this.selectedRecord = Vue.util.extend({}, record);
        },

        stopEditing() {
            this.selectedRecord = null;
            $('#closeBtn2').click();
        },

        save() {
            let form = new FormData();
            form.append('role_id', this.selectedRecord.role.id);

            axios.post(this.getDomain('business/' + this.business.id + '/user-management/' + this.selectedRecord.id + '/update', 'dashboard'), form, {
                headers: {
                    'Accept': 'application/json',
                }
            }).then(() => {
                this.stopEditing();
                this.reloadBusinessUsers();
                alert('Role successfully updated.');
            }).catch(({response: { data: { errors } } }) => {
                console.error(errors)
            });

            $('#editModal').modal('hide');
        },

        startDeleting(record) {
            this.selectedRecord = Vue.util.extend({}, record);
        },

        stopDeleting() {
            this.selectedRecord = null;
            $('#confirmRemoveModal').modal('hide');
        },

        detachUser() {
            axios.get(this.getDomain('business/' + this.business.id + '/user-management/' + this.selectedRecord.id + '/detach', 'dashboard'), {
                headers: {
                    'Accept': 'application/json',
                }
            }).then(() => {
                this.reloadBusinessUsers();
                this.stopDeleting();
            }).catch(({response: { data: { errors } } }) => {
                console.error(errors)
            });
        },
    }
}
</script>
