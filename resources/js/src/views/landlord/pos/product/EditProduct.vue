<script setup>
import { onMounted, ref, watch, computed } from 'vue';
import {
    PRODUCTS,
    SELECTED_ATTRIBUTE_VALUES,
    SELECTED_ATTRIBUTES,
    SELECTED_BRAND,
    SELECTED_CATEGORY,
} from '@/services/endpoints/landlord.js';
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import InputLabel from "@/views/global/input/label/InputLabel.vue";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import { Trash, Xmark } from "@iconoir/vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import router from "@/router/index.js";
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({ id: {} });
const formData = ref({
    warranty_text: 'Warranty will be applicable for Tekzo Care Protection',

});
const toast = useToast();
const errors = ref({});
const preloader = ref(false);
const pageLoader = ref(false);

const attributeList = ref([]);
const fetchAttributes = async () => {
    try {
        const { data } = await Axios.get(SELECTED_ATTRIBUTES);
        attributeList.value = data;
    } catch (error) {
        console.error('Error fetching attributes:', error);
    }
}


const productVariants = ref([{ name: '', attribute_id: '', attribute_value_id: [], values: [] }]);
const variantStockList = ref([]);
const variantTitleSearch = ref('');
const selectedColors = ref([]);
const colorImages = ref([]);
const removeSliderImages = ref([]);
const isThumbnailImage = ref(false);
const productVideoLinks = ref([]);
const updateProductVideoLinks = (files) => {
    productVideoLinks.value = files;
};

const changeAttribute = async (event, index) => {
    if (event.id) {
        try {
            productVariants.value[index].name = event.name;
            const { data } = await Axios.get(SELECTED_ATTRIBUTE_VALUES(event.id));
            productVariants.value[index].values = data.map(v => ({ id: v.id, value: v.value }));
        } catch (error) {
            console.error('Error fetching attribute values:', error);
        }
    }
};

const addNewVariant = () => {
    const availableVariants = attributeList.value
        .map(attr => attr.id)
        .filter(id => !productVariants.value.some(variant => variant.attribute_id === id));

    if (availableVariants.length) {
        productVariants.value.push({ name: '', attribute_id: '', attribute_value_id: [], values: [] });
    }
}

const getCombinations = (arr) => {
    const nonEmptyArrays = arr.filter(subArr => subArr.length > 0);
    if (nonEmptyArrays.length === 0) return [];

    const combineArrays = (arrays, prefix = []) => {
        if (arrays.length === 0) return [prefix];
        const [currentArray, ...remainingArrays] = arrays;
        return currentArray.flatMap(item => combineArrays(remainingArrays, [...prefix, item]));
    };

    return combineArrays(nonEmptyArrays);
};

const generateSku = () => {
    const random9Digit = Math.floor(100000000 + Math.random() * 900000000);
    return `SI-${random9Digit}`;
};

const generateSkuForRow = (index) => {
    const variant = filteredVariantStockList.value[index];
    if (variant && variant.selected) {
        variant.sku = generateSku();
    }
};

const updateVariantStockList = () => {
    const selectedValues = productVariants.value.map(v => {
        const selectedIds = (v.attribute_value_id || []).map(id => Number(id));
        return (v.values || []).filter(val => selectedIds.includes(Number(val.id)));
    });

    const colorVariant = productVariants.value.find(v => v.name?.toLowerCase() === "color");
    if (colorVariant) {
        selectedColors.value = (colorVariant.values || [])
            .filter(val => (colorVariant.attribute_value_id || []).map(id => Number(id)).includes(val.id))
            .map(val => ({
                id: val.id,
                value: val.value,
                featured_image: colorImages.value.find(c => c.color_id === val.id)?.featured_image || null,
                slider_images: colorImages.value.find(c => c.color_id === val.id)?.slider_images || [],
                product_video_links: colorImages.value.find(c => c.color_id === val.id)?.product_video_links || [],
                video_links: colorImages.value.find(c => c.color_id === val.id)?.video_links || [],
                remove_video_links: colorImages.value.find(c => c.color_id === val.id)?.remove_video_links || [],
            }));
    } else {
        selectedColors.value = [];
    }

    if (selectedValues.length === 0 || selectedValues.every(a => a.length === 0)) {
        variantStockList.value = [];
        return;
    }

    const combinedValues = getCombinations(selectedValues);

    variantStockList.value = combinedValues.map(combo => {
        const attrIds = combo.map(item => Number(item.id));

        const existing = variantStockList.value.find(v =>
            JSON.stringify([...v.attribute_values].sort()) === JSON.stringify([...attrIds].sort())
        );

        return existing ? { ...existing } : {
            attribute_values: attrIds,
            title: combo.map(item => item),
            sku: '',
            max_retail_price: '',
            discount_percent: '',
            discount_fixed: '',
            stock: false,
            selected: false,
        };
    });
};


const removeVariant = (index) => {
    if (productVariants.value.length > 1) {
        productVariants.value.splice(index, 1);
        updateVariantStockList();
    }
};

const filteredVariantStockList = computed(() => {
    if (!variantTitleSearch.value) return variantStockList.value;
    const searchLower = variantTitleSearch.value.toLowerCase();
    return variantStockList.value.filter(item => {
        const str = Array.isArray(item.title)
            ? item.title.map(t => t.value ?? t).join(' ')
            : String(item.title ?? '');
        return str.toLowerCase().includes(searchLower);
    });
});
const ensureColorExists = (color) => {
    let existing = colorImages.value.find(c => c.color_id === color.id);
    if (!existing) {
        existing = {
            color_id: color.id,
            color_value: color.value,
            featured_image: color.featured_image || null,
            slider_images: color.slider_images || [],
            product_video_links: color.product_video_links || [],
            video_links: color.video_links || [],
            remove_video_links: color.remove_video_links || [],
        };
        colorImages.value.push(existing);
    }
    return existing;
};

const removeExistingVideo = (videoId, color) => {
    color.product_video_links = (color.product_video_links || []).filter(pv => pv.id !== videoId);
    color.remove_video_links = [...(color.remove_video_links || []), videoId];
    const entry = colorImages.value.find(c => c.color_id === color.id);
    if (entry) {
        entry.product_video_links = color.product_video_links;
        entry.remove_video_links = color.remove_video_links;
    }
};

const updateColorVideoLinks = (colorIndex, files) => {
    if (selectedColors.value?.[colorIndex]) {
        selectedColors.value[colorIndex].video_links = files;
        const colorId = selectedColors.value[colorIndex].id;
        const entry = colorImages.value.find(c => c.color_id === colorId);
        if (entry) entry.video_links = files;
    }
};

const changeFeaturedImage = (file, color) => {
    if (!file) return;
    const entry = ensureColorExists(color);
    entry.featured_image = file;
    color.featured_image = file;
};

const changeColorSliderImage = (files, index, color) => {
    if (!files || !files.length) return;
    const entry = ensureColorExists(color);
    if (!entry.slider_images) entry.slider_images = {};
    const existingCount = Object.keys(entry.slider_images).length;
    files.forEach((file, i) => {
        entry.slider_images[`image_${index}_${existingCount + i}`] = file;
    });
    color.slider_images = { ...entry.slider_images };
};

const changeThumbnailImage = (file) => {
    if (!file) return;
    isThumbnailImage.value = true;
    formData.value.thumbnail_image = file;
};

const removeAttachments = (mediaId, color) => {
    if (!color.product_slider_images) color.product_slider_images = [];
    removeSliderImages.value.push(mediaId);
    color.product_slider_images = color.product_slider_images.filter(img => img.id !== mediaId);
};

// Syncs selectedColors → colorImages when the array reference changes (variant selection change).
// Individual property mutations (featured_image, video_links, etc.) are already synced
// by their own handlers — deep: true is intentionally omitted to avoid hang on every keystroke.
watch(
    selectedColors,
    (newColors) => {
        newColors.forEach(color => {
            let existing = colorImages.value.find(c => c.color_id === color.id);
            if (!existing) {
                colorImages.value.push({
                    color_id: color.id,
                    color_value: color.value,
                    featured_image: color.featured_image || null,
                    slider_images: color.slider_images || [],
                    product_video_links: color.product_video_links || [],
                    video_links: color.video_links || [],
                    remove_video_links: color.remove_video_links || [],
                });
            }
        });
        colorImages.value = colorImages.value.filter(c =>
            newColors.some(sc => sc.id === c.color_id)
        );
    },
    { immediate: true }
);

// media modal
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
    // Detect FEATURED IMAGE
    if (currentImageType.value.startsWith("featured_image_")) {
        const colorId = Number(currentImageType.value.replace("featured_image_", ""));
        const entry = colorImages.value.find(c => c.color_id === colorId);

        if (entry) entry.featured_image = url;

        const selected = selectedColors.value.find(c => c.id === colorId);
        if (selected) selected.featured_image = url;
    }

    else if (currentImageType.value === 'product_video_links') {
        const urls = Array.isArray(url) ? url : [url];
        productVideoLinks.value = [...productVideoLinks.value, ...urls];
    }

    else if (currentImageType.value.startsWith("video_links_")) {
        const colorId = Number(currentImageType.value.replace("video_links_", ""));
        const entry = colorImages.value.find(c => c.color_id === colorId);
        const selected = selectedColors.value.find(c => c.id === colorId);
        const urls = Array.isArray(url) ? url : [url];
        if (entry) entry.video_links = [...(entry.video_links || []), ...urls];
        if (selected) selected.video_links = [...(selected.video_links || []), ...urls];
    }

    // ✅ Detect SLIDER IMAGES
    else if (currentImageType.value.startsWith("slider_images_")) {
        const colorId = Number(currentImageType.value.replace("slider_images_", ""));
        const entry = colorImages.value.find(c => c.color_id === colorId);
        const selected = selectedColors.value.find(c => c.id === colorId);
        const key = "image_0";

        // Helper to add url(s) properly
        const addUrls = (obj) => {
            if (!obj.slider_images) obj.slider_images = {};
            if (!Array.isArray(obj.slider_images[key])) obj.slider_images[key] = [];

            if (Array.isArray(url)) {
                // If url is array, push all items individually without nesting
                url.forEach(u => {
                    if (!obj.slider_images[key].includes(u)) {
                        obj.slider_images[key].push(u);
                    }
                });
            } else {
                // Single url string
                if (!obj.slider_images[key].includes(url)) {
                    obj.slider_images[key].push(url);
                }
            }
        };

        if (entry) addUrls(entry);
        if (selected) addUrls(selected);
    } else {
        formData.value[currentImageType.value] = url;
    }

    mediaModalActive.value = false;
    currentImageType.value = null;
};

const updateSliderImages = (colorIndex, files) => {
    if (selectedColors.value && selectedColors.value[colorIndex]) {
        selectedColors.value[colorIndex].slider_images = files;
    }
};

const submit = async () => {
    preloader.value = true;
    errors.value = {};

    try {
        // 🔹 Prepare selected variants
        const selectedVariants = variantStockList.value
            .filter(v => v.selected)
            .map(v => ({
                attribute_values: v.attribute_values,
                title: v.title,
                sku: v.sku,
                max_retail_price: v.max_retail_price ?? '',
                discount_percent: v.discount_percent ?? '',
                discount_fixed: v.discount_fixed ?? '',
                stock: v.stock ? 1 : 0,
                selected: v.selected ? 1 : 0,
                id: v.id ?? '',
            }));


        // 🔹 Prepare color images (as JSON, no formData)
        const colorImagesPayload = colorImages.value.map(color => ({
            color_id: color.color_id,
            color_value: color.color_value,
            featured_image: color.featured_image ?? '',
            slider_images: color.slider_images
                ? Object.values(color.slider_images).flat()
                : [],
            video_links: color.video_links ?? [],
            remove_video_links: color.remove_video_links ?? [],
        }));

        // 🔹 Prepare attributes (JSON)
        const attributesPayload = productVariants.value.map(v => ({
            attribute_id: v.attribute_id,
            attribute_value_id: v.attribute_value_id,
        }));

        // 🔹 Prepare main payload
        const payload = {
            ...formData.value,
            short_description: formData.value.short_description ?? '',
            long_description: formData.value.long_description ?? '',
            tag: formData.value.tag ?? '',
            warranty_day: formData.value.warranty_day ?? '',
            booking_amount: formData.value.booking_amount ?? '',
            purchase_point: formData.value.purchase_point ?? '',
            warranty_text: formData.value.warranty_text ?? '',
            service_warranty: formData.value.service_warranty ?? '',
            variants: selectedVariants,
            attributes: attributesPayload,
            color_images: colorImagesPayload,
            remove_slider_images: removeSliderImages.value,
            video_links: productVideoLinks.value,
        };

        // 🔹 Axios PUT request
        await Axios.put(`${PRODUCTS}/${props.id}`, payload);

        toast.success('Product updated successfully');
        await router.push({ name: 'products' });

    } catch (error) {
        if (error.response?.status === 422) errors.value = error.response.data.errors;
        else toast.error(error.response?.data?.message || 'Error submitting form');
    } finally {
        preloader.value = false;
    }
};


const getVariantAttributeError = (field, index) => errors.value[`attributes.${index}.${field}`]?.[0] || null;

const getVariantError = (field, item) => {
    // get only the selected variants in the same order as backend
    const selectedVariants = variantStockList.value.filter(v => v.selected);

    // find the index of this row in the selectedVariants array
    const selectedIndex = selectedVariants.findIndex((v) => v === item);

    // if not selected, no validation error is relevant
    if (selectedIndex === -1) return null;

    // match Laravel's error bag structure
    return errors.value[`variants.${selectedIndex}.${field}`]?.[0] || null;
};

const getColorError = (field, index) => errors.value[`color_images.${index}.${field}`]?.[0] || null;

const getColorSliderImageErrors = (index) => {
    return Object.keys(errors.value)
        .filter(key => key.startsWith(`color_images.${index}.slider_images.`))
        .map(key => errors.value[key][0]);
};


const fetchEditProduct = async () => {
    try {
        pageLoader.value = true;
        const { data } = await Axios.get(`${PRODUCTS}/${props.id}`);
        formData.value = data;
        formData.value.warranty_text = data.warranty_text || 'Warranty will be applicable for Tekzo Care Protection';
        productVariants.value = data.attributes;
        productVideoLinks.value = data.video_links || [];


        updateVariantStockList();

        if (data.variants && data.variants.length) {
            variantStockList.value = variantStockList.value.map(combo => {
                const match = data.variants.find(v =>
                    JSON.stringify([...v.attribute_values].sort()) === JSON.stringify([...combo.attribute_values].sort())
                );

                return match ? { ...combo, ...match, stock: !!match.stock, selected: !!match.id } : combo;
            });
        }
        const colorAttr = data.attributes.find(attr => attr.name?.toLowerCase() === "color");
        if (colorAttr && colorAttr.images) {
            selectedColors.value = colorAttr.images.map(img => ({
                id: img.id,
                value: img.value,
                featured_image: img.featured_image || null,
                product_slider_images: img.slider_images || [],
                product_video_links: img.video_links || [],
                video_links: [],
                remove_video_links: [],
            }));
        }
    } catch (error) {
        console.error('Error fetching product:', error);
    } finally {
        pageLoader.value = false;
    }
};
const availableAttributeOptions = (currentIndex) => {
    const selectedIds = productVariants.value
        .map((v, i) => (i !== currentIndex ? v.attribute_id : null))
        .filter(Boolean);

    return attributeList.value.filter(attr => !selectedIds.includes(attr.id));
};

const isImage = (url) => {
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'tiff'];
    const ext = url.split('?')[0].split('#')[0].split('.').pop().toLowerCase();
    return imageExtensions.includes(ext);
};

const isVideoUrl = (url) => {
    const videoExtensions = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'm4v'];
    const ext = url.split('?')[0].split('#')[0].split('.').pop().toLowerCase();
    return videoExtensions.includes(ext);
};

const getFileIcon = (url) => {
    const ext = url.split('.').pop().toLowerCase();
    switch (ext) {
        case 'pdf':
            return 'assets/images/icons/pdf-icon.png';
        case 'doc':
        case 'docx':
            return 'assets/images/icons/doc-icon.png';
        case 'xls':
        case 'xlsx':
            return 'assets/images/icons/xls-icon.png';
        case 'zip':
            return 'assets/images/icons/zip-icon.png';
        default:
            return 'assets/images/icons/file-icon.png';
    }
};
const editorOptions = {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, 4, 5, 6, false] }], // <-- H1 to H6
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ align: [] }],
            ['clean'],
        ],
    },
};

function addSeoBlock() {
    if (!formData.value.seos) {
        formData.value.seos = [];
    }
    formData.value.seos.push({
        tenant_id: null,
        title: '',
        description: '',
        tags: ''
    });
}

watch(
    () => formData.value.seos,
    (newVal) => {
        if (!newVal || newVal.length === 0) {
            addSeoBlock();
        }
    },
    { immediate: true }
);

const selectAll = (event) => {
    // Scoped to the visible (filtered) rows, matching what the checkbox's
    // own checked state reflects (groupIsChecked below is also passed the
    // filtered list) — otherwise checking "select all" while a variant
    // search is active would silently select/deselect hidden rows too.
    const checked = event.target.checked;
    filteredVariantStockList.value.forEach(variant => {
        variant.selected = checked;
    });
};

const groupIsChecked = (variantStockList) => {
    let groupCheckStock = true;
    variantStockList.forEach(variant => {
        if (!variant.selected) {
            groupCheckStock = false;
        }
    });
    return groupCheckStock;
}




onMounted(() => {
    fetchAttributes();
    fetchEditProduct();
});
</script>


<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('update_product')" />
        <app-loader v-if="pageLoader" />
        <div class="card" v-else>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-7">
                        <!-- Product Information Card -->
                        <div>
                            <div class="card-header px-0 border-1">
                                <h6 class="title">{{ $t('product_information') }}</h6>
                            </div>
                            <div class="card-body px-0">
                                <div class="row g-4">
                                    <div class="col-xl-6">
                                        <app-input type="text" id="name" :label="$t('name')" label-required
                                            :placeholder="$t('name')" v-model="formData.name"
                                            :errors="$errors(errors, 'name')" />
                                    </div>
                                    <div class="col-xl-6">
                                        <app-input type="text" id="headline" :label="$t('headline')"
                                            :placeholder="$t('headline')" v-model="formData.headline"
                                            :errors="$errors(errors, 'headline')" />
                                    </div>
                                    <div class="col-xl-6">
                                        <app-input type="advance-search-select" id="brand" :label="$t('brand')"
                                            label-required list-value-field="name" v-model="formData.brand_id"
                                            :loading-length="10" :fetch-url="SELECTED_BRAND"
                                            :choose-label="$t('choose_a_brand')"
                                            :errors="$errors(errors, 'brand_id')" />
                                    </div>
                                    <div class="col-xl-6">
                                        <app-input type="advance-search-select" id="category" :label="$t('category')"
                                            label-required list-value-field="name" v-model="formData.category_id"
                                            :loading-length="10" :fetch-url="SELECTED_CATEGORY"
                                            :choose-label="$t('choose_a_category')"
                                            :errors="$errors(errors, 'category_id')" />
                                    </div>
                                    <div class="col-xl-12">
                                        <app-input type="editor" id="short_description" label="Key Features"
                                            v-model="formData.short_description" :editorOptions="editorOptions"
                                            :errors="$errors(errors, 'short_description')" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <!-- Product Image -->
                        <div>
                            <div class="card-header px-0 border-1">
                                <h6 class="title">{{ $t('product_image') }}</h6>
                            </div>
                            <div class="card-body px-0">
                                <Attachment :label="$t('thumbnail_image')" v-model="formData.thumbnail_image"
                                    @changeFile="changeThumbnailImage($event)"
                                    @click="mediaLibraryModal('thumbnail_image')"
                                    :error="$errors(errors, 'thumbnail_image')" :showInput="false"
                                    :recommended-size="$t('image_recommended_size')"
                                    :recommended-file-size="$t('setting_file_size')" />

                                <div class="mt-3">
                                    <app-input type="multi-image-uploader" id="product-video-links"
                                        :label="$t('video_links')" @click="mediaLibraryModal('product_video_links')"
                                        :showInput="false" :initialFiles="productVideoLinks"
                                        @getFiles="updateProductVideoLinks" />
                                </div>
                            </div>
                        </div>

                        <!-- Booking, Purchase Point, Warranty -->
                        <div class="row g-3 mt-1">
                            <div class="col-6">
                                <app-input type="number" id="booking_amount" :label="$t('booking_money')"
                                    :placeholder="$t('booking_money')" v-model="formData.booking_amount"
                                    :errors="$errors(errors, 'booking_amount')" />
                            </div>
                            <div class="col-6">
                                <app-input type="number" id="purchase_point" :label="$t('purchase_point')"
                                    :placeholder="$t('purchase_point')" v-model="formData.purchase_point"
                                    :errors="$errors(errors, 'purchase_point')" />
                            </div>
                            <div class="col-12">
                                <app-input type="number" id="warranty_day" :label="$t('warranty_day')"
                                    :placeholder="$t('warranty_day')" v-model="formData.warranty_day"
                                    :errors="$errors(errors, 'warranty_day')" />
                            </div>
                            <div class="col-12">
                                <app-input type="textarea" id="warranty_text" :label="$t('warranty_text')"
                                    v-model="formData.warranty_text" :errors="$errors(errors, 'warranty_text')" />
                            </div>
                            <div class="col-12">
                                <app-input type="text" id="service_warranty" :label="$t('service_warranty')"
                                    :placeholder="$t('service_warranty')" v-model="formData.service_warranty"
                                    :errors="$errors(errors, 'service_warranty')" />
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <!-- Product Variants Card -->
                        <div>
                            <div class="card-header px-0">
                                <h6 class="m-0">{{ $t('product_variants') }}</h6>
                            </div>
                            <div class="card-body px-0">
                                <div class="row">
                                    <div class="col-md-4">
                                        <input-label :label="$t('attribute_name')" required />
                                    </div>
                                    <div class="col-md-7">
                                        <input-label :label="$t('attribute_value')" required />
                                    </div>
                                    <div class="col-md-1">
                                        <input-label :label="$t('action')" />
                                    </div>
                                </div>

                                <div class="mb-4" v-for="(item, index) in productVariants" :key="index">
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <app-input type="select" id="attribute"
                                                :options="availableAttributeOptions(index)" list-value-field="name"
                                                :choose-label="$t('choose_a_attribute')" v-model="item.attribute_id"
                                                :selected-disable="false" @selectInput="changeAttribute($event, index)"
                                                :errors="getVariantAttributeError('attribute_id', index)" />
                                        </div>
                                        <div class="col-md-7">
                                            <app-input type="multi-select" id="attribute_value" list-value-field="value"
                                                v-model="item.attribute_value_id" :options="item.values"
                                                :choose-label="$t('choose_a_attribute_value')"
                                                @multiSelect="updateVariantStockList()"
                                                :errors="getVariantAttributeError('attribute_value_id', index)" />
                                        </div>
                                        <div class="col-md-1 text-center"
                                            v-if="productVariants.length > 1 && item.name.toLocaleLowerCase() !== 'color'">
                                            <a href="" class="text-danger" @click.prevent="removeVariant(index)">
                                                <Trash />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary" @click.prevent="addNewVariant">
                                    {{ $t('add_another_option') }}
                                </button>
                            </div>
                        </div>

                        <!-- Variant Images-->

                        <div class="col-lg-12">
                            <div class="card-header px-0">
                                <h6 class="m-0">{{ $t('variant_images') }}</h6>
                            </div>
                            <div class="card-body px-0" v-for="(color, colorIndex) in selectedColors" :key="color.id">
                                <p class="text-muted">{{ $t('images_for') }} <b>{{ color.value }}</b></p>


                                <div class="d-flex gap-4 flex-wrap">
                                    <div>
                                        <Attachment :label="$t('featured_image')" v-model="color.featured_image"
                                            @changeFile="changeFeaturedImage($event, color)"
                                            @click="mediaLibraryModal(`featured_image_${color.id}`)" :showInput="false"
                                            :recommended-size="$t('image_recommended_size')"
                                            :recommended-file-size="$t('setting_file_size')" />

                                        <small v-if="getColorError('featured_image', colorIndex)" class="text-danger">{{
                                            getColorError('featured_image', colorIndex)
                                            }}</small>
                                    </div>
                                    <div class="flex-grow-1">
                                        <app-input type="multi-image-uploader"
                                            :id="`variant-video-links-${color.id}-${colorIndex}`"
                                            :label="$t('video_links')"
                                            @click="mediaLibraryModal(`video_links_${color.id}`)" :showInput="false"
                                            :initialFiles="color.video_links || []"
                                            @getFiles="files => updateColorVideoLinks(colorIndex, files)" />
                                        <div v-if="color.product_video_links?.length"
                                            class="w-100 d-flex flex-wrap gap-2 mt-3">
                                            <div v-for="pv in color.product_video_links" :key="pv.id"
                                                class="d-block position-relative">
                                                <img v-if="isImage(pv.video_link)" :src="pv.video_link"
                                                    class="height-80 border rounded-3 overflow-hidden"
                                                    style="width:120px; object-fit:cover;" alt="media" />
                                                <video v-else :src="pv.video_link" preload="none"
                                                    class="height-80 border rounded-3 overflow-hidden"
                                                    style="width:120px; object-fit:cover;" />
                                                <div class="remove-media" @click="removeExistingVideo(pv.id, color)">
                                                    <Xmark />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <app-input type="multi-image-uploader"
                                            :id="`variant-gallery-images-${color.id}-${colorIndex}`"
                                            :label="$t('slider_images')"
                                            @click="mediaLibraryModal(`slider_images_${color.id}`)" :showInput="false"
                                            :initialFiles="color.slider_images ? Object.values(color.slider_images) : []"
                                            @getFiles="files => updateSliderImages(colorIndex, files)"
                                            @changeFile="changeColorSliderImage($event, colorIndex, color)" />

                                        <div v-for="(msg, i) in getColorSliderImageErrors(colorIndex)" :key="i"
                                            class="text-danger small">
                                            {{ msg }}
                                        </div>

                                        <div v-if="color.product_slider_images?.length"
                                            class="w-100 d-flex flex-wrap gap-2 mt-3">
                                            <template v-for="(colorImage, colIndex) in color.product_slider_images"
                                                :key="colorImage.id">
                                                <div class="d-block position-relative">
                                                    <img v-if="isImage(colorImage.image_path)"
                                                        :src="colorImage.image_path" alt="attachment"
                                                        class="height-80 border rounded-3 overflow-hidden" />
                                                    <div v-else>
                                                        <a :href="colorImage.image_path" target="_blank"
                                                            class="d-flex align-items-center justify-content-center height-80 width-80 border rounded-3 bg-light">
                                                            <img :src="getFileIcon(colorImage.image_path)"
                                                                alt="file-icon"
                                                                class="height-80 border rounded-3 overflow-hidden" />
                                                        </a>
                                                    </div>
                                                    <div class="remove-media"
                                                        @click="removeAttachments(colorImage.id, color)">
                                                        <Xmark />
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="col-lg-12">
                            <!-- Variant Stock Card -->
                            <div>
                                <div class="card-header px-0">
                                    <h6 class="m-0">{{ $t('variant_stocks') }}</h6>
                                    <small v-if="errors.variants" class="text-danger">{{ errors.variants[0] }}</small>

                                </div>
                                <div class="card-body px-0">
                                    <div class="mb-4">
                                        <label for="variant_title_search" class="form-label">{{
                                            $t('filter_by_variant_title') }}</label>
                                        <input id="variant_title_search" type="search" class="form-control"
                                            placeholder="Search..." v-model="variantTitleSearch" autocomplete="off" />
                                    </div>
                                    <div class="form-check mb-4">
                                        <input class="form-check-input" id="select_all" type="checkbox"
                                            @click="selectAll($event)"
                                            :checked="groupIsChecked(filteredVariantStockList)" />
                                        <label class="form-check-label" id="select_all">Select All</label>
                                    </div>

                                    <div class="vs-list">
                                        <!-- Desktop header -->
                                        <div class="vs-header d-none d-lg-grid">
                                            <div></div>
                                            <div>{{ $t('variant_title') }}</div>
                                            <div>{{ $t('sku') }}</div>
                                            <div>{{ $t('max_retail_price') }}</div>
                                            <div>Discount(%)</div>
                                            <div>{{ $t('discount_fixed') }}</div>
                                            <div>{{ $t('in_stock') }}</div>
                                        </div>

                                        <div v-for="(item, index) in filteredVariantStockList" :key="index"
                                            class="vs-row" :class="{ 'vs-row--selected': item.selected }">

                                            <!-- Checkbox -->
                                            <div class="vs-cell vs-cell--check">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input" type="checkbox"
                                                        :id="`vs_check_${index}`" v-model="item.selected" />
                                                </div>
                                            </div>

                                            <!-- Title -->
                                            <div class="vs-cell">
                                                <span class="vs-label d-lg-none">{{ $t('variant_title') }}</span>
                                                <div class="vs-identity">
                                                    <avatar :image="item?.image" name="variant" />
                                                    <div class="vs-chip-group">
                                                        <span
                                                            v-for="attr in (Array.isArray(item.title) ? item.title : [])"
                                                            :key="attr.id" class="vs-chip">
                                                            {{ attr.value }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- SKU -->
                                            <div class="vs-cell">
                                                <span class="vs-label d-lg-none">{{ $t('sku') }}</span>
                                                <app-input type="text" :id="`variant_sku_${index}`" v-model="item.sku"
                                                    :errors="getVariantError('sku', item)" />
                                                <button class="btn btn-sm btn-outline-success mt-1 w-100"
                                                    @click.prevent="generateSkuForRow(index)">
                                                    Generate SKU
                                                </button>
                                            </div>




                                            <!-- MRP -->
                                            <div class="vs-cell">
                                                <span class="vs-label d-lg-none">{{ $t('max_retail_price') }}</span>
                                                <app-input type="number" :id="`variant_max_retail_price_${index}`"
                                                    v-model="item.max_retail_price"
                                                    :errors="getVariantError('max_retail_price', item)" />
                                            </div>

                                            <!-- Discount % -->
                                            <div class="vs-cell">
                                                <span class="vs-label d-lg-none">{{ $t('discount_percent') }}</span>
                                                <app-input type="number" :id="`variant_discount_percent_${index}`"
                                                    v-model="item.discount_percent"
                                                    :errors="getVariantError('discount_percent', item)" />
                                            </div>

                                            <!-- Discount Fixed -->
                                            <div class="vs-cell">
                                                <span class="vs-label d-lg-none">{{ $t('discount_fixed') }}</span>
                                                <app-input type="number" :id="`variant_discount_fixed_${index}`"
                                                    v-model="item.discount_fixed"
                                                    :errors="getVariantError('discount_fixed', item)" />
                                            </div>

                                            <!-- Stock -->
                                            <div class="vs-cell">
                                                <span class="vs-label d-lg-none">{{ $t('in_stock') }}</span>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        :id="`variant_stock_${index}`" v-model="item.stock">
                                                    <label class="form-check-label" :for="`variant_stock_${index}`"
                                                        :class="item.stock ? 'text-success' : 'text-secondary'">
                                                        {{ item.stock ? $t('in_stock') : $t('out_of_stock') }}
                                                    </label>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="col-lg-12">
                        <div class="mt-3">
                            <div class="col-12 d-flex justify-content-end gap-3 mt-3">
                                <button class="btn btn-success text-white" @click.prevent="submit">
                                    <app-button-loader v-if="preloader" />
                                    <i class="bi bi-download"></i>
                                    {{ $t("save") }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <media-modal v-if="mediaModalActive" modal-id="media-modal" @selected="handleMediaSelected"
        @close="closeMediaModalActive" />
</template>

<style scoped lang="scss">
$vs-cols: 36px 1.8fr 1.6fr repeat(4, 1fr);

.vs-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.vs-header {
    grid-template-columns: $vs-cols;
    gap: 8px;
    align-items: center;
    padding: 8px 14px;
    background: #f8f9fb;
    border-radius: 8px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #8a94a6;
}

.vs-row {
    display: grid;
    grid-template-columns: $vs-cols;
    gap: 8px;
    align-items: center;
    padding: 12px 14px;
    border: 1px solid #f0f2f5;
    border-radius: 10px;
    background: #fff;
    transition: border-color 0.15s, box-shadow 0.15s;

    &:hover {
        border-color: #dde2ea;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    &--selected {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    @media (max-width: 991px) {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        padding: 12px;
    }

    @media (max-width: 480px) {
        grid-template-columns: 1fr;
    }
}

.vs-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;

    &--check {
        @media (max-width: 991px) {
            grid-column: 1 / -1;
        }
    }
}

.vs-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #8a94a6;
}

.vs-title {
    font-size: 0.84rem;
    font-weight: 500;
    color: #3d4a5c;
    word-break: break-word;
}

.vs-identity {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    flex-wrap: nowrap;
    min-width: 0;
}

.vs-chip-group {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    padding-top: 4px;
    flex: 1 1 0;
    min-width: 0;
}

.vs-chip {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    background: #f0f2f5;
    color: #3d4a5c;
    font-size: 0.76rem;
    font-weight: 500;
    white-space: nowrap;
    border: 1px solid #e4e8ef;
    transition: background 0.15s;

    &:hover {
        background: #e6eaf0;
    }
}

.form-check-input:checked {
    background-color: #22c55e;
    border-color: #22c55e;
}

/* Dark mode */
:global([theme="dark"]) {
    .vs-header {
        background: rgb(46, 50, 62);
        color: rgb(170, 170, 180);
    }

    .vs-row {
        background: #1e2330;
        border-color: #2a3045;

        &:hover {
            border-color: #3a4060;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        }

        &--selected {
            background: #1a2e20;
            border-color: #2d5a3a;
        }
    }

    .vs-title {
        color: #c8cfe0;
    }

    .vs-chip {
        background: #2a3045;
        color: #c8cfe0;
        border-color: #3a4060;

        &:hover {
            background: #323a55;
        }
    }
}
</style>
