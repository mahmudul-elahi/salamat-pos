<script setup>
import { ref } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { POPUP_DIALOGS } from "@/services/endpoints/Ecommerce.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);
const mediaModalActive = ref(false);
const activeImageField = ref('image');

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    name: null,
    image: null,
    mobile_image: null,
    url: null,
    status: 1,
};

const openMediaModal = (field = 'image') => {
    activeImageField.value = field;
    mediaModalActive.value = true;
};

const closeMediaModal = () => {
    mediaModalActive.value = false;
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';
};

const handleMediaSelected = (url) => {
    formData.value[activeImageField.value] = url;
    closeMediaModal();
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : POPUP_DIALOGS, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="selectedUrl ? $t('update_popup_dialog') : $t('add_popup_dialog')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input id="name" type="text" :label="$t('name')" label-required v-model="formData.name"
                            :placeholder="$t('name')" :errors="$errors(errors, 'name')" />
                    </div>

                    <div class="col-md-6">
                        <Attachment id="image" label-required v-model="formData.image" :label="$t('image')"
                            :error="$errors(errors, 'image')" :showInput="false" @click="openMediaModal('image')" />
                    </div>

                    <div class="col-md-6">
                        <Attachment id="mobile_image" v-model="formData.mobile_image" :label="$t('mobile_image')"
                            :error="$errors(errors, 'mobile_image')" :showInput="false"
                            @click="openMediaModal('mobile_image')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="url" type="text" :label="$t('url')" v-model="formData.url"
                            :placeholder="$t('url')" :errors="$errors(errors, 'url')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="status" type="select" label-required :label="$t('status')"
                            :options="[{ id: 1, name: 'Active' }, { id: 0, name: 'Inactive' }]" list-value-field="name"
                            v-model="formData.status" :errors="$errors(errors, 'status')" :selected-disable="false" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>

    <media-modal v-if="mediaModalActive" modal-id="popup-media-modal" @selected="handleMediaSelected"
        @close="closeMediaModal" />
</template>

<style scoped lang="scss"></style>
