<script setup>
import {ref} from "vue"
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {formDataAssigner, serverDateFormat} from "@/utils/helper.js";
import {
    EXPENSES,
    SELECTED_EXPENSE_CATEGORY,
    SELECTED_SHOPS,
} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import {Xmark} from "@iconoir/vue";
import {useProfileStore} from "@/stores/profile/profile.js";
import {storeToRefs} from "pinia";

const store = useProfileStore();
const {profileData} = storeToRefs(store);

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(['close']);
const {
    pageLoader,
    preloader,
    formData,
    errors,
    closeModal,
    afterSuccess,
    afterError,
    afterFinalResponse
} = useSubmitForm(props, emit);
const attachments = ref([]);
const filesSelected = (files) => {
    attachments.value = files;
};
const deletableAttachmentIds = ref([]);
const removeAttachments = (id) => {
    formData.value.images = formData.value.images.filter(attachment => attachment.id != id);
    deletableAttachmentIds.value.push(id);
}
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
const submit = () => {
    const formDataCustomization = {
        ...formData.value,
        date: serverDateFormat(formData.value.date),
        note: formData.value.note || '',
    }
    let data = formDataAssigner(new FormData(), formDataCustomization)
    deletableAttachmentIds.value.forEach(id => {
        data.append('deletable_attachment_ids[]', id);
    });
    attachments.value.forEach(attachment => {
        data.append('attachments[]', attachment);
    });
    save(props.selectedUrl ? props.selectedUrl : EXPENSES, data);
};

const save = (url, formData, type = 'patch', event = null) => {
    preloader.value = true
    if (props.selectedUrl) {
        if (type === 'patch') {
            Axios.post(url + '?_method=PATCH', formData, {headers: {'Content-Type': 'multipart/form-data'}}).then((response) => {
                afterSuccess(response, event)
            }).catch(({response}) => {
                afterError(response)
            }).finally(() => afterFinalResponse())
        } else {
            Axios.post(url + '?_method=PUT', formData, {headers: {'Content-Type': 'multipart/form-data'}}).then((response) => {
                afterSuccess(response, event)
            }).catch(({response}) => {
                afterError(response)
            }).finally(() => afterFinalResponse())
        }
    } else {
        Axios.post(url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then((response) => {
            afterSuccess(response, event)
        }).catch(({response}) => {
            afterError(response)
        }).finally(() => afterFinalResponse())
    }
}
</script>

<template>
    <app-modal :modal-id="modalId"
               :title="selectedUrl ? $t('update_expense') : $t('add_expense')"
               modal-size="large"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"></app-loader>
            <form v-else @submit.prevent="submit">
                <div class="mb-4" v-if="!profileData.tenant">
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
                        :errors="$errors(errors, 'tenant_id')"/>
                </div>
                <div class="mb-4">
                    <app-input type="text"
                               id="title"
                               :label="$t('title')"
                               label-required
                               :placeholder="$t('title')"
                               v-model="formData.title"
                               :errors="$errors(errors, 'title')"/>
                </div>

                <div class="mb-4">
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

                <div class="mb-4">
                    <app-input type="number"
                               id="amount"
                               :label="$t('amount')"
                               label-required
                               :placeholder="$t('amount')"
                               v-model="formData.amount"
                               :errors="$errors(errors, 'amount')"/>
                </div>

                <div class="mb-4">
                    <div class="mb-4">
                        <app-input type="advance-search-select"
                                   id="categories"
                                   :label="$t('category')"
                                   label-required
                                   listValueField="name"
                                   list-key-field="id"
                                   v-model="formData.expense_category_id"
                                   :loading-length="10"
                                   :fetch-url="`${SELECTED_EXPENSE_CATEGORY}?type=expense`"
                                   :choose-label="$t('choose_a_category')"
                                   :errors="$errors(errors, 'expense_category_id')"
                        />
                    </div>
                </div>


                <div class="mb-4">
                    <app-input type="multi-image-uploader"
                               id="multi-image-upload-expense-modal"
                               :label="$t('attachments')"
                               @changeFile="filesSelected"/>

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
                                    v-if="isImage(attachment.url)"
                                    :src="attachment.url"
                                    alt="attachment"
                                    class="height-80 border rounded-3 overflow-hidden"/>

                                <div v-else>
                                    <a :href="attachment.url" target="_blank"
                                       class="d-flex align-items-center justify-content-center height-80 width-80 border rounded-3 bg-light">
                                        <img :src="getFileIcon(attachment.url)" alt="file-icon"
                                             class="height-80 border rounded-3 overflow-hidden"/>
                                    </a>
                                </div>
                                <div class="remove-media" @click="removeAttachments(attachment.id)">
                                    <Xmark/>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mb-4">
                    <app-input type="textarea" id="note" :label="$t('note')" :placeholder="$t('note')"
                               v-model="formData.note" :errors="$errors(errors, 'note')"/>
                </div>
            </form>
        </template>
    </app-modal>
</template>


<style lang="scss"></style>
