<script setup>
import {ref, watch} from "vue"
import {formDataAssigner, serverDateFormat} from "@/utils/helper.js";
import {
    INVESTMENTS,  SELECTED_INVESTMENT_CATEGORY,
    SELECTED_SHOPS,
} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import {useToast} from "vue-toastification";
import {Xmark} from "@iconoir/vue";
import {useProfileStore} from "@/stores/profile/profile.js";
import {storeToRefs} from "pinia";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import usePermission from "@/composable/permission/usePermission.js";


const store = useProfileStore();
const {profileData} = storeToRefs(store);
const toast = useToast();
const formData = ref({
    tenant_id: profileData?.value?.tenant?.id || '',
    title: '',
    date: '',
    amount: '',
    investment_category_id: '',
    user_id: '', // This is for director
    note: '',
    images: []
});

// Reference for the directors select component
const directorsSelectRef = ref(null);

const attachments = ref([]);
const deletableAttachmentIds = ref([]);
const errors = ref({});
const preloader = ref(false);

// Watch for category changes to reset director selection
watch(() => formData.value.investment_category_id, (newCategoryId) => {
    if (newCategoryId) {
        // Reset the director selection when category changes
        formData.value.user_id = '';

        // If you need to refresh the directors dropdown programmatically
        if (directorsSelectRef.value && directorsSelectRef.value.refresh) {
            directorsSelectRef.value.refresh();
        }
    }
});

const submit = async () => {
    preloader.value = true;
    errors.value = {};

    const formDataCustomization = {
        ...formData.value,
        date: serverDateFormat(formData.value.date),
        note: formData.value.note || '',
    };

    let data = formDataAssigner(new FormData(), formDataCustomization);

    deletableAttachmentIds.value.forEach(id => {
        data.append('deletable_attachment_ids[]', id);
    });

    attachments.value.forEach(url => {
        data.append('attachments[]', url);
    });
    try {
        const response = await Axios.post(INVESTMENTS, data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        toast.success(response.data?.message);

        formData.value = {
            tenant_id: profileData?.value?.tenant?.id || '',
            title: '',
            date: '',
            amount: '',
            investment_category_id: '',
            user_id: '',
            note: '',
            images: []
        };
        attachments.value = [];
        deletableAttachmentIds.value = [];
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || 'Create income failed');
        }
    } finally {
        preloader.value = false;
    }
};

// media modal
const currentImageType = ref(null);
const mediaModalActive = ref(false);
const mediaLibraryModal = () => {
    currentImageType.value = 'attachments';
    mediaModalActive.value = true;
}
const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';
}

const handleMediaSelected = (files) => {
    if (currentImageType.value !== 'attachments') return;

    const urls = Array.isArray(files) ? files : [files];

    attachments.value = [
        ...attachments.value,
        ...urls.filter(url => !attachments.value.includes(url))
    ];

    mediaModalActive.value = false;
    currentImageType.value = null;
};
const updateInvestmentAttachments = (files) => {
    attachments.value = Array.isArray(files) ? [...files] : [];
};


const changeTenant = (event) => {
    formData.value.investment_category_id = null;
}

</script>
<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('create_investment')"/>
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-xl-6" v-if="!profileData?.tenant">
                        <app-input
                            type="advance-search-select"
                            id="tenant_id"
                            :label="$t('shop_name')"
                            label-required
                            list-value-field="name"
                            v-model="formData.tenant_id"
                            :loading-length="10"
                            :fetch-url="SELECTED_SHOPS"
                            :choose-label="$t('choose_a_shop')"
                            :errors="$errors(errors, 'tenant_id')"
                            @searchSelect="changeTenant"/>

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
                        <app-input type="date"
                                   id="date"
                                   v-model="formData.date"
                                   format="yyyy-MM-dd"
                                   :label="$t('date')"
                                   label-required
                                   :placeholder="$t('date')"
                                   :enable-time-picker="false"
                                   :max-date="new Date()"
                                   auto-apply
                                   :errors="$errors(errors, 'date')"/>
                    </div>
                    <div class="col-xl-6">
                        <app-input type="number"
                                   id="amount"
                                   :label="$t('amount')"
                                   label-required
                                   :placeholder="$t('amount')"
                                   v-model="formData.amount"
                                   :errors="$errors(errors, 'amount')"/>
                    </div>
                    <div class="col-xl-6"
                         :style="{
        cursor: formData.tenant_id ? 'pointer' : 'not-allowed',
        pointerEvents: formData.tenant_id ? 'auto' : 'none',

     }">
                        <app-input
                            type="advance-search-select"
                            id="categories"
                            :label="$t('director')"
                            label-required
                            listValueField="name"
                            list-key-field="id"
                            v-model="formData.investment_category_id"
                            :loading-length="10"
                            :fetch-url="`${SELECTED_INVESTMENT_CATEGORY}?warehouse=${formData.tenant_id}`"
                            :selected-disable="false"
                            :choose-label="$t('choose_a_category')"
                            :errors="$errors(errors, 'investment_category_id')"/>
                    </div>


                    <div class="col-xl-6">

                        <app-input
                            type="multi-image-uploader"
                            id="attachments"
                            :label="$t('attachments')"
                            :showInput="false"
                            :initial-files="attachments"
                            @getFiles="updateInvestmentAttachments"
                            @click="mediaLibraryModal"/>

                        <div class="mt-2">
                            <small class="text-muted">{{ $t('multi_image_upload_guide_line') }}</small>
                            <div v-for="(attachment, index) in attachments" :key="index">
                                <small v-if="errors['attachments.' + index]" class="text-danger">
                                    {{ errors['attachments.' + index][0] }}
                                </small>
                            </div>

                        </div>

                    </div>
                    <div class="col-xl-6">
                        <app-input type="textarea" id="note" :label="$t('note')" :placeholder="$t('note')"
                                   v-model="formData.note" :errors="$errors(errors, 'note')"/>
                    </div>
                    <div class="col-xl-12">
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary" @click="submit" :disabled="preloader">
                                <span v-if="preloader" class="spinner-border spinner-border-sm me-2" role="status"
                                      aria-hidden="true"></span>
                                {{ $t('submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <media-modal v-if="mediaModalActive" modal-id="media-modal" @close="closeMediaModalActive"
                 @selected="handleMediaSelected"/>
</template>
