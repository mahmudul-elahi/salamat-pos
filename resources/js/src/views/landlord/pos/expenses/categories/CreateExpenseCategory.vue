<script setup>
import { ref } from "vue"
import { formDataAssigner, serverDateFormat } from "@/utils/helper.js";
import {
    EXPENSE_CATEGORY,
} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import { Xmark } from "@iconoir/vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";

const toast = useToast();
const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
    tenant: {
        type: Object,
        default: () => ({})
    }
});
const formData = ref({
    name: '',
    logo: null,
});

const errors = ref({});
const preloader = ref(false);

const mediaModalActive = ref(false);
const openMediaModal = () => {
    mediaModalActive.value = true;
};
const handleMediaSelected = (url) => {
    formData.value.logo = url;
    mediaModalActive.value = false;
};

const submit = async () => {
  preloader.value = true;
  errors.value = {};

  try {
    const response = await Axios.post(EXPENSE_CATEGORY, { name: formData.value.name, logo: formData.value.logo });
    toast.success(response.data?.message);

    formData.value.name = '';
    formData.value.logo = null;
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


</script>
<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('create_expense_category')" />
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-xl-6">
                        <app-input type="text"
                            id="name"
                            :label="$t('name')"
                            label-required
                            :placeholder="$t('name')"
                            v-model="formData.name"
                            :errors="$errors(errors, 'name')" />
                    </div>
                    <div class="col-xl-6">
                        <Attachment
                            label="Logo (Optional)"
                            :value="formData.logo"
                            :showInput="false"
                            :error="$errors(errors, 'logo')"
                            @click="openMediaModal" />
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

        <media-modal v-if="mediaModalActive"
            modal-id="expense-category-create-media-modal"
            @selected="handleMediaSelected"
            @close="mediaModalActive = false" />
    </div>
</template>