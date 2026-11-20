<script setup>
import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import Axios from "@/services/axios/index.js";
import { MY_PROFILE } from "@/services/endpoints/app.js";
import { useToast } from "vue-toastification";
import { formDataAssigner } from "@/utils/helper.js";
import { Download } from "@iconoir/vue";

const props = defineProps({
    id: {},
});

const { t } = useI18n();
const toast = useToast()

const formData = ref({})
const preloader = ref(false)
const errors = ref({})
const phoneDetails = ref({
    phone_number: '',
    phone_country: 'BD',
})

const store = useProfileStore();
const { profileData } = storeToRefs(store);


watch(profileData, (user) => {
    formData.value = JSON.parse(JSON.stringify(user))
    phoneDetails.value = {
        phone_number: user.phone || '',
        phone_country: user.phone_country || 'BD'
    }
}, { immediate: true });


const handelSubmit = async () => {
    try {
        errors.value = {}
        preloader.value = true
        const customData = formDataAssigner(new FormData(), {
            ...formData.value,
            address: formData.value.address ?? '',
            gender: formData.value.gender ?? '',
            phone: phoneDetails.value.phone_number ?? '',
            phone_country: phoneDetails.value.phone_country ?? '',

        })
        const { data } = await Axios.post(MY_PROFILE, customData, {
            headers: {
                "Content-Type": "multipart/form-data",
            }
        })
        toast.success(data.message)
        errors.value = {}
        await store.fetchMyProfile()
    } catch ({ response }) {
        if (response.status === 422) {
            errors.value = response.data.errors;
        } else {
            toast.error(response.data.message);
        }
    } finally {
        preloader.value = false
    }
}

</script>

<template>
    <div class="row g-4">
        <div class="col-md-6">
            <app-input id="name" type="text" :label="$t('name')" label-required :placeholder="$t('name')"
                v-model="formData.name" :errors="$errors(errors, 'name')" />
        </div>
        <div class="col-md-6">
            <app-input id="email_address" type="email" :label="$t('email_address')" label-required
                :placeholder="$t('email_address')" v-model="formData.email" :errors="$errors(errors, 'email')" />
        </div>
        <div class="col-md-6">
            <!-- <app-input id="phone" type="text" :label="$t('phone_number')" :placeholder="$t('phone_number')"
                v-model="formData.phone" :errors="$errors(errors, 'phone')" /> -->

            <app-input id="phone" type="phone-number" :label="$t('phone')" :placeholder="$t('phone')"
                v-model="phoneDetails" :selected-country="phoneDetails.phone_country"
                :errors="$errors(errors, 'phone')" />
        </div>
        <div class="col-12">
            <button type="button" class="btn btn-primary shadow" :disabled="preloader" @click.prevent="handelSubmit">
                <app-button-loader v-if="preloader" />
                <Download />
                {{ $t('update') }}
            </button>
        </div>
    </div>
</template>
<style scoped lang="scss"></style>
