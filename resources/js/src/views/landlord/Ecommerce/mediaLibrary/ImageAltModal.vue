<script setup>
import {watch, onMounted} from 'vue'
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import Axios from "@/services/axios/index.js"
import {MEDIA_ALT_TEXT} from "@/services/endpoints/Ecommerce.js";
import {useToast} from "vue-toastification";

const props = defineProps({
    modalId: String,
    media: Object
})
const emit = defineEmits(['close', 'refresh'])
const toast = useToast();
const {preloader, formData, errors, closeModal} = useSubmitForm(props, emit)

onMounted(() => {
    formData.value.alt_text = props.media?.alt || ''  // populate alt text if exists
})

const submit = async () => {
    try {
        preloader.value = true
        const url = `${MEDIA_ALT_TEXT}/${props.media.id}`
        await Axios.patch(url, formData.value)
        emit('close')
        emit('refresh')
        toast.success("Image Alt text added successfully")
    } catch (error) {
        toast.error("There was an error with the Image Alt text")
    } finally {
        preloader.value = false
    }
}
</script>
<template>
    <app-modal :modal-id="modalId"
               modal-size="large"
               title="Image Alt Text"
               :preloader="preloader"
               @submit="submit" @close="closeModal">
        <template v-slot:body>

            <div class="mb-4">
                <app-input type="textarea"
                           id="alt_text"
                           :label="$t('alt_text')"
                           label-required
                           :placeholder="$t('alt_text')"
                           v-model="formData.alt_text"
                           :errors="$errors(errors, 'alt_text')"/>
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
</style>
