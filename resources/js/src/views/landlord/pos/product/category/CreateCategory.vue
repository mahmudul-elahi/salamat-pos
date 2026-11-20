<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { CATEGORIES, SELECTED_NESTED_CATEGORIES, SELECTED_ECOMMERCES } from "@/services/endpoints/landlord.js";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import { useToast } from 'vue-toastification';
import InputLabel from "@/views/global/input/label/InputLabel.vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import { ref, onMounted } from "vue";
import { formDataAssigner } from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";

const toast = useToast();
const props = defineProps({
    selectedUrl: String,
});

const emit = defineEmits(["close", "category-created"]);

const {
    pageLoader,
    preloader,
    formData,
    errors,
    afterSuccess,
    afterError,
    afterFinalResponse,
} = useSubmitForm(props, emit);
const mediaModalActive = ref(false);
const mediaLibraryModal = (type) => {
    currentImageType.value = type;
    mediaModalActive.value = true;
}
const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';

}
const currentImageType = ref(null);

const handleMediaSelected = (url) => {
    if (currentImageType.value) {
        formData.value[currentImageType.value] = url;
    }
    mediaModalActive.value = false;
    currentImageType.value = null;
};


const submit = async () => {
    errors.value = {}; // clear previous errors

    const customData = {
        ...formData.value,
        description: formData.value.description ?? "",
        parent_id: formData.value.parent_id ?? "",
    };

    const data = formDataAssigner(new FormData(), customData);

    preloader.value = true;

    try {
        const response = await Axios.post(props.selectedUrl || CATEGORIES, data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        resetForm();
        emit('category-created');
        toast.success(response.data?.message || 'Category created successfully!');

    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors; // validation errors
        } else {
            toast.error(error.response?.data?.message || 'An error occurred');
        }
    } finally {
        preloader.value = false;
    }
};

const resetForm = () => {
    formData.value = {
        name: "",
        parent_id: "",
        description: "",
        logo: "",
        secondary_logo: "",
        banner: "",
        info_banner: "",
    };
};
onMounted(async () => {
    if (!props.selectedUrl) return;

    const { data } = await Axios.get(props.selectedUrl);

    Object.assign(formData.value, data);
});
//  nested categories fetch and flatten for select input
const categories = ref([]);
const fetchCategories = async () => {
    try {
        const response = await Axios.get(SELECTED_NESTED_CATEGORIES);
        categories.value = response.data || [];
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to load categories');
    }
};
const flattenCategories = (categories, level = 0) => {
    let result = [];
    categories.forEach(category => {
        result.push({
            id: category.id,
            name: `${'— '.repeat(level)}${category.name}`
        });
        if (category.children_recursive && category.children_recursive.length) {
            result = result.concat(flattenCategories(category.children_recursive, level + 1));
        }
    });
    return result;
};
onMounted(() => {
    fetchCategories();
});
</script>

<template>
    <h4 class="title mb-3">{{ $t('add_category') }}</h4>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <app-loader v-if="pageLoader"></app-loader>
                    <form v-else>
                        <!-- Category Name -->
                        <div class="mb-4">
                            <app-input
                                type="text"
                                id="name"
                                :label="$t('name')"
                                label-required
                                :placeholder="$t('name')"
                                v-model="formData.name"
                                :errors="$errors(errors, 'name')" />
                        </div>

                        <!-- Parent Category -->
                        <div class="mb-4">
                            <app-input
                                :show-search="true"
                                :selected-disable="false"
                                type="select"
                                id="categories"
                                :label="$t('category')"
                                listValueField="name"
                                listKeyField="id"
                                :options="flattenCategories(categories)"
                                v-model="formData.parent_id"
                                :choose-label="$t('choose_a_category')" />

                        </div>


                        <!-- Description -->
                        <!-- <div class="mb-4">
                            <app-input
                                type="editor"
                                id="description"
                                :label="$t('description')"
                                label-required
                                :placeholder="$t('description')"
                                v-model="formData.description"
                                :errors="$errors(errors, 'description')" />
                        </div> -->


                        <!-- Image Upload -->
                        <div class="mb-4">
                            <Attachment
                                :label="$t('logo')"
                                id="logo"
                                v-model="formData.logo"
                                :error="$errors(errors, 'logo')"
                                @click="mediaLibraryModal('logo')"
                                :showInput="false"
                                :recommended-size="$t('brand_image_recommended_size')"
                                :recommended-file-size="$t('setting_file_size')" />
                        </div>
                        <div class="mb-4">
                            <Attachment
                                :label="$t('secondary_logo')"
                                id="secondary_logo"
                                v-model="formData.secondary_logo"
                                @changeFile="changeSecondaryLogo($event)"
                                :error="$errors(errors, 'secondary_logo')"
                                @click="mediaLibraryModal('secondary_logo')"
                                :showInput="false"
                                :recommended-size="$t('brand_image_recommended_size')"
                                :recommended-file-size="$t('setting_file_size')" />
                        </div>
                        <div class="mb-4">
                            <Attachment
                                :label="$t('banner')"
                                id="banner"
                                v-model="formData.banner"
                                @changeFile="changeShopBanner($event)"
                                :error="$errors(errors, 'banner')"
                                @click="mediaLibraryModal('banner')"
                                :showInput="false"
                                :recommended-size="$t('brand_image_recommended_size')"
                                :recommended-file-size="$t('setting_file_size')" />
                        </div>
                        <div class="mb-4">
                            <Attachment
                                :label="$t('info_banner')"
                                id="info_banner"
                                v-model="formData.info_banner"
                                @changeFile="changeInfoBanner($event)"
                                :error="$errors(errors, 'info_banner')"
                                @click="mediaLibraryModal('info_banner')"
                                :showInput="false"
                                :recommended-size="$t('brand_image_recommended_size')"
                                :recommended-file-size="$t('setting_file_size')" />
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-3">
                            <button class="btn btn-success text-white" @click.prevent="submit">
                                <app-button-loader v-if="preloader" />
                                <i class="bi bi-download"></i>
                                {{ $t("save") }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <media-modal v-if="mediaModalActive" modal-id="media-modal" @selected="handleMediaSelected" @close="closeMediaModalActive" />
</template>
