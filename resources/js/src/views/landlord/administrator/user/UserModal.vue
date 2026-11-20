<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { LANDLORD_SELECTED_ROLES, SELECTED_SHOPS, LANDLORD_USERS } from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    tableId: String
})

const emit = defineEmits(['close'])
const { preloader, formData, errors, save, closeModal } = useSubmitForm(props, emit, 'no')
const submit = () => {
    save(LANDLORD_USERS, formData.value)
}

</script>
<template>
    <app-modal :modal-id="modalId"
        modal-size="large"
        :title="selectedUrl ? $t('update_user') : $t('add_user')"
        :preloader="preloader"
        @submit="submit"
        @close="closeModal">
        <template v-slot:body>
            <div class="mb-4">
                <app-input
                    type="text"
                    id="name"
                    :label="$t('name')"
                    label-required
                    :placeholder="$t('name')"
                    v-model="formData.name"
                    :errors="$errors(errors, 'name')" />
            </div>
            <div class="mb-4">
                <app-input
                    type="email"
                    id="email"
                    :label="$t('email')"
                    label-required
                    :placeholder="$t('email')"
                    v-model="formData.email"
                    :errors="$errors(errors, 'email')" />
            </div>
            <div class="mb-4">
                <app-input
                    type="text"
                    id="phone"
                    :label="$t('phone')"
                    label-required
                    :placeholder="$t('phone')"
                    v-model="formData.phone"
                    :errors="$errors(errors, 'phone')" />
            </div>
            <div class="mb-4">
                <app-input
                    type="text"
                    id="password"
                    :label="$t('password')"
                    label-required
                    :placeholder="$t('password')"
                    v-model="formData.password"
                    :errors="$errors(errors, 'password')" />
            </div>
            <div class="mb-4">
                <app-input type="advance-multi-select"
                    id="role"
                    listValueField="name"
                    list-key-field="id"
                    :label="$t('role')"
                    label-required
                    v-model="formData.role"
                    :loading-length="10"
                    :fetch-url="`${LANDLORD_SELECTED_ROLES}`"
                    :choose-label="$t('choose_a_role')"
                    :errors="$errors(errors, 'role')" />

            </div>
            <div class="mb-4">
                <app-input type="advance-search-select"
                    id="role"
                    listValueField="name"
                    list-key-field="id"
                    :label="$t('shop_name')"
                    label-required
                    v-model="formData.tenant_id"
                    :loading-length="10"
                    :fetch-url="SELECTED_SHOPS"
                    :choose-label="$t('choose_a_shop')"
                    :errors="$errors(errors, 'tenant_id')" />

            </div>
        </template>
    </app-modal>
</template>
