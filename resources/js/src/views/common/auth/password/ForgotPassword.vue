<script setup>
import {ref} from "vue"
import {useToast} from "vue-toastification";
import AuthLayout from "@/views/common/auth/layout/AuthLayout.vue";
import Axios from "@/services/axios/index.js";
import {FORGOT_PASSWORD} from "@/services/endpoints/app.js";

const preloader = ref(false)
const formData = ref({})
const errors = ref({})
const toast = useToast()
const handleSubmit = async () => {
    preloader.value = true;
    errors.value = {};
    try {
        const {data} = await Axios.post(FORGOT_PASSWORD, formData.value);
        toast.success(data.message);
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
                <div class="mb-4 position-relative">
                    <app-input
                        type="email"
                        id="email"
                        :label="$t('email')"
                        :label-required="true"
                        :placeholder="$t('email')"
                        v-model="formData.email"
                        :errors="$errors(errors, 'email')"
                    />
                    <router-link class="brand-color text-end d-block mt-2" to="/login">
                        {{ $t('back_to_login') }}
                    </router-link>
                </div>

                <button type="button"
                        class="btn btn-primary w-100 shadow rounded-3"
                        :disabled="preloader"
                        @click.prevent="handleSubmit">
                    <app-button-loader v-if="preloader"/>
                    {{ $t('send_reset_link') }}
                </button>
            </form>
        </template>
    </auth-layout>
</template>


<style scoped lang="scss">
</style>
