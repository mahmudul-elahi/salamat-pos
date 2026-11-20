<script setup>
import {ref} from "vue";
import {useI18n} from "vue-i18n";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import router from "@/router/index.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import {CAREERS} from "@/services/endpoints/Ecommerce.js";
import {serverDateFormat} from "@/utils/helper.js";

const {t} = useI18n();
const toast = useToast();

const formData = ref({
    heading: '',
    slug: '',
    vacancies: '',
    type: 'full_time',
    salary: '',
    image: null,
    qualification: '',
    deadline: '',
    description: '',
    benefits: '',
    keywords: '',
});

const errors = ref({});
const preloader = ref(false);
const mediaModalActive = ref(false);
const jobTypes = [
    {id: 'full_time', name: 'Full Time'},
    {id: 'part_time', name: 'Part Time'},
    {id: 'contractual', name: 'Contract'},
    {id: 'Internship', name: 'Internship'},
];
const currentImageType = ref(null);
const mediaLibraryModal = (type) => {
    currentImageType.value = type;
    mediaModalActive.value = true;
}
const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    currentImageType.value = null;
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';
}
const handleMediaSelected = (url) => {
    if (currentImageType.value) {
        formData.value[currentImageType.value] = url;
    }
    closeMediaModalActive();
};

const submit = async () => {
    preloader.value = true;
    errors.value = {};

    try {
        const {data} = await Axios.post(CAREERS, {
            ...formData.value,
            deadline: serverDateFormat(formData.value.deadline)
        });
        toast.success(data.message || t('career_created_successfully'));
        await router.push({name: 'careers'});
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || t('an_error_occurred'));
        }
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('add_career')"/>
        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submit">
                    <!-- Heading -->
                    <div class="row g-4 mb-4">
                        <!-- Heading -->
                        <div class="col-md-8">
                            <app-input
                                type="text"
                                id="heading"
                                :label="$t('heading')"
                                label-required
                                :placeholder="$t('heading')"
                                v-model="formData.heading"
                                :errors="$errors(errors, 'heading')"/>
                        </div>


                        <!-- Image -->
                        <div class="col-md-4">
                            <Attachment
                                :label="$t('image')"
                                id="image"
                                v-model="formData.image"
                                :error="$errors(errors, 'image')"
                                @click="mediaLibraryModal('image')"
                                :showInput="false"
                                :recommended-size="$t('image_recommended_size')"
                                :recommended-file-size="$t('setting_file_size')"/>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <!-- Vacancies -->
                        <div class="col-md-3">
                            <app-input
                                type="number"
                                id="vacancies"
                                :label="$t('vacancies')"
                                :placeholder="$t('vacancies')"
                                v-model="formData.vacancies"
                                :errors="$errors(errors, 'vacancies')"/>
                        </div>

                        <!-- Type -->
                        <div class="col-md-3">
                            <app-input
                                type="select"
                                id="type"
                                :label="$t('type')"
                                list-value-field="name"
                                :options="jobTypes"
                                v-model="formData.type"
                                :errors="$errors(errors, 'type')"/>
                        </div>

                        <!-- Salary -->
                        <div class="col-md-3">
                            <app-input
                                type="text"
                                id="salary"
                                :label="$t('salary')"
                                :placeholder="$t('salary')"
                                v-model="formData.salary"
                                :errors="$errors(errors, 'salary')"/>
                        </div>

                        <!-- Deadline -->
                        <div class="col-md-3">
                            <app-input
                                type="date"
                                id="deadline"
                                :label="$t('deadline')"
                                :placeholder="$t('dd/mm/yy')"
                                :enable-time-picker="false"
                                v-model="formData.deadline"
                                auto-apply
                                :errors="$errors(errors, 'deadline')"/>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <app-input
                            type="editor"
                            id="description"
                            :label="$t('description')"
                            v-model="formData.description"
                            :errors="$errors(errors, 'description')"/>
                    </div>

                    <!-- Qualification -->
                    <div class="mb-4">
                        <app-input
                            type="editor"
                            id="qualification"
                            :label="$t('qualification')"
                            v-model="formData.qualification"
                            :errors="$errors(errors, 'qualification')"/>
                    </div>

                    <!-- Benefits -->
                    <div class="mb-4">
                        <app-input
                            type="editor"
                            id="benefits"
                            :label="$t('benefits')"
                            v-model="formData.benefits"
                            :errors="$errors(errors, 'benefits')"/>
                    </div>

                    <!-- Keywords -->
                    <div class="mb-4">
                        <app-input
                            type="textarea"
                            id="keywords"
                            :label="$t('keywords')"
                            :placeholder="$t('keywords')"
                            v-model="formData.keywords"
                            :errors="$errors(errors, 'keywords')"/>
                    </div>

                    <div class="d-flex justify-content-end">

                        <button type="submit" class="btn btn-primary" :disabled="preloader">
                            <span v-if="preloader" class="spinner-border spinner-border-sm me-2"/>
                            {{ $t('submit') }}
                        </button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <media-modal
        v-if="mediaModalActive"
        modal-id="career-create-media-modal"
        @selected="handleMediaSelected"
        @close="closeMediaModalActive"/>
</template>

<style scoped lang="scss"></style>

