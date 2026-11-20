<script setup>
import {ref} from "vue"
import {useToast} from "vue-toastification";
import {useRoute} from "vue-router";
import router from "@/router/index.js";
import AuthLayout from "@/views/common/auth/layout/AuthLayout.vue";
import Axios from "@/services/axios/index.js";
import {CONFIRM_PASSWORD} from "@/services/endpoints/app.js";

const preloader = ref(false)
const errors = ref({})
const formData = ref({})
const toast = useToast()
const route = useRoute();
const handleSubmit = async () => {
    preloader.value = true;
    errors.value = {};
    const data = {
        ...formData.value,
        token: route.query.token, // Retrieve token from route query
    };

    try {
        const {data: response} = await Axios.post(CONFIRM_PASSWORD, data);
        toast.success(response.message);
        await router.push({name: 'login'}); // Redirect to the login page
    } catch (error) {
        const {response} = error;

        if (response?.status === 422) {
            errors.value = response.data.errors;
        } else {
            errors.value = {};
            toast.error(response?.data?.message || 'An unexpected error occurred.');
        }
    } finally {
        preloader.value = false;
    }
};

</script>
<template>
    <auth-layout>
        <template v-slot:auth-content>
            <form ref="form">
                <div class="mb-4">
                    <app-input type="password" id="new_password"
                               :label="$t('new_password')"
                               :label-required="true"
                               :placeholder="$t('new_password')"
                               v-model="formData.password"
                               :errors="$errors(errors, 'password')"/>
                </div>
                <div class="mb-4">
                    <app-input type="password"
                               id="confirm_password"
                               :label="$t('confirm_password')"
                               :label-required="true"
                               :placeholder="$t('confirm_password')"
                               v-model="formData.password_confirmation"
                               :errors="$errors(errors, 'password_confirmation')"/>
                </div>
                <button type="button"
                        class="btn btn-primary w-100 shadow"
                        :disabled="preloader"
                        @click.prevent="handleSubmit">
                    <app-button-loader v-if="preloader"/>
                    {{ $t('update_password') }}
                </button>
            </form>
        </template>
    </auth-layout>
</template>


<style scoped lang="scss">
</style>
