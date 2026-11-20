<script setup>
import { ref } from "vue"
import { formDataAssigner, serverDateFormat } from "@/utils/helper.js";
import {
    EXPENSES,
    SELECTED_EXPENSE_CATEGORY,
    SELECTED_SHOPS,
    SELECTED_PAYMENT_METHODS
} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";

const toast = useToast();
const store = useProfileStore();
const { profileData } = storeToRefs(store);

const formData = ref({
    tenant_id: profileData?.value?.tenant?.id || '',
    title: '',
    date: '',
    amount: '',
    expense_category_id: '',
    note: '',
    images: []
});
const attachments = ref([]);
const deletableAttachmentIds = ref([]);

const errors = ref({});
const preloader = ref(false);
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

    attachments.value.forEach(file => {
        data.append('attachments[]', file);
    });

    try {
        const response = await Axios.post(EXPENSES, data, {
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
            expense_category_id: '',
            note: '',
            images: []
        };
        attachments.value = [];
        deletableAttachmentIds.value = [];
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || 'Submission failed');
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
</script>
<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('create_expense')" />
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-xl-6" v-if="!profileData.tenant">
                        <app-input type="advance-search-select" id="tenant_id" :label="$t('shop_name')" label-required
                            list-value-field="name" v-model="formData.tenant_id" :loading-length="10"
                            :fetch-url="SELECTED_SHOPS" :choose-label="$t('choose_a_shop')"
                            :errors="$errors(errors, 'tenant_id')" />

                    </div>
                    <div class="col-xl-6">
                        <app-input type="text" id="title" :label="$t('title')" label-required :placeholder="$t('title')"
                            v-model="formData.title" :errors="$errors(errors, 'title')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="date" id="date" v-model="formData.date" format="yyyy-MM-dd" :label="$t('date')"
                            label-required :placeholder="$t('date')" :enable-time-picker="false" :max-date="new Date()"
                            auto-apply :errors="$errors(errors, 'date')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="number" id="amount" :label="$t('amount')" label-required
                            :placeholder="$t('amount')" v-model="formData.amount" :errors="$errors(errors, 'amount')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="advance-search-select" id="categories" :label="$t('category')" label-required
                            listValueField="name" list-key-field="id" v-model="formData.expense_category_id"
                            :loading-length="10" :fetch-url="`${SELECTED_EXPENSE_CATEGORY}?type=expense`"
                            :choose-label="$t('choose_a_category')" :errors="$errors(errors, 'expense_category_id')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="advance-search-select" id="payment_method" :label="$t('payment_method')"
                            label-required listValueField="name" list-key-field="id"
                            v-model="formData.payment_method_id" :loading-length="10"
                            :fetch-url="`${SELECTED_PAYMENT_METHODS}`" :choose-label="$t('choose_a_payment_method')"
                            :errors="$errors(errors, 'payment_method_id')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="multi-image-uploader" id="attachments" :label="$t('attachments')"
                            :showInput="false" :initial-files="attachments" @getFiles="updateInvestmentAttachments"
                            @click="mediaLibraryModal" />

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
                            v-model="formData.note" :errors="$errors(errors, 'note')" />
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
        @selected="handleMediaSelected" />
</template>
