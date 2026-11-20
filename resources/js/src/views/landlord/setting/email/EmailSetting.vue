<script setup>
import { useI18n } from "vue-i18n";
import { onMounted, ref } from "vue";
import { useToast } from "vue-toastification";
import Axios from "@/services/axios/index.js";
import TestMailModal from "@/views/common/email/TestMailModal.vue";
import { LANDLORD_EMAIL_SETTINGS, LANDLORD_SELECTED_EXIST_EMAIL_SETTINGS } from "@/services/endpoints/landlord.js";
import _ from "lodash";
import usePermission from "@/composable/permission/usePermission.js";
import { Download } from "@iconoir/vue";
const { canAccess } = usePermission();
const props = defineProps({
    id: {}
});

const { t } = useI18n();
const toast = useToast();

const providerList = [
    {
        id: 'sendmail',
        name: t('sendmail')
    },
    {
        id: 'smtp',
        name: t('smtp')
    }
];

const encryptionTypeList = [
    {
        id: 'ssl',
        name: t('ssl')
    },
    {
        id: 'tls',
        name: t('tls')
    }
];

const formData = ref({});
const errors = ref({});
const buttonLoader = ref(false);

const handleSubmit = async () => {
    buttonLoader.value = true;
    errors.value = {};
    try {
        const { data } = await Axios.post(LANDLORD_EMAIL_SETTINGS, formData.value);
        toast.success(data.message);
        await getServerEmailSetting();
    } catch ({ response }) {
        if (response.status === 422) {
            errors.value = response.data.errors;
        } else {
            toast.error(response.data.message);
        }
    } finally {
        buttonLoader.value = false;
    }
}

const preloader = ref(true);
const getServerEmailSetting = async () => {
    preloader.value = true;
    try {
        const { data } = await Axios.get(LANDLORD_EMAIL_SETTINGS);
        if (!_.isEmpty(data)) {
            formData.value = data;
        }
    } catch ({ response }) {
        toast.error(response.data.message);
    } finally {
        preloader.value = false;
    }
}

const isModalActive = ref(false)
const checkEmail = ref(null)

const existEmailCheck = () => {
    Axios.get(LANDLORD_SELECTED_EXIST_EMAIL_SETTINGS).then(({ data }) => {
        checkEmail.value = data
    })
}

onMounted(() => {
    getServerEmailSetting();
    existEmailCheck()
});

</script>

<template>
    <div class="form-center-loader d-flex justify-content-center" v-if="preloader">
        <app-loader />
    </div>
    <form v-else>
        <div class="mb-4">
            <app-input
                type="select"
                id="provider"
                :label="$t('provider')"
                label-required
                :options="providerList"
                list-value-field="name"
                :choose-label="$t('choose_a_provider')"
                v-model="formData.provider"
                :errors="$errors(errors, 'provider')" />
        </div>

        <div class="mb-4">
            <app-input
                type="text"
                id="from_name"
                :label="$t('from_name')"
                label-required
                :placeholder="$t('from_name')"
                v-model="formData.from_name"
                :errors="$errors(errors, 'from_name')" />
        </div>
        <div class="mb-4">
            <app-input
                type="email"
                id="from_email"
                :label="$t('from_email')"
                label-required
                :placeholder="$t('from_email')"
                v-model="formData.from_email"
                :errors="$errors(errors, 'from_email')" />
        </div>

        <template v-if="formData.provider === 'smtp'">
            <div class="mb-4">
                <app-input
                    type="text"
                    id="host"
                    :label="$t('smtp_host')"
                    label-required
                    :placeholder="$t('smtp_host')"
                    v-model="formData.smtp_host"
                    :errors="$errors(errors, 'smtp_host')" />
            </div>
            <div class="mb-4">
                <app-input
                    type="text"
                    id="smtp_port"
                    :label="$t('smtp_port')"
                    label-required
                    :placeholder="$t('smtp_port')"
                    v-model="formData.smtp_port"
                    :errors="$errors(errors, 'smtp_port')" />
            </div>
            <div class="mb-4">
                <app-input
                    type="select"
                    id="encryption_type"
                    :label="$t('encryption_type')"
                    label-required
                    :options="encryptionTypeList"
                    list-value-field="name"
                    :choose-label="$t('encryption_type')"
                    v-model="formData.encryption_type"
                    :errors="$errors(errors, 'encryption_type')" />

            </div>
            <div class="mb-4">
                <app-input
                    type="text"
                    id="smtp_username"
                    :label="$t('smtp_username')"
                    label-required
                    :placeholder="$t('smtp_username')"
                    v-model="formData.smtp_username"
                    :errors="$errors(errors, 'smtp_username')" />
            </div>
            <div class="mb-4">
                <app-input
                    type="password"
                    id="email_password"
                    :label="$t('email_password')"
                    label-required
                    :placeholder="$t('email_password')"
                    v-model="formData.email_password"
                    :errors="$errors(errors, 'email_password')" />
            </div>


        </template>
        <div class="d-grid gap-2 d-md-flex mt-3">
            <button type="button" class="btn btn-primary" :disabled="preloader" v-if="canAccess('landlord_email_setting_update')" @click.prevent="handleSubmit">
                <app-button-loader v-if="buttonLoader" />
                <Download />
                {{ $t('update') }}
            </button>

            <button type="button" v-if="checkEmail" class="btn btn-primary-outline fw-bold" @click.prevent="isModalActive = true">
                {{ $t('test_mail') }}
            </button>
        </div>
    </form>

    <test-mail-modal v-if="isModalActive" modal-id="test-mail-modal" @close="isModalActive = false" />
</template>

<style scoped lang="scss"></style>
