<script setup>
import { ref, onMounted } from "vue";
import { useToast } from "vue-toastification";
import Axios from "@/services/axios/index.js";
import router from "@/router/index.js";
import { LANDLORD_PERMISSIONS, LANDLORD_ROLES } from "@/services/endpoints/landlord.js";
import RolePermissionPicker from "@/views/landlord/administrator/role/RolePermissionPicker.vue";

const props = defineProps({
    id: {},
});

const toast = useToast();

const permissions = ref({});
const formData = ref({ name: "", permissions: [] });
const errors = ref({});
const preloader = ref(false);
const pageLoader = ref(false);

const getPermissions = () => {
    Axios.get(LANDLORD_PERMISSIONS).then(({ data }) => (permissions.value = data));
};

const getRole = () => {
    pageLoader.value = true;
    Axios.get(`${LANDLORD_ROLES}/${props.id}`)
        .then(({ data }) => {
            formData.value.name = data.name;
            formData.value.permissions = data.permissions.map((item) => item.id);
        })
        .finally(() => {
            pageLoader.value = false;
        });
};

const submit = async () => {
    preloader.value = true;
    errors.value = {};
    try {
        const response = await Axios.patch(`${LANDLORD_ROLES}/${props.id}`, formData.value);
        toast.success(response.data?.message);
        //await router.push({ name: "roles" });
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || "Failed to update role.");
        }
    } finally {
        preloader.value = false;
    }
};

onMounted(() => {
    getPermissions();
    getRole();
});
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('update_role')" />

        <div class="card p-4 mb-3">
            <div class="role-name-field">
                <app-input type="text" id="name" :label="$t('name')" label-required :placeholder="$t('role_name')"
                    v-model="formData.name" :errors="$errors(errors, 'name')" />
            </div>
        </div>

        <role-permission-picker :permissions="permissions" v-model="formData.permissions" />

        <div class="mt-3 d-flex justify-content-end gap-3">
            <button type="button" class="btn btn-outline-secondary" @click="router.push({ name: 'roles' })">
                {{ $t("cancel") }}
            </button>
            <button class="btn btn-success text-white" @click.prevent="submit">
                <app-button-loader v-if="preloader" />
                {{ $t("save") }}
            </button>
        </div>
    </div>
</template>

<style scoped lang="scss">
.role-name-field {
    max-width: 360px;
}
</style>
