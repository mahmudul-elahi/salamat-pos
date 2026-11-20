<script setup>
import {LANDLORD_SELECTED_ROLES} from "@/services/endpoints/landlord.js";
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {onMounted, ref} from "vue";
import Axios from "@/services/axios/index.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: {}
});
const emit = defineEmits(['close']);

const {preloader, formData, errors, save, closeModal} = useSubmitForm(props, emit, 'no')
const submit = () => {
    save(props.selectedUrl, {
        roles: roles.value
    })
}
const pageLoader = ref(false)
const roles = ref([])
const getEditData = () => {
    pageLoader.value = true
    Axios.get(props.selectedUrl).then(({data}) => {
        formData.value = data
        roles.value = data.roles.map(item => item.id)
    }).finally(() => {
        pageLoader.value = false
    })
}
onMounted(() => {
    getEditData()
})
</script>

<template>
    <app-modal
        :modal-id="modalId"
        :title="$t('manage_roles')"
        modal-size="large"
        :preloader="preloader"
        @submit="submit"
        @close="closeModal">

        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
            <form v-else>
                <div class="mb-4">
                    <app-input type="advance-multi-select"
                               id="role"
                               listValueField="name"
                               list-key-field="id"
                               :label="$t('role')"
                               v-model="roles"
                               :loading-length="10"
                               :fetch-url="`${LANDLORD_SELECTED_ROLES}`"
                               :choose-label="$t('choose_a_role')"
                               :errors="$errors(errors, 'role')"/>
                </div>

                <div class="mb-4">
                    <app-input
                        type="text"
                        id="name"
                        :label="$t('name')"
                        readonly
                        :placeholder="$t('name')"
                        v-model="formData.name"
                        :errors="$errors(errors, 'name')"/>
                </div>

                <div class="mb-4">
                    <app-input
                        type="email"
                        id="email"
                        :label="$t('email')"
                        readonly
                        :placeholder="$t('email')"
                        v-model="formData.email"
                        :errors="$errors(errors, 'email')"/>
                </div>

                <div class="mb-4">
                    <app-input
                        type="text"
                        id="phone"
                        :label="$t('phone')"
                        readonly
                        :placeholder="$t('phone')"
                        v-model="formData.phone"
                        :errors="$errors(errors, 'phone')"/>
                </div>

            </form>
        </template>

    </app-modal>
</template>

<style scoped lang="scss">
</style>
