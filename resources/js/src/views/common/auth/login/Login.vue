<script setup>
import {ref} from "vue";
import AuthLayout from "@/views/common/auth/layout/AuthLayout.vue";
import {LOGIN_API} from "@/services/endpoints/app.js";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import {setToken} from "@/utils/token.js";
import {urlGenerator} from "@/utils/urlGenerator.js";
import {setTenant} from "@/utils/tenant.js";
import router from "@/router/index.js";

const toast = useToast();

const formData = ref({});
const preloader = ref(false);
const errors = ref({});

// Handle form submission
const handleSubmit = async () => {
    preloader.value = true;
    errors.value = {};

    try {
        const {data} = await Axios.post(LOGIN_API, formData.value);
        setToken(data.result.access_token);
        toast.success(data.message);
        const tenant = data.result.user['tenant'];
        if (tenant) {
            setTenant(tenant);
        }
        window.location.replace(urlGenerator('dashboard'))

    } catch ({response}) {
        if (response.status === 422) {
            errors.value = response.data.errors;
        } else {
            toast.error(response.data.message);
        }
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <auth-layout>
        <template v-slot:auth-content>
            <form ref="form" @submit.prevent="handleSubmit">
                <div class="mb-4">
                    <app-input
                        type="email"
                        id="email"
                        :label="$t('email')"
                        :label-required="true"
                        :placeholder="$t('email')"
                        v-model="formData.email"
                        :errors="$errors(errors, 'email')"/>
                </div>
                <div class="mb-4 position-relative">
                    <app-input
                        type="password"
                        id="password"
                        :label="$t('password')"
                        :placeholder="$t('password')"
                        :label-required="true"
                        v-model="formData.password"
                        :errors="$errors(errors, 'password')"/>
                    <div class="d-flex justify-content-between">
                        <router-link
                            class="brand-color text-end d-block mt-2"
                            to="/forgot-password">{{ $t('forget_password') }}

                        </router-link>

                        <router-link
                            class="d-flex justify-content-end mt-2 brand-color"
                            to="/register">{{ $t("sign_up") }}
                        </router-link>
                    </div>


                </div>

                <button
                    type="submit"
                    class="btn btn-primary w-100 shadow rounded-3 mb-3"
                    :disabled="preloader">
                    <app-button-loader v-if="preloader"/>
                    {{ $t('sign_in') }}
                </button>
            </form>
        </template>
    </auth-layout>
</template>

<style scoped lang="scss"></style>
