<script setup>
import { onMounted } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { SELECTED_ECOMMERCES } from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import { PAGES, TEKZO_PAGES } from "@/services/endpoints/Ecommerce.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const emit = defineEmits(['close'])
const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit)
const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : TEKZO_PAGES, formData.value)
}
const editorOptions = {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, 4, 5, 6, false] }], // <-- H1 to H6
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ align: [] }],
            ['image'],
            ['clean'],
        ],
    },
};

</script>
<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="selectedUrl ? $t('update_page') : $t('add_page')"
        :preloader="preloader" @submit="submit" @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="mb-4">
                    <app-input type="text" id="title" :label="$t('title')" label-required :placeholder="$t('title')"
                        v-model="formData.title" :errors="$errors(errors, 'title')" />
                </div>
                <div class="mb-4">
                    <app-input type="text" id="slug" :label="$t('slug')" :placeholder="$t('slug')"
                        v-model="formData.slug" :errors="$errors(errors, 'slug')" />
                </div>
                <div class="mb-4">
                    <app-input type="editor" id="description" :label="$t('description')" v-model="formData.description"
                        :editorOptions="editorOptions" :errors="$errors(errors, 'description')" />
                </div>
                <div class="mb-4">
                    <app-input type="text" id="meta_title" :label="$t('meta_title')" :placeholder="$t('meta_title')"
                        v-model="formData.meta_title" :errors="$errors(errors, `meta_title`)" />
                </div>

                <div class="mb-4">
                    <app-input type="textarea" id="meta_desc" :label="$t('meta_description')"
                        :placeholder="$t('meta_description')" v-model="formData.meta_description"
                        :errors="$errors(errors, `meta_description`)" />
                </div>

                <div class="mb-4">
                    <app-input type="textarea" id="tags" :label="$t('meta_tag_separate_with_coma')"
                        :placeholder="$t('meta_tag_separate_with_coma')" v-model="formData.tags"
                        :errors="$errors(errors, `tags`)" />
                </div>
            </form>

        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
