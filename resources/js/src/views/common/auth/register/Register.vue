<script setup>
import AuthLayout from "@/views/common/auth/layout/AuthLayout.vue";
import { ref } from "vue";
import { useToast } from "vue-toastification";
import router from "@/router/index.js";
import Axios from "@/services/axios/index.js";
import { SUBSCRIBER_REGISTER } from "@/services/endpoints/app.js";

const formData = ref({ email: '' });
const errors = ref({});
const preloader = ref(false);
const toast = useToast();

const submit = () => {
    preloader.value = true;
    errors.value = {};
    Axios.post(SUBSCRIBER_REGISTER, formData.value).then(({ data }) => {
        toast.success(data.message);
        router.push({ name: "login" });
    }).catch(({ response }) => {
        if (response.status === 422) {
            errors.value = response.data.errors;
        } else {
            errors.value = {};
            toast.error(response.data.message);
        }
    }).finally(() => preloader.value = false);
}
</script>
<template>
    <auth-layout>
        <template v-slot:auth-content>
            <form action="" ref="form" @submit.prevent="submit">
                <div class="mb-4">
                    <app-input
                        type="text"
                        id="first_name"
                        :label="$t('first_name')"
                        :label-required="true"
                        :placeholder="$t('first_name')"
                        v-model="formData.first_name"
                        :errors="$errors(errors, 'first_name')" />
                </div>
                <div class="mb-4">
                    <app-input
                        type="text"
                        id="last_name"
                        :label="$t('last_name')"
                        :label-required="true"
                        :placeholder="$t('last_name')"
                        v-model="formData.last_name"
                        :errors="$errors(errors, 'last_name')" />
                </div>
                <div class="mb-4">
                    <app-input
                        type="email"
                        id="email"
                        :label="$t('email')"
                        :label-required="true"
                        :placeholder="$t('email_address')"
                        v-model="formData.email"
                        :errors="$errors(errors, 'email')" />
                </div>
                <div class="mb-4">
                    <app-input
                        type="text"
                        id="company_name"
                        :label="$t('company_name')"
                        :label-required="true"
                        :placeholder="$t('company_name')"
                        v-model="formData.company_name"
                        :errors="$errors(errors, 'company_name')" />
                    <router-link
                        class="d-flex justify-content-end mt-2 brand-color"
                        to="/login">{{ $t("already_have_account") }}
                    </router-link>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary w-100 shadow rounded-3 mb-3"
                    :disabled="preloader">
                    <app-button-loader v-if="preloader" />
                    {{ $t("sign_up") }}
                </button>
            </form>
        </template>
    </auth-layout>
</template>
