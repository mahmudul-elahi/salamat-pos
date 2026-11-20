<script setup>
import { onMounted, ref } from "vue";
import Axios from "@/services/axios/index.js";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { BLOG_TAGS } from "@/services/endpoints/Ecommerce.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(['close']);
const { preloader, errors, save, closeModal } = useSubmitForm(props, emit, 'no');

const formData = ref({
    name: '',
});

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : BLOG_TAGS, formData.value);
};

onMounted(async () => {
    if (!props.selectedUrl) return;

    const { data } = await Axios.get(props.selectedUrl);

    Object.assign(formData.value, data);
});
</script>

<template>
    <app-modal
        :modal-id="modalId"
        :title="selectedUrl ? $t('update_blog_tag') : $t('add_blog_tag')"
        :preloader="preloader"
        @submit="submit"
        @close="closeModal">
        <template #body>
            <form>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input type="text" id="name" :label="$t('name')" label-required :placeholder="$t('name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
