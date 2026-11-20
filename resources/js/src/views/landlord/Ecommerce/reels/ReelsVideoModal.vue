<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { REELS_VIDEOS } from "@/services/endpoints/Ecommerce.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import { SELECTED_PRODUCT_LIST } from "@/services/endpoints/landlord.js";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import { ref } from "vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);
const mediaModalActive = ref(false);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    product_id: null,
    video_link: null,
    status: 1,
};

const openMediaModal = () => {
    mediaModalActive.value = true;
};

const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';
};

const handleMediaSelected = (url) => {
    formData.value.video_link = url;
    closeMediaModalActive();
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : REELS_VIDEOS, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large"
        :title="selectedUrl ? $t('update_reels_video') : $t('add_reels_video')" :preloader="preloader" @submit="submit"
        @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">

                    <div class="col-md-12">
                        <app-input type="advance-search-select" id="product" :label="$t('product')" label-required
                            list-value-field="product_name" v-model="formData.product_id" :loading-length="10"
                            :fetch-url="SELECTED_PRODUCT_LIST" :choose-label="$t('choose_a_product')"
                            :errors="$errors(errors, 'product_id')" />
                    </div>

                    <div class="col-md-12">
                        <Attachment id="video_link" v-model="formData.video_link" :label="$t('video_link')"
                            :error="$errors(errors, 'video_link')" :showInput="false" @click="openMediaModal" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="status" type="select" :label="$t('status')"
                            :options="[{ id: 1, name: 'Active' }, { id: 0, name: 'Inactive' }]" list-value-field="name"
                            v-model="formData.status" :errors="$errors(errors, 'status')" :selected-disable="false" />
                    </div>

                </div>
            </form>
        </template>
    </app-modal>

    <media-modal v-if="mediaModalActive" modal-id="media-modal" @selected="handleMediaSelected"
        @close="closeMediaModalActive" />
</template>

<style scoped lang="scss"></style>