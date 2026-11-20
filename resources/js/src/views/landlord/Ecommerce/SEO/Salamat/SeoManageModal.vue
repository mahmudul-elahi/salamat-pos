<script setup>
import { watch } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { PAGES } from "@/services/endpoints/Ecommerce.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const emit = defineEmits(['close'])
const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit)

const submit = () => {
    formData.value.tenant_id = 3;
    save(props.selectedUrl ? props.selectedUrl : PAGES, formData.value)
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

watch(() => formData.value.title, (newTitle) => {
    if (newTitle && !pageLoader.value) {
        formData.value.slug = newTitle
            .toLowerCase()
            .replace(/[^\w ]+/g, '')
            .replace(/ +/g, '-');
    }
});

</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="$t('manage_seo')" :preloader="preloader" @submit="submit"
        @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader" />
            <div v-else>
                <div class="mb-4">
                    <app-input type="text" id="title" :label="$t('name')" label-required :placeholder="$t('name')"
                        v-model="formData.title" :errors="$errors(errors, 'title')" />
                </div>
                <div class="mb-4">
                    <app-input type="text" id="slug" label-required :label="$t('slug')" :placeholder="$t('slug')"
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
            </div>

        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
