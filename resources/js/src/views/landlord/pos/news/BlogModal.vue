<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { SELECTED_ECOMMERCES } from "@/services/endpoints/landlord.js";
import { BLOGS } from "@/services/endpoints/Ecommerce.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import Axios from "@/services/axios/index.js";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import { onMounted, ref } from "vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);
const showBrandModal = ref(true);
const mediaModalActive = ref(false);
const currentImageType = ref(null);

const mediaLibraryModal = (type) => {
    currentImageType.value = type;
    mediaModalActive.value = true;
    showBrandModal.value = false;
};

const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    showBrandModal.value = true;
};

const handleMediaSelected = (url) => {
    if (currentImageType.value) {
        formData.value[currentImageType.value] = url;
    }

    mediaModalActive.value = false;
    showBrandModal.value = true;
    currentImageType.value = null;
};

const formData = ref({
    name: "",
    heading: "",
    slug: "",
    description: "",
    image: null,
    seos: [],
});

const { preloader, errors, save, closeModal } = useSubmitForm(props, emit);

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : BLOGS, formData.value);
};

const addSeoBlock = () => {
    formData.value.seos.push({
        tenant_id: null,
        meta_title: "",
        meta_desc: "",
        tags: "",
    });
};

const removeSeoBlock = (index) => {
    formData.value.seos.splice(index, 1);
};

onMounted(async () => {
    if (!props.selectedUrl) return;

    const { data } = await Axios.get(props.selectedUrl);
    Object.assign(formData.value, data);

    formData.value.seos = (data.seos || []).map((seo) => ({
        id: seo.id,
        tenant_id: seo.tenant_id,
        meta_title: seo.title,
        meta_desc: seo.description,
        tags: seo.tags,
    }));
});
</script>

<template>
    <app-modal
        :modal-id="modalId"
        modal-size="large"
        :title="selectedUrl ? $t('update_blog') : $t('add_blog')"
        :preloader="preloader"
        @submit="submit"
        @close="closeModal"
    >
        <template #body>
            <form>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input
                            id="name"
                            v-model="formData.name"
                            type="text"
                            :label="$t('name')"
                            label-required
                            :placeholder="$t('name')"
                            :errors="$errors(errors, 'name')"
                        />
                    </div>

                    <div class="col-md-12">
                        <app-input
                            id="heading"
                            v-model="formData.heading"
                            type="text"
                            :label="$t('heading')"
                            label-required
                            :placeholder="$t('heading')"
                            :errors="$errors(errors, 'heading')"
                        />
                    </div>

                    <div class="col-md-12">
                        <app-input
                            id="slug"
                            v-model="formData.slug"
                            type="text"
                            :label="$t('slug')"
                            :placeholder="$t('slug')"
                            :errors="$errors(errors, 'slug')"
                        />
                    </div>

                    <div class="col-md-12">
                        <app-input
                            id="description"
                            v-model="formData.description"
                            type="editor"
                            :label="$t('description')"
                            :placeholder="$t('description')"
                            :errors="$errors(errors, 'description')"
                        />
                    </div>

                    <div class="col-md-6">
                        <Attachment
                            id="image"
                            v-model="formData.image"
                            :label="$t('image')"
                            :error="$errors(errors, 'image')"
                            :showInput="false"
                            :recommended-size="$t('brand_image_recommended_size')"
                            :recommended-file-size="$t('setting_file_size')"
                            @click="mediaLibraryModal('image')"
                        />
                    </div>

                    <div class="col-lg-12">
                        <div
                            v-for="(seo, index) in formData.seos"
                            :key="index"
                            class="row g-4"
                        >
                            <div class="col-lg-12">
                                <app-input
                                    type="advance-search-select"
                                    :id="`tenant_id_${index}`"
                                    :label="$t('shop_name')"
                                    list-value-field="name"
                                    v-model="seo.tenant_id"
                                    :loading-length="10"
                                    :fetch-url="SELECTED_ECOMMERCES"
                                    :choose-label="$t('choose_a_shop')"
                                    :errors="$errors(errors, `seos.${index}.tenant_id`)"
                                />
                            </div>

                            <div class="col-lg-12">
                                <app-input
                                    type="text"
                                    :id="`meta_title_${index}`"
                                    :label="$t('meta_title')"
                                    :placeholder="$t('meta_title')"
                                    v-model="seo.meta_title"
                                    :errors="$errors(errors, `seos.${index}.meta_title`)"
                                />
                            </div>

                            <div class="col-lg-12">
                                <app-input
                                    type="textarea"
                                    :id="`meta_desc_${index}`"
                                    :label="$t('meta_desc')"
                                    v-model="seo.meta_desc"
                                    :errors="$errors(errors, `seos.${index}.meta_desc`)"
                                />
                            </div>

                            <div class="col-lg-12">
                                <app-input
                                    type="textarea"
                                    :id="`tags_${index}`"
                                    :label="$t('meta_tag_separate_with_coma')"
                                    v-model="seo.tags"
                                    :errors="$errors(errors, `seos.${index}.tags`)"
                                />
                            </div>

                            <div class="col-12 text-end">
                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm"
                                    @click="removeSeoBlock(index)"
                                >
                                    {{ $t("remove_seo_block") }}
                                </button>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary btn-sm mt-3"
                            @click="addSeoBlock"
                        >
                            {{ $t("add_seo_block") }}
                        </button>
                    </div>
                </div>
            </form>
        </template>
    </app-modal>

    <media-modal
        v-if="mediaModalActive"
        modal-id="media-modal"
        @selected="handleMediaSelected"
        @close="closeMediaModalActive"
    />
</template>

<style scoped lang="scss"></style>

