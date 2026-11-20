<script setup>
import {ref, watch} from "vue"
import {Modal} from "bootstrap";
import {useToast} from "vue-toastification";
import {useI18n} from 'vue-i18n';
import usePermission from "@/composable/permission/usePermission.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import Axios from "@/services/axios/index.js";
import Avatar from "@/views/global/avatar/Avatar.vue";
import {useProfileStore} from "@/stores/profile/profile.js";
import {storeToRefs} from "pinia";
import {
    LANDLORD_ATTACH_ROLE_USER,
    LANDLORD_DETACH_ROLE_USER,
    LANDLORD_SELECTED_ROLE_WITHOUT_USERS
} from "@/services/endpoints/landlord.js";
import {Trash} from "@iconoir/vue";

const {canAccess} = usePermission();
const props = defineProps({
    modalId: {},
    tableId: {},
    manageUser: {}
})
const emit = defineEmits(['close'])
const toast = useToast()
const emitter = useEmitter()
const {t} = useI18n();

const users = ref([])
const errors = ref({})
const preloader = ref(false)
const submit = () => {
    preloader.value = true
    Axios.post(`${LANDLORD_ATTACH_ROLE_USER}/${props.manageUser.id}`, {
        'users': users.value
    }).then(({data}) => {
        emitter.emit('reload-' + props.tableId);
        toast.success(data?.message);
        closeModal()
    }).catch(({response}) => {
        if (response.status === 422) {
            errors.value = response?.data?.errors
        } else {
            toast.error(response?.data?.message);
        }
    }).finally(() => preloader.value = false)
}
const profilePicture = (user) => {
    if (user.profile_picture) {
        return user.profile_picture
    }
}

const existUsers = ref([])
watch((props.manageUser), (item) => {
    existUsers.value = item.users ? item.users.map((el) => el.id) : []
}, {immediate: true})

const store = useProfileStore();
const {profileData} = storeToRefs(store);
const detachUserRole = (user, index) => {
    if (user.id === profileData.value.id) {
        toast.error("You can't delete your own account role");
        return
    }

    Axios.patch(`${LANDLORD_DETACH_ROLE_USER}/${user.id}`).then(({data}) => {
        toast.success(data?.message);
        props.manageUser.users.splice(index, 1);
        emitter.emit('reload-' + props.tableId);
    }).catch(({response}) => {
        toast.error(response?.data?.message);
    })

}

const closeModal = () => {
    let modal = Modal.getInstance(document.getElementById(props.modalId))
    modal.hide()
    emit('close')
}
</script>

<template>
    <app-modal :modal-id="modalId"
               :title="$t('manage_users')"
               modal-size="large"
               :preloader="preloader"
               :permission="canAccess('landlord_role_attach_user')"
               @submit="submit"
               @close="closeModal">

        <template v-slot:body>
            <app-loader v-if="preloader"/>
            <div v-else class="d-flex align-items-center justify-content-between pb-3 mb-3"
                 v-for="(user, index) in manageUser.users" :key="index">
                <div class="d-flex align-items-center">
                    <div class="profile-avatar">
                        <avatar :image="profilePicture(user)"
                                :name="user.full_name"
                                :active="user?.status?.class"/>
                    </div>
                    <div class="media m-2">
                        <h6>
                            {{ user?.user_profile?.full_name }}
                        </h6>
                        <div class="email">
                            <h6>
                                {{ user.email }}
                            </h6>
                        </div>
                    </div>
                </div>

                <div class="delete" v-if="!(user?.is_admin) || canAccess('landlord_role_detach_user')">
                    <a href="" @click.prevent="detachUserRole(user, index)">
                        <Trash class="text-danger"/>
                    </a>
                </div>
            </div>
            <div class="mb-4" v-if="canAccess('landlord_role_attach_user')">
                <app-input type="advance-multi-select"
                           :label="$t('users')"
                           id="manage-role-users"
                           listValueField="full_name"
                           list-key-field="id"
                           v-model="users"
                           :loading-length="10"
                           :fetch-url="`${LANDLORD_SELECTED_ROLE_WITHOUT_USERS}?existing=${existUsers}`"
                           :choose-label="$t('choose_a_user')"
                           :errors="$errors(errors, 'users')"/>
            </div>
        </template>

    </app-modal>
</template>

<style scoped lang="scss"></style>
