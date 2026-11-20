<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { PRE_OWANED_PRODUCTS } from '@/services/endpoints/Ecommerce.js';
import {
    SELECTED_PRODUCT,
    SELECTED_PRODUCT_ATTRIBUTE,
    SELECTED_ATTRIBUTES,
    SELECTED_ATTRIBUTE_VALUES,
} from "@/services/endpoints/landlord.js";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import Axios from "@/services/axios/index.js";
import InputLabel from "@/views/global/input/label/InputLabel.vue";
import { Trash } from "@iconoir/vue";
import { useToast } from "vue-toastification";


const toast = useToast();
const router = useRouter();


const errors = ref({});
const currentImageType = ref(null);
const mediaModalActive = ref(false);
const attachments = ref([]);

const changeThumbnailImage = (file) => {
    if (!file) return;
    formData.value.thumbnail_image = file;
}

const blockInvalidKeys = (e) => {
    const invalidKeys = ['e', 'E', '+', '-', 'Minus', 'Equal'];
    if (
        invalidKeys.includes(e.key) ||
        invalidKeys.includes(e.code)
    ) {
        e.preventDefault();
    }
};
const blockNegativePaste = (e) => {
    const pasted = (e.clipboardData || window.clipboardData).getData('text');
    if (/[eE\-\+]/.test(pasted)) {
        e.preventDefault();
    }
};

const changeDiscountAmount = (event) => {
    let value = Number(event.target.value) || 0;

    if (formData.value.discount_type === 'fixed') {
        // Clamp fixed discount between 1 and subtotal
        const price = Number(formData.value.price) || 0;
        if (value < 1) value = 1;
        if (value > price) value = price;
    }

    if (formData.value.discount_type === 'percent') {
        // Clamp percent discount between 1% and 100%
        if (value < 1) value = 1;
        if (value > 100) value = 100;
    }

    formData.value.discount_amount = value;
    event.target.value = value;
};

const changeDiscountType = (event) => {
    formData.value.discount_amount = 0;
}

const mediaLibraryModal = (type = null) => {
    currentImageType.value = type ?? 'attachments';
    mediaModalActive.value = true;
};




const handleMediaSelected = (files) => {
    const urls = Array.isArray(files) ? files : files;

    if (currentImageType.value === 'thumbnail_image') {
        formData.value.thumbnail_image = urls;
        mediaModalActive.value = false;
        currentImageType.value = null;
        return;
    }

    if (currentImageType.value === 'attachments') {
        // ✅ multi — array te add koro
        attachments.value = [
            ...attachments.value,
            ...urls.filter(url => !attachments.value.includes(url))
        ];
        mediaModalActive.value = false;
        currentImageType.value = null;
        return;
    }

    mediaModalActive.value = false;
    currentImageType.value = null;
};


const updateInvestmentAttachments = (files) => {
    attachments.value = Array.isArray(files) ? [...files] : [];
};

const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    currentImageType.value = null;
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';
};
// ================= PRODUCT =================

const attributeValueList = ref([]);

const changeProduct = (product) => {
    if (!product?.id) return;

    formData.value.attribute_id = null;

    Axios.get(`${SELECTED_PRODUCT_ATTRIBUTE}/${product.id}`)
        .then(({ data }) => {
            attributeValueList.value = data || [];
        });
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


const productVariants = ref([{ name: '', attribute_id: '', attribute_value_id: [], values: [] }]);

const availableAttributeOptions = (currentIndex) => {
    const selectedIds = productVariants.value
        .map((v, i) => (i !== currentIndex ? v.attribute_id : null))
        .filter(Boolean);

    return attributeList.value.filter(attr => !selectedIds.includes(attr.id));
};

const attributeList = ref([]);
const fetchAttributes = async () => {
    try {
        const { data } = await Axios.get(SELECTED_ATTRIBUTES);
        attributeList.value = data;
    } catch (error) {
        console.error('Error fetching attributes:', error);
    }
}

const addNewVariant = () => {
    const availableVariants = attributeList.value
        .map(attr => attr.id)
        .filter(id => !productVariants.value.some(variant => variant.attribute_id === id));

    if (availableVariants.length) {
        productVariants.value.push({ name: '', attribute_id: '', attribute_value_id: [], values: [] });
    }
}

const removeVariant = (index) => {
    if (productVariants.value.length > 1) {
        productVariants.value.splice(index, 1);
    }
}

// ================= SUBMIT =================
const formData = ref({
    product_id: null,
    product_variant_id: null,
    description: null,
    price: null,
    booking_amount: null,
    purchase_point: null,
    serial_number: null,
    battery_health: null,
    box: 0,
    charging_time: null,
    official_service_warranty: null,
    thumbnail_image: null,
});
const submit = () => {
    Axios.post(PRE_OWANED_PRODUCTS, {
        ...formData.value,
        variant_attributes: productVariants.value.map(variant => ({
            attribute_id: variant.attribute_id,
            attribute_value_id: variant.attribute_value_id,
        })),
        attachments: attachments.value.map(p => p),
    }).then(() => {
        router.push({ name: 'PreOwnedProductList' });
    }).catch((err) => {
        if (err.response?.status === 422) {
            errors.value = err.response?.data?.errors || {};
            return;
        } else {
            toast.error(err.response?.data?.message || 'An error occurred while creating the pre-owned product');
        }

    });
};

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
const getVariantAttributeError = (field, index) => errors.value[`variant_attributes.${index}.${field}`]?.[0] || null;

onMounted(fetchAttributes);
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('add_pre_owned_product')" />
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <!-- PRODUCT -->
                    <div class="col-md-4">
                        <app-input type="advance-search-select" id="product" label="Product" label-required
                            v-model="formData.product_id" :loading-length="10" :errors="$errors(errors, 'product_id')"
                            list-value-field="name" :fetch-url="SELECTED_PRODUCT" @searchSelect="changeProduct"
                            choose-label="Choose a product" />
                    </div>

                    <div class="col-md-4"
                        :style="[formData.product_id ? {} : 'pointer-events: none; cursor: not-allowed;']">
                        <app-input type="select" :showSearch="true" id="attribute" list-value-field="attributes"
                            list-key-field="id" :label="$t('variant')" label-required
                            v-model="formData.product_variant_id" :options="attributeValueList"
                            :choose-label="$t('choose_an_attribute')" :selected-disable="false"
                            :errors="$errors(errors, 'product_variant_id')" />
                    </div>




                    <div class="col-md-4">
                        <app-input type="text" id="serial_number" :label="$t('serial_number')" label-required
                            :errors="$errors(errors, 'serial_number')" placeholder="Enter serial number"
                            v-model="formData.serial_number" />
                    </div>

                    <!-- PRICE -->
                    <div class="col-md-3">
                        <app-input type="number" id="special_price" label="Price" label-required
                            :errors="$errors(errors, 'price')" placeholder="Enter price" v-model="formData.price" />
                    </div>

                    <div class="col-md-3">

                        <app-input type="select" id="discount_type" :label="$t('discount_type')"
                            v-model="formData.discount_type" :options="[
                                { id: 'fixed', value: 'Fixed' },
                                { id: 'percent', value: 'Percent' }
                            ]" :choose-label="$t('choose_a_discount_type')" :errors="$errors(errors, 'discount_type')"
                            @selectInput="changeDiscountType" />

                    </div>

                    <div class="col-md-3">
                        <app-input type="number" id="discount_amount" :label="$t('discount_amount')"
                            :label-required="formData.discount_type === 'percent' || formData.discount_type === 'fixed'"
                            v-model.number="formData.discount_amount" :placeholder="$t('discount_amount')"
                            :disabled="!(formData.discount_type === 'percent' || formData.discount_type === 'fixed')"
                            min="1" :max="formData.discount_type === 'percent' ? 100 : formData.price"
                            @keydown="blockInvalidKeys" @paste="blockNegativePaste"
                            :errors="$errors(errors, 'discount_amount')" @input="changeDiscountAmount" />
                    </div>

                    <!-- BATTERY -->
                    <div class="col-md-3">
                        <app-input type="text" id="battery_health" label="Battery Health"
                            :errors="$errors(errors, 'battery_health')" placeholder="Enter battery health"
                            v-model="formData.battery_health" />
                    </div>

                    <div class="col-md-3">
                        <app-input type="number" id="booking_amount" label="Booking Amount"
                            :errors="$errors(errors, 'booking_amount')" placeholder="Enter booking amount"
                            v-model="formData.booking_amount" />
                    </div>

                    <div class="col-md-3">
                        <app-input type="number" id="purchase_point" label="Purchase Point"
                            :errors="$errors(errors, 'purchase_point')" placeholder="Enter purchase Point"
                            v-model="formData.purchase_point" />
                    </div>

                    <div class="col-md-4">
                        <input-label label-id="box" label="Box Available" />
                        <div>
                            <app-input type="radio" id="box" :errors="$errors(errors, 'box')" list-value-field="label"
                                list-key-field="value" radio-checkbox-name="box" name="box" v-model="formData.box"
                                :options="[
                                    { label: 'Yes', value: 1 },
                                    { label: 'No', value: 0 },
                                ]" />
                        </div>


                    </div>

                    <div class="col-md-4">
                        <app-input type="number" id="charging_time" label="Charging Time"
                            :errors="$errors(errors, 'charging_time')" placeholder="Enter charging time"
                            v-model="formData.charging_time" />
                    </div>

                    <div class="col-md-4">
                        <app-input type="text" id="official_service_warranty" label="Official Service Warranty"
                            :errors="$errors(errors, 'official_service_warranty')"
                            placeholder="Enter Official Service warranty"
                            v-model="formData.official_service_warranty" />
                    </div>


                    <div class="col-lg-12">

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
                                            @selectInput="changeAttribute($event, index)"
                                            :errors="getVariantAttributeError('attribute_id', index)" />
                                    </div>
                                    <div class="col-md-7">
                                        <app-input type="select" id="attribute_value" list-value-field="value"
                                            v-model="item.attribute_value_id" :options="item.values"
                                            :choose-label="$t('choose_a_attribute_value')" :show-search="true"
                                            :selected-disable="false"
                                            :errors="getVariantAttributeError('attribute_id', index)" />
                                    </div>
                                    <div class="col-md-1 text-center" v-if="productVariants.length > 1">
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

                    <div class="col-md-4">
                        <div @click="mediaLibraryModal('thumbnail_image')">
                            <Attachment :label="$t('thumbnail_image')"
                                :preview="formData.thumbnail_image" v-model="formData.thumbnail_image"
                                @changeFile="changeThumbnailImage($event)" :error="$errors(errors, 'thumbnail_image')"
                                :showInput="false" :recommended-size="$t('image_recommended_size')"
                                :recommended-file-size="$t('setting_file_size')" />
                        </div>

                    </div>

                    <!-- DESCRIPTION -->
                    <div class="col-md-12">
                        <app-input type="editor" id="description" label="Description"
                            :errors="$errors(errors, 'description')" placeholder="Enter description"
                            :editorOptions="editorOptions" v-model="formData.description" />
                    </div>



                    <!-- IMAGE UPLOADER -->
                    <div class="col-md-12">
                        <div @click="mediaLibraryModal('attachments')">
                            <app-input type="multi-image-uploader" id="attachments" :label="$t('attachments')"
                                :showInput="false" :initial-files="attachments" @getFiles="updateInvestmentAttachments"
                                :errors="$errors(errors, 'attachments')" />
                        </div>
                    </div>

                    <!-- SUBMIT -->
                    <div class="col-12">
                        <button class="btn btn-primary" @click="submit">
                            Submit
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- MEDIA MODAL -->
        <media-modal v-if="mediaModalActive" modal-id="media-modal"
            :selection-mode="currentImageType === 'attachments' ? 'multi' : 'single'" @selected="handleMediaSelected"
            @close="closeMediaModalActive" />
    </div>
</template>