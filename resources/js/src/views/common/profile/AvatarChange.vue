<script setup>
import { ref, watch } from "vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import Axios from "@/services/axios/index.js";
import { CHANGE_AVATAR } from "@/services/endpoints/app.js";
import { useToast } from "vue-toastification";
import { storeToRefs } from "pinia";
import { Download } from "@iconoir/vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
const props = defineProps({
    id: {}
});

const store = useProfileStore();
const toast = useToast();
const formData = ref({});
const errors = ref({});
const preloader = ref(false);

const updateProfilePicture = (file) => {
    formData.value.profile_picture = file;
};

const { profileData } = storeToRefs(store);

watch(profileData, (user) => {
    formData.value = JSON.parse(JSON.stringify(user))
}, { immediate: true });
const handelSubmit = async () => {
    try {
        errors.value = {};
        preloader.value = true;

        const customData = new FormData()
        customData.append('profile_picture', formData.value.profile_picture);

        const { data } = await Axios.post(CHANGE_AVATAR, customData, {
            headers: {
                "Content-Type": "multipart/form-data",
            }
        });

        formData.value.profile_picture = data.result?.profile_picture;
        toast.success(data.message);
        errors.value = {};
        await store.fetchMyProfile();
    } catch ({ response }) {
        if (response.status === 422) {
            errors.value = response.data.errors;
        }else if (response.status === 413){
            toast.error('File is too large');
        } else {
            toast.error(response.data.message);
        }
    } finally {
        preloader.value = false;
    }
};
</script>


<template>
    <div class="mb-4">
        <Attachment
            :label="$t('change_picture')"
            id="profile_picture"
            v-model="formData.profile_picture"
            @changeFile="updateProfilePicture"
            :error="$errors(errors, 'profile_picture')"
            :recommended-size="$t('profile_image_recommended_size')"
            :recommended-file-size="$t('setting_file_size')" />

    </div>

    <button type="button"
        class="btn btn-primary shadow"
        :disabled="preloader"
        @click.prevent="handelSubmit">
        <app-button-loader v-if="preloader" />
        <Download />
        {{ $t('update') }}
    </button>
</template>

<style scoped lang="scss"></style>
