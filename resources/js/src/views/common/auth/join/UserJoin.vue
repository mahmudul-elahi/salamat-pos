<script setup>
import AuthLayout from "@/views/common/auth/layout/AuthLayout.vue";
import {onMounted, ref} from "vue";
import {useToast} from "vue-toastification";
import router from "@/router/index.js";
import Axios from "@/services/axios/index.js";
import {AVAILABLE_TOKEN, USER_JOIN} from "@/services/endpoints/app.js";
import {useRoute} from 'vue-router'

const route = useRoute();

const formData = ref({email: ''});
const errors = ref({});
const preloader = ref(false);
const toast = useToast();

const submit = () => {
    preloader.value = true;
    errors.value = {};
    Axios.post(USER_JOIN, {
        ...formData.value,
        token: route.query.token
    }).then(({data}) => {
        toast.success(data.message);
        router.push({name: "login"});
    }).catch(({response}) => {
        if (response.status === 422) {
            errors.value = response.data.errors;
        } else {
            errors.value = {};
            toast.error(response.data.message);
        }
    }).finally(() => preloader.value = false);
}

const pageLoader = ref(false);
const tokenError = ref(false);
const getTokenCheck = (token) => {
    pageLoader.value = true;
    Axios.get(`${AVAILABLE_TOKEN}/${token}`).then(({data}) => {
    }).catch(({response}) => {
        toast.error(response?.data?.message);
        tokenError.value = true
    }).finally(() => pageLoader.value = false);
}
onMounted(() => {
    const token = route.query.token
    if (!token) {
        router.push({name: 'login'})
    }
    getTokenCheck(token)
})
</script>
<template>
    <auth-layout>
        <template v-slot:auth-content>
            <app-loader v-if="pageLoader"/>
            <form v-else action="" ref="form" @submit.prevent="submit">
                <div class="mb-4">
                    <app-input
                        type="text"
                        id="first_name"
                        :label="$t('first_name')"
                        :label-required="true"
                        :placeholder="$t('first_name')"
                        v-model="formData.first_name"
                        :errors="$errors(errors, 'first_name')"
                    />
                </div>
                <div class="mb-4">
                    <app-input
                        type="text"
                        id="last_name"
                        :label="$t('last_name')"
                        :label-required="true"
                        :placeholder="$t('last_name')"
                        v-model="formData.last_name"
                        :errors="$errors(errors, 'last_name')"
                    />
                </div>
                <div class="mb-4">
                    <app-input type="password"
                               id="new_password"
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

                <button
                    type="submit"
                    class="btn btn-primary w-100 shadow rounded-3 mb-3"
                    :disabled="tokenError || preloader">
                    <app-button-loader v-if="preloader"/>
                    {{ $t("join") }}
                </button>
            </form>
        </template>
    </auth-layout>
</template>

