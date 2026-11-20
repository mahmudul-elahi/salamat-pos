<script setup>
import {ref} from "vue";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import {CHANGE_PASSWORD} from "@/services/endpoints/app.js";
import { Download } from "@iconoir/vue";

const props = defineProps({
    id: {}
});
const toast = useToast()
const formData = ref({})
const errors = ref({})
const preloader = ref(false)

const handelSubmit = async () => {
    try {
        preloader.value = true
        errors.value = {}
        const {data} = await Axios.patch(CHANGE_PASSWORD, formData.value);
        toast.success(data.message);
        formData.value = {}
    } catch ({response}) {
        if (response.status === 422) {
            errors.value = response.data.errors;
        } else {
            toast.error(response.data.message);
        }
    } finally {
        preloader.value = false;
    }
}
</script>

<template>
    <div class="mb-4">
        <app-input id="current_password"
                   type="password"
                   :label="$t('current_password')"
                   label-required
                   :placeholder="$t('current_password')"
                   v-model="formData.current_password"
                   :errors="$errors(errors, 'current_password')"/>
    </div>
    <div class="mb-4">
        <app-input id="new_password"
                   type="password"
                   :label="$t('new_password')"
                   label-required
                   :placeholder="$t('new_password')"
                   v-model="formData.password"
                   :errors="$errors(errors, 'password')"/>
    </div>

    <div class="mb-4">
        <app-input id="confirm_password"
                   type="password"
                   :label="$t('confirm_password')"
                   label-required
                   :placeholder="$t('password_confirmation')"
                   v-model="formData.password_confirmation"
                   :errors="$errors(errors, 'password_confirmation')"/>
    </div>

    <button type="button"
            class="btn btn-primary shadow"
            :disabled="preloader"
            @click.prevent="handelSubmit">
        <app-button-loader v-if="preloader"/>
        <Download />
        {{ $t('update') }}
    </button>
</template>

<style scoped lang="scss">

</style>
