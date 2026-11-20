<script setup>
import { ref, onMounted } from "vue"
import { formDataAssigner, serverDateFormat } from "@/utils/helper.js";
import {
    INVESTMENTS, SELECTED_CATEGORY_DIRECTORS, SELECTED_DIRECTORS,
    SELECTED_INVESTMENT_CATEGORY,
    SELECTED_SHOPS,
} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import { Xmark } from "@iconoir/vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
const store = useProfileStore();
const { profileData } = storeToRefs(store);
const toast = useToast();
const props = defineProps({
    id: {},
});
const formData = ref({
    tenant_id: profileData?.value?.tenant?.id || '',
    title: '',
    date: '',
    amount: '',
    investment_category_id: '',
    note: '',
    images: []
});
const attachments = ref([]);

const deletableAttachmentIds = ref([]);
const removeAttachments = (attachment) => {
  console.log('Before remove:', formData.value.images.map(a => a.id));

  formData.value.images = formData.value.images.filter(a => a.id !== attachment.id);

  console.log('After remove:', formData.value.images.map(a => a.id));

  if (attachment.id) {
    deletableAttachmentIds.value.push(attachment.id);
  } else {
    attachments.value = attachments.value.filter(file => file !== attachment);
  }
};


const isImage = (url) => {
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
    const ext = url.split('.').pop().toLowerCase();
    return imageExtensions.includes(ext);
};

const getFileIcon = (url) => {
    const ext = url.split('.').pop().toLowerCase();
    switch (ext) {
        case 'pdf':
            return 'assets/images/icons/pdf-icon.png';
        case 'doc':
        case 'docx':
            return 'assets/images/icons/doc-icon.png';
        case 'xls':
        case 'xlsx':
            return 'assets/images/icons/xls-icon.png';
        case 'zip':
            return 'assets/images/icons/zip-icon.png';
        default:
            return 'assets/images/icons/file-icon.png';
    }
};
const errors = ref({});
const preloader = ref(false);
const pageLoader = ref(false);
const fetchIncome = async () => {

    if (!props.id) return;

    pageLoader.value = true;
    try {
        const response = await Axios.get(`${INVESTMENTS}/${props.id}`);
        const investmentData = response.data;

        formData.value = {
            tenant_id: investmentData.tenant_id,
            title: investmentData.title,
            date: investmentData.date,
            amount: investmentData.amount,
            user_id: investmentData.user_id,
            investment_category_id: investmentData.investment_category_id,
            note: investmentData.note,
            images: investmentData.attachments || [],
        };
    } catch (error) {
        toast.error('Failed to fetch investment data.');
        console.error('Fetch error:', error);
    } finally {
        pageLoader.value = false;
    }
}
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
    data.append('_method', 'patch')

    try {
        const response = await Axios.post(`${INVESTMENTS}/${props.id}`, data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        toast.success(response.data?.message);

        attachments.value = [];
        deletableAttachmentIds.value = [];
        await fetchIncome()
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || 'Update income failed');
        }
    } finally {
        preloader.value = false;
    }
};
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
const getDirectorsUrl = () => {
    let baseUrl = SELECTED_CATEGORY_DIRECTORS;
    if (formData.value.investment_category_id) {
        baseUrl = `${SELECTED_CATEGORY_DIRECTORS}/${formData.value.investment_category_id}`;
    }
    return baseUrl;
};
onMounted(() => {
    fetchIncome();
});
</script>
<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('edit_investment')" />
        <div class="card">
            <app-loader v-if="pageLoader"></app-loader>
            <div class="card-body" v-else>
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
                            :errors="$errors(errors, 'tenant_id')" />

                    </div>
                    <div class="col-xl-6">
                        <app-input type="text"
                            id="title"
                            :label="$t('title')"
                            label-required
                            :placeholder="$t('title')"
                            v-model="formData.title"
                            :errors="$errors(errors, 'title')" />
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
                            :errors="$errors(errors, 'date')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="number"
                            id="amount"
                            :label="$t('amount')"
                            label-required
                            :placeholder="$t('amount')"
                            v-model="formData.amount"
                            :errors="$errors(errors, 'amount')" />
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
                            @click="mediaLibraryModal" />

                        <div class="mt-2">
                            <small class="text-muted">{{ $t('multi_image_upload_guide_line') }}</small>
                            <div v-for="(attachment, index) in attachments" :key="index">
                                <small v-if="errors['attachments.' + index]" class="text-danger">
                                    {{ errors['attachments.' + index][0] }}
                                </small>
                            </div>

                        </div>

                        <div v-if="formData.images?.length" class="w-100 d-flex flex-wrap gap-2 mt-3">
                            <template v-for="attachment in formData.images" :key="attachment.id">
                                <div class="d-block position-relative">
                                    <img
                                        v-if="isImage(attachment.link)"
                                        :src="attachment.link"
                                        alt="attachment"
                                        class="height-80 border rounded-3 overflow-hidden" />

                                    <div v-else>
                                        <a :href="attachment.link" target="_blank"
                                            class="d-flex align-items-center justify-content-center height-80 width-80 border rounded-3 bg-light">
                                            <img :src="getFileIcon(attachment.link)" alt="file-icon"
                                                class="height-80 border rounded-3 overflow-hidden" />
                                        </a>
                                    </div>

                                    <div class="remove-media" @click="removeAttachments(attachment)">
                                        <Xmark />
                                    </div>
                                </div>
                            </template>
                        </div>


                    </div>
                    <div class="col-xl-6">
                        <app-input type="textarea" id="note" :label="$t('note')" :placeholder="$t('note')" v-model="formData.note" :errors="$errors(errors, 'note')" />
                    </div>
                    <div class="col-xl-12">
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary" @click="submit" :disabled="preloader">
                                <span v-if="preloader" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ $t('submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <media-modal v-if="mediaModalActive" modal-id="media-modal" @close="closeMediaModalActive" @selected="handleMediaSelected" />
</template>
