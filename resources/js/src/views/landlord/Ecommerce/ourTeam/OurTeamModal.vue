<script setup>
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {SELECTED_ECOMMERCES} from "@/services/endpoints/landlord.js";
import {OUR_TEAMS} from "@/services/endpoints/Ecommerce.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import {useI18n} from "vue-i18n";
import {onMounted, ref} from "vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const {t} = useI18n();
const emit = defineEmits(['close']);
const showModal = ref(true);

const mediaModalActive = ref(false);
const currentImageType = ref(null);

const mediaLibraryModal = (type) => {
    currentImageType.value = type;
    mediaModalActive.value = true;
    showModal.value = false;
};

const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    showModal.value = true;
};

const handleMediaSelected = (url) => {
    if (currentImageType.value) {
        formData.value[currentImageType.value] = url;
    }
    mediaModalActive.value = false;
    showModal.value = true;
    currentImageType.value = null;
};

const {preloader, pageLoader, errors, save, closeModal, formData} = useSubmitForm(props, emit);

formData.value = {
    name: '',
    designation: '',
    about: '',
    profile_photo: '',
    tenant_id: null,
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : OUR_TEAMS, formData.value);
};
</script>

<template>
    <app-modal v-if="showModal"
               :modal-id="modalId"
               modal-size="large"
               :title="selectedUrl ? $t('update_team_member') : $t('add_team_member')"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
            <form v-else>
                <div class="row g-4">
                    <!-- Name -->
                    <div class="col-lg-6">
                        <app-input
                            type="text"
                            id="name"
                            label-required
                            :label="$t('name')"
                            :placeholder="$t('name')"
                            v-model="formData.name"
                            :errors="$errors(errors, 'name')"/>
                    </div>

                    <!-- Designation -->
                    <div class="col-lg-6">
                        <app-input
                            type="text"
                            id="designation"
                            label-required
                            :label="$t('designation')"
                            :placeholder="$t('designation')"
                            v-model="formData.designation"
                            :errors="$errors(errors, 'designation')"/>
                    </div>

                    <!-- About -->
                    <div class="col-lg-12">
                        <app-input
                            type="textarea"
                            id="about"
                            label-required
                            :label="$t('about')"
                            :placeholder="$t('about')"
                            v-model="formData.about"
                            :errors="$errors(errors, 'about')"/>
                    </div>

                    <!-- Profile Photo -->
                    <div class="col-md-12">
                        <Attachment
                            :label="$t('profile_photo')"
                            id="profile_photo"
                            v-model="formData.profile_photo"
                            :error="$errors(errors, 'profile_photo')"
                            @click="mediaLibraryModal('profile_photo')"
                            :showInput="false"
                            :recommended-size="$t('brand_image_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')"/>
                    </div>
                </div>
            </form>
        </template>
    </app-modal>

    <media-modal
        v-if="mediaModalActive"
        modal-id="our-team-media-modal"
        @selected="handleMediaSelected"
        @close="closeMediaModalActive"/>
</template>

<style scoped lang="scss"></style>

