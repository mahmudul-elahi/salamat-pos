<script setup>
import { watch, onMounted } from 'vue'
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import Axios from "@/services/axios/index.js"
import { MEDIA_FOLDERS } from "@/services/endpoints/Ecommerce.js";
import { useToast } from "vue-toastification";
const props = defineProps({
    modalId: String,
    parentFolder: Object
})
const emit = defineEmits(['submit', 'close']);
const toast = useToast();
const { preloader, formData, errors, save, closeModal } = useSubmitForm(props, emit)

const submit = async () => {
    if (!formData.value.folder_name) return;

    try {
        preloader.value = true;

        await Axios.post(MEDIA_FOLDERS, {
            name: formData.value.folder_name,
            parent_id: props.parentFolder ? props.parentFolder.id : null,
        });

        toast.success('Folder created successfully');
        emit('submit');
        closeModal();
    } catch (error) {
        toast.error('Failed to create folder');
        console.error(error);
    } finally {
        preloader.value = false;
    }
};

</script>
<template>
    <app-modal :modal-id="modalId"
        modal-size="large"
        title="Create Folder"
        :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template v-slot:body>


            <div class="mb-4">
                <app-input type="text"
                    id="folder_name"
                    :label="$t('folder_name')"
                    label-required
                    :placeholder="$t('folder_name')"
                    v-model="formData.folder_name"
                    :errors="$errors(errors, 'folder_name')" />
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>