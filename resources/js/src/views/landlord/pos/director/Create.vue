<script setup>
import {ref} from "vue"
import {formDataAssigner} from "@/utils/helper.js";
import {
    DIRECTORS,
    SELECTED_SHOPS,
} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";

const toast = useToast();
const formData = ref({});

const errors = ref({});
const preloader = ref(false);
const submit = async () => {
    preloader.value = true;
    errors.value = {};

    const formDataCustomization = {
        ...formData.value
    };

    let data = formDataAssigner(new FormData(), formDataCustomization);

    try {
        const response = await Axios.post(DIRECTORS, data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        toast.success(response.data?.message);

        formData.value = {};
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || 'Director creation failed');
        }
    } finally {
        preloader.value = false;
    }
};
const mediaModalActive = ref(false);
const currentImageType = ref(null);

const mediaLibraryModal = (type) => {
    currentImageType.value = type;
    mediaModalActive.value = true;
}
const handleMediaSelected = (url) => {
    if (currentImageType.value) {
        formData.value[currentImageType.value] = url;
    }
    mediaModalActive.value = false;
    currentImageType.value = null;
};
const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    currentImageType.value = null;
};
</script>
<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('create_directors')"/>
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-xl-6">
                        <app-input type="text"
                                   id="full_name"
                                   :label="$t('full_name')"
                                   label-required
                                   :placeholder="$t('name')"
                                   v-model="formData.name"
                                   :errors="$errors(errors, 'name')"/>
                    </div>
                    <div class="col-xl-6">
                        <app-input type="text"
                                   id="title"
                                   :label="$t('title')"
                                   label-required
                                   :placeholder="$t('title')"
                                   v-model="formData.title"
                                   :errors="$errors(errors, 'title')"/>
                    </div>
                    <div class="col-xl-6">
                        <app-input type="email"
                                   id="email"
                                   :label="$t('email')"
                                   label-required
                                   :placeholder="$t('email')"
                                   v-model="formData.email"
                                   :errors="$errors(errors, 'email')"/>
                    </div>
                    <div class="col-xl-6">
                        <app-input type="number"
                                   id="phone"
                                   :label="$t('phone')"
                                   label-required
                                   :placeholder="$t('phone')"
                                   v-model="formData.phone"
                                   :errors="$errors(errors, 'phone')"/>
                    </div>
                    <div class="col-xl-6">
                        <app-input type="password"
                                   id="password"
                                   :label="$t('password')"
                                   label-required
                                   :placeholder="$t('password')"
                                   v-model="formData.password"
                                   :errors="$errors(errors, 'password')"/>
                    </div>

                    <div class="col-xl-6">
                        <Attachment
                            :label="$t('profile_photo')"
                            id="profile_photo"
                            v-model="formData.profile_photo"
                            :error="$errors(errors, 'profile_photo')"
                            @click="mediaLibraryModal('profile_photo')"
                            :showInput="false"
                            :recommended-size="$t('profile_photo_size')"/>

                    </div>
                    <div class="col-xl-12">
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary" @click="submit" :disabled="preloader">
                                <span v-if="preloader"
                                      class="spinner-border spinner-border-sm me-2"
                                      role="status"
                                      aria-hidden="true">
                                </span>
                                {{ $t('submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <media-modal v-if="mediaModalActive"
                 modal-id="media-modal"
                 @close="closeMediaModalActive"
                 @selected="handleMediaSelected"/>
</template>
