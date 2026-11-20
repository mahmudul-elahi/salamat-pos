<script setup>
import { ref, onMounted, watch } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { CATEGORIES, SELECTED_NESTED_CATEGORIES, SELECTED_ECOMMERCES } from "@/services/endpoints/landlord.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import Axios from "@/services/axios/index.js";
import { useToast } from 'vue-toastification';
const props = defineProps({
    categoryId: {
        type: Number,
        default: null,
    },
});
const toast = useToast();
const emit = defineEmits(["close", "category-updated"]);

const {
    pageLoader,
    preloader,
    formData,
    errors,
} = useSubmitForm(props, emit);

// Media modal state & helpers
const mediaModalActive = ref(false);
const currentImageType = ref(null);

function mediaLibraryModal(type) {
    currentImageType.value = type;
    mediaModalActive.value = true;
}

function closeMediaModalActive() {
    mediaModalActive.value = false;
    document.body.classList.remove("modal-open");
    document.body.style.overflow = "visible";
    document.body.style.paddingRight = "0px";
}

function handleMediaSelected(url) {
    if (currentImageType.value) {
        formData.value[currentImageType.value] = url;
    }
    closeMediaModalActive();
}




// Fetch category data by ID including SEO info
async function fetchCategoryData(categoryId) {
    try {
        pageLoader.value = true;
        const { data } = await Axios.get(`${CATEGORIES}/${categoryId}`);

        // Assign main category data
        formData.value = {
            name: data.name || "",
            parent_id: data.parent_id ?? "",
            description: data.description ?? "",
            logo: data.logo ?? null,
            secondary_logo: data.secondary_logo ?? null,
            banner: data.banner ?? null,
            info_banner: data.info_banner ?? null,
        };

        // Defensive: ensure seos is array
        if (!Array.isArray(formData.value.seos)) {
            formData.value.seos = [];
        }
    } catch (error) {
        console.error("Error fetching category details:", error);
    } finally {
        pageLoader.value = false;
    }
}
const categories = ref([]);
const fetchCategories = async () => {
    try {
        const response = await Axios.get(SELECTED_NESTED_CATEGORIES);
        categories.value = response.data || [];
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to load categories');
    }
};
// A category can't be its own parent, and picking one of its own descendants
// would create a cycle — so both are excluded from its "choose parent" list.
// Skipping the branch (not just the node) drops the whole excluded subtree.
const flattenCategories = (categories, level = 0) => {
    let result = [];
    categories.forEach(category => {
        if (category.id === props.categoryId) return;

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
// Watch for changes to categoryId prop and fetch new data
watch(
    () => props.categoryId,
    (newId) => {
        if (newId) {
            fetchCategoryData(newId);
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

// Submit handler
const submit = async () => {
    errors.value = {}; // clear previous errors

    const customData = {
        ...formData.value,
        description: formData.value.description ?? "",
        parent_id: formData.value.parent_id ?? "",
    };

    const data = new FormData();
    for (const key in customData) {
        if (key === "seos" && Array.isArray(customData.seos)) {
            customData.seos.forEach((seo, index) => {
                for (const seoKey in seo) {
                    data.append(`seos[${index}][${seoKey}]`, seo[seoKey] ?? "");
                }
            });
        } else {
            data.append(key, customData[key] ?? "");
        }
    }

    preloader.value = true;

    try {
        // Use PATCH emulation by appending _method=PATCH in query string
        const url = `${props.selectedUrl || CATEGORIES}/${props.categoryId}?_method=PATCH`;

        const response = await Axios.post(url, data, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        emit('category-updated');

        toast.success(response.data?.message || "Category updated successfully!");

        // No resetForm or emit here, as you requested

    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors; // validation errors
        } else {
            toast.error(error.response?.data?.message || "An error occurred");
        }
    } finally {
        preloader.value = false;
    }
}



// Reset form data to empty
function resetForm() {
    formData.value = {
        name: "",
        parent_id: "",
        description: "",
        logo: null,
        secondary_logo: null,
        banner: null,
        info_banner: null,
        meta_title: null,
        meta_desc: null,
    };
}
onMounted(() => {
    fetchCategories();
});
</script>

<template>
    <h4 class="title mb-3">{{ $t("update_category") }}</h4>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <app-loader v-if="pageLoader" />
                    <form v-else @submit.prevent="submit">
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

                        <!-- Image Uploads -->
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
                                :error="$errors(errors, 'info_banner')"
                                @click="mediaLibraryModal('info_banner')"
                                :showInput="false"
                                :recommended-size="$t('brand_image_recommended_size')"
                                :recommended-file-size="$t('setting_file_size')" />
                        </div>

                        <!-- Submit -->
                        <div>
                            <button type="submit" class="btn btn-success text-white" :disabled="preloader">
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

    <media-modal
        v-if="mediaModalActive"
        modal-id="media-modal"
        @selected="handleMediaSelected"
        @close="closeMediaModalActive" />
</template>
